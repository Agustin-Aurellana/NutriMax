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
  USER: 'nutriai_user',
  LOGS: 'nutriai_logs',
  // RECIPES eliminado: los datos de recetas ahora se persisten
  // en MySQL a través de la API (/api/v1/recetas). El localStorage
  // NO debe ser la fuente de verdad para datos del usuario.
  GOALS: 'nutriai_goals',
  APIKEY: 'nutriai_apikey',
  CHAT: 'nutriai_chat',
  THEME: 'nutriai_theme',
  LOCATION: 'nutriai_location',
  WEIGHT_HIST: 'nutriai_weight_history',
};

const GOALS_CONFIG = {
  definition: { label: 'Definición', icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0z"></path><path d="M15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"></path></svg>`, calMod: -0.20, protein: 0.40, carbs: 0.35, fat: 0.25 },
  volume: { label: 'Volumen', icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>`, calMod: 0.15, protein: 0.30, carbs: 0.50, fat: 0.20 },
  maintenance: { label: 'Mantenimiento', icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M12 3v18"></path><path d="M7 12l2 10"></path><path d="M17 12l-2 10"></path></svg>`, calMod: 0, protein: 0.30, carbs: 0.45, fat: 0.25 },
  recomp: { label: 'Recomposición', icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>`, calMod: 0, protein: 0.40, carbs: 0.35, fat: 0.25 },
  custom: { label: 'Personalizado', icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`, calMod: 0, protein: 0, carbs: 0, fat: 0 },
};

const ACTIVITY_MULTIPLIERS = {
  sedentary: { label: 'Sedentario (sin ejercicio)', value: 1.2 },
  light: { label: 'Ligero (1-3 días/semana)', value: 1.375 },
  moderate: { label: 'Moderado (3-5 días/semana)', value: 1.55 },
  active: { label: 'Activo (6-7 días/semana)', value: 1.725 },
  very_active: { label: 'Muy activo (2 entrenamientos/día)', value: 1.9 },
};

const MEAL_TYPES = ['Desayuno', 'Almuerzo', 'Cena', 'Snack'];
const MEAL_ICONS = { Desayuno: '🍳', Almuerzo: '🥗', Cena: '🍗', Snack: '🍎' };

// ──────────────────────────────────────────
// 2. LOCALSTORAGE HELPERS
// ──────────────────────────────────────────
// --- INDEXEDDB SYNC LAYER ---
// Esta capa asegura que los datos locales (alimentos, perfil) se guarden de forma persistente
// usando IndexedDB, previniendo la pérdida de datos si localStorage es limpiado por el navegador.
let idbPromise = null;
function getDB() {
  if (!idbPromise) {
    idbPromise = new Promise((resolve, reject) => {
      const request = indexedDB.open('NutriMaxDB', 1);

      // Evento disparado si la base de datos no existe o se actualiza la versión
      request.onupgradeneeded = e => {
        const db = e.target.result;
        // Crea un almacén de objetos genérico llamado 'store'
        if (!db.objectStoreNames.contains('store')) db.createObjectStore('store');
      };

      // Evento de éxito: retorna la conexión a la base de datos
      request.onsuccess = e => resolve(e.target.result);
      // Evento de error: rechaza la promesa
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

window.syncFromDbToLocal = function () {
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
    } catch (e) { resolve(); }
  });
};

const store = {
  get: (k, d) => {
    const v = localStorage.getItem(k);
    if (!v) return d;
    try { return JSON.parse(v); } catch (e) { return d; }
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
function getUser() { return store.get(KEYS.USER); }
function isLoggedIn() { return !!getUser(); }
function logout() {
  store.remove(KEYS.USER);
  localStorage.removeItem('nutrimax_token'); // Limpiar JWT al cerrar sesión
  window.location.href = 'index.html';
}

function requireAuth() {
  const user = getUser();
  const token = getToken();

  // Sin usuario o sin token: no autenticado
  if (!user || !token) {
    logout();
    return false;
  }

  // Verificar expiración del JWT client-side (sin llamada al servidor).
  // El payload es la segunda parte del token, codificada en Base64.
  try {
    const payloadB64 = token.split('.')[1];
    const payload = JSON.parse(atob(payloadB64.replace(/-/g, '+').replace(/_/g, '/')));
    if (payload.exp && Date.now() / 1000 > payload.exp) {
      // Token expirado: limpiar sesión y redirigir al login
      logout();
      return false;
    }
  } catch (e) {
    // Token malformado
    logout();
    return false;
  }

  return true;
}

function saveUser(profile) { store.set(KEYS.USER, profile); }

/**
 * Retorna el token JWT almacenado en localStorage.
 * @returns {string|null}
 */
function getToken() {
  return localStorage.getItem('nutrimax_token');
}

/**
 * Devuelve los headers necesarios para autenticar un fetch() a la API.
 * Uso: fetch('/api/v1/ruta', { headers: getAuthHeaders(), ... })
 * @param {Object} extra Headers adicionales a combinar (opcional).
 * @returns {Object}
 */
function getAuthHeaders(extra = {}) {
  const token = getToken();
  return {
    'Content-Type': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...extra
  };
}

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
        return { name: f.label, calories: Math.round(f.nutrients.ENERC_KCAL || 0), protein: f.nutrients.PROCNT || 0, carbs: f.nutrients.CHOCDF || 0, fat: f.nutrients.FAT || 0, barcode, source: 'Edamam' };
      }
    } catch (e) { }
  }

  // 2. Fallback a OFF
  try {
    const url = `https://world.openfoodfacts.org/api/v0/product/${barcode}.json`;
    const res = await fetch(url);
    const data = await res.json();
    if (data.status === 1) {
      const p = data.product;
      return { name: p.product_name, calories: Math.round(p.nutriments['energy-kcal_100g'] || 0), protein: p.nutriments.proteins_100g || 0, carbs: p.nutriments.carbohydrates_100g || 0, fat: p.nutriments.fat_100g || 0, barcode, source: 'OpenFoodFacts' };
    }
  } catch (e) { }

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
    .map(char => 127397 + char.charCodeAt());
  return String.fromCodePoint(...codePoints);
}

