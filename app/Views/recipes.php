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
  <title>Recetas — NutriMax</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
    .filter-chip {
      padding:7px 16px; border-radius:99px; font-size:13px; font-weight:600;
      background:var(--white); border:1.5px solid var(--gray-200); color:var(--gray-600);
      cursor:pointer; transition:var(--transition);
    }
    .filter-chip.active, .filter-chip:hover {
      background:var(--green-50); border-color:var(--green-400); color:var(--green-700);
    }
    .recipe-steps { list-style:none; display:flex; flex-direction:column; gap:10px; }
    .recipe-steps li { display:flex; gap:12px; align-items:flex-start; }
    .step-num {
      width:24px; height:24px; border-radius:50%; background:var(--green-500); color:white;
      font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .step-text { font-size:13px; color:var(--gray-700); line-height:1.5; padding-top:2px; }
    .ing-list { display:flex; flex-direction:column; gap:6px; }
    .ing-item { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--gray-700); }
    .ing-item::before { content:'•'; color:var(--green-500); font-size:18px; line-height:1; }
  </style>
<link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#111827">
  <meta name="view-transition" content="same-origin">
</head>
<body>

<div class="app-shell">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="main-content">
    <!-- Header -->
    <div class="page-header flex justify-between items-center mb-24" style="flex-wrap:wrap;gap:16px;">
      <div>
        <h1 class="page-title">Recetas Gourmet</h1>
        <p class="page-subtitle">Explora platos nutritivos diseñados para tus metas</p>
      </div>
      <button class="btn btn-primary" onclick="openCreateModal()">+ Crear Receta</button>
    </div>

    <!-- Search + Filters -->
    <div class="card mb-24" style="padding: 20px;">
      <div class="form-group mb-16">
        <div class="search-bar" id="recipeSearchBar">
          <span class="search-icon">??</span>
          <input type="text" id="recipeSearch" placeholder="Buscar por nombre, ingrediente o etiqueta..." />
        </div>
      </div>
      <div class="flex items-center gap-12" style="flex-wrap:wrap;">
        <span class="form-label" style="margin:0; font-size:12px; color:var(--text-muted);">FILTRAR POR META:</span>
        <div class="filter-bar" style="margin:0; gap:8px;">
          <button class="filter-chip active" data-goal="all">Todas</button>
          <button class="filter-chip" data-goal="definition">Definición</button>
          <button class="filter-chip" data-goal="volume">Volumen</button>
          <button class="filter-chip" data-goal="maintenance">Balance</button>
          <button class="filter-chip" data-goal="recomp">Recomp</button>
        </div>
      </div>
    </div>

    <!-- Recipe Grid -->
    <div class="grid-auto" id="recipeGrid"></div>
  </main>
</div>

<!-- View Recipe Modal -->
<div class="modal-overlay" id="viewModal">
  <div class="modal" style="max-width:650px;">
    <div class="modal-header">
      <h2 class="modal-title" id="viewTitle">Receta</h2>
      <button class="modal-close" onclick="closeViewModal()">?</button>
    </div>
    <div class="modal-body" id="viewBody" style="padding:0;"></div>
    <div class="modal-footer" style="background:var(--bg-app); border-top:1px solid var(--border-color);">
      <button class="btn btn-ghost" onclick="closeViewModal()">Cerrar</button>
      <button class="btn btn-primary" id="addToLogBtn">Añadir a mi Diario</button>
    </div>
  </div>
</div>

<!-- Create Recipe Modal -->
<div class="modal-overlay" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Nueva Creación</h2>
      <button class="modal-close" onclick="closeCreateModal()">?</button>
    </div>
    <div class="modal-body">
      <div class="form-group mb-16">
        <label class="form-label">Nombre</label>
        <input class="form-input" type="text" id="crName" placeholder="Bowl energético de salmón" />
      </div>
      <div class="grid-2 mb-16" style="gap:16px;">
        <div class="form-group">
          <label class="form-label">Categoría</label>
          <select class="form-select" id="crCategory">
            <option>Desayuno</option><option selected>Almuerzo</option><option>Cena</option><option>Snack</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Meta Recomendada</label>
          <select class="form-select" id="crGoal">
            <option value="definition">Definición</option>
            <option value="volume">Volumen</option>
            <option value="maintenance">Mantenimiento</option>
            <option value="recomp">Recomposición</option>
          </select>
        </div>
      </div>
      <div class="grid-2 mb-16" style="gap:16px;">
        <div class="form-group">
          <label class="form-label">Calorías (kcal)</label>
          <input class="form-input" type="number" id="crCals" oninput="updateMacroPreview()" />
        </div>
        <div class="form-group">
          <label class="form-label">Proteína (g)</label>
          <input class="form-input" type="number" id="crProtein" oninput="updateMacroPreview()" />
        </div>
      </div>
      <div class="grid-2 mb-16" style="gap:16px;">
        <div class="form-group">
          <label class="form-label">Carbohidratos (g)</label>
          <input class="form-input" type="number" id="crCarbs" oninput="updateMacroPreview()" />
        </div>
        <div class="form-group">
          <label class="form-label">Grasas (g)</label>
          <input class="form-input" type="number" id="crFat" oninput="updateMacroPreview()" />
        </div>
      </div>
      <div id="macroPrev" style="background:var(--primary-subtle); border-radius:var(--radius); padding:12px; margin-bottom:16px; display:none;">
        <span style="font-size:12px; font-weight:700; color:var(--primary);">CALORÍAS CALCULADAS: <span id="calcCals">0</span> kcal</span>
      </div>
      <div class="form-group">
        <label class="form-label">Ingredientes (uno por línea)</label>
        <textarea class="form-input" id="crIngredients" rows="4" placeholder="150g Salmón fresco..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeCreateModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="saveNewRecipe()">Guardar Receta</button>
    </div>
  </div>
