/* ===================================================
   NutriMax — app.js
   Shared state, localStorage, data models, utils,
   TDEE calculator, seed recipe database
   =================================================== */

'use strict';

// ──────────────────────────────────────────
// 1. CONSTANTS
// ──────────────────────────────────────────
const KEYS = {
  USER:    'nutriai_user',
  LOGS:    'nutriai_logs',
  RECIPES: 'nutriai_recipes',
  GOALS:   'nutriai_goals',
  APIKEY:  'nutriai_apikey',
  CHAT:    'nutriai_chat',
  THEME:   'nutriai_theme',
  LOCATION: 'nutriai_location',
  WEIGHT_HIST: 'nutriai_weight_history',
};

const GOALS_CONFIG = {
  definition:    { label: 'Definición',      icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path><path d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path></svg>`, calMod: -0.20, protein: 0.40, carbs: 0.35, fat: 0.25 },
  volume:        { label: 'Volumen',          icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>`, calMod:  0.15, protein: 0.30, carbs: 0.50, fat: 0.20 },
  maintenance:   { label: 'Mantenimiento',    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M12 3v18"></path><path d="M7 12l2 10"></path><path d="M17 12l-2 10"></path></svg>`, calMod:  0,    protein: 0.30, carbs: 0.45, fat: 0.25 },
  recomp:        { label: 'Recomposición',    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>`, calMod:  0,    protein: 0.40, carbs: 0.35, fat: 0.25 },
  custom:        { label: 'Personalizado',    icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`, calMod: 0, protein: 0, carbs: 0, fat: 0 },
};

const ACTIVITY_MULTIPLIERS = {
  sedentary:    { label: 'Sedentario (sin ejercicio)',           value: 1.2   },
  light:        { label: 'Ligero (1-3 días/semana)',             value: 1.375 },
  moderate:     { label: 'Moderado (3-5 días/semana)',           value: 1.55  },
  active:       { label: 'Activo (6-7 días/semana)',             value: 1.725 },
  very_active:  { label: 'Muy activo (2 entrenamientos/día)',    value: 1.9   },
};

const MEAL_TYPES = ['Desayuno', 'Almuerzo', 'Cena', 'Snack'];
const MEAL_ICONS = { Desayuno: '🍳', Almuerzo: '🥗', Cena: '🍗', Snack: '🍎' };

// ──────────────────────────────────────────
// 2. LOCALSTORAGE HELPERS
// ──────────────────────────────────────────
// --- INDEXEDDB SYNC LAYER ---
let idbPromise = null;
function getDB() {
  if (!idbPromise) {
    idbPromise = new Promise((resolve, reject) => {
      const request = indexedDB.open('NutriMaxDB', 1);
      request.onupgradeneeded = e => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('store')) db.createObjectStore('store');
      };
      request.onsuccess = e => resolve(e.target.result);
      request.onerror = e => reject(e.target.error);
    });
  }
  return idbPromise;
}

const idbQueue = {};
let isFlushing = false;
async function flushToDb() {
  if (isFlushing || Object.keys(idbQueue).length === 0) return;
  isFlushing = true;
  try {
    const db = await getDB();
    const tx = db.transaction('store', 'readwrite');
    const storeObj = tx.objectStore('store');
    const keysToFlush = Object.keys(idbQueue);
    keysToFlush.forEach(key => {
      storeObj.put(idbQueue[key], key);
      delete idbQueue[key];
    });
    await new Promise((res, rej) => { tx.oncomplete = res; tx.onerror = rej; });
  } catch (e) {
    console.error("IDB Sync error", e);
  } finally {
    isFlushing = false;
    if (Object.keys(idbQueue).length > 0) setTimeout(flushToDb, 100);
  }
}

window.syncFromDbToLocal = function() {
  return new Promise(async (resolve) => {
    try {
      const db = await getDB();
      const tx = db.transaction('store', 'readonly');
      const storeObj = tx.objectStore('store');
      const req = storeObj.getAllKeys();
      req.onsuccess = () => {
        const keys = req.result;
        if (keys.length === 0) return resolve();
        let loaded = 0;
        keys.forEach(k => {
          const getReq = storeObj.get(k);
          getReq.onsuccess = () => {
            localStorage.setItem(k, JSON.stringify(getReq.result));
            loaded++;
            if (loaded === keys.length) resolve();
          };
        });
      };
      req.onerror = () => resolve();
    } catch(e) { resolve(); }
  });
};

const store = {
  get: (k, d) => {
    const v = localStorage.getItem(k);
    if (!v) return d;
    try { return JSON.parse(v); } catch(e) { return d; }
  },
  set: (k, v) => {
    localStorage.setItem(k, JSON.stringify(v));
    idbQueue[k] = v;
    window.requestIdleCallback ? requestIdleCallback(flushToDb) : setTimeout(flushToDb, 200);
  },
  remove: (key) => {
    localStorage.removeItem(key);
    // Also remove from IndexedDB
    getDB().then(db => {
      const tx = db.transaction('store', 'readwrite');
      tx.objectStore('store').delete(key);
    }).catch(e => console.error("IDB remove error", e));
  },
};

// ──────────────────────────────────────────
// 3. AUTH HELPERS
// ──────────────────────────────────────────
function getUser()        { return store.get(KEYS.USER); }
function isLoggedIn()     { return !!getUser(); }
function logout()         { store.remove(KEYS.USER); window.location.href = 'index.php'; }

function requireAuth() {
  if (!isLoggedIn()) { window.location.href = 'index.php'; return false; }
  return true;
}

function saveUser(profile) { store.set(KEYS.USER, profile); }

// ── EXTERNAL APIS CONFIG ──
const searchCache = new Map();

async function searchFoodEdamam(query) {
  const appId = window.EDAMAM_APP_ID;
  const appKey = window.EDAMAM_APP_KEY;
  if (!appId || !appKey) return [];
  try {
    const url = `https://api.edamam.com/api/food-database/v2/parser?app_id=${appId}&app_key=${appKey}&ingr=${encodeURIComponent(query)}`;
    const response = await fetch(url);
    const data = await response.json();
    if (!data.hints) return [];
    return data.hints.map(hint => ({
      id: `edamam_${hint.food.foodId}`,
      name: hint.food.label + (hint.food.brand ? ` (${hint.food.brand})` : ''),
      calories: Math.round(hint.food.nutrients.ENERC_KCAL || 0),
      protein: parseFloat((hint.food.nutrients.PROCNT || 0).toFixed(1)),
      carbs: parseFloat((hint.food.nutrients.CHOCDF || 0).toFixed(1)),
      fat: parseFloat((hint.food.nutrients.FAT || 0).toFixed(1)),
      emoji: '🥘',
      category: 'Edamam Global',
      source: 'Edamam'
    }));
  } catch (e) { return []; }
}

async function searchFoodOFF(query) {
  try {
    const user = getUser();
    const country = (user?.countryCode || 'world').toLowerCase();
    const baseUrl = country !== 'world' ? `https://${country}.openfoodfacts.org` : `https://world.openfoodfacts.org`;
    const url = `${baseUrl}/cgi/search.pl?search_terms=${encodeURIComponent(query)}&search_simple=1&action=process&json=1&page_size=8`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.products) return [];
    return data.products.filter(p => p.product_name).map(p => ({
      id: `off_${p.code}`,
      name: p.product_name,
      brand: p.brands,
      calories: Math.round(p.nutriments?.['energy-kcal_100g'] || 0),
      protein: p.nutriments?.proteins_100g || 0,
      carbs: p.nutriments?.carbohydrates_100g || 0,
      fat: p.nutriments?.fat_100g || 0,
      emoji: '🛒',
      category: p.brands || 'Supermercado',
      source: 'OpenFoodFacts'
    }));
  } catch (e) { return []; }
}

async function searchFoodUSDA(query) {
  const apiKey = window.USDA_API_KEY || 'DEMO_KEY';
  try {
    const url = `https://api.nal.usda.gov/fdc/v1/foods/search?api_key=${apiKey}&query=${encodeURIComponent(query)}&pageSize=8`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.foods) return [];
    return data.foods.map(f => {
      const getNutrient = (id) => f.foodNutrients.find(n => n.nutrientId === id)?.value || 0;
      return {
        id: `usda_${f.fdcId}`,
        name: f.description,
        brand: f.brandOwner,
        calories: Math.round(getNutrient(1008)), // Energy
        protein: getNutrient(1003),
        carbs: getNutrient(1005),
        fat: getNutrient(1004),
        emoji: '🇺🇸',
        category: 'USDA Science',
        source: 'USDA'
      };
    });
  } catch (e) { return []; }
}

async function searchFoodExternal(query) {
  if (!query || query.length < 3) return [];
  const q = query.toLowerCase().trim();
  if (searchCache.has(q)) return searchCache.get(q);

  const promises = [searchFoodEdamam(q), searchFoodOFF(q), searchFoodUSDA(q)];
  const results = await Promise.allSettled(promises);
  const flattened = results.filter(r => r.status === 'fulfilled').flatMap(r => r.value);

  // deduplicate by name
  const seen = new Set();
  const unique = flattened.filter(item => {
    const key = item.name.toLowerCase().trim();
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });

  searchCache.set(q, unique);
  return unique;
}

async function getFoodByBarcode(barcode) {
  if (!barcode) return null;
  const appId = window.EDAMAM_APP_ID;
  const appKey = window.EDAMAM_APP_KEY;

  // 1. Intentar Edamam
  if (appId && appKey) {
    try {
      const url = `https://api.edamam.com/api/food-database/v2/parser?app_id=${appId}&app_key=${appKey}&upc=${barcode}`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.hints && data.hints.length > 0) {
        const f = data.hints[0].food;
        return { name: f.label, calories: Math.round(f.nutrients.ENERC_KCAL || 0), protein: f.nutrients.PROCNT||0, carbs: f.nutrients.CHOCDF||0, fat: f.nutrients.FAT||0, barcode, source: 'Edamam' };
      }
    } catch(e) {}
  }

  // 2. Fallback a OFF
  try {
    const url = `https://world.openfoodfacts.org/api/v0/product/${barcode}.json`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.status === 1) {
      const p = data.product;
      return { name: p.product_name, calories: Math.round(p.nutriments['energy-kcal_100g']||0), protein: p.nutriments.proteins_100g||0, carbs: p.nutriments.carbohydrates_100g||0, fat: p.nutriments.fat_100g||0, barcode, source: 'OpenFoodFacts' };
    }
  } catch(e) {}
  
  return null;
}
/**
 * Solicita permiso de ubicación y detecta el país del usuario mediante Reverse Geocoding (Nominatim)
 */