// ──────────────────────────────────────────
// 4. TDEE & MACRO CALCULATOR
// ──────────────────────────────────────────

// Calcula el Gasto Energético Diario Total (TDEE) basándose en los datos del perfil
function calculateTDEE(profile) {
  const { weight, height, age, sex, activityLevel } = profile;
  let bmr;

  // Normalizar el género a un formato consistente ('M' o 'F') que soporte tanto palabras completas como iniciales
  const normalizedSex = sex ? sex.toString().toUpperCase().charAt(0) : 'M';

  // Calcular la Tasa Metabólica Basal (BMR) usando la ecuación de Mifflin-St Jeor
  if (normalizedSex === 'M') {
    bmr = (10 * weight) + (6.25 * height) - (5 * age) + 5;
  } else {
    bmr = (10 * weight) + (6.25 * height) - (5 * age) - 161;
  }

  // Multiplicar el BMR por el factor de actividad física
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
  const pastEntries = history.filter(e => e.date <= dateStr).sort((a, b) => new Date(b.date) - new Date(a.date));
  if (pastEntries.length > 0) return pastEntries[0].weight;

  // Si no hay ninguno anterior, intentar con el user profile
  const user = getUser();
  return user?.weight || 70;
}


// Calcula la distribución de macronutrientes (Proteínas, Carbohidratos, Grasas)
// de acuerdo al objetivo del usuario (definición, volumen, mantenimiento, etc.)
function calculateMacros(tdee, goal, weight, customTargets = null) {
  // Si el objetivo es personalizado, se usan los valores manuales
  if (goal === 'custom' && customTargets) {
    return {
      calories: customTargets.calories || 2000,
      protein: customTargets.protein || 150,
      carbs: customTargets.carbs || 200,
      fat: customTargets.fat || 65,
    };
  }

  // Obtener la configuración del objetivo o usar mantenimiento por defecto
  const cfg = GOALS_CONFIG[goal] ?? GOALS_CONFIG.maintenance;

  // Calcular calorías objetivo agregando/restando el modificador del objetivo
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

function getLogs() { return store.get(KEYS.LOGS, {}); }
function getTodayLog() {
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
    acc.protein += (e.protein || 0);
    acc.carbs += (e.carbs || 0);
    acc.fat += (e.fat || 0);
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
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
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
  const pPro = Math.min(1, totals.protein / (targets.protein || 150));
  const pCar = Math.min(1, totals.carbs / (targets.carbs || 200));
  const pFat = Math.min(1, totals.fat / (targets.fat || 70));

  const score = (pCal * 0.4 + pPro * 0.3 + pCar * 0.15 + pFat * 0.15) * 100;
  return Math.round(score);
}

// ──────────────────────────────────────────
// 6. RECIPE HELPERS — API-Driven
// ──────────────────────────────────────────
// Las recetas ya NO se almacenan en localStorage.
// Toda la persistencia ocurre en MySQL a través de /api/v1/recetas.
//
// Estrategia de caché: usamos un objeto en memoria de sesión (_recipeCache)
// para evitar llamadas duplicadas a la API dentro de la misma carga de página.
// Al recargar, el caché se descarta → siempre se consulta la fuente de verdad.

const _recipeCache = {
  data: null,   // Array de recetas o null si no se ha cargado aún
  ts: 0,      // Timestamp del último fetch
};
const _RECIPE_CACHE_TTL = 60_000; // 1 minuto: tiempo máximo antes de re-fetch

/**
 * Invalida el caché en memoria para forzar un nuevo GET en la próxima llamada.
 * Debe invocarse después de crear o eliminar una receta.
 */
function _invalidateRecipeCache() {
  _recipeCache.data = null;
  _recipeCache.ts = 0;
}

/**
 * Obtiene todas las recetas (globales + propias) desde la API.
 * Usa caché en memoria para evitar re-fetches innecesarios.
 *
 * @returns {Promise<Array>} Lista de recetas.
 */
async function getAllRecipes() {
  // Servir desde caché si está vigente
  if (_recipeCache.data && (Date.now() - _recipeCache.ts) < _RECIPE_CACHE_TTL) {
    return _recipeCache.data;
  }

  try {
    const res = await fetch('api/v1/recetas', {
      headers: getAuthHeaders(),
    });
    const json = await res.json();

    if (json.status === 'success' && Array.isArray(json.data?.recipes)) {
      _recipeCache.data = json.data.recipes;
      _recipeCache.ts = Date.now();
      return _recipeCache.data;
    }
  } catch (e) {
    console.error('[RecipeAPI] Error al obtener recetas:', e);
  }

  return []; // Fallback seguro: array vacío
}

/**
 * Alias para compatibilidad. Devuelve solo las recetas propias del usuario.
 * @returns {Promise<Array>}
 */
async function getUserRecipes() {
  const all = await getAllRecipes();
  return all.filter(r => r.is_custom);
}

/**
 * Busca recetas filtrando por texto libre y/o tipo de dieta.
 * Delega los filtros al backend para mayor eficiencia.
 *
 * @param {string} query  Texto libre de búsqueda.
 * @param {string} goal   Tipo de dieta (ej: 'Keto', 'Vegana'). Vacío = todos.
 * @returns {Promise<Array>}
 */
async function searchRecipes(query = '', goal = '') {
  // Si no hay filtros activos, servimos desde caché
  if (!query && (!goal || goal === 'all')) {
    return getAllRecipes();
  }

  try {
    const params = new URLSearchParams();
    if (query) params.set('query', query);
    if (goal && goal !== 'all') params.set('goal', goal);

    const res = await fetch(`api/v1/recetas?${params.toString()}`, {
      headers: getAuthHeaders(),
    });
    const json = await res.json();

    if (json.status === 'success' && Array.isArray(json.data?.recipes)) {
      return json.data.recipes;
    }
  } catch (e) {
    console.error('[RecipeAPI] Error en búsqueda de recetas:', e);
  }

  return [];
}

/**
 * Realiza una búsqueda de ingredientes en el backend.
 * Consume el endpoint /api/v1/ingredientes enviando el término de búsqueda.
 *
 * @param {string} query Término de búsqueda (filtro por nombre).
 * @returns {Promise<Array>} Lista de ingredientes encontrados.
 */
async function searchIngredients(query = '') {
  try {
    const params = new URLSearchParams();
    if (query) params.set('query', query);

    const res = await fetch(`api/v1/ingredientes?${params.toString()}`, {
      headers: getAuthHeaders(),
    });
    const json = await res.json();

    if (json.status === 'success' && Array.isArray(json.data?.ingredients)) {
      return json.data.ingredients;
    }
  } catch (e) {
    console.error('[IngredientAPI] Error en búsqueda de ingredientes:', e);
  }

  return [];
}

/**
 * Crea una nueva receta personalizada del usuario en la BD.
 * Invalida el caché para que el próximo getAllRecipes() refleje el cambio.
 *
 * @param {Object} recipeData Datos de la receta: name, emoji, descrip, instr, porciones, dieta.
 * @returns {Promise<Object|null>} Receta creada con su ID asignado, o null si falló.
 */
async function saveUserRecipe(recipeData) {
  try {
    const res = await fetch('api/v1/recetas', {
      method: 'POST',
      headers: getAuthHeaders(),
      body: JSON.stringify(recipeData),
    });
    const json = await res.json();

    if (json.status === 'success') {
      _invalidateRecipeCache(); // Forzar re-fetch en la próxima consulta
      return { ...recipeData, ID_RECETA: json.data.id, is_custom: true };
    }

    showToast(json.message || 'Error al guardar la receta', 'error');
  } catch (e) {
    console.error('[RecipeAPI] Error al crear receta:', e);
    showToast('Error de conexión al guardar la receta', 'error');
  }

  return null;
}

/**
 * Elimina una receta propia del usuario.
 * Solo funcionará si la receta pertenece al usuario autenticado (validado en el backend).
 *
 * @param {string} id UUID de la receta a eliminar.
 * @returns {Promise<boolean>} true si se eliminó correctamente.
 */
async function deleteUserRecipe(id) {
  try {
    const res = await fetch('api/v1/recetas', {
      method: 'DELETE',
      headers: getAuthHeaders(),
      body: JSON.stringify({ id }),
    });
    const json = await res.json();

    if (json.status === 'success') {
      _invalidateRecipeCache();
      return true;
    }

    showToast(json.message || 'No se pudo eliminar la receta', 'error');
  } catch (e) {
    console.error('[RecipeAPI] Error al eliminar receta:', e);
    showToast('Error de conexión al eliminar la receta', 'error');
  }

  return false;
}


// ──────────────────────────────────────────
// 7. TOAST NOTIFICATIONS (glassmorphism, theme-aware, Lucide icons)
// ──────────────────────────────────────────

// ── Lucide SVG icon strings (inline, no external dependency) ──
const _TOAST_ICONS = {
  success: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="9 11 12 14 22 4"/></svg>`,
  error: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
  warning: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  info: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
  default: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
};

(function _injectToastStyles() {
  if (document.getElementById('_nutrimax-toast-styles')) return;
  const style = document.createElement('style');
  style.id = '_nutrimax-toast-styles';
  style.textContent = `
    /* ── Toast Container: fixed bottom-right ── */
    .nm-toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 99999;
      display: flex;
      flex-direction: column-reverse;
      gap: 10px;
      pointer-events: none;
      width: 260px;
    }

    /* ── Single Toast ── */
    .nm-toast {
      position: relative;
      overflow: hidden;
      border-radius: 14px;
      padding: 14px 14px 18px 14px;
      display: flex;
      align-items: flex-start;
      gap: 10px;
      pointer-events: all;
      font-family: inherit;
      font-size: 13px;
      font-weight: 600;
      line-height: 1.45;
      backdrop-filter: blur(18px) saturate(200%);
      -webkit-backdrop-filter: blur(18px) saturate(200%);
      border: 1px solid var(--nm-toast-border);
      border-left: 4px solid var(--nm-toast-bar-color, #7c3aed);
      box-shadow: 0 8px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.08);
      opacity: 0;
      transform: translateX(20px) scale(0.96);
      transition: opacity 0.26s cubic-bezier(.4,0,.2,1),
                  transform 0.26s cubic-bezier(.4,0,.2,1);
      will-change: transform, opacity;
      /* theme tokens (light defaults) */
      --nm-toast-border: var(--border, #e5e7eb);
      color: #111827;
    }
    .nm-toast.nm-visible {
      opacity: 1;
      transform: translateX(0) scale(1);
    }
    .nm-toast.nm-hide {
      opacity: 0;
      transform: translateX(16px) scale(0.95);
    }

    /* ── Type backgrounds: high contrast in light mode ── */
    .nm-toast.error {
      background: #fff1f1;
      --nm-toast-border: #fca5a5;
      --nm-toast-icon-color: #b91c1c;
      --nm-toast-bar-color: #ef4444;
      color: #7f1d1d;
    }
    .nm-toast.success {
      background: #f0fdf4;
      --nm-toast-border: #6ee7b7;
      --nm-toast-icon-color: #15803d;
      --nm-toast-bar-color: #22c55e;
      color: #14532d;
    }
    .nm-toast.info {
      background: #eff6ff;
      --nm-toast-border: #93c5fd;
      --nm-toast-icon-color: #1d4ed8;
      --nm-toast-bar-color: #3b82f6;
      color: #1e3a8a;
    }
    .nm-toast.warning {
      background: #fefce8;
      --nm-toast-border: #fde047;
      --nm-toast-icon-color: #b45309;
      --nm-toast-bar-color: #f59e0b;
      color: #78350f;
    }
    .nm-toast.default {
      background: #f8fafc;
      --nm-toast-border: var(--border, #e2e8f0);
      --nm-toast-icon-color: #475569;
      --nm-toast-bar-color: var(--primary, #7c3aed);
      color: #1e293b;
    }

    /* ── Dark theme overrides ── */
    [data-theme="dark"] .nm-toast.error {
      background: color-mix(in srgb, #1c0505 85%, transparent);
      --nm-toast-border: rgba(239,68,68,0.35);
    }
    [data-theme="dark"] .nm-toast.success {
      background: color-mix(in srgb, #052012 85%, transparent);
      --nm-toast-border: rgba(34,197,94,0.35);
    }
    [data-theme="dark"] .nm-toast.info {
      background: color-mix(in srgb, #030c1f 85%, transparent);
      --nm-toast-border: rgba(59,130,246,0.35);
    }
    [data-theme="dark"] .nm-toast.warning {
      background: color-mix(in srgb, #1a0e00 85%, transparent);
      --nm-toast-border: rgba(245,158,11,0.35);
    }
    [data-theme="dark"] .nm-toast.default {
      background: color-mix(in srgb, var(--bg-card, #121316) 85%, transparent);
    }
    [data-theme="dark"] .nm-toast {
      color: var(--text-main, #f1f5f9);
    }

    /* ── Icon wrapper ── */
    .nm-toast-icon {
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--nm-toast-icon-color, #6b7280);
      margin-top: 1px;
    }

    /* ── Progress Bar ── */
    .nm-toast-bar {
      position: absolute;
      top: 0;
      left: 0;
      height: 3px;
      width: 100%;
      border-radius: 14px 14px 0 0;
      transform-origin: left center;
      background: var(--nm-toast-bar-color, #7c3aed);
      transition: none;
    }
    .nm-toast-bar.nm-bar-anim {
      transition: transform linear;
      transform: scaleX(0);
    }
  `;
  document.head.appendChild(style);
})();

const _toastRegistry = new Map(); // key → { el, barEl, timer, hideTimer }
const TOAST_DURATION = 2800;
const TOAST_MAX = 2;

function _getOrCreateToastContainer() {
  let c = document.getElementById('_nm-toast-cnt');
  if (!c) {
    c = document.createElement('div');
    c.id = '_nm-toast-cnt';
    c.className = 'nm-toast-container';
    document.body.appendChild(c);
  }
  return c;
}

function _dismissToast(key) {
  const entry = _toastRegistry.get(key);
  if (!entry) return;
  clearTimeout(entry.timer);
  clearTimeout(entry.hideTimer);
  entry.el.classList.add('nm-hide');
  entry.hideTimer = setTimeout(() => {
    entry.el.remove();
    _toastRegistry.delete(key);
  }, 300);
}

function _startBar(barEl, duration) {
  barEl.classList.remove('nm-bar-anim');
  barEl.style.transitionDuration = '';
  barEl.style.transform = 'scaleX(1)';
  void barEl.offsetWidth; // force reflow
  barEl.classList.add('nm-bar-anim');
  barEl.style.transitionDuration = duration + 'ms';
  barEl.style.transform = 'scaleX(0)';
}

function showToast(msg, type = 'default', duration = TOAST_DURATION) {
  const key = `${type}::${msg}`;
  _getOrCreateToastContainer();

  // ── Duplicate: reset bar & timer ──
  if (_toastRegistry.has(key)) {
    const entry = _toastRegistry.get(key);
    clearTimeout(entry.timer);
    clearTimeout(entry.hideTimer);
    entry.el.classList.remove('nm-hide');
    _startBar(entry.barEl, duration);
    entry.timer = setTimeout(() => _dismissToast(key), duration);
    return;
  }

  // ── Enforce max 2 simultaneous ──
  if (_toastRegistry.size >= TOAST_MAX) {
    const oldestKey = _toastRegistry.keys().next().value;
    _dismissToast(oldestKey);
  }

  // ── Build toast element ──
  const toast = document.createElement('div');
  toast.className = `nm-toast ${type}`;

  const bar = document.createElement('div');
  bar.className = 'nm-toast-bar';
  toast.appendChild(bar);

  const iconWrap = document.createElement('span');
  iconWrap.className = 'nm-toast-icon';
  iconWrap.innerHTML = _TOAST_ICONS[type] || _TOAST_ICONS.default;
  toast.appendChild(iconWrap);

  const text = document.createElement('span');
  text.textContent = msg;
  toast.appendChild(text);

  document.getElementById('_nm-toast-cnt').appendChild(toast);

  // ── Animate in ──
  requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('nm-visible')));

  // ── Start progress bar ──
  setTimeout(() => _startBar(bar, duration), 30);

  // ── Auto-dismiss ──
  const timer = setTimeout(() => _dismissToast(key), duration);

  _toastRegistry.set(key, { el: toast, barEl: bar, timer, hideTimer: null });
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
    modal.onclick = function (e) { if (e.target === modal) modal.classList.remove('active'); };
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

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('./sw.js')
      .then(() => console.debug('SW registered'))
      .catch(err => console.warn('SW registration failed:', err));
  });
}