</div>

<!-- Add to log sub-modal -->
<div class="modal-overlay" id="logMealModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-header">
      <h2 class="modal-title">¿A qué momento?</h2>
      <button class="modal-close" onclick="document.getElementById('logMealModal').classList.remove('open');">?</button>
    </div>
    <div class="modal-body" style="display:flex;flex-direction:column;gap:12px;" id="mealTypeButtons"></div>
  </div>
</div>

<script src="js/app.js"></script>
<script>
window.currentActivePage = 'recipes';
if (!requireAuth()) throw new Error('not logged in');
initSidebar('recipes');

let currentGoalFilter = 'all';
let currentSearch = '';
let selectedRecipe = null;
const recipeMap = new Map(); // Stores recipes by ID for safe onclick retrieval

function renderGrid() {
  const results = searchRecipes(currentSearch, currentGoalFilter);
  const gridEl = document.getElementById('recipeGrid');
  if (!results.length) {
    gridEl.innerHTML = `<div class="empty-state" style="grid-column:1/-1; padding: 60px 20px;">
      <div class="empty-state-icon">??</div>
      <div class="empty-state-title">No hay coincidencias</div>
      <div class="empty-state-desc">Prueba con otros términos o crea tu propia receta personalizada.</div>
    </div>`;
    return;
  }
  gridEl.innerHTML = results.map((r, i) => {
    const isSaved = isRecipeSaved(r.id) || r.saved;
    const categoryCls = r.category === 'Desayuno' ? 'amber' : r.category === 'Snack' ? 'purple' : 'green';
    // Store recipe in global map for safe retrieval (avoids JSON/HTML encoding issues in onclick)
    recipeMap.set(r.id, r);
    return `<div class="recipe-card" style="animation-delay:${(i*0.04).toFixed(2)}s" onclick="openViewModal('${r.id}')">
      <div class="recipe-card-img">${r.emoji ?? '???'}</div>
      <div class="recipe-card-body">
        <div class="flex justify-between items-start mb-8">
          <div class="recipe-card-title">${r.name}</div>
          ${isSaved ? '<span title="Guardada" style="font-size:16px;">?</span>' : ''}
        </div>
        <div class="recipe-card-tags mb-12">
          <span class="tag tag-${categoryCls}">${r.category ?? 'Almuerzo'}</span>
          ${(r.goals ?? []).slice(0,1).map(g => `<span class="tag tag-gray">${GOALS_CONFIG[g]?.label || g}</span>`).join('')}
        </div>
        <div class="recipe-card-macros">
          <div class="recipe-macro-item"><div class="macro-val">${r.calories}</div><div class="macro-key">kcal</div></div>
          <div class="recipe-macro-item"><div class="macro-val" style="color:var(--primary);">${r.protein}g</div><div class="macro-key">PROT</div></div>
          <div class="recipe-macro-item"><div class="macro-val" style="color:var(--text-main);">${r.carbs}g</div><div class="macro-key">CARBS</div></div>
          <div class="recipe-macro-item"><div class="macro-val" style="color:var(--text-main);">${r.fat}g</div><div class="macro-key">FAT</div></div>
        </div>
      </div>
    </div>`;
  }).join('');
}

// Filters
document.querySelectorAll('.filter-chip').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-chip').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    currentGoalFilter = this.dataset.goal;
    renderGrid();
  });
});
document.getElementById('recipeSearch').addEventListener('input', function() {
  currentSearch = this.value;
  renderGrid();
});