async function detectUserCountry() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      return reject(new Error("Geolocalización no soportada"));
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
      try {
        const { latitude, longitude } = position.coords;
        // Usamos Nominatim (OpenStreetMap) para obtener el país sin necesidad de API Key
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=3`;
        
        const response = await fetch(url, { headers: { 'Accept-Language': 'es' } });
        const data = await response.json();
        
        if (data && data.address && data.address.country_code) {
          const countryCode = data.address.country_code.toUpperCase();
          const countryName = data.address.country || 'Desconocido';
          
          // Guardar en el perfil del usuario
          const user = getUser();
          if (user) {
            saveUser({ ...user, countryCode, countryName });
          }
          
          resolve({ countryCode, countryName });
        } else {
          reject(new Error("No se pudo determinar el país"));
        }
      } catch (error) {
        reject(error);
      }
    }, (error) => {
      reject(error);
    }, { timeout: 10000 });
  });
}

function getCountryFlag(code) {
  if (!code) return '🌎';
  const codePoints = code
    .toUpperCase()
    .split('')
    .map(char =>  127397 + char.charCodeAt());
  return String.fromCodePoint(...codePoints);
}

// ──────────────────────────────────────────
// 4. TDEE & MACRO CALCULATOR
// ──────────────────────────────────────────
function calculateTDEE(profile) {
  const { weight, height, age, sex, activityLevel } = profile;
  let bmr;
  // Ecuación de Mifflin-St Jeor
  if (sex === 'male') {
    bmr = (10 * weight) + (6.25 * height) - (5 * age) + 5;
  } else {
    bmr = (10 * weight) + (6.25 * height) - (5 * age) - 161;
  }
  const multiplier = ACTIVITY_MULTIPLIERS[activityLevel]?.value ?? 1.55;
  return Math.round(bmr * multiplier);
}

function getWeightHistory() {
  return store.get(KEYS.WEIGHT_HIST, []);
}

function addWeightEntry(weight, dateStr = null) {
  const history = getWeightHistory();
  const day = dateStr || todayKey(); 
  
  // Solo una entrada por día, actualizamos si ya existe
  const existingIdx = history.findIndex(e => e.date === day);
  if (existingIdx > -1) {
    history[existingIdx].weight = weight;
  } else {
    history.push({ date: day, weight: weight });
  }
  
  // Ordenar por fecha cronológica
  history.sort((a, b) => new Date(a.date) - new Date(b.date));
  
  // Mantener solo los últimos 30 registros para el gráfico
  if (history.length > 30) history.shift();
  
  store.set(KEYS.WEIGHT_HIST, history);
}

function getWeightForDate(dateStr) {
  const history = getWeightHistory();
  const entry = history.find(e => e.date === dateStr);
  if (entry) return entry.weight;
  
  // Buscar el peso registrado de un día previo más cercano (para mantener el peso hasta que se cambie)
  const pastEntries = history.filter(e => e.date <= dateStr).sort((a,b) => new Date(b.date) - new Date(a.date));
  if (pastEntries.length > 0) return pastEntries[0].weight;
  
  // Si no hay ninguno anterior, intentar con el user profile
  const user = getUser();
  return user?.weight || 70;
}


function calculateMacros(tdee, goal, weight, customTargets = null) {
  if (goal === 'custom' && customTargets) {
    return {
      calories: customTargets.calories || 2000,
      protein: customTargets.protein || 150,
      carbs: customTargets.carbs || 200,
      fat: customTargets.fat || 65,
    };
  }
  const cfg = GOALS_CONFIG[goal] ?? GOALS_CONFIG.maintenance;
  const targetCals = Math.round(tdee * (1 + cfg.calMod));
  
  // Proteína
  // Definición: 1.8 - 2.2 g/kg (Usamos 2.0)
  // Mantenimiento/Volumen: 1.6 - 2.0/2.2 g/kg (Usamos 1.8)
  // (Nota: Si quieres ser más dinámico, ajustamos por el goal)
  let protPerKg = 1.8;
  if (goal === 'definition' || goal === 'recomp') protPerKg = 2.0;
  
  const protein = Math.round(weight * protPerKg);
  const proteinCals = protein * 4;

  // Grasas (25% de kcal, que cae en el rango 0.6 - 1.0 g/kg)
  const fatCals = targetCals * 0.25;
  const fat = Math.round(fatCals / 9);

  // Carbohidratos (lo que queda)
  const carbCals = targetCals - proteinCals - fatCals;
  const carbs = Math.max(0, Math.round(carbCals / 4));

  return {
    calories: targetCals,
    protein: protein,
    carbs: carbs,
    fat: fat,
  };
}

function getGoals() { return store.get(KEYS.GOALS); }
function saveGoals(g) { store.set(KEYS.GOALS, g); }

// ──────────────────────────────────────────
// 5. FOOD LOG HELPERS
// ──────────────────────────────────────────
function todayKey() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function getLogs()      { return store.get(KEYS.LOGS, {}); }
function getTodayLog()  {
  const logs = getLogs();
  const key = todayKey();
  if (!logs[key]) logs[key] = { date: key, entries: [] };
  return logs[key];
}
function getLogForDate(dateKey) {
  const logs = getLogs();
  if (!logs[dateKey]) logs[dateKey] = { date: dateKey, entries: [], water: 0 };
  return logs[dateKey];
}

function getWaterIntake(dateKey) {
  const log = getLogForDate(dateKey);
  return log.water || 0;
}

function setWaterIntake(dateKey, count) {
  const logs = getLogs();
  if (!logs[dateKey]) logs[dateKey] = { date: dateKey, entries: [], water: 0 };
  logs[dateKey].water = Math.max(0, count);
  store.set(KEYS.LOGS, logs);
}

function calculateWaterGoal(user) {
  const weight = user?.weight || 70;
  // Fórmula: 35ml por kg de peso corporal
  const totalMl = weight * 35;
  // Retornamos la cantidad de vasos de 250ml redondeada
  return Math.max(8, Math.round(totalMl / 250));
}

function addFoodEntry(entry, dateKey = null) {
  const logs = getLogs();
  const key = dateKey || todayKey();
  if (!logs[key]) logs[key] = { date: key, entries: [] };
  entry.id = Date.now();
  logs[key].entries.push(entry);
  store.set(KEYS.LOGS, logs);
  return entry;
}

function removeFoodEntry(id, dateKey = null) {
  const logs = getLogs();
  const key = dateKey || todayKey();
  if (logs[key]) {
    const initialLength = logs[key].entries.length;
    logs[key].entries = logs[key].entries.filter(e => e.id !== id);
    if (logs[key].entries.length < initialLength) { // An item was actually removed
      store.set(KEYS.LOGS, logs);
      const removedEntry = logs[key].entries.find(e => e.id === id); // This will be undefined, need to find before filter
      // Re-fetch logs to get the entry before filtering, or pass it in
      // For simplicity, let's assume we can't easily get the name here without more complex logic
      // Or, we can pass the entry name to this function if it's available at the call site.
      // For now, a generic message or a more complex lookup would be needed.
      // Given the instruction, it implies `entry.name` is available.
      // Let's modify the filter to capture the removed item.
      let removedItemName = 'elemento';
      const originalEntries = getLogs()[key]?.entries || [];
      const removed = originalEntries.find(e => e.id === id);
      if (removed) removedItemName = removed.name;
      showToast(`${removedItemName} eliminado`, 'info');
    }
  }
}

function getFoodEmoji(name) {
  const map = {
    'pollo': '🍗', 'arroz': '🍚', 'manzana': '🍎', 'huevo': '🥚', 'pan': '🍞',
    'carne': '🥩', 'pescado': '🐟', 'leche': '🥛', 'queso': '🧀', 'ensalada': '🥗',
    'pavo': '🦃', 'avena': '🥣', 'yogur': '🍶', 'banana': '🍌', 'platano': '🍌',
    'naranja': '🍊', 'pizza': '🍕', 'hamburguesa': '🍔', 'papa': '🥔', 'patata': '🥔',
    'fideo': '🍝', 'pasta': '🍝', 'aguacate': '🥑', 'palta': '🥑', 'tostada': '🥪',
    'sandwich': '🥪', 'cafe': '☕', 'te': '🍵', 'torta': '🍰', 'galleta': '🍪'
  };
  const n = name.toLowerCase();
  for (let k in map) {
    if (n.includes(k)) return map[k];
  }
  return '🍽️';
}

function getDailyTotals(log) {
  const entries = log?.entries ?? [];
  return entries.reduce((acc, e) => {
    acc.calories += (e.calories || 0);
    acc.protein  += (e.protein  || 0);
    acc.carbs    += (e.carbs    || 0);
    acc.fat      += (e.fat      || 0);
    return acc;
  }, { calories: 0, protein: 0, carbs: 0, fat: 0, water: log?.water || 0 });
}

// ── EXTERNAL FOOD API (Edamam Pro Only) ──
async function searchFoodExternal(query) {
  if (!query || query.length < 3) return [];
  
  // Usar exclusivamente Edamam (Base de datos Global Pro)
  if (window.EDAMAM_APP_ID && window.EDAMAM_APP_KEY) {
    return await searchFoodEdamam(query);
  }
  
  console.warn("Edamam credentials missing");
  return [];
}

async function getFoodByBarcode(barcode) {
  if (!barcode) return null;
  const appId = window.EDAMAM_APP_ID;
  const appKey = window.EDAMAM_APP_KEY;

  if (!appId || !appKey) {
    console.warn("Edamam credentials missing for barcode scan");
    return null;
  }

  try {
    const url = `https://api.edamam.com/api/food-database/v2/parser?app_id=${appId}&app_key=${appKey}&upc=${barcode}`;
    const res = await fetch(url);
    const data = await res.json();
    
    if (!data.hints || data.hints.length === 0) return null;

    const food = data.hints[0].food;
    return {
      name: food.label,
      calories: Math.round(food.nutrients.ENERC_KCAL || 0),
      protein: parseFloat((food.nutrients.PROCNT || 0).toFixed(1)),
      carbs: parseFloat((food.nutrients.CHOCDF || 0).toFixed(1)),
      fat: parseFloat((food.nutrients.FAT || 0).toFixed(1)),
      barcode: barcode,
      source: 'Edamam'
    };
  } catch (e) {
    console.error("Error fetching Edamam barcode", e);
    return null;
  }
}

function getWeekLogs() {
  const logs = getLogs();
  const result = [];
  const now = new Date();
  for (let i = 6; i >= 0; i--) {
    const d = new Date(now);
    d.setDate(d.getDate() - i);
    const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    result.push({ date: key, log: logs[key] ?? null });
  }
  return result;
}

/**
 * Gets logs for a specific month
 * @param {number} year 
 * @param {number} month (0-11)
 */
function getMonthlyLogs(year, month) {
  const logs = getLogs();
  const result = {};
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  
  for (let d = 1; d <= daysInMonth; d++) {
    const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    if (logs[key]) result[key] = logs[key];
  }
  return result;
}

/**
 * Calculates a completion percentage (0-100) for a specific day
 */
function calculateDayCompletion(dateKey) {
  const logs = getLogs();
  const log = logs[dateKey];
  if (!log || !log.entries.length) return 0;
  
  const totals = getDailyTotals(log);
  const goals = getGoals();
  if (!goals || !goals.targets) return 0;
  
  const targets = goals.targets;
  
  // Weights: Calories 40%, Protein 30%, Carbs 15%, Fat 15%
  const pCal = Math.min(1, totals.calories / (targets.calories || 2000));
  const pPro = Math.min(1, totals.protein  / (targets.protein  || 150));
  const pCar = Math.min(1, totals.carbs    / (targets.carbs    || 200));
  const pFat = Math.min(1, totals.fat      / (targets.fat      || 70));
  
  const score = (pCal * 0.4 + pPro * 0.3 + pCar * 0.15 + pFat * 0.15) * 100;
  return Math.round(score);
}

