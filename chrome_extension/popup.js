document.addEventListener('DOMContentLoaded', () => {
  const scrapeBtn = document.getElementById('scrapeBtn');
  const downloadJsonBtn = document.getElementById('downloadJsonBtn');
  const apiUrlInput = document.getElementById('apiUrl');
  const statusCard = document.getElementById('statusCard');
  const statusTitle = document.getElementById('statusTitle');
  const statusBadge = document.getElementById('statusBadge');
  const statusText = document.getElementById('statusText');
  const itemList = document.getElementById('itemList');

  let lastScrapedData = null;

  // Restore saved API URL if any
  chrome.storage.local.get(['cgo_api_url'], (res) => {
    if (res.cgo_api_url) {
      apiUrlInput.value = res.cgo_api_url;
    }
  });

  apiUrlInput.addEventListener('change', () => {
    chrome.storage.local.set({ cgo_api_url: apiUrlInput.value.trim() });
  });

  scrapeBtn.addEventListener('click', async () => {
    const apiUrl = apiUrlInput.value.trim();
    if (!apiUrl) {
      alert("Harap masukkan URL Endpoint CicalengkaGO!");
      return;
    }

    scrapeBtn.disabled = true;
    scrapeBtn.innerHTML = '<span>⏳ Mengambil Data Resto...</span>';
    statusCard.classList.add('active');
    statusTitle.textContent = "Meng-scrape GrabFood...";
    statusBadge.textContent = "SCRAPING";
    statusBadge.className = "badge";
    statusText.textContent = "Membaca data toko dan menu dari halaman aktif...";
    itemList.style.display = 'none';

    try {
      // 1. Get active tab
      const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
      if (!tab) throw new Error("Tidak menemukan tab aktif.");

      if (!tab.url.includes("food.grab.com")) {
        throw new Error("Harap buka halaman resto GrabFood terlebih dahulu (food.grab.com)!");
      }

      // 2. Send message to content script
      chrome.tabs.sendMessage(tab.id, { action: 'SCRAPE_DATA' }, async (response) => {
        if (chrome.runtime.lastError || !response || !response.success) {
          // Fallback: inject content.js dynamically
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

  async function processScrapedData(data, apiUrl) {
    lastScrapedData = data;
    downloadJsonBtn.style.display = 'block';

    if (!data.name) {
      handleError("Nama resto tidak terdeteksi di halaman ini.");
      return;
    }

    statusTitle.textContent = data.name;
    statusText.textContent = `Ditemukan ${data.products.length} menu makanan. Mengirim ke CicalengkaGO...`;
    
    // Render item preview list
    itemList.innerHTML = '';
    itemList.style.display = 'block';
    data.products.slice(0, 10).forEach(p => {
      const li = document.createElement('li');
      li.innerHTML = `<span>${p.name}</span><strong>Rp ${p.price.toLocaleString('id-ID')}</strong>`;
      itemList.appendChild(li);
    });

    // Send data to CicalengkaGO API
    try {
      const resp = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        mode: 'cors',
        body: JSON.stringify(data)
      });

      const resText = await resp.text();
      let json = null;
      try {
        json = JSON.parse(resText);
      } catch (parseErr) {
        // Strip HTML tags to get clean error message if server returned PHP error HTML
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
      statusText.textContent = `Pesan dari server (${apiUrl}): ${netErr.message}`;
    } finally {
      scrapeBtn.disabled = false;
      scrapeBtn.innerHTML = '<span>🚀 Scrape & Impor Toko Ini</span>';
    }
  }

  function handleError(msg) {
    statusTitle.textContent = "Gagal Scrape";
    statusBadge.textContent = "ERROR";
    statusBadge.className = "badge error";
    statusText.textContent = msg;
    scrapeBtn.disabled = false;
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
