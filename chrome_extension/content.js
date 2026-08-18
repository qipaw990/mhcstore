/**
 * Content script to extract store and menu data from GrabFood pages
 */

function extractGrabFoodData() {
  const result = {
    name: '',
    address: 'Cicalengka, Kab. Bandung',
    latitude: -6.98350000,
    longitude: 107.83350000,
    phone: '',
    category: 'Kuliner & Snack',
    rating: 4.8,
    reviews_count: 100,
    delivery_time: '15-25 min',
    logo: '',
    cover_photo: '',
    products: []
  };

  // 1. Try extracting from __NEXT_DATA__
  try {
    const scriptEl = document.getElementById('__NEXT_DATA__');
    if (scriptEl) {
      const nextData = JSON.parse(scriptEl.textContent);
      const redux = nextData?.props?.initialReduxState;
      
      // Check pageRestaurantDetail in redux
      const prd = redux?.pageRestaurantDetail;
      const entities = prd?.entities;
      
      if (entities) {
        for (const key in entities) {
          const item = entities[key];
          if (item?.name && item?.menu) {
            result.name = item.name;
            result.address = item.address || result.address;
            result.latitude = item.latlng?.latitude || result.latitude;
            result.longitude = item.latlng?.longitude || result.longitude;
            result.rating = item.rating || result.rating;
            result.logo = item.photoHref || item.photo || '';
            result.cover_photo = item.photoHref || '';
            
            const categories = item.menu?.categories || [];
            categories.forEach(cat => {
              const catName = cat.name || 'Menu Utama';
              (cat.items || []).forEach(p => {
                result.products.push({
                  name: p.name || 'Menu Makanan',
                  description: p.description || '',
                  price: (p.priceInCents || 0) / 100,
                  image: p.imgHref || p.photoHref || result.logo,
                  is_recommended: p.imgHref ? 1 : 0,
                  category: catName
                });
              });
            });
          }
        }
      }
    }
  } catch (e) {
    console.warn("CicalengkaGO Scraper NEXT_DATA extraction warning:", e);
  }

  // 2. DOM Fallback if NEXT_DATA had incomplete products
  if (!result.name) {
    const titleEl = document.querySelector('h1.name, h1.merchant-name, .name___3oO_B, h1');
    if (titleEl) {
      result.name = titleEl.textContent.trim();
    }
  }

  if (result.products.length === 0) {
    // Scrape DOM menu cards
    const menuCards = document.querySelectorAll('.menu-item, .item-card, [data-testid="menu-item"], .item___1vV-F');
    menuCards.forEach(card => {
      const nameEl = card.querySelector('.name, .item-name, h3, h4, .title___1b994');
      const priceEl = card.querySelector('.price, .item-price, .price___2k_92');
      const descEl = card.querySelector('.description, .item-desc, .desc___1LgG4');
      const imgEl = card.querySelector('img');

      if (nameEl && priceEl) {
        const rawPrice = priceEl.textContent.replace(/[^0-9]/g, '');
        const price = rawPrice ? parseInt(rawPrice, 10) : 15000;
        result.products.push({
          name: nameEl.textContent.trim(),
          description: descEl ? descEl.textContent.trim() : '',
          price: price,
          image: imgEl ? imgEl.src : '',
          is_recommended: 0
        });
      }
    });
  }

  // Set default images if empty
  if (!result.logo) {
    result.logo = 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80';
  }
  if (!result.cover_photo) {
    result.cover_photo = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
  }

  return result;
}

// Listen for requests from extension popup
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'SCRAPE_DATA') {
    const scraped = extractGrabFoodData();
    sendResponse({ success: true, data: scraped });
  }
  return true;
});