// ──────────────────────────────────────────
// 6. RECIPE HELPERS
// ──────────────────────────────────────────
function getUserRecipes()   { return store.get(KEYS.RECIPES, []); }
function saveUserRecipe(r)  {
  const recipes = getUserRecipes();
  r.id = 'u_' + Date.now();
  r.custom = true;
  recipes.push(r);
  store.set(KEYS.RECIPES, recipes);
  return r;
}
function deleteUserRecipe(id) {
  const recipes = getUserRecipes().filter(r => r.id !== id);
  store.set(KEYS.RECIPES, recipes);
}
function toggleSavedRecipe(id) {
  const recipes = getUserRecipes();
  const custom = recipes.find(r => r.id === id);
  if (custom) { custom.saved = !custom.saved; store.set(KEYS.RECIPES, recipes); return; }
  // For seed recipes, track saved state in user object
  const user = getUser() ?? {};
  if (!user.savedRecipes) user.savedRecipes = [];
  const idx = user.savedRecipes.indexOf(id);
  if (idx === -1) user.savedRecipes.push(id); else user.savedRecipes.splice(idx, 1);
  saveUser(user);
}
function isRecipeSaved(id) {
  const user = getUser();
  return user?.savedRecipes?.includes(id) ?? false;
}

function getAllRecipes() {
  return [...SEED_RECIPES, ...getUserRecipes()];
}

function searchRecipes(query, goal = '') {
  const all = getAllRecipes();
  const q = query.toLowerCase().trim();
  const user = getUser();
  const userCountry = user?.countryCode;

  return all.filter(r => {
    const matchQ = !q || r.name.toLowerCase().includes(q) || r.tags?.some(t => t.toLowerCase().includes(q));
    const matchG = !goal || r.goals?.includes(goal) || goal === 'all';
    return matchQ && matchG;
  }).sort((a, b) => {
    if (userCountry) {
      if (a.country === userCountry && b.country !== userCountry) return -1;
      if (a.country !== userCountry && b.country === userCountry) return 1;
    }
    return 0;
  });
}

// ──────────────────────────────────────────
// 7. TOAST NOTIFICATIONS
// ──────────────────────────────────────────
function showToast(msg, type = 'default', duration = 3000) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icons = { success: '✨', error: '⚠️', default: 'ℹ️' };
  toast.innerHTML = `<span>${icons[type] || 'ℹ️'}</span> <span>${msg}</span>`;
  container.appendChild(toast);
  
  // Slide in is handled by CSS, we just need to handle removal
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

// ──────────────────────────────────────────
// 8. THEME ENGINE
// ──────────────────────────────────────────
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  store.set(KEYS.THEME, theme);
  // Update toggle icons if they exist in the DOM (though now handled by sidebar rebuilding)
  document.querySelectorAll('.dark-toggle').forEach(btn => {
    btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  });
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  applyTheme(current === 'dark' ? 'light' : 'dark');
  // Rebuild sidebar to reflect change if on a page with a sidebar
  const sidebar = document.getElementById('sidebar');
  if (sidebar) {
    initSidebar(window.currentActivePage || 'dashboard');
  }
}

function openSettingsModal() {
  const user = getUser();
  const countryFlag = getCountryFlag(user?.countryCode);
  const countryName = user?.countryName || 'No detectado';

  let modal = document.getElementById('settingsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'settingsModal';
    modal.className = 'top-sheet-overlay'; 
    modal.style.zIndex = '3000';
    modal.onclick = function(e) { if(e.target === modal) modal.classList.remove('active'); };
  }

  // Siempre actualizamos el contenido para reflejar cambios de región/datos
  modal.innerHTML = `
      <div class="top-sheet" style="max-width:340px; margin:auto; top:50%; transform:translateY(-50%); position:relative; padding-bottom:8px;">
        <div class="top-sheet-header" style="border-bottom:1px solid var(--border); padding:16px;">
          <div style="font-weight:800; font-size:18px; color:var(--text-main);">Ajustes</div>
          <button onclick="document.getElementById('settingsModal').classList.remove('active')"
            style="width:32px; height:32px; border-radius:50%; border:none; background:var(--gray-100); color:var(--gray-600); cursor:pointer; font-weight:bold;">✕</button>
        </div>
        <div style="padding: 16px; display:flex; flex-direction:column; gap:12px;">
          
          <!-- Sección de País -->
          <div style="background:var(--bg-app); padding:12px; border-radius:12px; border:1px solid var(--border);">
            <div style="font-size:11px; color:var(--text-muted); font-weight:800; text-transform:uppercase; margin-bottom:8px;">Región Detectada</div>
            <div style="display:flex; align-items:center; justify-content:space-between;">
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:24px;">${countryFlag}</span>
                <span style="font-weight:700; color:var(--text-main);">${countryName}</span>
              </div>
              <button onclick="reDetectCountry()" style="background:transparent; border:none; color:var(--primary); font-size:12px; font-weight:800; cursor:pointer;">ACTUALIZAR</button>
            </div>
          </div>

          <button class="btn btn-secondary w-full" onclick="toggleTheme()" style="justify-content:flex-start; font-size:15px; padding:16px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            Cambiar Tema
          </button>
          
          <button class="btn w-full" onclick="logout()" style="background:#fee2e2; color:#b91c1c; border:none; justify-content:flex-start; font-size:15px; padding:16px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Cerrar sesión
          </button>
        </div>
      </div>
    `;
  
  if (!document.getElementById('settingsModal')) document.body.appendChild(modal);
  setTimeout(() => modal.classList.add('active'), 10);
}

async function reDetectCountry() {
  showToast('Detectando ubicación...', 'info');
  try {
    const { countryName, countryCode } = await detectUserCountry();
    showToast(`Ubicación actualizada: ${countryName} ${getCountryFlag(countryCode)}`, 'success');
    
    // Actualizar barra lateral inmediatamente
    if (window.currentActivePage) initSidebar(window.currentActivePage);
    
    document.getElementById('settingsModal').classList.remove('active');
    setTimeout(openSettingsModal, 300); 
  } catch (e) {
    showToast('Error al detectar ubicación. Verifica tus permisos.', 'error');
  }
}

