document.addEventListener('DOMContentLoaded', async () => {
  const scrapeBtn = document.getElementById('scrapeBtn');
  const batchScrapeBtn = document.getElementById('batchScrapeBtn');
  const storeCountEl = document.getElementById('storeCount');
  const downloadJsonBtn = document.getElementById('downloadJsonBtn');
  const apiUrlInput = document.getElementById('apiUrl');
  const setOnlineUrl = document.getElementById('setOnlineUrl');
  const setLocalUrl = document.getElementById('setLocalUrl');
  const statusCard = document.getElementById('statusCard');
  const statusTitle = document.getElementById('statusTitle');
  const statusBadge = document.getElementById('statusBadge');
  const statusText = document.getElementById('statusText');
  const itemList = document.getElementById('itemList');
  const progressContainer = document.getElementById('progressContainer');
  const progressBar = document.getElementById('progressBar');
  const progressText = document.getElementById('progressText');
  const progressPercent = document.getElementById('progressPercent');

  let lastScrapedData = null;
  let listingStores = [];

  // Restore saved API URL if any
  chrome.storage.local.get(['cgo_api_url'], (res) => {
    if (res.cgo_api_url) {
      apiUrlInput.value = res.cgo_api_url;
    }
  });

  apiUrlInput.addEventListener('change', () => {
    chrome.storage.local.set({ cgo_api_url: apiUrlInput.value.trim() });
  });

  if (setOnlineUrl) {
    setOnlineUrl.addEventListener('click', () => {
      apiUrlInput.value = 'https://cicago.store/api/import-store';
      chrome.storage.local.set({ cgo_api_url: apiUrlInput.value });
    });
  }

  if (setLocalUrl) {
    setLocalUrl.addEventListener('click', () => {
      apiUrlInput.value = 'http://localhost/CicalengkaGO/api/import-store';
      chrome.storage.local.set({ cgo_api_url: apiUrlInput.value });
    });
  }

  // Check if active tab is a Store Listing page
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab && tab.url && tab.url.includes('food.grab.com')) {
      chrome.tabs.sendMessage(tab.id, { action: 'GET_LISTING_STORES' }, (response) => {
        if (response && response.success && Array.isArray(response.stores) && response.stores.length > 0) {
          listingStores = response.stores;
          storeCountEl.textContent = listingStores.length;
          batchScrapeBtn.style.display = 'flex';
        }
      });
    }
  } catch (e) {}

  // Helper to send JSON to API with automatic local fallback
  async function sendToApi(targetUrl, payload) {
    try {
      return await fetch(targetUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
    } catch (err) {
      // If fetching production domain fails, try local XAMPP as fallback
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

  // Single Store Scrape
  scrapeBtn.addEventListener('click', async () => {
    const apiUrl = apiUrlInput.value.trim();
    if (!apiUrl) {
      alert("Harap masukkan URL Endpoint CicalengkaGO!");
      return;
    }

    scrapeBtn.disabled = true;
    batchScrapeBtn.disabled = true;
    scrapeBtn.innerHTML = '<span>⏳ Mengambil Data Resto...</span>';
    statusCard.classList.add('active');
    statusTitle.textContent = "Meng-scrape GrabFood...";
    statusBadge.textContent = "SCRAPING";
    statusBadge.className = "badge";
    statusText.textContent = "Membaca data toko dan menu dari halaman aktif...";
    itemList.style.display = 'none';

    try {
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (!tab) throw new Error("Tidak menemukan tab aktif.");
      if (!tab.url.includes("food.grab.com")) {
        throw new Error("Harap buka halaman resto GrabFood terlebih dahulu (food.grab.com)!");
      }

      chrome.tabs.sendMessage(tab.id, { action: 'SCRAPE_DATA' }, async (response) => {
        if (chrome.runtime.lastError || !response || !response.success) {
          await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['content.js']
          });
          
          chrome.tabs.sendMessage(tab.id, { action: 'SCRAPE_DATA' }, (resp2) => {
            if (resp2 && resp2.success) {
              processScrapedData(resp2.data, apiUrl);
            } else {
              handleError("Gagal membaca DOM GrabFood. Pastikan halaman sudah selesai ter-load.");
            }
          });
        } else {
          processScrapedData(response.data, apiUrl);
        }
      });
    } catch (err) {
      handleError(err.message);
    }
  });

  // Batch Store Scrape (Sequential Multi-Tab)
  batchScrapeBtn.addEventListener('click', async () => {
    const apiUrl = apiUrlInput.value.trim();
    if (!apiUrl) {
      alert("Harap masukkan URL Endpoint CicalengkaGO!");
      return;
    }

    if (!listingStores || listingStores.length === 0) {
      alert("Tidak ada toko yang terdeteksi di halaman ini.");
      return;
    }

    if (!confirm(`Apakah Anda yakin ingin meng-scrape & mengimpor ${listingStores.length} toko sekaligus ke CicalengkaGO?`)) {
      return;
    }

    scrapeBtn.disabled = true;
    batchScrapeBtn.disabled = true;
    progressContainer.style.display = 'block';
    statusCard.classList.add('active');
    statusTitle.textContent = "🚀 Scrape Massal Berjalan...";
    statusBadge.textContent = "BATCH SCRAPE";
    statusBadge.className = "badge";

    let importedCount = 0;
    let failedCount = 0;
    const total = listingStores.length;

    for (let i = 0; i < total; i++) {
      const storeUrl = listingStores[i];
      const stepNum = i + 1;
      const pct = Math.round((stepNum / total) * 100);

      progressBar.style.width = `${pct}%`;
      progressPercent.textContent = `${pct}%`;
      progressText.textContent = `[${stepNum}/${total}] Membuka toko...`;
      statusText.textContent = `[${stepNum}/${total}] Membuka tab: ${storeUrl.split('/').pop().slice(0, 30)}...`;

      try {
        const tab = await chrome.tabs.create({ url: storeUrl, active: false });

        // Wait 4.2s for page load & DOM rendering
        await new Promise(resolve => setTimeout(resolve, 4200));

        try {
          await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ['content.js']
          });
        } catch (e) {}

        const scrapedData = await new Promise((resolve) => {
          chrome.tabs.sendMessage(tab.id, { action: 'SCRAPE_DATA' }, (res) => {
            if (res && res.success && res.data) {
              resolve(res.data);
            } else {
              resolve(null);
            }
          });
        });

        try {
          await chrome.tabs.remove(tab.id);
        } catch (e) {}

        if (scrapedData && scrapedData.name) {
          statusText.textContent = `[${stepNum}/${total}] Mengirim '${scrapedData.name}' (${scrapedData.products.length} menu) ke CicalengkaGO...`;
          
          const postResp = await sendToApi(apiUrl, scrapedData);
          if (postResp.ok) {
            importedCount++;
          } else {
            failedCount++;
          }
        } else {
          failedCount++;
        }
      } catch (err) {
        failedCount++;
      }
    }

    progressBar.style.width = '100%';
    progressPercent.textContent = '100%';
    progressText.textContent = 'Selesai!';

    statusTitle.textContent = "🎉 BATCH IMPORT SELESAI!";
    statusBadge.textContent = "COMPLETED";
    statusBadge.className = "badge success";
    statusText.textContent = `Berhasil mengimpor ${importedCount} toko dari total ${total} toko ke CicalengkaGO!${failedCount > 0 ? ` (${failedCount} gagal)` : ''}`;

    scrapeBtn.disabled = false;
    batchScrapeBtn.disabled = false;
  });

  async function processScrapedData(data, apiUrl) {
    lastScrapedData = data;
    downloadJsonBtn.style.display = 'block';

    if (!data.name) {
      handleError("Nama resto tidak terdeteksi di halaman ini.");
      return;
    }

    statusTitle.textContent = data.name;
    statusText.textContent = `Ditemukan ${data.products.length} menu makanan. Mengirim ke CicalengkaGO...`;
    
    itemList.innerHTML = '';
    itemList.style.display = 'block';
    data.products.slice(0, 10).forEach(p => {
      const li = document.createElement('li');
      li.innerHTML = `<span>${p.name}</span><strong>Rp ${p.price.toLocaleString('id-ID')}</strong>`;
      itemList.appendChild(li);
    });

    try {
      const resp = await sendToApi(apiUrl, data);
      const resText = await resp.text();
      let json = null;
      try {
        json = JSON.parse(resText);
      } catch (parseErr) {
        const cleanMsg = resText.replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(cleanMsg.slice(0, 180) || "Server tidak mengembalikan format JSON yang valid.");
      }

      if (resp.ok && json.success) {
        statusTitle.textContent = "✅ Berhasil Diimpor!";
        statusBadge.textContent = "SUCCESS";
        statusBadge.className = "badge success";
        statusText.textContent = json.message || `Toko '${data.name}' berhasil diimpor!`;
      } else {
        statusTitle.textContent = "⚠️ Gagal Impor ke Server";
        statusBadge.textContent = "ERROR";
        statusBadge.className = "badge error";
        statusText.textContent = json.message || "Terjadi kesalahan server saat mengimpor toko.";
      }
    } catch (netErr) {
      statusTitle.textContent = "⚠️ Koneksi / Respon Server Gagal";
      statusBadge.textContent = "ERROR";
      statusBadge.className = "badge error";
      statusText.textContent = `Gagal menghubungkan ke server endpoint (${apiUrl}). Silakan coba klik tombol '💻 Local (XAMPP)' jika Anda menguji di komputer lokal. Detail: ${netErr.message}`;
    } finally {
      scrapeBtn.disabled = false;
      batchScrapeBtn.disabled = false;
      scrapeBtn.innerHTML = '<span>🚀 Scrape & Impor Toko Ini</span>';
    }
  }

  function handleError(msg) {
    statusTitle.textContent = "Gagal Scrape";
    statusBadge.textContent = "ERROR";
    statusBadge.className = "badge error";
    statusText.textContent = msg;
    scrapeBtn.disabled = false;
    batchScrapeBtn.disabled = false;
    scrapeBtn.innerHTML = '<span>🚀 Scrape & Impor Toko Ini</span>';
  }

  downloadJsonBtn.addEventListener('click', () => {
    if (!lastScrapedData) return;
    const blob = new Blob([JSON.stringify(lastScrapedData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `grabfood_${lastScrapedData.name.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.json`;
    a.click();
    URL.revokeObjectURL(url);
  });
});
