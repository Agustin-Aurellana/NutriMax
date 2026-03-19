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
};

const GOALS_CONFIG = {
  definition:    { label: 'Definición',      icon: '🔥', calMod: -0.20, protein: 0.40, carbs: 0.35, fat: 0.25 },
  volume:        { label: 'Volumen',          icon: '💪', calMod:  0.15, protein: 0.30, carbs: 0.50, fat: 0.20 },
  maintenance:   { label: 'Mantenimiento',    icon: '⚖️', calMod:  0,    protein: 0.30, carbs: 0.45, fat: 0.25 },
  recomp:        { label: 'Recomposición',    icon: '🔄', calMod:  0,    protein: 0.40, carbs: 0.35, fat: 0.25 },
};

const ACTIVITY_MULTIPLIERS = {
  sedentary:    { label: 'Sedentario (sin ejercicio)',           value: 1.2   },
  light:        { label: 'Ligero (1-3 días/semana)',             value: 1.375 },
  moderate:     { label: 'Moderado (3-5 días/semana)',           value: 1.55  },
  active:       { label: 'Activo (6-7 días/semana)',             value: 1.725 },
  very_active:  { label: 'Muy activo (2 entrenamientos/día)',    value: 1.9   },
};

const MEAL_TYPES = ['Desayuno', 'Almuerzo', 'Cena', 'Snack'];
const MEAL_ICONS = { Desayuno: '🌅', Almuerzo: '🍽️', Cena: '🌙', Snack: '🍎' };

// ──────────────────────────────────────────
// 2. LOCALSTORAGE HELPERS
// ──────────────────────────────────────────
const store = {
  get: (key, fallback = null) => {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch { return fallback; }
  },
  set: (key, value) => {
    try { localStorage.setItem(key, JSON.stringify(value)); }
    catch (e) { console.error('store.set error', e); }
  },
  remove: (key) => localStorage.removeItem(key),
};

// ──────────────────────────────────────────
// 3. AUTH HELPERS
// ──────────────────────────────────────────
function getUser()        { return store.get(KEYS.USER); }
function isLoggedIn()     { return !!getUser(); }
function logout()         { store.remove(KEYS.USER); window.location.href = 'index.html'; }

function requireAuth() {
  if (!isLoggedIn()) { window.location.href = 'index.html'; return false; }
  return true;
}

function saveUser(profile) { store.set(KEYS.USER, profile); }

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

function calculateMacros(tdee, goal, weight) {
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
function todayKey() { return new Date().toISOString().split('T')[0]; }

function getLogs()      { return store.get(KEYS.LOGS, {}); }
function getTodayLog()  {
  const logs = getLogs();
  const key = todayKey();
  if (!logs[key]) logs[key] = { date: key, entries: [] };
  return logs[key];
}

function addFoodEntry(entry) {
  const logs = getLogs();
  const key = todayKey();
  if (!logs[key]) logs[key] = { date: key, entries: [] };
  entry.id = Date.now();
  logs[key].entries.push(entry);
  store.set(KEYS.LOGS, logs);
  return entry;
}

function removeFoodEntry(id) {
  const logs = getLogs();
  const key = todayKey();
  if (logs[key]) {
    logs[key].entries = logs[key].entries.filter(e => e.id !== id);
    store.set(KEYS.LOGS, logs);
  }
}

function getDailyTotals(log) {
  const entries = log?.entries ?? [];
  return entries.reduce((acc, e) => {
    acc.calories += (e.calories || 0);
    acc.protein  += (e.protein  || 0);
    acc.carbs    += (e.carbs    || 0);
    acc.fat      += (e.fat      || 0);
    return acc;
  }, { calories: 0, protein: 0, carbs: 0, fat: 0 });
}

function getWeekLogs() {
  const logs = getLogs();
  const result = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const key = d.toISOString().split('T')[0];
    result.push({ date: key, log: logs[key] ?? null });
  }
  return result;
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
  return all.filter(r => {
    const matchQ = !q || r.name.toLowerCase().includes(q) || r.tags?.some(t => t.toLowerCase().includes(q));
    const matchG = !goal || r.goals?.includes(goal) || goal === 'all';
    return matchQ && matchG;
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
  const icons = { success: '✅', error: '❌', default: 'ℹ️' };
  toast.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), duration + 300);
}

// ──────────────────────────────────────────
// 8. THEME ENGINE
// ──────────────────────────────────────────
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  store.set(KEYS.THEME, theme);
  // Update all toggle button icons
  document.querySelectorAll('.dark-toggle').forEach(btn => {
    btn.title = theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
    btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  });
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'light';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// Apply saved theme immediately (prevents flash)
(function initTheme() {
  const saved = store.get(KEYS.THEME, 'light');
  document.documentElement.setAttribute('data-theme', saved);
})();

// ──────────────────────────────────────────
// 9. SIDEBAR BUILDER (shared across pages)
// ──────────────────────────────────────────
function buildSidebar(activePage) {
  const user = getUser();
  const goals = getGoals();
  const goalLabel = goals ? (GOALS_CONFIG[goals.goal]?.label ?? 'Sin objetivo') : 'Configura tu objetivo';
  const initial = user?.name?.charAt(0)?.toUpperCase() ?? '?';
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

  const navItems = [
    { icon: '📊', label: 'Dashboard',  href: 'dashboard.html',  id: 'dashboard' },
    { icon: '📝', label: 'Registro',   href: 'food-log.html',   id: 'food-log'  },
    { icon: '🥗', label: 'Recetas',    href: 'recipes.html',    id: 'recipes'   },
    { icon: '🎯', label: 'Objetivos',  href: 'goals.html',      id: 'goals'     },
    { icon: '🤖', label: 'Coach IA',   href: 'ai-coach.html',   id: 'ai-coach'  },
  ];

  return `
    <div class="sidebar-logo">
      <div class="logo-icon">🥑</div>
      <div class="logo-text">Nutri<span>Max</span></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Principal</span>
      ${navItems.map(item => `
        <a href="${item.href}" class="nav-item ${activePage === item.id ? 'active' : ''}">
          <div class="nav-icon">${item.icon}</div>
          <span>${item.label}</span>
        </a>
      `).join('')}
    </nav>
    <button class="dark-toggle" onclick="toggleTheme()" title="${isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}">
      ${isDark ? '☀️' : '🌙'}
    </button>
    <div class="sidebar-user" onclick="logout()" title="Cerrar sesión">
      <div class="user-avatar">${initial}</div>
      <div class="user-info">
        <div class="user-name">${user?.name ?? 'Usuario'}</div>
        <div class="user-goal">${goalLabel}</div>
      </div>
    </div>
  `;
}

function initSidebar(activePage) {
  const el = document.getElementById('sidebar');
  if (el) el.innerHTML = buildSidebar(activePage);

  // Mobile toggle
  const toggle = document.getElementById('mobileToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }
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
  { id: 'ing_99', name: 'Café', emoji: '☕', category: 'Ingrediente', calories: 1, protein: 0.1, fat: 0.0, carbs: 0.0, servings: 1, time: 0 },
  { id: 'ing_100', name: 'Té', emoji: '🍵', category: 'Ingrediente', calories: 1, protein: 0.0, fat: 0.0, carbs: 0.0, servings: 1, time: 0 }
];
