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
      // Create background tab
      const tab = await chrome.tabs.create({ url: storeUrl, active: false });

      // Wait 4.5 seconds for page load & image rendering
      await new Promise(r => setTimeout(r, 4500));

      if (cancelRequested) {
        await chrome.tabs.remove(tab.id).catch(() => {});
        break;
      }

      // Inject content script to be safe
      try {
        await chrome.scripting.executeScript({
          target: { tabId: tab.id },
          files: ['content.js']
        });
      } catch (e) {}

      // Send SCRAPE_DATA request
      const scrapedData = await new Promise((resolve) => {
        chrome.tabs.sendMessage(tab.id, { action: 'SCRAPE_DATA' }, (res) => {
          if (res && res.success && res.data) {
            resolve(res.data);
          } else {
            resolve(null);
          }
        });
      });

      // Close tab
      await chrome.tabs.remove(tab.id).catch(() => {});

      if (scrapedData && scrapedData.name) {
        statusObj.statusText = `[${currentStep}/${total}] Mengirim '${scrapedData.name}' (${scrapedData.products.length} menu) ke CicalengkaGO...`;
        await chrome.storage.local.set({ batchStatus: statusObj });

        try {
          await sendToApi(apiUrl, scrapedData);
        } catch (apiErr) {
          console.error("API Error importing store", scrapedData.name, apiErr);
        }
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
        statusText: `🎉 BATCH IMPORT SELESAI! Berhasil memproses ${total} toko!`
      }
    });
  }
}

async function sendToApi(targetUrl, payload) {
  try {
    const res = await fetch(targetUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    if (res.ok) return res;
    throw new Error(`HTTP status ${res.status}`);
  } catch (err) {
    if (targetUrl.includes('cicago.store')) {
      const fallbackUrl = 'http://localhost/CicalengkaGO/api/import-store';
      return await fetch(fallbackUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
    }
    throw err;
  }
}
