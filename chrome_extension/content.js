/**
 * Enhanced CicalengkaGO GrabFood Store & Menu Scraper Content Script
 * Exhaustively extracts store details, high-res images, categories, and products.
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
    reviews_count: 120,
    delivery_time: '15-25 min',
    opening_time: '08:00:00',
    closing_time: '22:00:00',
    logo: '',
    cover_photo: '',
    products: []
  };

  function parseHoursText(text) {
    if (!text) return null;
    const lower = text.toLowerCase();
    if (lower.includes('24 jam') || lower.includes('24 hour') || lower.includes('24-hour')) {
      return { opening_time: '00:00:00', closing_time: '23:59:59' };
    }
    const match = text.match(/(\d{1,2})[:.](\d{2})\s*[-–—to]+\s*(\d{1,2})[:.](\d{2})/);
    if (match) {
      const openH = match[1].padStart(2, '0');
      const openM = match[2];
      const closeH = match[3].padStart(2, '0');
      const closeM = match[4];
      return {
        opening_time: `${openH}:${openM}:00`,
        closing_time: `${closeH}:${closeM}:00`
      };
    }
    return null;
  }

  // Helper to sanitize image URLs
  function cleanImageUrl(url) {
    if (!url) return '';
    if (url.includes('logo-grabfood') || url.includes('placeholder') || url.includes('.svg')) return '';
    if (url.startsWith('//')) url = 'https:' + url;
    if (url.startsWith('/')) url = 'https://food.grab.com' + url;
    // Upgrade low-res thumbnail parameters if present
    url = url.replace(/w=\d+/, 'w=800').replace(/h=\d+/, 'h=800');
    return url;
  }

  // -------------------------------------------------------------
  // STRATEGY 1: RECURSIVE JSON & SCRIPT TAG CRAWLER
  // -------------------------------------------------------------
  function scanObjectForMenu(obj, depth = 0) {
    if (!obj || typeof obj !== 'object' || depth > 10) return;

    // Check if this object is a Store/Merchant
    if (obj.name && (obj.latlng || obj.latitude || obj.location || obj.coordinates || obj.photoHref || obj.merchantID || obj.menu)) {
      if (!result.name) result.name = obj.name;
      if (obj.address) result.address = obj.address;
      
      const lat = obj.latitude || obj.lat || obj.latlng?.latitude || obj.latlng?.lat || obj.location?.latitude || obj.location?.lat || obj.coordinates?.latitude || obj.coordinates?.lat;
      const lng = obj.longitude || obj.lng || obj.latlng?.longitude || obj.latlng?.lng || obj.location?.longitude || obj.location?.lng || obj.coordinates?.longitude || obj.coordinates?.lng;

      if (lat && lng) {
        result.latitude = parseFloat(lat);
        result.longitude = parseFloat(lng);
      }
      
      if (obj.rating) result.rating = obj.rating;
      if (obj.reviewsCount) result.reviews_count = obj.reviewsCount;
      if (obj.openingHours || obj.businessHours || obj.openHours) {
        const hoursObj = parseHoursText(obj.openingHours || obj.businessHours || obj.openHours);
        if (hoursObj) {
          result.opening_time = hoursObj.opening_time;
          result.closing_time = hoursObj.closing_time;
        }
      }
      if (obj.photoHref || obj.photo) {
        const photo = cleanImageUrl(obj.photoHref || obj.photo);
        if (!result.logo) result.logo = photo;
        if (!result.cover_photo) result.cover_photo = photo;
      }
    }

    // Check if this object is a Menu Category or Menu Item list
    if (Array.isArray(obj.categories)) {
      obj.categories.forEach(cat => {
        const catName = cat.name || 'Menu Utama';
        const catNameLower = catName.toLowerCase();

        // Skip "Untukmu" / "Rekomendasi" / "For You" recommendation section
        if (catNameLower.includes('untukmu') || 
            catNameLower.includes('untuk mu') || 
            catNameLower.includes('for you') || 
            catNameLower.includes('rekomendasi') || 
            catNameLower.includes('recommended')) {
          return;
        }

        if (cat.name && (result.category === 'Kuliner & Snack' || result.category === 'Menu Utama')) {
          result.category = cat.name;
        }

        const items = cat.items || cat.menuItems || [];
        items.forEach(p => {
          if (p && p.name) {
            const price = (p.priceInCents ? p.priceInCents / 100 : (p.price || 15000));
            const img = cleanImageUrl(p.imgHref || p.photoHref || p.photo || p.image || p.url || '');
            
            // Check duplicate
            if (!result.products.some(existing => existing.name === p.name)) {
              result.products.push({
                name: p.name.trim(),
                description: (p.description || '').trim(),
                price: parseFloat(price) || 15000,
                image: img,
                is_recommended: 0,
                category: catName
              });
            }
          }
        });
      });
    // Check if this object contains direct items or menuItems list
    if (Array.isArray(obj.items) || Array.isArray(obj.menuItems) || Array.isArray(obj.products)) {
      const itemsList = obj.items || obj.menuItems || obj.products || [];
      itemsList.forEach(p => {
        if (p && p.name && (p.priceInCents !== undefined || p.price !== undefined || p.imgHref || p.photoHref)) {
          const price = (p.priceInCents ? p.priceInCents / 100 : (p.price || 15000));
          const img = cleanImageUrl(p.imgHref || p.photoHref || p.photo || p.image || p.url || '');
          if (!result.products.some(existing => existing.name === p.name)) {
            result.products.push({
              name: p.name.trim(),
              description: (p.description || '').trim(),
              price: parseFloat(price) || 15000,
              image: img,
              is_recommended: 0,
              category: result.category || 'Menu Utama'
            });
          }
        }
      });
    }

    // Recursively scan keys
    if (Array.isArray(obj)) {
      obj.forEach(child => scanObjectForMenu(child, depth + 1));
    } else {
      Object.keys(obj).forEach(key => {
        if (typeof obj[key] === 'object' && obj[key] !== null) {
          scanObjectForMenu(obj[key], depth + 1);
        }
      });
    }
  }

  // 1. Scan window.__NEXT_DATA__
  try {
    const nextScript = document.getElementById('__NEXT_DATA__');
    if (nextScript && nextScript.textContent) {
      const nextData = JSON.parse(nextScript.textContent);
      scanObjectForMenu(nextData);
    }
  } catch (e) {
    console.warn("CicalengkaGO NEXT_DATA scan error:", e);
  }

  // 2. Scan all script tags containing JSON data
  if (result.products.length === 0) {
    const scripts = document.querySelectorAll('script[type="application/json"], script:not([src])');
    scripts.forEach(s => {
      const txt = s.textContent || '';
      if (txt.includes('priceInCents') || txt.includes('categories') || txt.includes('imgHref')) {
        try {
          const parsed = JSON.parse(txt);
          scanObjectForMenu(parsed);
        } catch (e) {
          // Attempt regex extraction of JSON objects
          const matches = txt.match(/\{"name":.*?"priceInCents":.*?\}/g);
          if (matches) {
            matches.forEach(m => {
              try {
                const p = JSON.parse(m);
                if (p.name) {
                  result.products.push({
                    name: p.name,
                    description: p.description || '',
                    price: (p.priceInCents || 0) / 100,
                    image: cleanImageUrl(p.imgHref || p.photoHref || ''),
                    is_recommended: 0
                  });
                }
              } catch (err) {}
            });
          }
        }
      }
    });
  }

  // -------------------------------------------------------------
  // STRATEGY 2: COMPREHENSIVE DOM SCRAPER & IMAGE EXTRACTOR
  // -------------------------------------------------------------

  // 1. Extract Store Name & Images & Coordinates from DOM
  if (!result.name) {
    const h1El = document.querySelector('h1[class*="name"], h1[class*="merchant"], h1');
    if (h1El && h1El.textContent.trim()) {
      result.name = h1El.textContent.trim();
    }
  }

  if (!result.name) {
    const ogTitle = document.querySelector('meta[property="og:title"]');
    const titleTxt = ogTitle ? (ogTitle.getAttribute('content') || ogTitle.content) : document.title;
    if (titleTxt) {
      result.name = titleTxt.split('|')[0].replace(/- Delivery.*/i, '').replace(/ - GrabFood.*/i, '').trim();
    }
  }

  if (!result.name) {
    const pathParts = window.location.pathname.split('/');
    const restIdx = pathParts.indexOf('restaurant');
    if (restIdx !== -1 && pathParts[restIdx + 1]) {
      const slug = pathParts[restIdx + 1].replace(/-delivery.*/i, '').replace(/-/g, ' ');
      result.name = slug.replace(/\b\w/g, l => l.toUpperCase());
    }
  }

  // Fallback coordinate extraction from HTML source or Google Maps links
  if (!result.latitude || !result.longitude) {
    const pageHtml = document.documentElement.innerHTML;
    const latLngMatch = pageHtml.match(/"latitude"\s*:\s*(-?\d+\.\d+).*?"longitude"\s*:\s*(-?\d+\.\d+)/) ||
                        pageHtml.match(/"lat"\s*:\s*(-?\d+\.\d+).*?"lng"\s*:\s*(-?\d+\.\d+)/);
    if (latLngMatch) {
      result.latitude = parseFloat(latLngMatch[1]);
      result.longitude = parseFloat(latLngMatch[2]);
    } else {
      const mapsMatch = pageHtml.match(/maps\.google\.com.*?q=(-?\d+\.\d+),(-?\d+\.\d+)/) ||
                        pageHtml.match(/google\.com\/maps.*?@(-?\d+\.\d+),(-?\d+\.\d+)/);
      if (mapsMatch) {
        result.latitude = parseFloat(mapsMatch[1]);
        result.longitude = parseFloat(mapsMatch[2]);
      }
    }
  }

  // Cover & Logo image from DOM
  const heroImgs = document.querySelectorAll('img[src*="food-cms"], img[src*="grab"], img[class*="header"], img[class*="hero"], img[class*="merchant"]');
  heroImgs.forEach(img => {
    const src = cleanImageUrl(img.src || img.getAttribute('data-src') || '');
    if (src && (!result.logo || result.logo.includes('unsplash'))) {
      result.logo = src;
      result.cover_photo = src;
    }
  });

  // 2. Extract DOM Menu Items if products are still empty or missing images
  const domCards = document.querySelectorAll('[class*="itemCard"], [class*="menuItem"], [class*="item-"], [class*="MenuItem"], div[role="button"]');
  
  domCards.forEach(card => {
    // Skip cards inside 'Untukmu' recommendation section
    const parentSec = card.closest('section, [class*="category"], [class*="Category"], [class*="section"]');
    if (parentSec) {
      const headerText = (parentSec.querySelector('h1, h2, h3, h4, [class*="title"], [class*="header"]') || {}).textContent || '';
      const htLower = headerText.toLowerCase();
      if (htLower.includes('untukmu') || htLower.includes('untuk mu') || htLower.includes('for you') || htLower.includes('rekomendasi') || htLower.includes('recommended')) {
        return;
      }
    }

    const nameEl = card.querySelector('h3, h4, [class*="name"], [class*="title"], [class*="itemName"]');
    const priceEl = card.querySelector('[class*="price"], [class*="Price"]');
    const descEl = card.querySelector('p, [class*="desc"], [class*="Description"]');
    const imgEl = card.querySelector('img');

    if (nameEl && priceEl) {
      const nameText = nameEl.textContent.trim();
      if (!nameText || nameText.length < 2) return;

      const rawPrice = priceEl.textContent.replace(/[^0-9]/g, '');
      const priceVal = rawPrice ? parseInt(rawPrice, 10) : 15000;

      let imgUrl = '';
      if (imgEl) {
        imgUrl = cleanImageUrl(imgEl.src || imgEl.getAttribute('data-src') || imgEl.getAttribute('srcset') || '');
      }
      
      // Fallback background-image
      if (!imgUrl) {
        const bgEl = card.querySelector('[style*="background-image"]');
        if (bgEl) {
          const bgMatch = bgEl.style.backgroundImage.match(/url\(['"]?(.*?)['"]?\)/);
          if (bgMatch) imgUrl = cleanImageUrl(bgMatch[1]);
        }
      }

      // Check if product already exists in list
      const existingProduct = result.products.find(p => p.name.toLowerCase() === nameText.toLowerCase());
      if (existingProduct) {
        // Update missing image or description
        if (!existingProduct.image && imgUrl) existingProduct.image = imgUrl;
        if (!existingProduct.description && descEl) existingProduct.description = descEl.textContent.trim();
      } else {
        result.products.push({
          name: nameText,
          description: descEl ? descEl.textContent.trim() : '',
          price: priceVal,
          image: imgUrl || result.logo,
          is_recommended: imgUrl ? 1 : 0
        });
      }
    }
  });

  // Filter & clean scraped product titles and images
  result.products.forEach((p) => {
    // Clean concatenated product name e.g. "Nasi goreng sosisNasi Goreng campur..."
    if (p.name) {
      const match = p.name.match(/([a-z0-9])([A-Z])/);
      if (match && match.index) {
        const title = p.name.substring(0, match.index + 1).trim();
        const desc  = p.name.substring(match.index + 1).trim();
        if (title.length > 2) {
          p.name = title;
          if (!p.description) p.description = desc;
        }
      }
    }

    // Check if image is a REAL GrabFood merchant food photo (contains food-cms / huawei-food-cms / compressed_webp / menueditor_item)
    const isRealGrabPhoto = p.image && (
      p.image.includes('food-cms') || 
      p.image.includes('huawei-food-cms') || 
      p.image.includes('compressed_webp') || 
      p.image.includes('menueditor_item')
    );

    if (!isRealGrabPhoto) {
      p.image = getSmartFoodPhoto(p.name);
    }
  });

  if (!result.logo || !result.logo.includes('food-cms')) {
    result.logo = 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=300&q=80';
  }
  if (!result.cover_photo || !result.cover_photo.includes('food-cms')) {
    result.cover_photo = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
  }

  return result;
}

// Smart Indonesian culinary photo resolver for menu items lacking GrabFood merchant photos
function getSmartFoodPhoto(name) {
  const n = (name || '').toLowerCase();
  if (n.includes('nasi goreng') || n.includes('nasgor')) {
    return 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('kwetiau') || n.includes('kwetiew')) {
    return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('mie') || n.includes('ramen') || n.includes('noodle')) {
    return 'https://images.unsplash.com/photo-1612927601601-6638404737ce?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('ayam') || n.includes('geprek') || n.includes('chicken')) {
    return 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('seblak')) {
    return 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('bakso') || n.includes('baso')) {
    return 'https://images.unsplash.com/photo-1541696432-82c6da8ce7bf?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('sate') || n.includes('satay')) {
    return 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('martabak') || n.includes('cake') || n.includes('cheese')) {
    return 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('es') || n.includes('kopi') || n.includes('tea') || n.includes('drink') || n.includes('latte') || n.includes('jus')) {
    return 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('wonton') || n.includes('dimsum')) {
    return 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('burger')) {
    return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80';
  }
  if (n.includes('fries') || n.includes('kentang')) {
    return 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=600&q=80';
  }
  return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80';
}

// Extract all store URLs on the listing page
function extractStoreLinksFromListing() {
  const storeUrls = new Set();

  // 1. Scan DOM <a> elements matching GrabFood restaurant links
  const links = document.querySelectorAll('a[href*="/restaurant/"], a[href*="/store/"]');
  links.forEach(a => {
    let href = a.getAttribute('href');
    if (!href) return;
    if (href.startsWith('/')) href = 'https://food.grab.com' + href;
    const cleanUrl = href.split('?')[0];
    if (cleanUrl.includes('/restaurant/')) {
      storeUrls.add(cleanUrl);
    }
  });

  // 2. Scan RestaurantListCol, RestaurantListRow, and asList card containers
  const cols = document.querySelectorAll('[class*="RestaurantListCol"], [class*="asList"], [class*="restaurantCard"], [class*="RestaurantCard"]');
  cols.forEach(col => {
    const a = col.querySelector('a') || col.closest('a');
    if (a) {
      let href = a.getAttribute('href');
      if (href) {
        if (href.startsWith('/')) href = 'https://food.grab.com' + href;
        const cleanUrl = href.split('?')[0];
        if (cleanUrl.includes('/restaurant/')) {
          storeUrls.add(cleanUrl);
        }
      }
    }
  });

  // 3. Scan __NEXT_DATA__ JSON script tag
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

// Expose extraction functions globally on window for direct script execution
window.extractGrabFoodData = extractGrabFoodData;
window.extractStoreLinksFromListing = extractStoreLinksFromListing;

// Listen for requests from popup.js
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'GET_LISTING_STORES') {
    const stores = extractStoreLinksFromListing();
    sendResponse({ success: true, stores: stores });
    return true;
  }

  if (request.action === 'SCRAPE_DATA') {
    const scraped = extractGrabFoodData();
    sendResponse({ success: true, data: scraped });
    return true;
  }
});
