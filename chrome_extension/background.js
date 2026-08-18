let isBatchRunning = false;
let cancelRequested = false;

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'START_BATCH_SCRAPE') {
    if (!isBatchRunning) {
      runBatchScrape(request.stores, request.apiUrl);
    }
    sendResponse({ success: true, message: 'Batch scrape started in background' });
    return true;
  }

  if (request.action === 'STOP_BATCH_SCRAPE') {
    cancelRequested = true;
    isBatchRunning = false;
    chrome.storage.local.set({
      batchStatus: {
        isBatchRunning: false,
        completed: false,
        statusText: '⚠️ Batch scrape dihentikan oleh pengguna.'
      }
    });
    sendResponse({ success: true });
    return true;
  }

  if (request.action === 'GET_BATCH_STATUS') {
    chrome.storage.local.get(['batchStatus'], (res) => {
      sendResponse(res.batchStatus || { isBatchRunning: false });
    });
    return true;
  }
});

async function runBatchScrape(stores, apiUrl) {
  isBatchRunning = true;
  cancelRequested = false;
  const total = stores.length;

  for (let i = 0; i < total; i++) {
    if (cancelRequested) break;

    const storeUrl = stores[i];
    const currentStep = i + 1;
    const pct = Math.round((currentStep / total) * 100);

    const statusObj = {
      isBatchRunning: true,
      current: currentStep,
      total: total,
      percent: pct,
      statusText: `[${currentStep}/${total}] Membuka toko: ${storeUrl.split('/').pop().slice(0, 30)}...`
    };
    await chrome.storage.local.set({ batchStatus: statusObj });

    try {
      // Create tab in BACKGROUND so user view doesn't switch away
      const tab = await chrome.tabs.create({ url: storeUrl, active: false });

      // Wait 3.8 seconds for full DOM & menu item rendering
      await new Promise(r => setTimeout(r, 3800));

      if (cancelRequested) {
        await chrome.tabs.remove(tab.id).catch(() => {});
        break;
      }

      // Inject content script
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        files: ['content.js']
      }).catch(() => {});

      // Scroll slightly to trigger lazy-loaded items
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => { window.scrollTo(0, 500); }
      }).catch(() => {});

      // Execute extraction directly in tab DOM
      const results = await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          if (typeof extractGrabFoodData === 'function') {
            return extractGrabFoodData();
          }
          return null;
        }
      });

      const scrapedData = results && results[0] ? results[0].result : null;

      // Close tab
      await chrome.tabs.remove(tab.id).catch(() => {});

      if (scrapedData && scrapedData.name) {
        const prodCount = scrapedData.products ? scrapedData.products.length : 0;
        statusObj.statusText = `[${currentStep}/${total}] Mengirim '${scrapedData.name}' (${prodCount} menu) ke server...`;
        await chrome.storage.local.set({ batchStatus: statusObj });

        try {
          await sendToApi(apiUrl, scrapedData);
          statusObj.statusText = `[${currentStep}/${total}] ✅ Sukses: '${scrapedData.name}' (${prodCount} menu terimpor)`;
          await chrome.storage.local.set({ batchStatus: statusObj });
        } catch (apiErr) {
          console.error("API Error importing store", scrapedData.name, apiErr);
          statusObj.statusText = `[${currentStep}/${total}] ⚠️ Gagal API (${scrapedData.name}): ${apiErr.message}`;
          await chrome.storage.local.set({ batchStatus: statusObj });
        }
      } else {
        statusObj.statusText = `[${currentStep}/${total}] ⚠️ Gagal membaca DOM resto`;
        await chrome.storage.local.set({ batchStatus: statusObj });
      }
    } catch (err) {
      console.error("Batch error for store index", i, err);
    }
  }

  isBatchRunning = false;
  if (!cancelRequested) {
    await chrome.storage.local.set({
      batchStatus: {
        isBatchRunning: false,
        completed: true,
        current: total,
        total: total,
        percent: 100,
        statusText: `🎉 BATCH IMPORT SELESAI! Selesai memproses ${total} toko!`
      }
    });
  }
}

async function sendToApi(targetUrl, payload) {
  let response = null;
  try {
    response = await fetch(targetUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
  } catch (err) {
    if (targetUrl.includes('cicago.store')) {
      const fallbackUrl = 'http://localhost/CicalengkaGO/api/import-store';
      response = await fetch(fallbackUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
    } else {
      throw err;
    }
  }

  if (!response || !response.ok) {
    const errTxt = response ? await response.text().catch(() => '') : 'Koneksi Gagal';
    throw new Error(`Server Error ${response ? response.status : ''}`);
  }

  return response;
}
