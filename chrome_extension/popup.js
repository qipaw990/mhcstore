document.addEventListener('DOMContentLoaded', async () => {
  const scrapeBtn = document.getElementById('scrapeBtn');
  const batchScrapeBtn = document.getElementById('batchScrapeBtn');
  const stopBatchBtn = document.getElementById('stopBatchBtn');
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
  chrome.storage.local.get(['cgo_api_url', 'batchStatus'], (res) => {
    if (res.cgo_api_url) {
      apiUrlInput.value = res.cgo_api_url;
    }
    if (res.batchStatus) {
      updateBatchUI(res.batchStatus);
    }
  });

  // Listen to background storage changes for live progress updates
  chrome.storage.onChanged.addListener((changes, area) => {
    if (area === 'local' && changes.batchStatus) {
      updateBatchUI(changes.batchStatus.newValue);
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

  // Helper function to scan tab DOM for store URLs
  async function scanTabForStores(tabId) {
    try {
      await chrome.scripting.executeScript({
        target: { tabId: tabId },
        files: ['content.js']
      }).catch(() => {});

      const res = await chrome.scripting.executeScript({
        target: { tabId: tabId },
        func: () => {
          if (typeof window.extractStoreLinksFromListing === 'function') {
            const list = window.extractStoreLinksFromListing();
            if (list && list.length > 0) return list;
          }
          if (typeof extractStoreLinksFromListing === 'function') {
            const list = extractStoreLinksFromListing();
            if (list && list.length > 0) return list;
          }
          
          const storeUrls = new Set();
          const links = document.querySelectorAll('a[href*="/restaurant/"], a[href*="/store/"]');
          links.forEach(a => {
            let href = a.getAttribute('href');
            if (!href) return;
            if (href.startsWith('/')) href = 'https://food.grab.com' + href;
            const cleanUrl = href.split('?')[0];
            if (cleanUrl.includes('/restaurant/')) storeUrls.add(cleanUrl);
          });

          const cols = document.querySelectorAll('[class*="RestaurantListCol"], [class*="asList"], [class*="restaurantCard"], [class*="RestaurantCard"]');
          cols.forEach(col => {
            const a = col.querySelector('a') || col.closest('a');
            if (a) {
              let href = a.getAttribute('href');
              if (href) {
                if (href.startsWith('/')) href = 'https://food.grab.com' + href;
                const cleanUrl = href.split('?')[0];
                if (cleanUrl.includes('/restaurant/')) storeUrls.add(cleanUrl);
              }
            }
          });

          try {
            const nextScript = document.getElementById('__NEXT_DATA__');
            if (nextScript && nextScript.textContent) {
              const txt = nextScript.textContent;
              const matches = txt.match(/\/id\/id\/restaurant\/[a-zA-Z0-9\-_]+(?:\/[a-zA-Z0-9\-_]+)?/g) ||
                              txt.match(/https:\/\/food\.grab\.com\/[a-z]{2}\/[a-z]{2}\/restaurant\/[a-zA-Z0-9\-_]+(?:\/[a-zA-Z0-9\-_]+)?/g);
              if (matches) {
                matches.forEach(m => {
                  let fullUrl = m;
                  if (fullUrl.startsWith('/')) fullUrl = 'https://food.grab.com' + fullUrl;
                  storeUrls.add(fullUrl.split('?')[0]);
                });
              }
            }
          } catch (e) {}

          return Array.from(storeUrls);
        }
      });

      return res && res[0] ? res[0].result : [];
    } catch (e) {
      return [];
    }
  }

  // Check if active tab is a Store Listing page
  try {
    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    if (tab && tab.url && tab.url.includes('food.grab.com')) {
      const stores = await scanTabForStores(tab.id);
      if (Array.isArray(stores) && stores.length > 0) {
        listingStores = stores;
        storeCountEl.textContent = listingStores.length;
      }
    }
  } catch (e) {}

  function updateBatchUI(status) {
    if (!status) return;

    if (status.isBatchRunning) {
      scrapeBtn.disabled = true;
      batchScrapeBtn.disabled = true;
      progressContainer.style.display = 'block';
      statusCard.classList.add('active');
      statusTitle.textContent = "🚀 Scrape Massal Berjalan (Background)...";
      statusBadge.textContent = "RUNNING";
      statusBadge.className = "badge";
      
      const pct = status.percent || 0;
      progressBar.style.width = `${pct}%`;
      progressPercent.textContent = `${pct}%`;
      progressText.textContent = `[${status.current || 1}/${status.total || 1}] Memproses...`;
      statusText.textContent = status.statusText || "Meng-scrape toko...";
    } else if (status.completed) {
      progressContainer.style.display = 'block';
      progressBar.style.width = '100%';
      progressPercent.textContent = '100%';
      progressText.textContent = 'Selesai!';

      statusCard.classList.add('active');
      statusTitle.textContent = "🎉 BATCH IMPORT SELESAI!";
      statusBadge.textContent = "COMPLETED";
      statusBadge.className = "badge success";
      statusText.textContent = status.statusText || "Seluruh toko berhasil diimpor ke CicalengkaGO!";

      scrapeBtn.disabled = false;
      batchScrapeBtn.disabled = false;
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
      if (!tab.url || !tab.url.includes("food.grab.com")) {
        throw new Error("Harap buka halaman resto GrabFood terlebih dahulu (food.grab.com)!");
      }

      // Inject content script
      await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        files: ['content.js']
      }).catch(() => {});

      // Execute extraction function directly in tab DOM
      const results = await chrome.scripting.executeScript({
        target: { tabId: tab.id },
        func: () => {
          if (typeof extractGrabFoodData === 'function') {
            const res = extractGrabFoodData();
            if (res && res.name) return res;
          }
          // Inline DOM Fallback
          const h1Text = document.querySelector('h1')?.textContent?.trim();
          const titleText = document.title ? document.title.split('|')[0].replace(/- Delivery.*/i, '').trim() : '';
          const name = h1Text || titleText || 'Resto GrabFood';
          
          return {
            name: name,
            address: 'Cicalengka, Kab. Bandung',
            latitude: -6.98350000,
            longitude: 107.83350000,
            category: 'Kuliner & Snack',
            products: []
          };
        }
      });

      const data = results && results[0] ? results[0].result : null;

      if (data && data.name) {
        processScrapedData(data, apiUrl);
      } else {
        handleError("Gagal membaca DOM GrabFood. Pastikan Anda berada di halaman resto GrabFood yang valid.");
      }
    } catch (err) {
      handleError(err.message);
    }
  });

  // Batch Store Scrape (Delegate to Background Worker)
  batchScrapeBtn.addEventListener('click', async () => {
    const apiUrl = apiUrlInput.value.trim();
    if (!apiUrl) {
      alert("Harap masukkan URL Endpoint CicalengkaGO!");
      return;
    }

    // Try extracting listing stores on the fly if not detected yet
    if (!listingStores || listingStores.length === 0) {
      try {
        const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
        if (tab && tab.url && tab.url.includes('food.grab.com')) {
          const stores = await scanTabForStores(tab.id);
          if (Array.isArray(stores) && stores.length > 0) {
            listingStores = stores;
            storeCountEl.textContent = listingStores.length;
          } else if (tab.url.includes('/restaurant/')) {
            // Fallback: If user is on a single store page, batch scrape this 1 store
            listingStores = [tab.url.split('?')[0]];
            storeCountEl.textContent = "1";
          }
        }
      } catch (e) {}
    }

    if (!listingStores || listingStores.length === 0) {
      alert("Harap buka halaman daftar restoran GrabFood (food.grab.com/id/id/restaurants) terlebih dahulu!");
      return;
    }

    if (!confirm(`Apakah Anda yakin ingin meng-scrape & mengimpor ${listingStores.length} toko sekaligus ke CicalengkaGO?\n\nProses ini berjalan otomatis di background.`)) {
      return;
    }

    scrapeBtn.disabled = true;
    batchScrapeBtn.disabled = true;
    progressContainer.style.display = 'block';
    statusCard.classList.add('active');
    statusTitle.textContent = "🚀 Memulai Scrape Massal...";
    statusBadge.textContent = "STARTING";
    statusBadge.className = "badge";

    // Send task to background service worker
    chrome.runtime.sendMessage({
      action: 'START_BATCH_SCRAPE',
      stores: listingStores,
      apiUrl: apiUrl
    });
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
      const resp = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(data)
      });

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
