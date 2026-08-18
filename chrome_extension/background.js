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

// Helper to wait until tab HTTP load is complete
async function waitForTabComplete(tabId, maxWaitMs = 12000) {
  const startTime = Date.now();
  while (Date.now() - startTime < maxWaitMs) {
    try {
      const tab = await chrome.tabs.get(tabId);
      if (tab && tab.status === 'complete') return true;
    } catch (e) {
      return false;
    }
    await new Promise(r => setTimeout(r, 400));
  }
  return false;
}

// Helper to poll DOM until Next.js / React hydration renders content
async function waitForDomHydration(tabId, maxWaitMs = 10000) {
  const startTime = Date.now();
  while (Date.now() - startTime < maxWaitMs) {
    try {
      const res = await chrome.scripting.executeScript({
        target: { tabId: tabId },
        func: () => {
          const hasH1 = !!document.querySelector('h1')?.textContent?.trim();
          const hasNextData = !!document.getElementById('__NEXT_DATA__')?.textContent;
          const hasItems = document.querySelectorAll('[class*="menuItem"], [class*="itemRow"], [class*="restaurantCard"], [class*="itemCard"], [class*="category"]').length > 0;
          return hasH1 || hasNextData || hasItems;
        }
      });
      if (res && res[0] && res[0].result === true) {
        return true;
      }
    } catch (e) {}
    await new Promise(r => setTimeout(r, 500));
  }
  return false;
}

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
      statusText: `[${currentStep}/${total}] Membuka & memuat toko: ${storeUrl.split('/').pop().slice(0, 30)}...`
    };
    await chrome.storage.local.set({ batchStatus: statusObj });

    try {
      // 1. Create tab in BACKGROUND so user view doesn't switch away
      const tab = await chrome.tabs.create({ url: storeUrl, active: false });

      // 2. Wait until tab HTTP state is complete (max 12s)
      await waitForTabComplete(tab.id, 12000);

      // 3. Wait until React / Next.js DOM is hydrated (max 10s)
      await waitForDomHydration(tab.id, 10000);

      if (cancelRequested) {
        await chrome.tabs.remove(tab.id).catch(() => {});
        break;
      }

      // 4. Inject content.js
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        files: ['content.js']
      }).catch(() => {});

      // 5. Scroll page down & up to trigger lazy-loaded product cards and images
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          window.scrollTo(0, 1000);
          setTimeout(() => window.scrollTo(0, 0), 200);
        }
      }).catch(() => {});

      // 6. Give 1.5 seconds buffer for React menu state update
      await new Promise(r => setTimeout(r, 1500));

      // 7. Primary Extraction Attempt
      let results = await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          if (typeof extractGrabFoodData === 'function') {
            return extractGrabFoodData();
          }
          return null;
        }
      }).catch(() => null);

      let scrapedData = results && results[0] ? results[0].result : null;

      // 8. Retry Mechanism: If 0 products extracted, wait 2s and retry extraction once
      if (!scrapedData || !scrapedData.products || scrapedData.products.length === 0) {
        await new Promise(r => setTimeout(r, 2000));
        results = await chrome.scripting.executeScript({
          target: { tabId: tab.id },
          func: () => {
            if (typeof extractGrabFoodData === 'function') {
              return extractGrabFoodData();
            }
            return null;
          }
        }).catch(() => null);
        scrapedData = (results && results[0] && results[0].result) ? results[0].result : scrapedData;
      }

      // 9. Close tab ONLY AFTER extraction is complete
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