// View Modal
function openViewModal(idOrObj) {
  // Accept either a recipe ID string or a recipe object (for backwards compatibility)
  const r = (typeof idOrObj === 'string') ? recipeMap.get(idOrObj) : idOrObj;
  if (!r) return;
  selectedRecipe = r;
  document.getElementById('viewTitle').textContent = `${r.emoji ?? '???'} ${r.name}`;
  document.getElementById('viewBody').innerHTML = `
    <div style="padding:24px;">
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
        ${(r.tags ?? []).map(t => `<span class="tag tag-green">${t}</span>`).join('')}
        <span class="tag tag-gray">${r.category}</span>
      </div>
      <div class="grid-4 mb-24" style="background:var(--bg-app); border-radius:var(--radius); padding:20px;">
        <div style="text-align:center;"><div style="font-size:24px;font-weight:900;color:var(--text-main);">${r.calories}</div><div style="font-size:10px;color:var(--text-muted);font-weight:800;letter-spacing:1px;">KCAL</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:900;color:var(--primary);">${r.protein}g</div><div style="font-size:10px;color:var(--text-muted);font-weight:800;letter-spacing:1px;">PROT</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:900;color:var(--text-main);">${r.carbs}g</div><div style="font-size:10px;color:var(--text-muted);font-weight:800;letter-spacing:1px;">CARBS</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:900;color:var(--text-main);">${r.fat}g</div><div style="font-size:10px;color:var(--text-muted);font-weight:800;letter-spacing:1px;">FAT</div></div>
      </div>
      
      ${r.ingredients?.length ? `
      <div class="mb-24">
        <h4 class="form-label" style="font-size:14px; color:var(--text-main);">Ingredientes</h4>
        <div class="ing-list" style="margin-top:12px;">${r.ingredients.map(i => `<div class="ing-item">${i}</div>`).join('')}</div>
      </div>` : ''}
      
      ${r.steps?.length ? `
      <div>
        <h4 class="form-label" style="font-size:14px; color:var(--text-main);">Preparación</h4>
        <ul class="recipe-steps" style="margin-top:12px;">${r.steps.map((s,i) => `<li><div class="step-num">${i+1}</div><div class="step-text">${s}</div></li>`).join('')}</ul>
      </div>` : ''}
    </div>
  `;
  document.getElementById('addToLogBtn').onclick = () => openLogMealModal();
  document.getElementById('viewModal').classList.add('open');
}
function closeViewModal() { document.getElementById('viewModal').classList.remove('open'); }

function openLogMealModal() {
  document.getElementById('mealTypeButtons').innerHTML = MEAL_TYPES.map(t => `
    <button class="btn btn-secondary" style="width:100%; justify-content:flex-start; gap:12px; font-weight:600;" onclick="addRecipeToLog('${t}')">
      <span style="font-size:20px;">${MEAL_ICONS[t]}</span> ${t}
    </button>`).join('');
  document.getElementById('logMealModal').classList.add('open');
}
function addRecipeToLog(mealType) {
  if (!selectedRecipe) return;
  addFoodEntry({ mealType, name: selectedRecipe.name, calories: selectedRecipe.calories,
    protein: selectedRecipe.protein, carbs: selectedRecipe.carbs, fat: selectedRecipe.fat });
  document.getElementById('logMealModal').classList.remove('open');
  closeViewModal();
  showToast(`"${selectedRecipe.name}" añadida`, 'success');
}

function openCreateModal()  { clearCreateForm(); document.getElementById('createModal').classList.add('open'); }
function closeCreateModal() { document.getElementById('createModal').classList.remove('open'); }
function clearCreateForm()  { ['crName','crCals','crProtein','crCarbs','crFat','crIngredients'].forEach(id => document.getElementById(id).value = ''); document.getElementById('macroPrev').style.display = 'none'; }

function updateMacroPreview() {
  const p = parseFloat(document.getElementById('crProtein').value) || 0;
  const c = parseFloat(document.getElementById('crCarbs').value) || 0;
  const f = parseFloat(document.getElementById('crFat').value) || 0;
  const calc = Math.round(p * 4 + c * 4 + f * 9);
  const prev = document.getElementById('macroPrev');
  if (p || c || f) { prev.style.display = 'block'; document.getElementById('calcCals').textContent = calc; }
  else { prev.style.display = 'none'; }
}

function saveNewRecipe() {
  const name       = document.getElementById('crName').value.trim();
  const category   = document.getElementById('crCategory').value;
  const goal       = document.getElementById('crGoal').value;
  const calories   = parseFloat(document.getElementById('crCals').value) || 0;
  const protein    = parseFloat(document.getElementById('crProtein').value) || 0;
  const carbs      = parseFloat(document.getElementById('crCarbs').value) || 0;
  const fat        = parseFloat(document.getElementById('crFat').value) || 0;
  const ingredients = document.getElementById('crIngredients').value.split('\n').filter(Boolean);
  if (!name || !calories) { showToast('Nombre y calorías son requeridos', 'error'); return; }
  saveUserRecipe({ name, category, goals: [goal], calories, protein, carbs, fat, ingredients, emoji: '??' });
  closeCreateModal();
  renderGrid();
  showToast('Receta guardada', 'success');
}

['viewModal','createModal','logMealModal'].forEach(id => {
  document.getElementById(id).addEventListener('click', e => {
    if (e.target === document.getElementById(id)) document.getElementById(id).classList.remove('open');
  });
});

renderGrid();
</script>
</body>
</html>



