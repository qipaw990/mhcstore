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
      return { opening_time: '00:00:00', closing_time: '23:59:59', operating_hours: '00:00 - 23:59' };
    }
    const matches = Array.from(text.matchAll(/(\d{1,2})[:.](\d{2})\s*[-–—to]+\s*(\d{1,2})[:.](\d{2})/g));
    if (matches.length > 0) {
      const first = matches[0];
      const last  = matches[matches.length - 1];
      const openH = first[1].padStart(2, '0');
      const openM = first[2];
      const closeH = last[3].padStart(2, '0');
      const closeM = last[4];

      const shifts = matches.map(m => {
        return `${m[1].padStart(2, '0')}:${m[2]} - ${m[3].padStart(2, '0')}:${m[4]}`;
      }).join(', ');

      return {
        opening_time: `${openH}:${openM}:00`,
        closing_time: `${closeH}:${closeM}:00`,
        operating_hours: shifts
      };
    }
    return null;
  }

  // Helper to sanitize image URLs
  function cleanImageUrl(url) {
    if (!url) return '';
    if (typeof url === 'object') {
      url = url.url || url.href || url.src || url.photo || '';
    }
    if (typeof url !== 'string') return '';
    if (url.includes('logo-grabfood') || url.includes('placeholder') || url.includes('.svg')) return '';
    if (url.startsWith('//')) url = 'https:' + url;
    if (url.startsWith('/')) url = 'https://food.grab.com' + url;
    // Upgrade low-res thumbnail parameters if present
    url = url.replace(/w=\d+/, 'w=800').replace(/h=\d+/, 'h=800');
    return url;
  }

  // Clean trailing price pattern e.g. " 40.000" or ".40.000" appended to title
  function cleanProductName(t) {
    if (!t) return '';
    return t.replace(/[\s\.\,]+\d{1,3}[\.\,]\d{3}\s*$/, '')
            .replace(/[\s\.\,]+\d{4,6}\s*$/, '')
            .trim();
  }

  // Normalized key for fuzzy cross-strategy matching
  function normalizeTitleKey(t) {
    if (!t) return '';
    return cleanProductName(t).toLowerCase().replace(/[^a-z0-9]/g, '');
  }

  // -------------------------------------------------------------
  // STRATEGY 0: SCHEMA.ORG JSON-LD PARSER (FASTEST & MOST ACCURATE)
  // -------------------------------------------------------------
  try {
    const ldScripts = document.querySelectorAll('script[type="application/ld+json"]');
    ldScripts.forEach(script => {
      if (!script.textContent) return;
      try {
        const ldData = JSON.parse(script.textContent);
        const itemsToScan = Array.isArray(ldData) ? ldData : [ldData];
        
        itemsToScan.forEach(ldObj => {
          if (!ldObj || typeof ldObj !== 'object') return;
          const typeStr = JSON.stringify(ldObj['@type'] || '');

          if (typeStr.includes('Restaurant') || typeStr.includes('LocalBusiness') || typeStr.includes('FoodEstablishment') || ldObj.name) {
            if (!result.name && ldObj.name) result.name = ldObj.name.trim();
            if (ldObj.address) {
              const addrStr = typeof ldObj.address === 'string' ? ldObj.address : (ldObj.address.streetAddress || ldObj.address.addressLocality || '');
              if (addrStr && !result.address) result.address = addrStr.trim();
            }
            if (ldObj.image) {
              const cleanImg = cleanImageUrl(typeof ldObj.image === 'string' ? ldObj.image : (ldObj.image.url || ''));
              if (cleanImg && !result.logo) {
                result.logo = cleanImg;
                result.cover_photo = cleanImg;
              }
            }
            if (ldObj.aggregateRating && ldObj.aggregateRating.ratingValue) {
              result.rating = parseFloat(ldObj.aggregateRating.ratingValue);
            }
            if (ldObj.aggregateRating && ldObj.aggregateRating.ratingCount) {
              result.reviews_count = parseInt(ldObj.aggregateRating.ratingCount, 10);
            }

            // Parse Menu Sections & Items
            const hasMenu = ldObj.hasMenu || ldObj.menu;
            const sections = (hasMenu && hasMenu.hasMenuSection) ? hasMenu.hasMenuSection : (ldObj.hasMenuSection || []);
            const sectionsArr = Array.isArray(sections) ? sections : [sections];

            sectionsArr.forEach(section => {
              if (!section) return;
              const catName = section.name || 'Menu Utama';
              const menuItems = section.hasMenuItem || section.itemListElement || section.items || [];
              const itemsArr = Array.isArray(menuItems) ? menuItems : [menuItems];

              itemsArr.forEach(item => {
                if (item && item.name) {
                  let priceVal = 15000;
                  if (item.offers) {
                    const offerObj = Array.isArray(item.offers) ? item.offers[0] : item.offers;
                    if (offerObj && offerObj.price) {
                      const pStr = String(offerObj.price || '').replace(/[^0-9]/g, '');
                      if (pStr) priceVal = parseInt(pStr, 10);
                    }
                  }

                  const nameTrimmed = cleanProductName(item.name);
                  const normKey = normalizeTitleKey(nameTrimmed);
                  const existingP = result.products.find(p => normalizeTitleKey(p.name) === normKey);
                  if (existingP) {
                    const itemImg = item.image ? cleanImageUrl(typeof item.image === 'string' ? item.image : (item.image.url || item.photo || '')) : '';
                    if (!existingP.image && itemImg) existingP.image = itemImg;
                  } else if (nameTrimmed) {
                    const itemImg = item.image ? cleanImageUrl(typeof item.image === 'string' ? item.image : (item.image.url || item.photo || '')) : '';
                    result.products.push({
                      name: nameTrimmed,
                      description: (item.description || '').trim(),
                      price: priceVal,
                      image: itemImg || '',
                      is_recommended: 0,
                      category: catName
                    });
                  }
                }
              });
            });
          }
        });
      } catch (err) {}
    });
  } catch (e) {}

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

    // Initialize photo harvest cache
    window.__grabProductPhotos = window.__grabProductPhotos || {};

    // Check if this object is a Menu Category or Menu Item list
    if (Array.isArray(obj.categories)) {
      obj.categories.forEach(cat => {
        const catName = cat.name || 'Menu Utama';
        const catNameLower = catName.toLowerCase();

        const isRecSection = catNameLower.includes('untukmu') || 
                             catNameLower.includes('untuk mu') || 
                             catNameLower.includes('for you') || 
                             catNameLower.includes('rekomendasi') || 
                             catNameLower.includes('recommended');

        if (cat.name && !isRecSection && (result.category === 'Kuliner & Snack' || result.category === 'Menu Utama')) {
          result.category = cat.name;
        }

        const items = cat.items || cat.menuItems || [];
        items.forEach(p => {
          if (p && p.name) {
            const price = (p.priceInCents ? p.priceInCents / 100 : (p.price || 15000));
            let rawImg = p.imgHref || p.photoHref || p.photo || p.image || p.url || p.photoUrl || '';
            if (!rawImg && Array.isArray(p.photos) && p.photos[0]) {
              rawImg = p.photos[0].photoHref || p.photos[0].url || p.photos[0] || '';
            }
            const img = cleanImageUrl(rawImg);
            const pNameClean = cleanProductName(p.name);
            const normKey = normalizeTitleKey(pNameClean);
            
            // Harvest photo into memory cache regardless of section
            if (img && normKey) {
              window.__grabProductPhotos[normKey] = img;
            }

            // Do not add recommendation items as duplicate products
            if (isRecSection) return;

            const existing = result.products.find(e => normalizeTitleKey(e.name) === normKey);
            if (existing) {
              if (!existing.image && img) existing.image = img;
              if (!existing.description && p.description) existing.description = p.description.trim();
            } else if (pNameClean) {
              result.products.push({
                name: pNameClean,
                description: (p.description || '').trim(),
                price: parseFloat(price) || 15000,
                image: img || (window.__grabProductPhotos[normKey] || ''),
                is_recommended: 0,
                category: catName
              });
            }
          }
        });
      });
    }

    // Check if this object contains direct items or menuItems list
    if (Array.isArray(obj.items) || Array.isArray(obj.menuItems) || Array.isArray(obj.products)) {
      const itemsList = obj.items || obj.menuItems || obj.products || [];
      itemsList.forEach(p => {
        if (p && p.name) {
          const price = (p.priceInCents ? p.priceInCents / 100 : (p.price || 15000));
          let rawImg = p.imgHref || p.photoHref || p.photo || p.image || p.url || p.photoUrl || '';
          if (!rawImg && Array.isArray(p.photos) && p.photos[0]) {
            rawImg = p.photos[0].photoHref || p.photos[0].url || p.photos[0] || '';
          }
          const img = cleanImageUrl(rawImg);
          const pNameClean = cleanProductName(p.name);
          const normKey = normalizeTitleKey(pNameClean);

          const existing = result.products.find(e => normalizeTitleKey(e.name) === normKey);
          if (existing) {
            if (!existing.image && img) existing.image = img;
            if (!existing.description && p.description) existing.description = p.description.trim();
          } else if (pNameClean) {
            result.products.push({
              name: pNameClean,
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

  // Fallback coordinate & operating hours extraction from HTML source or Google Maps links
  if (!result.latitude || !result.longitude || !result.opening_time || result.opening_time === '08:00:00') {
    const pageHtml = document.documentElement.innerHTML;
    const pageTxt  = document.body ? document.body.innerText : '';

    // Extract hours from DOM text
    const hoursMatch = pageTxt.match(/(?:Jam Buka|Buka|Opening Hours|Operasional|Jam operasional)[\s:]*(\d{1,2}[:.]\d{2}\s*[-–—to]+\s*\d{1,2}[:.]\d{2})/i) ||
                       pageTxt.match(/(\d{1,2}[:.]\d{2}\s*[-–—to]+\s*\d{1,2}[:.]\d{2})/);
    if (hoursMatch) {
      const parsed = parseHoursText(hoursMatch[1]);
      if (parsed) {
        result.opening_time = parsed.opening_time;
        result.closing_time = parsed.closing_time;
      }
    }

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

  // 2. Extract DOM Menu Items
  const cardSelectors = [
    'div[class*="menuItem___"]',
    'div[class*="menuItem--"]',
    'div[class*="menuItemWrapper"]',
    'div[class*="itemCard"]',
    'div[class*="MenuItem"]',
    'div[class*="categoryContent"] > div',
    '.ant-col-lg-8'
  ];
  
  let domCards = Array.from(document.querySelectorAll(cardSelectors.join(', ')));
  
  // Deduplicate nested card elements if outer and inner match
  const filteredCards = domCards.filter(c => {
    return !domCards.some(other => other !== c && other.contains(c));
  });

  filteredCards.forEach(card => {
    const parentSec = card.closest('div[class*="categoryContent"], div[class*="CategoryContent"], section, [class*="category"], [class*="Category"], [class*="section"]');
    let catName = 'Menu Utama';
    let isRecSection = false;
    if (parentSec) {
      const headerEl = parentSec.querySelector('h1, h2, h3, h4, [class*="categoryTitle"], [class*="categoryName"], [class*="categoryHeader"], [class*="title"], [class*="header"]');
      const headerText = headerEl ? headerEl.textContent.trim() : '';
      const htLower = headerText.toLowerCase();
      if (htLower) {
        if (htLower.includes('untukmu') || htLower.includes('untuk mu') || htLower.includes('for you') || htLower.includes('rekomendasi') || htLower.includes('recommended')) {
          isRecSection = true;
        } else {
          catName = headerText;
        }
      }
    }

    const titleEl = card.querySelector('p[class*="itemNameTitle"], [class*="itemNameTitle"], [class*="itemName"] p, [class*="itemName"], [class*="item-name"], [class*="product-name"], h3, h4, h5');
    const priceEl = card.querySelector('p[class*="discountedPrice"], [class*="discountedPrice"], [class*="itemPrice"] p, [class*="itemPrice"], [class*="price"], [class*="Price"]');
    const descEl  = card.querySelector('p[class*="itemDescription"], [class*="itemDescription"], [class*="product-description"], p[class*="desc"]');
    const imgEl   = card.querySelector('img[class*="realImage"], div[class*="menuItemPhoto"] img, div[class*="menuItemPhotoContainer"] img, img[src*="food-cms"], img[src*="huawei-food-cms"], img[src*="grab"], img');

    if (titleEl) {
      const nameText = cleanProductName(titleEl.textContent.trim());
      if (!nameText || nameText.length < 2 || nameText.toLowerCase().includes('antar ke') || nameText.toLowerCase().includes('masuk/daftar')) return;

      let priceVal = 15000;
      if (priceEl) {
        const rawPrice = priceEl.textContent.replace(/[^0-9]/g, '');
        if (rawPrice) priceVal = parseInt(rawPrice, 10);
      }

      let imgUrl = '';
      if (imgEl) {
        imgUrl = cleanImageUrl(imgEl.src || imgEl.getAttribute('data-src') || imgEl.getAttribute('srcset') || '');
      }
      if (!imgUrl) {
        const bgEl = card.querySelector('[style*="background-image"]');
        if (bgEl) {
          const bgMatch = bgEl.style.backgroundImage.match(/url\(['"]?(.*?)['"]?\)/);
          if (bgMatch) imgUrl = cleanImageUrl(bgMatch[1]);
        }
      }

      let descText = descEl ? descEl.textContent.replace(/\u00a0/g, ' ').trim() : '';
      if (descText === nameText) descText = '';

      const normKey = normalizeTitleKey(nameText);

      // Harvest photo into memory cache regardless of section
      if (imgUrl && normKey) {
        window.__grabProductPhotos[normKey] = imgUrl;
      }

      // Skip creating duplicate products from "Untukmu" section
      if (isRecSection) return;

      const existingProduct = result.products.find(p => normalizeTitleKey(p.name) === normKey);
      if (existingProduct) {
        existingProduct.name = nameText;
        if (!existingProduct.image && imgUrl) existingProduct.image = imgUrl;
        if (!existingProduct.description && descText) existingProduct.description = descText;
      } else {
        result.products.push({
          name: nameText,
          description: descText,
          price: priceVal,
          image: imgUrl || (window.__grabProductPhotos[normKey] || ''),
          is_recommended: imgUrl ? 1 : 0,
          category: catName
        });
      }
    }
  });

  // 1. Exclude products under 'Untukmu', 'Rekomendasi', or 'For You' recommendation categories
  result.products = result.products.filter(p => {
    if (!p.category) return true;
    const cat = p.category.toLowerCase().trim();
    return !cat.includes('untukmu') &&
           !cat.includes('untuk mu') &&
           !cat.includes('for you') &&
           !cat.includes('rekomendasi') &&
           !cat.includes('recommended');
  });

  // Filter & clean scraped product titles and images
  result.products.forEach((p) => {
    p.name = cleanProductName(p.name);

    if (p.name) {
      // Check 1: UPPERCASE title concatenated with TitleCase description
      const upperMatch = p.name.match(/^([A-Z0-9\s\-\.\/]{3,})([A-Z][a-z].*)$/);
      if (upperMatch && upperMatch[1].trim().length >= 3) {
        const title = upperMatch[1].trim();
        const desc  = upperMatch[2].trim();
        p.name = title;
        if (!p.description) p.description = desc;
      } else {
        // Check 2: Lowercase/digit concatenated with Uppercase
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
    }

    // Check harvest cache window.__grabProductPhotos if image is missing
    if (!p.image && p.name && window.__grabProductPhotos) {
      const normKey = normalizeTitleKey(p.name);
      if (window.__grabProductPhotos[normKey]) {
        p.image = window.__grabProductPhotos[normKey];
      }
    }

    // Check if image is a REAL GrabFood merchant item photo (and NOT a merchant logo/hero photo)
    const isMerchantLogo = p.image && (
      p.image.includes('/merchants/') ||
      p.image.includes('/hero/') ||
      p.image.includes('logo-grabfood') ||
      p.image.includes('placeholder')
    );

    const isRealItemPhoto = p.image && !isMerchantLogo && (
      p.image.includes('menueditor_item') ||
      p.image.includes('/items/') ||
      p.image.includes('compressed_webp') ||
      p.image.includes('huawei-food-cms') ||
      p.image.includes('food-cms') ||
      p.image.includes('cloudfront.net') ||
      p.image.includes('grab.com')
    );

    if (!isRealItemPhoto) {
      p.image = '';
    }
  });

  // Fallback logo & cover_photo from first product photo if merchant logo is generic
  if ((!result.logo || (!result.logo.includes('food-cms') && !result.logo.includes('huawei-food-cms'))) && result.products.length > 0) {
    const firstGrabImg = result.products.find(p => p.image && (p.image.includes('food-cms') || p.image.includes('huawei-food-cms')))?.image;
    if (firstGrabImg) {
      result.logo = firstGrabImg;
      result.cover_photo = firstGrabImg;
    }
  }

  if (!result.logo || (!result.logo.includes('food-cms') && !result.logo.includes('huawei-food-cms'))) {
    result.logo = '';
  }
  if (!result.cover_photo || (!result.cover_photo.includes('food-cms') && !result.cover_photo.includes('huawei-food-cms'))) {
    result.cover_photo = '';
  }

  return result;
}

// Smart Indonesian culinary photo resolver (Returns empty string if no real Grab photo)
function getSmartFoodPhoto(name) {
  return '';
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
