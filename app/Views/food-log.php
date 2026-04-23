<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="assets/img/launchericon-192x192.png" type="image/png">
  <link rel="apple-touch-icon" href="assets/img/launchericon-192x192.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NutriMax">

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Registro de Comidas — NutriMAx</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .day-total-bar {
      background: var(--white); border-radius: var(--radius);
      border: 1px solid var(--gray-100); padding: 20px 24px;
      margin-bottom: 24px; box-shadow: var(--shadow-sm);
    }
    .meal-section { margin-bottom: 20px; }
    .meal-section-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 16px; background: var(--gray-50);
      border-radius: var(--radius-sm) var(--radius-sm) 0 0;
      border: 1px solid var(--gray-200); border-bottom: none;
    }
    .meal-section-title { font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; }
    .meal-section-cals { font-size:13px; font-weight:600; color:var(--green-600); }
    .meal-section-body {
      border: 1px solid var(--gray-200); border-radius: 0 0 var(--radius-sm) var(--radius-sm);
      overflow: hidden; background: var(--white);
    }
    .search-results { position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:50;
      background:var(--white); border-radius:var(--radius-sm); box-shadow:var(--shadow-lg);
      border:1px solid var(--gray-200); max-height:300px; overflow-y:auto; }
    .search-result-item { padding:12px 16px; cursor:pointer; transition:var(--transition);
      display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .search-result-item:hover { background:var(--green-50); }
    .search-result-item:not(:last-child) { border-bottom:1px solid var(--gray-100); }
    .sri-name { font-size:14px; font-weight:600; }
    .sri-meta { font-size:12px; color:var(--gray-500); }
  </style>
<link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#111827">
  <meta name="view-transition" content="same-origin">
</head>
<body>

<div class="app-shell">
  <aside class="sidebar" id="sidebar"></aside>

  <main class="main-content">
    <div class="page-header flex justify-between items-center" style="flex-wrap:wrap;gap:12px;">
      <div>
        <h1 class="page-title">Registro de Comidas</h1>
        <p class="page-subtitle" id="dateLabel"></p>
      </div>
    </div>
    <div class="day-total-summary grid-4 mb-24" id="dayBadges"></div>
    
    <div class="card mb-24">
      <div class="card-title">Metas del Día</div>
      <div class="card-subtitle">Progreso acumulado hoy</div>
      <div style="display:flex;flex-direction:column;gap:16px;margin-top:20px;" id="dayProgressBars"></div>
    </div>

    <!-- Meal sections -->
    <div id="mealSections"></div>
  </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="addFoodModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="addFoodModalTitle">Añadir comida</h2>
      <button class="modal-close" onclick="closeModal()">?</button>
    </div>
    <div class="modal-body">
      <div class="form-group mb-20" style="position:relative;">
        <label class="form-label">Buscar en base de datos</label>
        <div class="search-bar" id="foodSearchBar">
          <span class="search-icon">??</span>
          <input type="text" id="foodSearchInput" placeholder="Ej: Pollo, Avena..." autocomplete="off" />
        </div>
        <div class="search-results hidden" id="searchResults"></div>
      </div>

      <div class="divider-text mb-20">O INGRESAR MANUALMENTE</div>
      
      <div class="form-group mb-16">
        <label class="form-label">Nombre</label>
        <input class="form-input" type="text" id="customName" placeholder="Tostadas con aguacate" />
      </div>
      
      <div class="grid-2 mb-16" style="gap:16px;">
        <div class="form-group">
          <label class="form-label">Calorías (kcal)</label>
          <input class="form-input" type="number" id="customCals" placeholder="0" />
        </div>
        <div class="form-group">
          <label class="form-label">Proteína (g)</label>
          <input class="form-input" type="number" id="customProtein" placeholder="0" />
        </div>
      </div>
      
      <div class="grid-2" style="gap:16px;">
        <div class="form-group">
          <label class="form-label">Carbos (g)</label>
          <input class="form-input" type="number" id="customCarbs" placeholder="0" />
        </div>
        <div class="form-group">
          <label class="form-label">Grasas (g)</label>
          <input class="form-input" type="number" id="customFat" placeholder="0" />
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="confirmAddFood()">Guardar Alimento</button>
    </div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
window.currentActivePage = 'food-log';
if (!requireAuth()) throw new Error('not logged in');
initSidebar('food-log');
function updateDateLabel() {
  const dateStr = formatDate(todayKey());
  const location = getLocation();
  document.getElementById('dateLabel').innerHTML = location 
    ? `${dateStr} <span style="opacity:0.6;">•</span> <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> ${location}</span>`
    : dateStr;
}
updateDateLabel();
syncLocation();

window.addEventListener('locationUpdated', () => {
  updateDateLabel();
});

const foodMap = new Map(); // Stores recipes by id to avoid JSON/HTML encoding in onclick
let activeMealType = 'Desayuno';
const goals = getGoals();
const targets = goals?.targets ?? { calories: 2000, protein: 150, carbs: 225, fat: 55 };

function renderPage() {
  const log    = getTodayLog();
  const totals = getDailyTotals(log);
  renderTotals(totals);
  renderMealSections(log);
}

function renderTotals(totals) {
  const badges = [
    { label:'Calorías', val: totals.calories, target: targets.calories, unit:'kcal', primary:true },
    { label:'Proteína', val: totals.protein,  target: targets.protein,  unit:'g',    cls:'protein' },
    { label:'Carbos',   val: totals.carbs,    target: targets.carbs,    unit:'g',    cls:'carbs'   },
    { label:'Grasas',   val: totals.fat,      target: targets.fat,      unit:'g',    cls:'fat'     },
  ];
  document.getElementById('dayBadges').innerHTML = badges.map(b => `
    <div class="stat-card ${b.primary ? 'primary' : ''}">
      <div class="stat-label">${b.label}</div>
      <div class="stat-value" style="font-size:20px;">${b.val}<span class="stat-unit" style="font-size:12px;">/${b.target}${b.unit}</span></div>
    </div>`).join('');

  document.getElementById('dayProgressBars').innerHTML = badges.map(b => `
    <div class="macro-progress-item">
      <div class="flex justify-between items-center mb-8">
        <span class="form-label">${b.label}</span>
        <span class="text-sm font-bold">${b.val}${b.unit} <span class="text-gray" style="font-weight:500;">/ ${b.target}${b.unit}</span></span>
      </div>
      <div class="progress-bar-wrap" style="height:10px;">
        <div class="progress-bar-fill ${b.cls || ''}" style="width:${pct(b.val, b.target)}%;${b.primary?'background:var(--primary)':''}"></div>
      </div>
    </div>`).join('');
}

function renderMealSections(log) {
  const entries = log.entries ?? [];
  const byMeal = {};
  MEAL_TYPES.forEach(t => byMeal[t] = []);
  entries.forEach(e => { if (byMeal[e.mealType] !== undefined) byMeal[e.mealType].push(e); });

  document.getElementById('mealSections').innerHTML = MEAL_TYPES.map(type => {
    const items = byMeal[type];
    const total = items.reduce((s, e) => s + (e.calories || 0), 0);
    const rows = items.length ? items.map(e => `
      <div class="food-row">
        <div class="food-row-info">
          <div class="food-row-name">${e.name}</div>
          <div class="food-row-meta">P: ${e.protein}g · C: ${e.carbs}g · G: ${e.fat}g</div>
        </div>
        <div class="food-row-cals">${e.calories} kcal</div>
        <button class="food-row-del" onclick="removeEntry(${e.id})" title="Eliminar">?</button>
      </div>`).join('')
      : `<div class="empty-state" style="padding:40px 20px;">
          <div class="empty-state-icon" style="font-size:32px;">???</div>
          <div class="empty-state-desc">No has registrado nada en el ${type.toLowerCase()}</div>
        </div>`;

    return `
    <div class="card mb-20" style="padding:0; overflow:hidden;">
      <div class="flex justify-between items-center" style="padding:16px 20px; background:var(--bg-app); border-bottom:1px solid var(--border-color);">
        <div style="display:flex; align-items:center; gap:12px;">
          <span style="font-size:20px;">${MEAL_ICONS[type]}</span>
          <div>
            <div class="card-title" style="margin:0; font-size:16px;">${type}</div>
            <div class="card-subtitle" style="margin:0;">${total} kcal totales</div>
          </div>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="openAddModal('${type}')">+ Añadir</button>
      </div>
      <div class="meal-body">${rows}</div>
    </div>`;
  }).join('');
}

function removeEntry(id) {
  removeFoodEntry(id);
  renderPage();
  showToast('Alimento eliminado', 'success');
}

function openAddModal(mealType) {
  activeMealType = mealType;
  const label = { Desayuno: 'Desayuno', Almuerzo: 'Almuerzo', Cena: 'Cena', Snack: 'Snack' }[mealType] || mealType;
  document.getElementById('addFoodModalTitle').textContent = `Agregar a ${label}`;
  clearModal();
  document.getElementById('addFoodModal').classList.add('open');
  setTimeout(() => document.getElementById('foodSearchInput').focus(), 100);
}

function closeModal() {
  document.getElementById('addFoodModal').classList.remove('open');
  document.getElementById('searchResults').classList.add('hidden');
}
function clearModal() {
  ['customName','customCals','customProtein','customCarbs','customFat'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('foodSearchInput').value = '';
}

document.getElementById('foodSearchInput').addEventListener('input', function() {
  const q = this.value.trim();
  const resultsEl = document.getElementById('searchResults');
  if (!q) { resultsEl.classList.add('hidden'); return; }
  const results = searchRecipes(q);
  if (!results.length) { 
    resultsEl.innerHTML = '<div style="padding:14px;color:var(--text-muted);font-size:13px;font-weight:600;">Sin resultados</div>'; 
    resultsEl.classList.remove('hidden'); 
    return; 
  }
  resultsEl.innerHTML = results.slice(0, 8).map(r => {
    foodMap.set(r.id, r);
    return `<div class="search-result-item" onclick="selectRecipe('${r.id}')">
      <div>
        <div class="sri-name">${r.emoji ?? '???'} ${r.name}</div>
        <div class="sri-meta">P: ${r.protein}g · C: ${r.carbs}g · G: ${r.fat}g</div>
      </div>
      <span style="font-size:14px;font-weight:700;color:var(--primary);">${r.calories} kcal</span>
    </div>`;
  }).join('');
  resultsEl.classList.remove('hidden');
});

function selectRecipe(idOrObj) {
  const r = (typeof idOrObj === 'string') ? foodMap.get(idOrObj) : idOrObj;
  if (!r) return;
  document.getElementById('customName').value    = r.name;
  document.getElementById('customCals').value    = r.calories;
  document.getElementById('customProtein').value = r.protein;
  document.getElementById('customCarbs').value   = r.carbs;
  document.getElementById('customFat').value     = r.fat;
  document.getElementById('foodSearchInput').value = '';
  document.getElementById('searchResults').classList.add('hidden');
  showToast('Receta seleccionada', 'success');
}

function confirmAddFood() {
  const name    = document.getElementById('customName').value.trim();
  const calories = parseFloat(document.getElementById('customCals').value) || 0;
  const protein  = parseFloat(document.getElementById('customProtein').value) || 0;
  const carbs    = parseFloat(document.getElementById('customCarbs').value) || 0;
  const fat      = parseFloat(document.getElementById('customFat').value) || 0;
  if (!name) { showToast('Ingresa un nombre para el alimento', 'error'); return; }
  if (!calories) { showToast('Ingresa las calorías', 'error'); return; }
  addFoodEntry({ mealType: activeMealType, name, calories, protein, carbs, fat });
  closeModal();
  renderPage();
  showToast(`${name} añadido`, 'success');
}

document.getElementById('addFoodModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.addEventListener('click', e => {
  if (!e.target.closest('#addFoodModal')) return;
  if (!e.target.closest('#foodSearchBar') && !e.target.closest('#searchResults')) {
    document.getElementById('searchResults').classList.add('hidden');
  }
});

renderPage();
</script>
</body>
</html>