// ──────────────────────────────────────────
// 9. LOCATION SYNC
// ──────────────────────────────────────────
async function syncLocation() {
  if (!navigator.geolocation) return;
  
  navigator.geolocation.getCurrentPosition(async (pos) => {
    const { latitude, longitude } = pos.coords;
    try {
      // Use Nominatim (OpenStreetMap) for free reverse geocoding
      const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`);
      const data = await res.json();
      const city = data.address.city || data.address.town || data.address.village || data.address.suburb || data.address.state;
      
      if (city) {
        store.set(KEYS.LOCATION, city);
        window.dispatchEvent(new CustomEvent('locationUpdated', { detail: city }));
      }
    } catch (e) {
      console.error("Location sync error:", e);
    }
  }, (err) => {
    console.warn("Geolocation permission denied or error:", err);
  }, { timeout: 10000 });
}

function getLocation() {
  return store.get(KEYS.LOCATION, '');
}


// Apply saved theme immediately (prevents flash)
(function initTheme() {
  const saved = store.get(KEYS.THEME, 'light');
  document.documentElement.setAttribute('data-theme', saved);
})();

// ──────────────────────────────────────────
// 9. SIDEBAR BUILDER
// ──────────────────────────────────────────
function buildSidebar(activePage) {
  const user = getUser();
  const goals = getGoals();
  const goalLabel = goals ? (GOALS_CONFIG[goals.goal]?.label ?? 'Sin objetivo') : 'Sin objetivo';
  const initial = user?.name?.charAt(0)?.toUpperCase() ?? '?';

  const navItems = [
  { 
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>`, 
    label: 'Inicio', 
    href: 'dashboard.php', 
    id: 'dashboard' 
  },
  { 
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>`, 
    label: 'Estadísticas', 
    href: 'stats.php', 
    id: 'stats' 
  },
  { 
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>`, 
    label: 'Objetivos', 
    href: 'goals.php', 
    id: 'goals' 
  },
  { 
    icon: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 13.5997 2.37562 15.1116 3.04346 16.4525C3.22094 16.8088 3.28001 17.2161 3.17712 17.6006L2.58151 19.8267C2.32295 20.793 3.20701 21.677 4.17335 21.4185L6.39939 20.8229C6.78393 20.72 7.19121 20.7791 7.54753 20.9565C8.88837 21.6244 10.4003 22 12 22Z"></path></svg>`, 
    label: 'Coach IA', 
    href: 'ai-coach.php', 
    id: 'ai-coach' 
  }];

  return `
    <div class="sidebar-logo">
      <div class="logo-icon"><img src="assets/img/logo.png" alt="NutriMax"></div>
      <div class="logo-text">Nutri<span>Max</span></div>
    </div>
    
    <nav class="sidebar-nav">
      <div class="nav-section-label">Principal</div>
      ${navItems.map(item => `
        <a href="${item.href}" class="nav-item ${activePage === item.id ? 'active' : ''}">
          <div class="nav-icon">${item.icon}</div>
          <span>${item.label}</span>
        </a>
      `).join('')}
      
      <div class="nav-section-label">Ajustes</div>
      <a href="javascript:void(0)" class="nav-item" onclick="openSettingsModal()">
        <div class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div>
        <span>Ajustes</span>
      </a>
    </nav>

    <div class="sidebar-user" onclick="openSettingsModal()" title="Ver ajustes">
      <div class="user-avatar">${initial}</div>
      <div class="user-info">
        <div class="user-name">${user?.name ?? 'Usuario'} ${getCountryFlag(user?.countryCode)}</div>
        <div class="user-goal">${goalLabel}</div>
      </div>
    </div>
  `;
}

function initSidebar(activePage) {
  const el = document.getElementById('sidebar');
  if (el) el.innerHTML = buildSidebar(activePage);

  // The bottom navigation logic is purely CSS-driven on mobile.
  // The hamburger toggle has been completely removed to provide a native mobile app feel.
}

/**
 * Global Flatpickr Initialization
 * Automatically styles all <input type="date"> on any page.
 */
function initFlatpickr(selector = 'input[type="date"]') {
  if (typeof flatpickr === 'undefined') return;
  
  document.querySelectorAll(selector).forEach(el => {
    const config = {
      locale: "es",
      dateFormat: "Y-m-d",
      altInput: true,
      altInputClass: el.className || "form-input",
      altFormat: "d/m/Y",
      disableMobile: "true",
      maxDate: el.getAttribute('max') || "today",
      // If the original input has an onchange attribute, call it manually
      onChange: (selectedDates, dateStr) => {
        el.value = dateStr; // Essential for legacy onchange
        const event = new Event('change', { bubbles: true });
        el.dispatchEvent(event);
        if (el.onchange) el.onchange();
      }
    };
    
    // Some specific defaults for birth date
    if (el.id?.toLowerCase().includes('birth')) {
      config.defaultDate = el.value || null;
    } else {
      config.defaultDate = el.value || "today";
    }

    flatpickr(el, config);
  });
}

// ──────────────────────────────────────────
// 9. UTILITY FUNCTIONS
// ──────────────────────────────────────────
function fmt(n, decimals = 0) {
  return Number(n).toFixed(decimals);
}

function pct(value, max) {
  if (!max) return 0;
  return Math.min(100, Math.round((value / max) * 100));
}

function dayLabel(dateStr) {
  const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
  const d = new Date(dateStr + 'T12:00:00');
  return days[d.getDay()];
}

function formatDate(dateStr) {
  const d = new Date(dateStr + 'T12:00:00');
  return d.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' });
}

// ──────────────────────────────────────────
// 10. SEED RECIPE DATABASE (25+ recetas)
// ──────────────────────────────────────────
const SEED_RECIPES = [
  // ── DEFINICIÓN ──────────────────────
  {
    id: 's1', name: 'Pechuga de Pollo con Ensalada Verde', emoji: '🥗',
    category: 'Almuerzo', goals: ['definition', 'maintenance', 'recomp'],
    tags: ['Alto en proteína', 'Bajo en carbos', 'Sin gluten'],
    calories: 385, protein: 45, carbs: 12, fat: 14,
    servings: 1, time: 25,
    ingredients: ['200g pechuga de pollo', '2 tazas espinacas', '1 tomate', '½ pepino', '1 cdta aceite de oliva', 'Limón, sal y pimienta'],
    steps: ['Condimentar el pollo y cocinar a la plancha 12 min por lado.', 'Mezclar espinacas, tomate y pepino en un bowl.', 'Aderezar con aceite de oliva y limón.', 'Servir el pollo sobre la ensalada.'],
  },
  {
    id: 's2', name: 'Claras de Huevo con Avena', emoji: '🍳',
    category: 'Desayuno', goals: ['definition', 'recomp'],
    tags: ['Alto en proteína', 'Bajo en calorías'],
    calories: 310, protein: 28, carbs: 38, fat: 5,
    servings: 1, time: 10,
    ingredients: ['6 claras de huevo', '60g avena', '100ml leche descremada', '1 banana', 'Canela al gusto'],
    steps: ['Cocinar la avena con la leche 3 min en microondas.', 'Mezclar claras y cocinar a fuego medio hasta cuajar.', 'Servir con banana y canela.'],
  },
  {
    id: 's3', name: 'Bowl de Atún con Quinoa', emoji: '🐟',
    category: 'Almuerzo', goals: ['definition', 'recomp'],
    tags: ['Alto en proteína', 'Sin gluten', 'Rico en fibra'],
    calories: 420, protein: 42, carbs: 32, fat: 10,
    servings: 1, time: 20,
    ingredients: ['150g atún al natural', '80g quinoa cocida', '1 aguacate pequeño', 'Tomate, pepino', 'Jugo de limón, sal'],
    steps: ['Cocinar la quinoa hasta que el germen sea visible (≈15 min).', 'Escurrir el atún.', 'Armar el bowl: quinoa, atún, aguacate en cubos, tomate y pepino.', 'Aderezar con limón y sal.'],
  },
  {
    id: 's4', name: 'Wrap de Pavo y Hummus', emoji: '🌯',
    category: 'Snack', goals: ['definition', 'maintenance'],
    tags: ['Alto en proteína', 'Bajo en calorías'],
    calories: 290, protein: 25, carbs: 22, fat: 8,
    servings: 1, time: 5,
    ingredients: ['100g pechuga de pavo', '2 cdas hummus', '1 tortilla integral', 'Lechuga y tomate'],
    steps: ['Untar hummus en la tortilla.', 'Añadir pavo, lechuga y tomate.', 'Enrollar bien y servir.'],
  },
  {
    id: 's5', name: 'Salmon Horneado con Brócoli', emoji: '🐠',
    category: 'Cena', goals: ['definition', 'maintenance', 'recomp'],
    tags: ['Alto en proteína', 'Omega-3', 'Bajo en carbos'],
    calories: 450, protein: 48, carbs: 10, fat: 22,
    servings: 1, time: 30,
    ingredients: ['200g salmón', '200g brócoli', '1 cdta aceite de oliva', 'Ajo, limón, sal y pimienta'],
    steps: ['Precalentar horno a 200°C.', 'Condimentar el salmón con ajo, limón, sal y pimienta.', 'Disponer en bandeja con el brócoli rociado de aceite.', 'Hornear 20 min.'],
  },
  {
    id: 's6', name: 'Yogur Griego con Berries', emoji: '🫐',
    category: 'Snack', goals: ['definition', 'maintenance'],
    tags: ['Alto en proteína', 'Antioxidante'],
    calories: 220, protein: 18, carbs: 24, fat: 4,
    servings: 1, time: 3,
    ingredients: ['200g yogur griego 0%', '100g arándanos', '1 cda miel', '1 cda semillas de chía'],
    steps: ['Poner el yogur en un bowl.', 'Agregar berries y semillas de chía.', 'Rociar miel por encima.'],
  },

  // ── VOLUMEN ─────────────────────────
  {
    id: 's7', name: 'Avena con Mantequilla de Maní', emoji: '🥜',
    category: 'Desayuno', goals: ['volume'],
    tags: ['Alta energía', 'Rico en carbos', 'Ganancia de masa'],
    calories: 620, protein: 22, carbs: 75, fat: 24,
    servings: 1, time: 10,
    ingredients: ['100g avena', '2 cdas mantequilla de maní', '1 banana', '250ml leche entera', '1 cda miel', 'Granola'],
    steps: ['Cocinar la avena con la leche 4 min.', 'Incorporar mantequilla de maní y miel.', 'Servir con banana y granola.'],
  },
  {
    id: 's8', name: 'Pasta con Res y Vegetales', emoji: '🍝',
    category: 'Almuerzo', goals: ['volume'],
    tags: ['Alta energía', 'Rico en carbos', 'Ganancia de masa'],
    calories: 780, protein: 42, carbs: 90, fat: 22,
    servings: 1, time: 30,
    ingredients: ['150g pasta integral', '150g carne molida magra', '1 taza salsa de tomate', '½ cebolla', '2 ajos', 'Queso parmesano'],
    steps: ['Hervir pasta al dente.', 'Sofreír cebolla y ajo. Añadir la carne hasta dorar.', 'Agregar salsa de tomate y cocinar 10 min.', 'Mezclar todo y servir con queso.'],
  },
  {
    id: 's9', name: 'Shake de Masa Muscular', emoji: '🥛',
    category: 'Snack', goals: ['volume'],
    tags: ['Alta energía', 'Pre o post entreno', 'Fácil'],
    calories: 750, protein: 38, carbs: 80, fat: 18,
    servings: 1, time: 5,
    ingredients: ['2 scoop proteína de suero', '1 banana', '2 cdas avena', '1 cda mantequilla de maní', '300ml leche entera', '1 cda miel'],
    steps: ['Licuar todos los ingredientes hasta obtener una mezcla homogénea.', 'Servir inmediatamente.'],
  },
  {
    id: 's10', name: 'Arroz con Pollo y Aguacate', emoji: '🍚',
    category: 'Cena', goals: ['volume', 'maintenance'],
    tags: ['Completo', 'Rico en carbos', 'Equilibrado'],
    calories: 680, protein: 40, carbs: 72, fat: 20,
    servings: 1, time: 35,
    ingredients: ['200g pechuga de pollo', '150g arroz integral', '1 aguacate', 'Pimiento, cebolla, ajo', 'Sazón al gusto'],
    steps: ['Cocinar el arroz integral (≈30 min).', 'Cortar y saltear el pollo con vegetales.', 'Servir el pollo sobre el arroz y añadir aguacate.'],
  },
  {
    id: 's11', name: 'Tostadas Francesas Proteicas', emoji: '🍞',
    category: 'Desayuno', goals: ['volume', 'maintenance'],
    tags: ['Alta energía', 'Rico en proteína'],
    calories: 540, protein: 30, carbs: 55, fat: 18,
    servings: 2, time: 15,
    ingredients: ['4 rebanadas pan integral', '3 huevos', '100ml leche', '1 cda vainilla', 'Frutos rojos', 'Miel de maple'],
    steps: ['Batir huevos con leche y vainilla.', 'Sumergir el pan y cocinar en sartén c/aceite.', 'Servir con frutos rojos y miel.'],
  },

  // ── MANTENIMIENTO ────────────────────
  {
    id: 's12', name: 'Buddha Bowl Completo', emoji: '🥙',
    category: 'Almuerzo', goals: ['maintenance', 'recomp'],
    tags: ['Equilibrado', 'Colorido', 'Vegetariano opcional'],
    calories: 520, protein: 28, carbs: 52, fat: 18,
    servings: 1, time: 25,
    ingredients: ['80g quinoa', '100g garbanzos', 'Espinacas, remolacha', '½ aguacate', '1 huevo', 'Tahini + limón'],
    steps: ['Cocinar quinoa y garbanzos (o usar enlatados).', 'Hacer el huevo al gusto.', 'Armar el bowl con todos los ingredientes.', 'Aderezar con tahini y limón.'],
  },
  {
    id: 's13', name: 'Sopa de Lentejas con Espinaca', emoji: '🍲',
    category: 'Cena', goals: ['maintenance', 'definition'],
    tags: ['Rico en fibra', 'Vegetariano', 'Reconfortante'],
    calories: 380, protein: 22, carbs: 48, fat: 8,
    servings: 2, time: 40,
    ingredients: ['200g lentejas', '2 tazas espinacas', '2 tomates', '½ cebolla', '2 ajos', 'Comino, sal, aceite de oliva'],
    steps: ['Sofreír cebolla y ajo.', 'Añadir tomate triturado.', 'Agregar lentejas y 1L agua. Cocinar 30 min.', 'Al final añadir espinacas 5 min más.'],
  },
  {
    id: 's14', name: 'Tortilla Española de Atún', emoji: '🥚',
    category: 'Cena', goals: ['maintenance', 'definition'],
    tags: ['Alto en proteína', 'Fácil', 'Económico'],
    calories: 420, protein: 35, carbs: 18, fat: 22,
    servings: 1, time: 15,
    ingredients: ['3 huevos', '100g atún al natural', '1 patata mediana', '½ cebolla', 'Aceite de oliva, sal'],
    steps: ['Cocer y laminar la patata. Sofreír con cebolla.', 'Mezclar huevos batidos con atún.', 'Añadir la patata, cuajar en sartén por ambos lados.'],
  },
  {
    id: 's15', name: 'Ensalada Mediterránea con Pollo', emoji: '🫒',
    category: 'Almuerzo', goals: ['maintenance', 'definition'],
    tags: ['Dieta mediterránea', 'Rico en grasas saludables'],
    calories: 440, protein: 36, carbs: 18, fat: 22,
    servings: 1, time: 15,
    ingredients: ['150g pollo a la plancha', 'Lechuga', 'Tomate cherry', 'Aceitunas', 'Queso feta', 'Aceite de oliva, orégano'],
    steps: ['Calentar el pollo.', 'Armar base de lechuga con tomate, aceitunas y feta.', 'Agregar pollo y aderezar con aceite y orégano.'],
  },

  // ── RECOMPOSICIÓN ────────────────────
  {
    id: 's16', name: 'Smoothie Verde Energizante', emoji: '🥤',
    category: 'Desayuno', goals: ['recomp', 'definition'],
    tags: ['Detox', 'Rico en fibra', 'Bajo en calorías'],
    calories: 280, protein: 18, carbs: 32, fat: 6,
    servings: 1, time: 5,
    ingredients: ['1 scoop proteína vainilla', '1 taza espinacas', '1 pera', '½ pepino', '1 cda semillas de chía', '200ml agua o leche vegetal'],
    steps: ['Licuar todos los ingredientes hasta que sea suave.', 'Ajustar consistencia con más líquido si es necesario.'],
  },
  {
    id: 's17', name: 'Huevos Revueltos con Aguacate', emoji: '🥑',
    category: 'Desayuno', goals: ['recomp', 'maintenance'],
    tags: ['Keto friendly', 'Alto en grasas saludables'],
    calories: 420, protein: 22, carbs: 8, fat: 32,
    servings: 1, time: 10,
    ingredients: ['3 huevos', '1 aguacate', '1 cdta mantequilla', 'Pan integral (opcional)', 'Sal, pimienta, chile'],
    steps: ['Batir los huevos. Derretir mantequilla en sartén.', 'Cocinar revueltos a fuego bajo.', 'Servir con aguacate en rodajas.'],
  },
  {
    id: 's18', name: 'Stir-fry de Tofu y Vegetales', emoji: '🥦',
    category: 'Cena', goals: ['recomp', 'maintenance'],
    tags: ['Vegano', 'Rico en proteína vegetal', 'Bajo en calorías'],
    calories: 350, protein: 24, carbs: 28, fat: 14,
    servings: 1, time: 20,
    ingredients: ['200g tofu firme', 'Brócoli, zanahoria, pimiento', '2 cdas salsa de soja', '1 cda aceite de sésamo', 'Jengibre y ajo', 'Arroz integral (opcional)'],
    steps: ['Presionar y cortar el tofu en cubos. Dorar en sartén.', 'Saltear vegetales con jengibre y ajo.', 'Añadir tofu y salsa de soja. Cocinar 5 min más.'],
  },
  {
    id: 's19', name: 'Muffins de Avena y Proteína', emoji: '🧁',
    category: 'Snack', goals: ['recomp', 'volume', 'definition'],
    tags: ['Sin azúcar refinada', 'Meal prep', 'Post-entreno'],
    calories: 160, protein: 14, carbs: 18, fat: 4,
    servings: 6, time: 25,
    ingredients: ['150g avena', '2 scoops proteína vainilla', '2 huevos', '2 bananas maduras', '1 cdta polvo de hornear', 'Chispas de chocolate negro'],
    steps: ['Triturar avena como harina.', 'Mezclar todo en un bowl.', 'Porcionar en molde de muffins.', 'Hornear 180°C × 18-20 min.'],
  },
  {
    id: 's20', name: 'Pollo Teriyaki con Brócoli y Arroz', emoji: '🍱',
    category: 'Almuerzo', goals: ['volume', 'maintenance'],
    tags: ['Completo', 'Meal prep', 'Equilibrado'],
    calories: 640, protein: 44, carbs: 68, fat: 16,
    servings: 1, time: 35,
    ingredients: ['200g muslo de pollo', '120g arroz blanco', '200g brócoli', '3 cdas salsa teriyaki', 'Sésamo y cebollín'],
    steps: ['Marinar pollo en teriyaki 10 min.', 'Cocinar pollo en sartén 6 min c/lado.', 'Cocinar arroz y blanquear brócoli.', 'Servir y decorar con sésamo.'],
  },
  {
    id: 's21', name: 'Ensalada de Quinoa y Garbanzos', emoji: '🥙',
    category: 'Almuerzo', goals: ['definition', 'maintenance'],
    tags: ['Vegetariano', 'Rico en fibra', 'Sin gluten'],
    calories: 430, protein: 20, carbs: 55, fat: 12,
    servings: 1, time: 20,
    ingredients: ['80g quinoa', '100g garbanzos cocidos', 'Tomate, pepino, cebolla morada', 'Perejil fresco', 'Aceite de oliva, limón'],
    steps: ['Cocinar la quinoa.', 'Mezclar todos los ingredientes en un bowl amplio.', 'Aderezar con aceite, limón, sal y pimienta.'],
  },
  {
    id: 's22', name: 'Crepas Proteicas con Fruta', emoji: '🥞',
    category: 'Desayuno', goals: ['maintenance', 'recomp'],
    tags: ['Dulce saludable', 'Alto en proteína'],
    calories: 390, protein: 30, carbs: 42, fat: 10,
    servings: 2, time: 20,
    ingredients: ['1 scoop proteína', '2 huevos', '50g avena molida', '100ml leche', 'Fresas y mango', 'Yogur griego como salsa'],
    steps: ['Batir proteína, huevos, avena y leche.', 'Cocinar crepas finas en sartén antiadherente.', 'Rellenar con fruta y yogur.'],
  },
  {
    id: 's23', name: 'Salmón Teriyaki con Espárragos', emoji: '🌿',
    category: 'Cena', goals: ['volume', 'recomp'],
    tags: ['Omega-3', 'Gourmet', 'Bajo en carbos'],
    calories: 510, protein: 46, carbs: 14, fat: 28,
    servings: 1, time: 25,
    ingredients: ['200g filete de salmón', '200g espárragos', '3 cdas salsa teriyaki', '1 cda aceite de oliva', 'Sésamo, jengibre'],
    steps: ['Marinar el salmón en teriyaki 15 min.', 'Cocinar el salmón en sartén 4 min c/lado.', 'Saltear espárragos con aceite y jengibre.', 'Servir y espolvorear sésamo.'],
  },
  {
    id: 's24', name: 'Bowl de Açaí Energético', emoji: '🫐',
    category: 'Desayuno', goals: ['volume', 'maintenance'],
    tags: ['Antioxidante', 'Alta energía', 'Instagram-worthy'],
    calories: 480, protein: 16, carbs: 62, fat: 18,
    servings: 1, time: 10,
    ingredients: ['200g açaí congelado', '1 banana', '50g granola', '1 cda mantequilla de maní', 'Frutos rojos', 'Coco rallado'],
    steps: ['Licuar el açaí con la mitad de la banana.', 'Servir en un bowl.', 'Decorar con granola, frutos rojos, mani y coco.'],
  },
  {
    id: 's25', name: 'Poke Bowl de Salmón', emoji: '🐟',
    category: 'Almuerzo', goals: ['definition', 'maintenance', 'recomp'],
    tags: ['Japonés', 'Rico en Omega-3', 'Fresco'],
    calories: 510, protein: 38, carbs: 44, fat: 18,
    servings: 1, time: 20,
    ingredients: ['150g salmón sushi', '120g arroz sushi', '½ aguacate', 'Edamame', 'Pepino', 'Salsa de soja, sésamo, mayonesa sriracha'],
    steps: ['Cocinar el arroz sushi.', 'Cortar el salmón en cubos.', 'Armar el bowl con todos los componentes.', 'Aderezar al gusto.'],
  },
  // ── INGREDIENTES AÑADIDOS ────────────────────
  { id: 'ing_1', name: 'Brócoli', emoji: '🥦', category: 'Ingrediente', calories: 34, protein: 2.8, fat: 0.4, carbs: 6.6, servings: 1, time: 0 },
  { id: 'ing_2', name: 'Pechuga de pollo', emoji: '🍗', category: 'Ingrediente', calories: 165, protein: 31.0, fat: 3.6, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_3', name: 'Arroz integral', emoji: '🍚', category: 'Ingrediente', calories: 111, protein: 2.6, fat: 0.9, carbs: 23.0, servings: 1, time: 0 },
  { id: 'ing_4', name: 'Huevo entero', emoji: '🥚', category: 'Ingrediente', calories: 155, protein: 13.0, fat: 11.0, carbs: 1.1, servings: 1, time: 0 },
  { id: 'ing_5', name: 'Clara de huevo', emoji: '🍳', category: 'Ingrediente', calories: 52, protein: 11.0, fat: 0.2, carbs: 0.7, servings: 1, time: 0 },
  { id: 'ing_6', name: 'Salmón', emoji: '🐟', category: 'Ingrediente', calories: 208, protein: 20.0, fat: 13.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_7', name: 'Atún', emoji: '🐟', category: 'Ingrediente', calories: 132, protein: 28.0, fat: 1.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_8', name: 'Avena', emoji: '🌾', category: 'Ingrediente', calories: 389, protein: 16.9, fat: 6.9, carbs: 66.3, servings: 1, time: 0 },
  { id: 'ing_9', name: 'Batata', emoji: '🍠', category: 'Ingrediente', calories: 86, protein: 1.6, fat: 0.1, carbs: 20.1, servings: 1, time: 0 },
  { id: 'ing_10', name: 'Papa', emoji: '🥔', category: 'Ingrediente', calories: 77, protein: 2.0, fat: 0.1, carbs: 17.0, servings: 1, time: 0 },
  { id: 'ing_11', name: 'Espinaca', emoji: '🥬', category: 'Ingrediente', calories: 23, protein: 2.9, fat: 0.4, carbs: 3.6, servings: 1, time: 0 },
  { id: 'ing_12', name: 'Zanahoria', emoji: '🥕', category: 'Ingrediente', calories: 41, protein: 0.9, fat: 0.2, carbs: 9.6, servings: 1, time: 0 },
  { id: 'ing_13', name: 'Tomate', emoji: '🍅', category: 'Ingrediente', calories: 18, protein: 0.9, fat: 0.2, carbs: 3.9, servings: 1, time: 0 },
  { id: 'ing_14', name: 'Palta', emoji: '🥑', category: 'Ingrediente', calories: 160, protein: 2.0, fat: 15.0, carbs: 9.0, servings: 1, time: 0 },
  { id: 'ing_15', name: 'Almendras', emoji: '🥜', category: 'Ingrediente', calories: 579, protein: 21.0, fat: 50.0, carbs: 22.0, servings: 1, time: 0 },
  { id: 'ing_16', name: 'Nueces', emoji: '🥜', category: 'Ingrediente', calories: 654, protein: 15.0, fat: 65.0, carbs: 14.0, servings: 1, time: 0 },
  { id: 'ing_17', name: 'Yogur griego', emoji: '🥛', category: 'Ingrediente', calories: 59, protein: 10.0, fat: 0.4, carbs: 3.6, servings: 1, time: 0 },
  { id: 'ing_18', name: 'Leche descremada', emoji: '🥛', category: 'Ingrediente', calories: 34, protein: 3.4, fat: 0.1, carbs: 5.0, servings: 1, time: 0 },
  { id: 'ing_19', name: 'Lentejas', emoji: '🍲', category: 'Ingrediente', calories: 116, protein: 9.0, fat: 0.4, carbs: 20.0, servings: 1, time: 0 },
  { id: 'ing_20', name: 'Garbanzos', emoji: '🧆', category: 'Ingrediente', calories: 164, protein: 8.9, fat: 2.6, carbs: 27.0, servings: 1, time: 0 },
  { id: 'ing_21', name: 'Quinoa', emoji: '🍚', category: 'Ingrediente', calories: 120, protein: 4.4, fat: 1.9, carbs: 21.3, servings: 1, time: 0 },
  { id: 'ing_22', name: 'Tofu', emoji: '🟩', category: 'Ingrediente', calories: 76, protein: 8.0, fat: 4.8, carbs: 1.9, servings: 1, time: 0 },
  { id: 'ing_23', name: 'Carne magra', emoji: '🥩', category: 'Ingrediente', calories: 250, protein: 26.0, fat: 15.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_24', name: 'Pavo', emoji: '🦃', category: 'Ingrediente', calories: 135, protein: 29.0, fat: 1.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_25', name: 'Manzana', emoji: '🍎', category: 'Ingrediente', calories: 52, protein: 0.3, fat: 0.2, carbs: 14.0, servings: 1, time: 0 },
  { id: 'ing_26', name: 'Banana', emoji: '🍌', category: 'Ingrediente', calories: 89, protein: 1.1, fat: 0.3, carbs: 23.0, servings: 1, time: 0 },
  { id: 'ing_27', name: 'Frutilla', emoji: '🍓', category: 'Ingrediente', calories: 32, protein: 0.7, fat: 0.3, carbs: 7.7, servings: 1, time: 0 },
  { id: 'ing_28', name: 'Arándanos', emoji: '🫐', category: 'Ingrediente', calories: 57, protein: 0.7, fat: 0.3, carbs: 14.5, servings: 1, time: 0 },
  { id: 'ing_29', name: 'Mango', emoji: '🥭', category: 'Ingrediente', calories: 60, protein: 0.8, fat: 0.4, carbs: 15.0, servings: 1, time: 0 },
  { id: 'ing_30', name: 'Piña', emoji: '🍍', category: 'Ingrediente', calories: 50, protein: 0.5, fat: 0.1, carbs: 13.0, servings: 1, time: 0 },
  { id: 'ing_31', name: 'Pepino', emoji: '🥒', category: 'Ingrediente', calories: 16, protein: 0.7, fat: 0.1, carbs: 3.6, servings: 1, time: 0 },
  { id: 'ing_32', name: 'Lechuga', emoji: '🥬', category: 'Ingrediente', calories: 15, protein: 1.4, fat: 0.2, carbs: 2.9, servings: 1, time: 0 },
  { id: 'ing_33', name: 'Coliflor', emoji: '🥦', category: 'Ingrediente', calories: 25, protein: 1.9, fat: 0.3, carbs: 5.0, servings: 1, time: 0 },
  { id: 'ing_34', name: 'Kale', emoji: '🥬', category: 'Ingrediente', calories: 49, protein: 4.3, fat: 0.9, carbs: 9.0, servings: 1, time: 0 },
  { id: 'ing_35', name: 'Remolacha', emoji: '🍠', category: 'Ingrediente', calories: 43, protein: 1.6, fat: 0.2, carbs: 10.0, servings: 1, time: 0 },
  { id: 'ing_36', name: 'Chía', emoji: '⚫', category: 'Ingrediente', calories: 486, protein: 17.0, fat: 31.0, carbs: 42.0, servings: 1, time: 0 },
  { id: 'ing_37', name: 'Semillas de lino', emoji: '🌰', category: 'Ingrediente', calories: 534, protein: 18.0, fat: 42.0, carbs: 29.0, servings: 1, time: 0 },
  { id: 'ing_38', name: 'Pan integral', emoji: '🍞', category: 'Ingrediente', calories: 247, protein: 13.0, fat: 4.2, carbs: 41.0, servings: 1, time: 0 },
  { id: 'ing_39', name: 'Pasta integral', emoji: '🍝', category: 'Ingrediente', calories: 124, protein: 5.0, fat: 0.6, carbs: 25.0, servings: 1, time: 0 },
  { id: 'ing_40', name: 'Aceite de oliva', emoji: '🫒', category: 'Ingrediente', calories: 884, protein: 0.0, fat: 100.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_41', name: 'Queso bajo en grasa', emoji: '🧀', category: 'Ingrediente', calories: 200, protein: 25.0, fat: 10.0, carbs: 3.0, servings: 1, time: 0 },
  { id: 'ing_42', name: 'Ricota', emoji: '🧀', category: 'Ingrediente', calories: 174, protein: 11.0, fat: 13.0, carbs: 3.0, servings: 1, time: 0 },
  { id: 'ing_43', name: 'Jamón cocido', emoji: '🍖', category: 'Ingrediente', calories: 145, protein: 21.0, fat: 6.0, carbs: 1.5, servings: 1, time: 0 },
  { id: 'ing_44', name: 'Pollo molido', emoji: '🍗', category: 'Ingrediente', calories: 143, protein: 17.0, fat: 8.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_45', name: 'Carne de cerdo magra', emoji: '🥩', category: 'Ingrediente', calories: 242, protein: 27.0, fat: 14.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_46', name: 'Hummus', emoji: '🥣', category: 'Ingrediente', calories: 166, protein: 8.0, fat: 9.6, carbs: 14.0, servings: 1, time: 0 },
  { id: 'ing_47', name: 'Porotos negros', emoji: '🫘', category: 'Ingrediente', calories: 132, protein: 8.9, fat: 0.5, carbs: 24.0, servings: 1, time: 0 },
  { id: 'ing_48', name: 'Porotos rojos', emoji: '🫘', category: 'Ingrediente', calories: 127, protein: 8.7, fat: 0.5, carbs: 22.8, servings: 1, time: 0 },
  { id: 'ing_49', name: 'Edamame', emoji: '🫛', category: 'Ingrediente', calories: 121, protein: 11.0, fat: 5.0, carbs: 10.0, servings: 1, time: 0 },
  { id: 'ing_50', name: 'Leche de almendra', emoji: '🥛', category: 'Ingrediente', calories: 17, protein: 0.6, fat: 1.2, carbs: 0.3, servings: 1, time: 0 },
  { id: 'ing_51', name: 'Leche de soja', emoji: '🥛', category: 'Ingrediente', calories: 54, protein: 3.3, fat: 1.8, carbs: 6.0, servings: 1, time: 0 },
  { id: 'ing_52', name: 'Proteína en polvo', emoji: '🥤', category: 'Ingrediente', calories: 400, protein: 80.0, fat: 5.0, carbs: 10.0, servings: 1, time: 0 },
  { id: 'ing_53', name: 'Chocolate negro', emoji: '🍫', category: 'Ingrediente', calories: 546, protein: 4.9, fat: 31.0, carbs: 61.0, servings: 1, time: 0 },
  { id: 'ing_54', name: 'Miel', emoji: '🍯', category: 'Ingrediente', calories: 304, protein: 0.3, fat: 0.0, carbs: 82.0, servings: 1, time: 0 },
  { id: 'ing_55', name: 'Azúcar mascabo', emoji: '🧊', category: 'Ingrediente', calories: 380, protein: 0.0, fat: 0.0, carbs: 98.0, servings: 1, time: 0 },
  { id: 'ing_56', name: 'Pera', emoji: '🍐', category: 'Ingrediente', calories: 57, protein: 0.4, fat: 0.1, carbs: 15.0, servings: 1, time: 0 },
  { id: 'ing_57', name: 'Durazno', emoji: '🍑', category: 'Ingrediente', calories: 39, protein: 0.9, fat: 0.3, carbs: 10.0, servings: 1, time: 0 },
  { id: 'ing_58', name: 'Ciruela', emoji: '🫐', category: 'Ingrediente', calories: 46, protein: 0.7, fat: 0.3, carbs: 11.0, servings: 1, time: 0 },
  { id: 'ing_59', name: 'Kiwi', emoji: '🥝', category: 'Ingrediente', calories: 61, protein: 1.1, fat: 0.5, carbs: 15.0, servings: 1, time: 0 },
  { id: 'ing_60', name: 'Naranja', emoji: '🍊', category: 'Ingrediente', calories: 47, protein: 0.9, fat: 0.1, carbs: 12.0, servings: 1, time: 0 },
  { id: 'ing_61', name: 'Mandarina', emoji: '🍊', category: 'Ingrediente', calories: 53, protein: 0.8, fat: 0.3, carbs: 13.0, servings: 1, time: 0 },
  { id: 'ing_62', name: 'Uva', emoji: '🍇', category: 'Ingrediente', calories: 69, protein: 0.7, fat: 0.2, carbs: 18.0, servings: 1, time: 0 },
  { id: 'ing_63', name: 'Sandía', emoji: '🍉', category: 'Ingrediente', calories: 30, protein: 0.6, fat: 0.2, carbs: 8.0, servings: 1, time: 0 },
  { id: 'ing_64', name: 'Melón', emoji: '🍈', category: 'Ingrediente', calories: 34, protein: 0.8, fat: 0.2, carbs: 8.0, servings: 1, time: 0 },
  { id: 'ing_65', name: 'Coco', emoji: '🥥', category: 'Ingrediente', calories: 354, protein: 3.3, fat: 33.0, carbs: 15.0, servings: 1, time: 0 },
  { id: 'ing_66', name: 'Harina de avena', emoji: '🌾', category: 'Ingrediente', calories: 389, protein: 16.9, fat: 6.9, carbs: 66.0, servings: 1, time: 0 },
  { id: 'ing_67', name: 'Harina integral', emoji: '🌾', category: 'Ingrediente', calories: 340, protein: 13.0, fat: 2.5, carbs: 72.0, servings: 1, time: 0 },
  { id: 'ing_68', name: 'Couscous', emoji: '🍲', category: 'Ingrediente', calories: 112, protein: 3.8, fat: 0.2, carbs: 23.0, servings: 1, time: 0 },
  { id: 'ing_69', name: 'Cebada', emoji: '🌾', category: 'Ingrediente', calories: 123, protein: 2.3, fat: 0.4, carbs: 28.0, servings: 1, time: 0 },
  { id: 'ing_70', name: 'Maíz', emoji: '🌽', category: 'Ingrediente', calories: 96, protein: 3.4, fat: 1.5, carbs: 21.0, servings: 1, time: 0 },
  { id: 'ing_71', name: 'Polenta', emoji: '🥣', category: 'Ingrediente', calories: 70, protein: 1.7, fat: 0.3, carbs: 15.0, servings: 1, time: 0 },
  { id: 'ing_72', name: 'Queso cheddar', emoji: '🧀', category: 'Ingrediente', calories: 403, protein: 25.0, fat: 33.0, carbs: 1.3, servings: 1, time: 0 },
  { id: 'ing_73', name: 'Queso mozzarella', emoji: '🧀', category: 'Ingrediente', calories: 280, protein: 28.0, fat: 17.0, carbs: 3.0, servings: 1, time: 0 },
  { id: 'ing_74', name: 'Yogur natural', emoji: '🥛', category: 'Ingrediente', calories: 61, protein: 3.5, fat: 3.3, carbs: 4.7, servings: 1, time: 0 },
  { id: 'ing_75', name: 'Kefir', emoji: '🥛', category: 'Ingrediente', calories: 41, protein: 3.3, fat: 1.0, carbs: 4.5, servings: 1, time: 0 },
  { id: 'ing_76', name: 'Champiñones', emoji: '🍄', category: 'Ingrediente', calories: 22, protein: 3.1, fat: 0.3, carbs: 3.3, servings: 1, time: 0 },
  { id: 'ing_77', name: 'Berenjena', emoji: '🍆', category: 'Ingrediente', calories: 25, protein: 1.0, fat: 0.2, carbs: 6.0, servings: 1, time: 0 },
  { id: 'ing_78', name: 'Zapallo', emoji: '🎃', category: 'Ingrediente', calories: 26, protein: 1.0, fat: 0.1, carbs: 7.0, servings: 1, time: 0 },
  { id: 'ing_79', name: 'Zucchini', emoji: '🥒', category: 'Ingrediente', calories: 17, protein: 1.2, fat: 0.3, carbs: 3.1, servings: 1, time: 0 },
  { id: 'ing_80', name: 'Cebolla', emoji: '🧅', category: 'Ingrediente', calories: 40, protein: 1.1, fat: 0.1, carbs: 9.3, servings: 1, time: 0 },
  { id: 'ing_81', name: 'Ajo', emoji: '🧄', category: 'Ingrediente', calories: 149, protein: 6.4, fat: 0.5, carbs: 33.0, servings: 1, time: 0 },
  { id: 'ing_82', name: 'Jengibre', emoji: '🫚', category: 'Ingrediente', calories: 80, protein: 1.8, fat: 0.8, carbs: 18.0, servings: 1, time: 0 },
  { id: 'ing_83', name: 'Maní', emoji: '🥜', category: 'Ingrediente', calories: 567, protein: 26.0, fat: 49.0, carbs: 16.0, servings: 1, time: 0 },
  { id: 'ing_84', name: 'Mantequilla de maní', emoji: '🥜', category: 'Ingrediente', calories: 588, protein: 25.0, fat: 50.0, carbs: 20.0, servings: 1, time: 0 },
  { id: 'ing_85', name: 'Semillas de girasol', emoji: '🌻', category: 'Ingrediente', calories: 584, protein: 21.0, fat: 51.0, carbs: 20.0, servings: 1, time: 0 },
  { id: 'ing_86', name: 'Semillas de calabaza', emoji: '🎃', category: 'Ingrediente', calories: 559, protein: 30.0, fat: 49.0, carbs: 11.0, servings: 1, time: 0 },
  { id: 'ing_87', name: 'Granola', emoji: '🥣', category: 'Ingrediente', calories: 471, protein: 10.0, fat: 20.0, carbs: 64.0, servings: 1, time: 0 },
  { id: 'ing_88', name: 'Barrita proteica', emoji: '🍫', category: 'Ingrediente', calories: 350, protein: 20.0, fat: 10.0, carbs: 40.0, servings: 1, time: 0 },
  { id: 'ing_89', name: 'Pasta blanca', emoji: '🍝', category: 'Ingrediente', calories: 131, protein: 5.0, fat: 1.1, carbs: 25.0, servings: 1, time: 0 },
  { id: 'ing_90', name: 'Arroz blanco', emoji: '🍚', category: 'Ingrediente', calories: 130, protein: 2.7, fat: 0.3, carbs: 28.0, servings: 1, time: 0 },
  { id: 'ing_91', name: 'Fideos de arroz', emoji: '🍜', category: 'Ingrediente', calories: 109, protein: 1.8, fat: 0.2, carbs: 25.0, servings: 1, time: 0 },
  { id: 'ing_92', name: 'Tortilla de trigo', emoji: '🌯', category: 'Ingrediente', calories: 218, protein: 6.0, fat: 5.0, carbs: 36.0, servings: 1, time: 0 },
  { id: 'ing_93', name: 'Pan de centeno', emoji: '🍞', category: 'Ingrediente', calories: 259, protein: 9.0, fat: 3.3, carbs: 48.0, servings: 1, time: 0 },
  { id: 'ing_94', name: 'Leche entera', emoji: '🥛', category: 'Ingrediente', calories: 60, protein: 3.2, fat: 3.3, carbs: 5.0, servings: 1, time: 0 },
  { id: 'ing_95', name: 'Crema de leche', emoji: '🥛', category: 'Ingrediente', calories: 340, protein: 2.0, fat: 36.0, carbs: 3.0, servings: 1, time: 0 },
  { id: 'ing_96', name: 'Helado', emoji: '🍨', category: 'Ingrediente', calories: 207, protein: 3.5, fat: 11.0, carbs: 24.0, servings: 1, time: 0 },
  { id: 'ing_97', name: 'Chocolate con leche', emoji: '🍫', category: 'Ingrediente', calories: 535, protein: 7.0, fat: 30.0, carbs: 59.0, servings: 1, time: 0 },
  { id: 'ing_98', name: 'Bebida isotónica', emoji: '🥤', category: 'Ingrediente', calories: 24, protein: 0.0, fat: 0.0, carbs: 6.0, servings: 1, time: 0 },
  { id: 'ing_100', name: 'Té', emoji: '🍵', category: 'Ingrediente', calories: 1, protein: 0.0, fat: 0.0, carbs: 0.0, servings: 1, time: 0 },

  // ── LATAM COMMON FOODS ──────────────────────
  // Argentina
  { id: 'lat_ar_1', name: 'Empanada de carne (Horno)', emoji: '🥟', category: 'Argentina', calories: 280, protein: 12, fat: 14, carbs: 26, servings: 1, country: 'AR' },
  { id: 'lat_ar_2', name: 'Choripán', emoji: '🌭', category: 'Argentina', calories: 450, protein: 18, fat: 28, carbs: 32, servings: 1, country: 'AR' },
  { id: 'lat_ar_3', name: 'Milanesa de Pollo', emoji: '🍗', category: 'Argentina', calories: 250, protein: 22, fat: 10, carbs: 18, servings: 1, country: 'AR' },
  { id: 'lat_ar_4', name: 'Alfajor de Maicena', emoji: '🍪', category: 'Argentina', calories: 350, protein: 4, fat: 15, carbs: 50, servings: 1, country: 'AR' },
  { id: 'lat_ar_5', name: 'Asado (Tira de Asado)', emoji: '🥩', category: 'Argentina', calories: 250, protein: 24, fat: 17, carbs: 0, servings: 1, country: 'AR' },
  { id: 'lat_ar_6', name: 'Dulce de Leche', emoji: '🍯', category: 'Argentina', calories: 315, protein: 6, fat: 7, carbs: 57, servings: 1, country: 'AR' },
  { id: 'lat_ar_7', name: 'Matambre a la pizza', emoji: '🍕', category: 'Argentina', calories: 320, protein: 28, fat: 22, carbs: 2, servings: 1, country: 'AR' },

  // México
  { id: 'lat_mx_1', name: 'Taco al Pastor', emoji: '🌮', category: 'México', calories: 150, protein: 9, fat: 7, carbs: 14, servings: 1, country: 'MX' },
  { id: 'lat_mx_2', name: 'Tamal de Pollo', emoji: '🫔', category: 'México', calories: 300, protein: 12, fat: 15, carbs: 30, servings: 1, country: 'MX' },
  { id: 'lat_mx_3', name: 'Guacamole Home-made', emoji: '🥑', category: 'México', calories: 160, protein: 2, fat: 15, carbs: 9, servings: 1, country: 'MX' },
  { id: 'lat_mx_4', name: 'Chilaquiles con Pollo', emoji: '🥘', category: 'México', calories: 420, protein: 24, fat: 22, carbs: 35, servings: 1, country: 'MX' },
  { id: 'lat_mx_5', name: 'Enchilada Verde', emoji: '🌯', category: 'México', calories: 210, protein: 11, fat: 10, carbs: 20, servings: 1, country: 'MX' },
  { id: 'lat_mx_6', name: 'Quesadilla de Queso', emoji: '🧀', category: 'México', calories: 250, protein: 12, fat: 14, carbs: 19, servings: 1, country: 'MX' },

  // Colombia / Venezuela
  { id: 'lat_co_1', name: 'Arepa con Queso', emoji: '🫓', category: 'Colombia/Venezuela', calories: 280, protein: 10, fat: 12, carbs: 34, servings: 1, country: 'CO' },
  { id: 'lat_ve_1', name: 'Pabellón Criollo', emoji: '🍱', category: 'Venezuela', calories: 650, protein: 35, fat: 20, carbs: 80, servings: 1, country: 'VE' },
  { id: 'lat_co_2', name: 'Bandeja Paisa', emoji: '🍽️', category: 'Colombia', calories: 950, protein: 45, fat: 45, carbs: 95, servings: 1, country: 'CO' },
  { id: 'lat_co_3', name: 'Patacón Pisao', emoji: '🍌', category: 'Colombia/Venezuela', calories: 240, protein: 2, fat: 12, carbs: 32, servings: 1, country: 'CO' },
  { id: 'lat_ve_2', name: 'Arepa Reina Pepiada', emoji: '🫓', category: 'Venezuela', calories: 380, protein: 18, fat: 24, carbs: 26, servings: 1, country: 'VE' },

  // Perú
  { id: 'lat_pe_1', name: 'Ceviche de Pescado', emoji: '🥗', category: 'Perú', calories: 140, protein: 20, fat: 2, carbs: 12, servings: 1, country: 'PE' },
  { id: 'lat_pe_2', name: 'Lomo Saltado', emoji: '🍳', category: 'Perú', calories: 520, protein: 30, fat: 22, carbs: 48, servings: 1, country: 'PE' },
  { id: 'lat_pe_3', name: 'Ají de Gallina', emoji: '🍛', category: 'Perú', calories: 460, protein: 28, fat: 24, carbs: 35, servings: 1, country: 'PE' },
  { id: 'lat_pe_4', name: 'Causa Limeña', emoji: '🥔', category: 'Perú', calories: 310, protein: 14, fat: 12, carbs: 38, servings: 1, country: 'PE' },

  // Chile
  { id: 'lat_cl_1', name: 'Empanada de Pino', emoji: '🥟', category: 'Chile', calories: 360, protein: 15, fat: 18, carbs: 35, servings: 1, country: 'CL' },
  { id: 'lat_cl_2', name: 'Cazuela de Vacuno', emoji: '🍲', category: 'Chile', calories: 410, protein: 26, fat: 18, carbs: 36, servings: 1, country: 'CL' },
  { id: 'lat_cl_3', name: 'Pastel de Choclo', emoji: '🥧', category: 'Chile', calories: 540, protein: 22, fat: 24, carbs: 62, servings: 1, country: 'CL' },
  { id: 'lat_cl_4', name: 'Humita', emoji: '🌽', category: 'Chile', calories: 320, protein: 8, fat: 12, carbs: 46, servings: 1, country: 'CL' },
  { id: 'lat_cl_5', name: 'Completo Italiano', emoji: '🌭', category: 'Chile', calories: 510, protein: 16, fat: 34, carbs: 36, servings: 1, country: 'CL' },

  // Brazil
  { id: 'lat_br_1', name: 'Feijoada', emoji: '🍲', category: 'Brasil', calories: 580, protein: 34, fat: 32, carbs: 40, servings: 1, country: 'BR' },
  { id: 'lat_br_2', name: 'Pão de Queijo (U)', emoji: '🥯', category: 'Brasil', calories: 80, protein: 2, fat: 4, carbs: 9, servings: 1, country: 'BR' },
  { id: 'lat_br_3', name: 'Coxinha de Frango', emoji: '🍗', category: 'Brasil', calories: 240, protein: 10, fat: 12, carbs: 24, servings: 1, country: 'BR' },

  // Otros LATAM
  { id: 'lat_generic_1', name: 'Pupusa de Queso', emoji: '🫓', category: 'El Salvador', calories: 230, protein: 9, fat: 10, carbs: 26, servings: 1, country: 'SV' },
  { id: 'lat_generic_2', name: 'Gallo Pinto', emoji: '🍛', category: 'C. Rica/Nicaragua', calories: 220, protein: 7, fat: 3, carbs: 42, servings: 1, country: 'CR' },
  { id: 'lat_generic_3', name: 'Chivito Uruguayo', emoji: '🥪', category: 'Uruguay', calories: 750, protein: 48, fat: 42, carbs: 46, servings: 1, country: 'UY' },
  { id: 'lat_generic_4', name: 'Sancocho', emoji: '🍲', category: 'Rep. Dominicana/Panamá', calories: 380, protein: 24, fat: 15, carbs: 38, servings: 1, country: 'DO' },
  { id: 'lat_generic_5', name: 'Yuca con Chicharrón', emoji: '🥓', category: 'Guatemala/Honduras', calories: 550, protein: 18, fat: 38, carbs: 45, servings: 1, country: 'GT' },

  // ── SUPERMARKET & SNACKS LATAM ────────────────
  // Argentina
  { id: 'snk_ar_1', name: 'Alfajor Guaymallén Blanco', emoji: '🍪', category: 'Snacks AR', calories: 165, protein: 2.2, fat: 6.8, carbs: 24, country: 'AR' },
  { id: 'snk_ar_2', name: 'Alfajor Jorgito Chocolate', emoji: '🍫', category: 'Snacks AR', calories: 215, protein: 3.1, fat: 9.8, carbs: 29, country: 'AR' },
  { id: 'snk_ar_3', name: 'Alfajor Havanna Mixto', emoji: '🎁', category: 'Snacks AR', calories: 190, protein: 2.8, fat: 8.5, carbs: 26, country: 'AR' },
  { id: 'snk_ar_4', name: 'Galletitas Chocolinas (3 u.)', emoji: '🍪', category: 'Snacks AR', calories: 134, protein: 1.8, fat: 5.8, carbs: 19, country: 'AR' },
  { id: 'snk_ar_5', name: 'Galletitas Oreo (3 u.)', emoji: '🍪', category: 'Snacks AR', calories: 145, protein: 1.5, fat: 6.3, carbs: 21, country: 'AR' },
  { id: 'snk_ar_6', name: 'Galletitas Traviata (3 u.)', emoji: '🍞', category: 'Snacks AR', calories: 120, protein: 3.1, fat: 2.1, carbs: 22, country: 'AR' },
  { id: 'snk_ar_7', name: 'Barrita Cereal Mix Manzana', emoji: '🌾', category: 'Snacks AR', calories: 95, protein: 1.2, fat: 3.1, carbs: 16, country: 'AR' },
  { id: 'snk_ar_8', name: 'Galletitas Criollitas (3 u.)', emoji: '🍪', category: 'Snacks AR', calories: 125, protein: 3.2, fat: 4.8, carbs: 20, country: 'AR' },
  { id: 'snk_ar_9', name: 'Alfajor Capitán del Espacio', emoji: '🚀', category: 'Snacks AR', calories: 185, protein: 2.5, fat: 8.0, carbs: 25, country: 'AR' },
  { id: 'snk_ar_10', name: 'Tita (u.)', emoji: '🍪', category: 'Snacks AR', calories: 88, protein: 1.0, fat: 4.2, carbs: 12, country: 'AR' },
  { id: 'snk_ar_11', name: 'Rhodesia (u.)', emoji: '🍫', category: 'Snacks AR', calories: 110, protein: 1.2, fat: 5.5, carbs: 14, country: 'AR' },
  { id: 'snk_ar_12', name: 'Yogur La Serenísima c/Cereales', emoji: '🥛', category: 'Lácteos AR', calories: 180, protein: 6.0, fat: 4.5, carbs: 28, country: 'AR' },

  // México
  { id: 'snk_mx_1', name: 'Gansito Marinela (u.)', emoji: '🧁', category: 'Snacks MX', calories: 190, protein: 2.0, fat: 9.0, carbs: 25, country: 'MX' },
  { id: 'snk_mx_2', name: 'Pingüinos Marinela (2 u.)', emoji: '🧁', category: 'Snacks MX', calories: 330, protein: 3.0, fat: 16, carbs: 44, country: 'MX' },
  { id: 'snk_mx_3', name: 'Sabritas Sal (bolsa pequeña)', emoji: '🥔', category: 'Snacks MX', calories: 230, protein: 2.0, fat: 15, carbs: 22, country: 'MX' },
  { id: 'snk_mx_4', name: 'Takis Fuego (bolsa pequeña)', emoji: '🔥', category: 'Snacks MX', calories: 280, protein: 3.0, fat: 16, carbs: 32, country: 'MX' },
  { id: 'snk_mx_5', name: 'Pan Bimbo Blanco (tajada)', emoji: '🍞', category: 'Bimbo', calories: 70, protein: 2.5, fat: 1.0, carbs: 13, country: 'MX' },
  { id: 'snk_mx_6', name: 'Tortilla de Harina Tía Rosa', emoji: '🫓', category: 'Bimbo', calories: 95, protein: 2.5, fat: 3.0, carbs: 15, country: 'MX' },
  { id: 'snk_mx_7', name: 'Mazapán De la Rosa (u.)', emoji: '🍬', category: 'Snacks MX', calories: 130, protein: 3.0, fat: 7, carbs: 14, country: 'MX' },

  // Chile
  { id: 'snk_cl_1', name: 'Super 8 (u.)', emoji: '🍫', category: 'Snacks CL', calories: 152, protein: 1.8, fat: 7.2, carbs: 20, country: 'CL' },
  { id: 'snk_cl_2', name: 'Galleta Vino Costa (4 u.)', emoji: '🍪', category: 'Snacks CL', calories: 150, protein: 2.2, fat: 4.8, carbs: 25, country: 'CL' },
  { id: 'snk_cl_3', name: 'Negrita / Chokita (u.)', emoji: '🍫', category: 'Snacks CL', calories: 160, protein: 1.5, fat: 8.5, carbs: 20, country: 'CL' },
  { id: 'snk_cl_4', name: 'Ramitas Evervess Sal (bolsa)', emoji: '🍟', category: 'Snacks CL', calories: 180, protein: 2.0, fat: 10, carbs: 21, country: 'CL' },
  { id: 'snk_cl_5', name: 'Krytpo (u.)', emoji: '🍫', category: 'Snacks CL', calories: 220, protein: 3.0, fat: 12, carbs: 26, country: 'CL' },

  // Colombia / Venezuela
  { id: 'snk_co_1', name: 'Chocoramo (U)', emoji: '🍰', category: 'Snacks CO', calories: 260, protein: 3.0, fat: 14, carbs: 32, country: 'CO' },
  { id: 'snk_co_2', name: 'Galletas Festival Fresa (4 u.)', emoji: '🍪', category: 'Snacks CO', calories: 190, protein: 2.0, fat: 8.0, carbs: 28, country: 'CO' },
  { id: 'snk_co_3', name: 'Choclitos (bolsa)', emoji: '🍟', category: 'Snacks CO', calories: 180, protein: 2.0, fat: 9.0, carbs: 22, country: 'CO' },
  { id: 'snk_ve_1', name: 'Susy (u.)', emoji: '🍪', category: 'Snacks VE', calories: 140, protein: 1.2, fat: 7.0, carbs: 18, country: 'VE' },
  { id: 'snk_ve_2', name: 'Cocosette (u.)', emoji: '🍪', category: 'Snacks VE', calories: 260, protein: 2.5, fat: 14, carbs: 32, country: 'VE' },
  { id: 'snk_ve_3', name: 'Chocolate Jet (u.)', emoji: '🍫', category: 'Snacks CO/VE', calories: 65, protein: 1.0, fat: 4.0, carbs: 7.0, country: 'VE' },
  { id: 'snk_ve_4', name: 'Harina P.A.N. (100g seco)', emoji: '🌽', category: 'Basics', calories: 350, protein: 7.0, fat: 1.5, carbs: 77, country: 'VE' },

  // Uruguay
  { id: 'snk_uy_1', name: 'Alfajor Portezuelo', emoji: '🍪', category: 'Snacks UY', calories: 175, protein: 2.3, fat: 7.5, carbs: 25, country: 'UY' },
  { id: 'snk_uy_2', name: 'Galletitas Bridge (3 u.)', emoji: '🍪', category: 'Snacks UY', calories: 130, protein: 1.8, fat: 5.5, carbs: 19, country: 'UY' },

  // Global / Bebidas
  { id: 'bev_lat_1', name: 'Coca Cola (Lata 354ml)', emoji: '🥤', category: 'Bebidas', calories: 140, protein: 0, fat: 0, carbs: 37, country: 'WW' },
  { id: 'bev_lat_2', name: 'Inca Kola (Lata 354ml)', emoji: '🥤', category: 'Bebidas', calories: 150, protein: 0, fat: 0, carbs: 41, country: 'PE' },
  { id: 'bev_lat_3', name: 'Gatorade (Bote 500ml)', emoji: '🥤', category: 'Bebidas', calories: 120, protein: 0, fat: 0, carbs: 30, country: 'WW' },
  { id: 'bev_lat_4', name: 'Cerveza Quilmes (330ml)', emoji: '🍺', category: 'Bebidas', calories: 145, protein: 1.0, fat: 0, carbs: 11, country: 'AR' },
  { id: 'bev_lat_5', name: 'Cerveza Corona (355ml)', emoji: '🍺', category: 'Bebidas', calories: 148, protein: 1.2, fat: 0, carbs: 14, country: 'MX' }
];

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('./sw.js')
      .then(() => console.debug('SW registered'))
      .catch(err => console.warn('SW registration failed:', err));
  });
}

