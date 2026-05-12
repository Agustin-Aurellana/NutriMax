<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="assets/img/launchericon-192x192.png" type="image/png">
  <link rel="apple-touch-icon" href="assets/img/launchericon-192x192.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NutriMax">

  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Dashboard — NutriMax</title>
  <meta name="description" content="Tu panel de control de nutrición diaria." />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#111827">
  <meta name="view-transition" content="same-origin">
  <!-- Barcode Scanner Library -->
  <script src="https://unpkg.com/html5-qrcode"></script>
</head>

<body>
  <!-- Premium Splash Screen -->
  <div class="splash-screen" id="appSplashScreen">
    <img src="assets/img/logo-sinfondo-color.png" class="splash-logo" alt="NutriMax Logo">
  </div>

  <!-- Daily Summary Modal -->
  <div class="summary-modal" id="dailySummaryModal" onclick="this.classList.remove('active')">
    <div class="summary-title" id="summaryTitle">Hoy</div>
    <div class="summary-subtitle">Resumen</div>
    <div class="rings-top">
      <div class="summary-ring">
        <svg>
          <circle cx="45" cy="45" r="40" fill="#111827" fill-opacity="0.6" stroke="rgba(255,255,255,0.03)"
            stroke-width="6" />
          <circle id="ring-car" cx="45" cy="45" r="40" fill="transparent" stroke="#fbbf24" stroke-width="6"
            stroke-dasharray="251.2" stroke-dashoffset="251.2"
            style="transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); stroke-linecap: round;" />
        </svg>
        <div class="summary-ring-val" id="val-car">0</div>
        <div class="summary-ring-lbl" style="color: #fbbf24; opacity: 0.9;">Carbos</div>
      </div>
      <div class="summary-ring">
        <svg>
          <circle cx="45" cy="45" r="40" fill="#111827" fill-opacity="0.6" stroke="rgba(255,255,255,0.03)"
            stroke-width="6" />
          <circle id="ring-fat" cx="45" cy="45" r="40" fill="transparent" stroke="#f87171" stroke-width="6"
            stroke-dasharray="251.2" stroke-dashoffset="251.2"
            style="transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); stroke-linecap: round;" />
        </svg>
        <div class="summary-ring-val" id="val-fat">0</div>
        <div class="summary-ring-lbl" style="color: #f87171; opacity: 0.9;">Grasas</div>
      </div>
      <div class="summary-ring">
        <svg>
          <circle cx="45" cy="45" r="40" fill="#111827" fill-opacity="0.6" stroke="rgba(255,255,255,0.03)"
            stroke-width="6" />
          <circle id="ring-pro" cx="45" cy="45" r="40" fill="transparent" stroke="#818cf8" stroke-width="6"
            stroke-dasharray="251.2" stroke-dashoffset="251.2"
            style="transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); stroke-linecap: round;" />
        </svg>
        <div class="summary-ring-val" id="val-pro">0</div>
        <div class="summary-ring-lbl" style="color: #818cf8; opacity: 0.9;">Proteína</div>
      </div>
    </div>
    <div class="rings-bottom" style="display: flex; justify-content: center; gap: 40px; margin-top: 20px;">
      <div class="summary-ring">
        <svg>
          <circle cx="45" cy="45" r="40" fill="#111827" fill-opacity="0.6" stroke="rgba(255,255,255,0.03)"
            stroke-width="6" />
          <circle id="ring-cal" cx="45" cy="45" r="40" fill="transparent" stroke="#4ade80" stroke-width="6"
            stroke-dasharray="251.2" stroke-dashoffset="251.2"
            style="transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); stroke-linecap: round;" />
        </svg>
        <div class="summary-ring-val" id="val-cal">0</div>
        <div class="summary-ring-lbl" style="color: #4ade80; opacity: 0.9;">Calorías</div>
      </div>
      <div class="summary-ring">
        <svg>
          <circle cx="45" cy="45" r="40" fill="#111827" fill-opacity="0.6" stroke="rgba(255,255,255,0.03)"
            stroke-width="6" />
          <circle id="ring-water" cx="45" cy="45" r="40" fill="transparent" stroke="#3b82f6" stroke-width="6"
            stroke-dasharray="251.2" stroke-dashoffset="251.2"
            style="transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1); stroke-linecap: round;" />
        </svg>
        <div class="summary-ring-val" id="val-water">0</div>
        <div class="summary-ring-lbl" style="color: #3b82f6; opacity: 0.9;">Agua</div>
      </div>
    </div>
  </div>

  <div class="app-shell">
    <aside class="sidebar" id="sidebar"></aside>

    <main class="main-content" style="padding: 16px 16px 90px 16px; max-width: 600px; margin: 0 auto;">
      <!-- Weekly Header -->
      <!-- Calendar picker modal -->
      <div id="calPickerOverlay"
        style="display:none; position:fixed; inset:0; z-index:4000; background:rgba(0,0,0,0.55); backdrop-filter:blur(6px); align-items:flex-end; justify-content:center;"
        onclick="closeCalPicker(event)">
        <div id="calPickerSheet"
          style="background:var(--bg-card); border-radius:24px 24px 0 0; width:100%; max-width:480px; padding:20px 20px 32px; box-shadow:0 -4px 40px rgba(0,0,0,.18); transform:translateY(100%); transition:transform .35s cubic-bezier(.22,1,.36,1);">
          <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <button id="calPrevMonth" onclick="shiftCalMonth(-1)"
              style="width:36px;height:36px;border-radius:50%;border:1px solid var(--border);background:var(--bg-app);color:var(--text-main);font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">&#8249;</button>
            <div id="calMonthLabel" style="font-size:16px;font-weight:800;color:var(--text-main);"></div>
            <button id="calNextMonth" onclick="shiftCalMonth(1)"
              style="width:36px;height:36px;border-radius:50%;border:1px solid var(--border);background:var(--bg-app);color:var(--text-main);font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;">&#8250;</button>
          </div>
          <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:8px;">
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">L</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">M</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">M</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">J</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">V</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">S</div>
            <div style="text-align:center;font-size:11px;font-weight:800;color:var(--text-muted);padding:4px 0;">D</div>
          </div>
          <div id="calGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;"></div>
          <button onclick="jumpToday()"
            style="margin-top:16px;width:100%;padding:12px;border-radius:99px;border:none;background:var(--primary);color:#fff;font-weight:800;font-size:14px;cursor:pointer;">Ir
            a Hoy</button>
        </div>
      </div>

      <div class="fitia-week-header" id="weekHeader" onclick="openCalPicker()" style="cursor:pointer;"
        title="Ver calendario"></div>

      <!-- Macro Arc Card -->
      <div class="fitia-macro-card" id="mainMacroCard"></div>

      <!-- Water Tracker -->
      <div id="waterTracker" class="water-card">
        <div class="water-header">
          <div class="water-title">Agua</div>
          <div class="water-arrow">›</div>
        </div>
        <div class="water-stats-row">
          <div class="water-liters" id="waterLitersDisplay">
            <span class="water-icon">🥛</span> 0.0 <span class="water-target-label">/ 0.0 L</span>
          </div>
          <div class="water-glasses-text" id="waterGlassesDisplay">0 de 0 vasos</div>
        </div>
        <div class="water-progress-container">
          <div class="water-progress-fill" id="waterProgressFill" style="width: 0%;"></div>
        </div>
        <div class="water-controls">
          <button class="water-btn minus" onclick="changeWater(-1)">—</button>
          <button class="water-btn plus" onclick="changeWater(1)">+</button>
        </div>
      </div>

      <!-- Meals List -->
      <div id="mealSections"></div>
    </main>
  </div>

  <!-- Top-Sheet Modal -->
  <div class="top-sheet-overlay" id="addFoodModal">
    <div class="top-sheet">
      <div class="top-sheet-header">
        <div style="font-weight:800; font-size:18px;" id="addFoodModalTitle">Agregar Comida</div>
        <button class="modal-close" onclick="closeAddFoodModal()"
          style="width:32px; height:32px; border-radius:50%; border:none; background:var(--gray-100); cursor:pointer;">✕</button>
      </div>

      <div class="top-sheet-tabs">
        <button class="ts-tab active" id="tabBtnDb" onclick="switchTab('db')">Base de Datos</button>
        <button class="ts-tab" id="tabBtnRecipes" onclick="switchTab('recipes')">Recetas</button>
      </div>

      <div class="ts-content active" id="tabDb">
        <div class="search-bar mb-16" id="foodSearchBar">
          <span class="search-icon">🔍</span>
          <input type="text" id="foodSearchInput" placeholder="Buscar alimento..." autocomplete="off"
            style="width:100%">
        </div>
        
        <button class="scan-btn-mobile mb-16" id="startScanBtn" onclick="toggleScanner()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
            <path d="M7 8h10M7 12h10M7 16h10"></path>
          </svg>
          Escanear Código de Barras
        </button>

        <div id="barcodeScannerContainer" style="display:none;"></div>

        <div class="search-results hidden" id="searchResults"
          style="position:relative; box-shadow:none; border:none; margin-bottom:16px;"></div>

        <div class="divider-text mb-16"
          style="font-size:11px; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; text-align:center;">
          O Ingreso Manual</div>
        <div class="form-group mb-12">
          <input class="form-input" type="text" id="customName" placeholder="Nombre (Ej: Manzana 100g)" />
        </div>
        <div class="grid-4" style="gap:8px; margin-bottom:24px;">
          <input class="form-input" type="number" id="customCals" placeholder="Kcal" />
          <input class="form-input" type="number" id="customProtein" placeholder="Prot" />
          <input class="form-input" type="number" id="customCarbs" placeholder="Carb" />
          <input class="form-input" type="number" id="customFat" placeholder="Grasa" />
        </div>
        <button class="btn btn-primary w-full btn-lg" onclick="confirmAddFood()"
          style="border-radius:0px; font-size:16px;">Guardar Alimento</button>
      </div>

      <div class="ts-content" id="tabRecipes">
        <button class="btn btn-primary w-full btn-lg mb-16" onclick="openCreateRecipeModal()" style="border-radius:12px; font-size:15px; margin-bottom:16px;">+ Crear Nueva Receta</button>
        <div id="recipesGrid" style="display:flex; flex-direction:column; gap:12px;"></div>
      </div>
    </div>
  </div>

  <!-- Specific Modal for Creating Recipes -->
  <div class="top-sheet-overlay" id="createRecipeModal" style="z-index: 3000;">
    <div class="top-sheet" style="max-height: 92vh; overflow-y: auto;">
      <div class="top-sheet-header">
        <div style="font-weight:800; font-size:18px;">Constructor de Recetas</div>
        <button class="modal-close" onclick="closeCreateRecipeModal()"
          style="width:32px; height:32px; border-radius:50%; border:none; background:var(--gray-100); cursor:pointer;">✕</button>
      </div>

      <div class="ts-content-recipe active" style="padding: 24px 20px 40px; background: var(--bg-card); display: block; min-height: 400px;">
        <div class="form-group mb-16">
          <label class="form-label" style="font-size:12px; color:var(--primary); font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom: 8px; display: block;">1. Información Básica</label>
          <input class="form-input" type="text" id="recipeName" placeholder="Nombre (Ej: Bowl de Pollo y Arroz)" style="font-size:16px; font-weight:700; width: 100%;" />
        </div>

        <!-- Macro Summary Board -->
        <div style="background:var(--bg-app); border-radius:16px; padding:16px; margin-bottom:20px; border:1px solid var(--border); display:flex; justify-content:space-around; text-align:center;">
          <div>
            <div style="font-size:18px; font-weight:900; color:var(--text-main);" id="recipeTotalCals">0</div>
            <div style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Kcal</div>
          </div>
          <div>
            <div style="font-size:18px; font-weight:900; color:var(--primary);" id="recipeTotalPro">0g</div>
            <div style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Prot</div>
          </div>
          <div>
            <div style="font-size:18px; font-weight:900; color:var(--orange-500);" id="recipeTotalCarb">0g</div>
            <div style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Carb</div>
          </div>
          <div>
            <div style="font-size:18px; font-weight:900; color:var(--red-500);" id="recipeTotalFat">0g</div>
            <div style="font-size:10px; font-weight:800; color:var(--text-muted); text-transform:uppercase;">Grasa</div>
          </div>
        </div>

        <label class="form-label" style="font-size:12px; color:var(--primary); font-weight:800; text-transform:uppercase; letter-spacing:1px; margin-bottom: 8px; display: block;">2. Añadir Ingredientes</label>
        <div class="search-bar mb-12" style="position: relative;">
          <span class="search-icon">🔍</span>
          <input type="text" id="recipeIngSearch" placeholder="Buscar ingrediente..." autocomplete="off" style="width: 100%;" />
        </div>
        <div id="recipeIngResults" class="hidden" style="max-height:200px; overflow-y:auto; border:1px solid var(--border); border-radius:12px; margin-bottom:12px; background:var(--bg-app); position: relative; z-index: 10;"></div>

        <div id="selectedIngList" style="display:flex; flex-direction:column; gap:8px; margin: 16px 0 32px; min-height: 50px;">
           <!-- Dynamic ingredients list -->
        </div>

      <button class="btn btn-primary w-full btn-lg" onclick="saveNewRecipe()"
          style="border-radius:16px; font-size:16px; height:56px; width: 100%; box-shadow:0 4px 15px rgba(99,102,241,0.3);">Finalizar y Guardar Receta</button>
      </div>
      </div>
    </div>
  </div>

  <script src="js/config.local.js"></script>
  <script src="js/app.js"></script>
  <script>
    window.currentActivePage = 'dashboard';
    if (!requireAuth()) throw new Error('not logged in');
    initSidebar('dashboard');

    // ── Guard: reset to today if the real calendar day changed
    let _lastKnownDay = todayKey();
    function checkDayRollover() {
      const nowKey = todayKey();
      if (nowKey !== _lastKnownDay) {
        _lastKnownDay = nowKey;
        viewingDateStr = nowKey;
        renderPage();
      }
    }
    setInterval(checkDayRollover, 60000);

    let viewingDateStr = todayKey();
    let activeMealType = 'Desayuno';
    const foodMap = new Map();
    const goals = getGoals();
    const targets = goals?.targets ?? { calories: 2000, protein: 150, carbs: 225, fat: 55 };

    // Cambiar la fecha actual mostrada en el dashboard (avanzar o retroceder días)
    function changeDay(offset) {
      const d = new Date(viewingDateStr + 'T12:00:00');
      d.setDate(d.getDate() + offset);
      viewingDateStr = d.toISOString().slice(0, 10);
      renderPage();
    }

    // Saltar a una fecha específica desde el calendario (evita seleccionar días futuros)
    function goToDate(dateStr) {
      if (dateStr > todayKey()) return;
      viewingDateStr = dateStr;
      _closeCalPicker();
      renderPage();
    }

    // Función principal de renderizado: Actualiza todos los componentes visuales de la vista actual
    function renderPage() {
      const log = getLogForDate(viewingDateStr);
      const totals = getDailyTotals(log);
      renderWeekHeader();
      renderMacroCard(totals);
      renderWaterTracker();
      renderMealSections(log);
    }

    // ── 0. Rastreador de Agua (Water Tracker)
    // Calcula y dibuja la barra de progreso del consumo de agua
    function renderWaterTracker() {
      const user = getUser();
      const goal = calculateWaterGoal(user);
      const current = getWaterIntake(viewingDateStr);
      
      const litersCurrent = (current * 0.25).toFixed(1);
      const litersGoal = (goal * 0.25).toFixed(1);
      const percentage = Math.min(100, (current / goal) * 100);

      document.getElementById('waterLitersDisplay').innerHTML = `<span style="font-size:24px;">🥛</span> ${litersCurrent} <span style="margin-left: -5px; color:#888;">/ ${litersGoal} L</span>`;
      document.getElementById('waterGlassesDisplay').textContent = `${current} de ${goal} vasos`;
      document.getElementById('waterProgressFill').style.width = `${percentage}%`;
    }

    // Aumentar o disminuir la cantidad de vasos de agua registrados
    function changeWater(delta) {
      let current = getWaterIntake(viewingDateStr);
      const newCount = Math.max(0, current + delta);
      setWaterIntake(viewingDateStr, newCount);
      renderWaterTracker();
      
      if (viewingDateStr === todayKey()) {
        if (delta > 0) showToast('¡Agua añadida! 💧', 'info');
      }
    }

    // ── 1. Week Header
    function renderWeekHeader() {
      const days = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
      const now = new Date();
      const todayIdx = (now.getDay() + 6) % 7;

      let html = '';
      for (let i = 0; i < 7; i++) {
        const d = new Date(now);
        d.setDate(d.getDate() - (todayIdx - i));
        const k = d.toISOString().slice(0, 10);
        const score = calculateDayCompletion(k);
        const isViewing = k === viewingDateStr;

        let dotClass = 'dot';
        if (i === todayIdx) dotClass += ' today';
        else if (score > 10) dotClass += ' done';

        html += `
          <div class="fitia-week-day ${i === todayIdx ? 'today' : ''} ${isViewing && i !== todayIdx ? 'viewing' : ''}"
               onclick="goToDate('${k}')" style="cursor:pointer;">
            ${days[i]}
            <div class="${dotClass}"></div>
          </div>`;
      }
      document.getElementById('weekHeader').innerHTML = html;
    }

    // ── 2. Macro Card
    function renderMacroCard(totals) {
      const remaining = Math.max(0, targets.calories - totals.calories);
      const pctCal = Math.min(100, Math.max(0, pct(totals.calories, targets.calories)));

      const arcHTML = `
        <svg viewBox="0 0 200 100" style="width:100%; height:100%; overflow:visible;">
          <path d="M 20 90 A 70 70 0 0 1 180 90" fill="none" stroke="var(--gray-200)" stroke-width="12" stroke-linecap="round" />
          <path d="M 20 90 A 70 70 0 0 1 180 90" fill="none" stroke="var(--primary)" stroke-width="12" stroke-linecap="round"
            stroke-dasharray="251.2" stroke-dashoffset="${251.2 - (251.2 * pctCal / 100)}"
            style="transition: stroke-dashoffset 1s ease-out;" />
        </svg>`;

      let dateLabel = 'Hoy';
      if (viewingDateStr !== todayKey()) {
        const fDate = formatDate(viewingDateStr);
        dateLabel = fDate.split(',')[0].charAt(0).toUpperCase() + fDate.split(',')[0].slice(1) + ' ' + fDate.split(' ')[1];
      }
      
      const currentDayWeight = getWeightForDate(viewingDateStr);

      document.getElementById('mainMacroCard').innerHTML = `
        <div style="font-weight:800; font-size:18px; text-align:left; color:var(--text-main); display:flex; justify-content:space-between; align-items:center;">
          <div onclick="promptDayWeight()" style="cursor:pointer; display:flex; align-items:center; gap:6px; background:var(--bg-app); padding:4px 12px; border-radius:99px; border:1px solid var(--border); font-size:13px; color:var(--text-main);" title="Actualizar peso de este día">
            <span style="color:var(--text-muted);font-size:14px;">⚖️</span> ${currentDayWeight} kg
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <button class="cal-nav-btn" onclick="changeDay(-1)" style="width:28px;height:28px;border-radius:50%;font-size:12px;border:none;background:var(--bg-app);cursor:pointer;">❮</button>
            <span onclick="openCalPicker()" style="font-size:13px;color:var(--text-main);font-weight:800;display:flex;gap:6px;align-items:center;background:var(--bg-app);padding:4px 14px;border-radius:99px;cursor:pointer;">
              <span style="font-size:16px;">📅</span> ${dateLabel}
            </span>
            <button class="cal-nav-btn" onclick="changeDay(1)" style="width:28px;height:28px;border-radius:50%;font-size:12px;border:none;background:var(--bg-app);cursor:pointer;opacity:${viewingDateStr >= todayKey() ? '0.2' : '1'};pointer-events:${viewingDateStr >= todayKey() ? 'none' : 'auto'}">❯</button>
          </div>
        </div>
        <div class="fitia-macro-arc">
          ${arcHTML}
          <div class="fitia-macro-arc-text">
            <div class="fitia-macro-val">${remaining}</div>
            <div class="fitia-macro-lbl">kcal restantes</div>
          </div>
        </div>
        <div class="grid-3" style="gap:16px;">
          <div class="macro-progress-item">
            <div class="flex justify-between items-center mb-8"><span class="form-label" style="font-size:12px;">Proteínas</span></div>
            <div class="text-sm font-bold mb-8">${Math.max(0, targets.protein - totals.protein)} g rest.</div>
            <div class="progress-bar-wrap" style="height:6px;"><div class="progress-bar-fill protein" style="width:${pct(totals.protein, targets.protein)}%;"></div></div>
          </div>
          <div class="macro-progress-item">
            <div class="flex justify-between items-center mb-8"><span class="form-label" style="font-size:12px;">Carbos</span></div>
            <div class="text-sm font-bold mb-8">${Math.max(0, targets.carbs - totals.carbs)} g rest.</div>
            <div class="progress-bar-wrap" style="height:6px;"><div class="progress-bar-fill carbs" style="width:${pct(totals.carbs, targets.carbs)}%;"></div></div>
          </div>
          <div class="macro-progress-item">
            <div class="flex justify-between items-center mb-8"><span class="form-label" style="font-size:12px;">Grasas</span></div>
            <div class="text-sm font-bold mb-8">${Math.max(0, targets.fat - totals.fat)} g rest.</div>
            <div class="progress-bar-wrap" style="height:6px;"><div class="progress-bar-fill fat" style="width:${pct(totals.fat, targets.fat)}%;"></div></div>
          </div>
        </div>
        <button class="btn btn-secondary btn-lg" style="margin-top:16px;border-radius:99px;border-color:var(--gray-200);background:transparent;font-weight:600;font-size:15px;" onclick="showDailySummary()">Terminar Día</button>`;
    }

    // ── 3. Comidas (Meals)
    // Renderiza las secciones de comidas (Desayuno, Almuerzo, Cena, Snacks) con sus elementos
    function renderMealSections(log) {
      const entries = log.entries ?? [];
      const byMeal = {};
      MEAL_TYPES.forEach(t => byMeal[t] = []);
      entries.forEach(e => { if (byMeal[e.mealType] !== undefined) byMeal[e.mealType].push(e); });

      document.getElementById('mealSections').innerHTML = MEAL_TYPES.map(type => {
        const items = byMeal[type];
        const tCal = items.reduce((s, e) => s + (e.calories || 0), 0);
        const tPro = items.reduce((s, e) => s + (e.protein || 0), 0);
        const tCar = items.reduce((s, e) => s + (e.carbs || 0), 0);
        const tFat = items.reduce((s, e) => s + (e.fat || 0), 0);

        const rows = items.map(e => `
          <div class="fmc-item">
            <div class="fmc-item-left">
              <div class="fmc-icon" style="background:var(--bg-app);font-size:18px;">${getFoodEmoji(e.name)}</div>
              <div>
                <div class="fmc-item-name">${e.name}</div>
                <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-top:4px;">${e.calories} kcal · ${e.protein}g P · ${e.carbs}g C · ${e.fat}g G</div>
              </div>
            </div>
            <button onclick="removeEntry(${e.id})" style="background:transparent;border:none;width:32px;height:32px;font-size:16px;color:var(--text-muted);display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:50%;">✕</button>
          </div>`).join('');

        return `
          <div class="fitia-meal-card">
            <div class="fmc-header">
              <div>
                <div class="fmc-title">${type}</div>
                <div class="fmc-macros">🔥 ${tCal} kcal · ${tPro}g P | ${tCar}g C | ${tFat}g G</div>
              </div>
              ${tCal > 0 ? '<div style="width:24px;height:24px;border-radius:50%;background:var(--green-500);color:white;display:flex;align-items:center;justify-content:center;font-size:12px;">✓</div>' : ''}
            </div>
            <div class="fmc-body">${rows}</div>
            <button class="fmc-add-btn" onclick="openAddFoodModal('${type}')">+</button>
          </div>`;
      }).join('');
    }

    function removeEntry(id) {
      // Read a fresh snapshot, remove, save — fully isolated per day
      const logs = getLogs();
      const key = viewingDateStr;
      if (logs[key]) {
        const before = logs[key].entries.find(e => e.id === id);
        logs[key].entries = logs[key].entries.filter(e => e.id !== id);
        store.set(KEYS.LOGS, logs);
        if (before) showToast(`${before.name} eliminado`, 'success');
      }
      renderPage();
    }

    // ── 4. Modal Handling
    function openAddFoodModal(mealType) {
      activeMealType = mealType;
      document.getElementById('addFoodModalTitle').textContent = mealType;
      document.getElementById('addFoodModal').classList.add('active');
      switchTab('db');
    }
    function closeAddFoodModal() {
      document.getElementById('addFoodModal').classList.remove('active');
    }

    function switchTab(tabId) {
      document.querySelectorAll('.ts-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.ts-content').forEach(c => c.classList.remove('active'));
      if (tabId === 'db') {
        document.getElementById('tabBtnDb').classList.add('active');
        document.getElementById('tabDb').classList.add('active');
      } else {
        document.getElementById('tabBtnRecipes').classList.add('active');
        document.getElementById('tabRecipes').classList.add('active');
        renderRecipesTab();
      }
    }

    // ── 5. Database Tab
    let searchTimeout = null;
    document.getElementById('foodSearchInput').addEventListener('input', function () {
      const q = this.value.trim();
      const resultsEl = document.getElementById('searchResults');
      
      if (searchTimeout) clearTimeout(searchTimeout);
      if (!q) { 
        resultsEl.classList.add('hidden'); 
        resultsEl.innerHTML = '';
        return; 
      }

      // 1. Mostrar resultados locales AL INSTANTE
      const localResults = searchRecipes(q);
      renderLocalSearchResults(localResults);
      resultsEl.classList.remove('hidden');

      // 2. Debounce para resultados externos con Súper Búsqueda Paralela
      if (q.length >= 3) {
        searchTimeout = setTimeout(async () => {
          // Mostrar indicador de carga sutil
          const loadingHtml = `<div id="search-loading" style="padding:12px; font-size:11px; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:1px; text-align:center; animation: pulse 1.5s infinite;">🔍 Escaneando la nube...</div>`;
          resultsEl.innerHTML += loadingHtml;
          
          const externalResults = await searchFoodExternal(q);
          
          // Quitar indicador de carga
          const loadingEl = document.getElementById('search-loading');
          if (loadingEl) loadingEl.remove();
          
          appendExternalResults(externalResults);
        }, 400); 
      }
    });

    function renderLocalSearchResults(results) {
      const resultsEl = document.getElementById('searchResults');
      if (results.length === 0) {
        resultsEl.innerHTML = '<div style="padding:14px;color:var(--text-muted);font-size:13px;font-weight:600;">Buscando coincidencias...</div>';
        return;
      }
      resultsEl.innerHTML = results.slice(0, 5).map(r => {
        foodMap.set(r.id, r);
        return `
          <div class="search-result-item" onclick="selectDbItem('${r.id}')" style="background:var(--bg-app); border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:4px; padding:12px; display:flex; justify-content:space-between; align-items:center; cursor:pointer;">
            <div>
              <div style="font-size:14px;font-weight:700; color:var(--text-main);">${r.name}</div>
              <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">P: ${r.protein}g · C: ${r.carbs}g · G: ${r.fat}g</div>
            </div>
            <span style="font-size:13px;font-weight:800;color:var(--text-main);">${r.calories} kcal</span>
          </div>`;
      }).join('');
    }

    function appendExternalResults(results) {
      const resultsEl = document.getElementById('searchResults');
      if (!results || results.length === 0) return;

      const html = `
        <div style="font-size:10px; font-weight:800; color:var(--primary); text-transform:uppercase; letter-spacing:1px; margin:14px 0 8px; padding-left:4px; border-top:1px solid var(--border); padding-top:12px;">Resultados Globales</div>
        ` + results.map(r => {
          foodMap.set(r.id, r);
          const sourceColor = r.source === 'Edamam' ? '#10b981' : (r.source === 'USDA' ? '#f97316' : '#3b82f6');
          return `
            <div class="search-result-item" onclick="selectDbItem('${r.id}')" style="background:var(--bg-app); border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:4px; padding:12px; display:flex; align-items:center; cursor:pointer;">
              <div style="width:36px; height:36px; border-radius:10px; background:var(--bg-card); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin-right:12px; font-size:18px;">${r.emoji || '🥘'}</div>
              <div style="flex:1;">
                <div style="font-size:14px;font-weight:700;display:flex;align-items:center;gap:6px; color:var(--text-main);">
                  ${r.name} 
                  <span style="font-size:8px; background:${sourceColor}; color:white; padding:2px 5px; border-radius:4px; text-transform:uppercase; font-weight:900;">${r.source}</span>
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${r.brand || ''} · P: ${r.protein}g · C: ${r.carbs}g</div>
              </div>
              <span style="font-size:13px;font-weight:800;color:var(--text-main);">${r.calories} kcal</span>
            </div>`;
        }).join('');
      
      resultsEl.innerHTML += html;
    }

    // ── 5.1 Barcode Scanner
    let html5QrCode = null;
    async function toggleScanner() {
      const container = document.getElementById('barcodeScannerContainer');
      const btn = document.getElementById('startScanBtn');
      
      if (html5QrCode && html5QrCode.isScanning) {
        stopScanner();
        return;
      }

      container.style.display = 'block';
      btn.innerHTML = '✕ Detener Escáner';
      btn.style.background = 'var(--red-500)';
      btn.style.color = 'white';

      html5QrCode = new Html5Qrcode("barcodeScannerContainer");
      const config = { fps: 10, qrbox: { width: 250, height: 150 } };

      try {
        await html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess);
      } catch (err) {
        console.error("No se pudo iniciar la cámara", err);
        showToast("Error al acceder a la cámara", "error");
        stopScanner();
      }
    }

    async function stopScanner() {
      if (html5QrCode) {
        try {
          await html5QrCode.stop();
        } catch (e) {}
        html5QrCode = null;
      }
      const container = document.getElementById('barcodeScannerContainer');
      container.style.display = 'none';
      const btn = document.getElementById('startScanBtn');
      btn.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
          <path d="M7 8h10M7 12h10M7 16h10"></path>
        </svg>
        Escanear Código de Barras`;
      btn.style.background = 'var(--gray-100)';
      btn.style.color = 'var(--text-main)';
    }

    async function onScanSuccess(decodedText) {
      showToast("¡Código detectado!", "success");
      stopScanner();
      
      showToast("Buscando producto...", "info");
      const product = await getFoodByBarcode(decodedText);
      
      if (product) {
        document.getElementById('customName').value = product.name;
        document.getElementById('customCals').value = product.calories;
        document.getElementById('customProtein').value = product.protein;
        document.getElementById('customCarbs').value = product.carbs;
        document.getElementById('customFat').value = product.fat;
        
        // Auto-save as recipe if it's a new barcode
        const existing = searchRecipes(product.name).find(r => r.name === product.name);
        if (!existing) {
          saveUserRecipe({
            name: product.name,
            calories: product.calories,
            protein: product.protein,
            carbs: product.carbs,
            fat: product.fat,
            emoji: '📦',
            category: 'Escaneado'
          });
        }
      } else {
        showToast("Producto no encontrado en la base de datos", "error");
      }
    }

    function selectDbItem(id) {
      const r = foodMap.get(id);
      if (!r) return;
      document.getElementById('customName').value = r.name;
      document.getElementById('customCals').value = r.calories;
      document.getElementById('customProtein').value = r.protein;
      document.getElementById('customCarbs').value = r.carbs;
      document.getElementById('customFat').value = r.fat;
      document.getElementById('foodSearchInput').value = '';
      document.getElementById('searchResults').classList.add('hidden');
    }

    function confirmAddFood() {
      const name = document.getElementById('customName').value.trim();
      const calories = parseFloat(document.getElementById('customCals').value) || 0;
      const protein = parseFloat(document.getElementById('customProtein').value) || 0;
      const carbs = parseFloat(document.getElementById('customCarbs').value) || 0;
      const fat = parseFloat(document.getElementById('customFat').value) || 0;
      if (!name) { showToast('Ingresa un nombre para el alimento', 'error'); return; }
      addFoodEntry({ mealType: activeMealType, name, calories, protein, carbs, fat }, viewingDateStr);
      clearModal();
      closeAddFoodModal();
      renderPage();
      showToast(`${name} añadido`, 'success');
    }
    function clearModal() {
      ['customName', 'customCals', 'customProtein', 'customCarbs', 'customFat', 'foodSearchInput']
        .forEach(id => document.getElementById(id).value = '');
      document.getElementById('searchResults').classList.add('hidden');
    }

    // ── 6. Recipes Tab
    function renderRecipesTab() {
      const recipes = getAllRecipes();
      // Reverse to show newest custom recipes first
      const sorted = [...recipes].reverse();
      
      document.getElementById('recipesGrid').innerHTML = sorted.slice(0, 30).map(r => `
        <div class="recipe-card" onclick="addRecipeDirectly('${r.id}')" style="display:flex;flex-direction:row;padding:14px;gap:12px;align-items:center;background:var(--bg-card);border-radius:var(--radius-lg);border:1px solid var(--border);box-shadow:none; position:relative;">
          <div style="width:48px;height:48px;border-radius:12px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:20px;background:var(--bg-app);flex-shrink:0;">${r.emoji || (r.custom ? '🥘' : '🍲')}</div>
          <div style="flex:1;">
            <div style="font-weight:800;font-size:14px;color:var(--text-main);">${r.name}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${r.calories} kcal · ${r.protein}g P · ${r.carbs}g C</div>
            ${r.custom ? '<div style="font-size:9px; color:var(--primary); font-weight:800; margin-top:4px; text-transform:uppercase;">Custom</div>' : ''}
          </div>
          <div style="width:28px;height:28px;background:var(--primary-100);color:var(--primary);display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:800;font-size:18px;">+</div>
        </div>`).join('');
    }

    function openCreateRecipeModal() {
      closeAddFoodModal();
      document.getElementById('createRecipeModal').classList.add('active');
      renderSelectedIngredients();
    }

    function closeCreateRecipeModal() {
      document.getElementById('createRecipeModal').classList.remove('active');
    }

    let recipeBuilder = {
      ingredients: []
    };

    document.getElementById('recipeIngSearch').addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      const resultsEl = document.getElementById('recipeIngResults');
      if (!q) { resultsEl.classList.add('hidden'); return; }

      const all = getAllRecipes().filter(r => r.category === 'Ingrediente'); // Filter only basic ingredients
      const results = all.filter(r => r.name.toLowerCase().includes(q));

      if (!results.length) {
        resultsEl.innerHTML = '<div style="padding:14px;color:var(--text-muted);font-size:13px;font-weight:600;">Sin ingredientes encontrados</div>';
        resultsEl.classList.remove('hidden');
        return;
      }

      resultsEl.innerHTML = results.slice(0, 6).map(r => `
        <div onclick="addIngToRecipeBuilder('${r.id}')" style="padding:12px; cursor:pointer; display:flex; gap:10px; align-items:center; border-bottom:1px solid var(--border);">
           <span>${r.emoji}</span>
           <span style="font-weight:700; flex:1;">${r.name}</span>
           <span style="font-size:11px; color:var(--text-muted);">${r.calories} kcal/100g</span>
        </div>
      `).join('');
      resultsEl.classList.remove('hidden');
    });

    function addIngToRecipeBuilder(id) {
      const item = getAllRecipes().find(x => x.id === id);
      if (!item) return;
      
      recipeBuilder.ingredients.push({
        id: item.id,
        name: item.name,
        emoji: item.emoji,
        grams: 100,
        macros: { // saved per 100g
          calories: item.calories,
          protein: item.protein,
          carbs: item.carbs,
          fat: item.fat
        }
      });

      document.getElementById('recipeIngSearch').value = '';
      document.getElementById('recipeIngResults').classList.add('hidden');
      renderSelectedIngredients();
    }

    function removeIngFromRecipe(idx) {
      recipeBuilder.ingredients.splice(idx, 1);
      renderSelectedIngredients();
    }

    function updateIngQty(idx, qty) {
      const grams = parseFloat(qty) || 0;
      recipeBuilder.ingredients[idx].grams = grams;
      recalcRecipeTotal();
    }

    function renderSelectedIngredients() {
      const list = document.getElementById('selectedIngList');
      if (recipeBuilder.ingredients.length === 0) {
        list.innerHTML = `<div style="padding:20px; text-align:center; color:var(--text-muted); font-size:13px; font-weight:600; background:rgba(0,0,0,0.02); border-radius:12px; border:2px dashed var(--border);">No has añadido ingredientes</div>`;
        recalcRecipeTotal();
        return;
      }

      list.innerHTML = recipeBuilder.ingredients.map((ing, idx) => `
        <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:12px; display:flex; align-items:center; gap:12px;">
           <span style="font-size:18px;">${ing.emoji}</span>
           <div style="flex:1;">
             <div style="font-weight:800; font-size:14px;">${ing.name}</div>
             <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase;">Cant (g)</div>
           </div>
           <input type="number" value="${ing.grams}" oninput="updateIngQty(${idx}, this.value)" 
             style="width:70px; background:var(--bg-app); border:1px solid var(--border); padding:6px; border-radius:8px; font-weight:800; text-align:center;" />
           <button onclick="removeIngFromRecipe(${idx})" style="background:transparent; border:none; color:var(--red-500); cursor:pointer; font-size:18px;">✕</button>
        </div>
      `).join('');
      recalcRecipeTotal();
    }

    function recalcRecipeTotal() {
      let totals = { c: 0, p: 0, car: 0, f: 0 };
      recipeBuilder.ingredients.forEach(ing => {
        const factor = ing.grams / 100;
        totals.c += (ing.macros.calories * factor);
        totals.p += (ing.macros.protein * factor);
        totals.car += (ing.macros.carbs * factor);
        totals.f += (ing.macros.fat * factor);
      });

      document.getElementById('recipeTotalCals').textContent = Math.round(totals.c);
      document.getElementById('recipeTotalPro').textContent = Math.round(totals.p) + 'g';
      document.getElementById('recipeTotalCarb').textContent = Math.round(totals.car) + 'g';
      document.getElementById('recipeTotalFat').textContent = Math.round(totals.f) + 'g';
    }

    function saveNewRecipe() {
      const name = document.getElementById('recipeName').value.trim();
      if (!name) { showToast('Ingresa un nombre para la receta', 'error'); return; }
      if (recipeBuilder.ingredients.length === 0) { showToast('Añade al menos un ingrediente', 'error'); return; }

      let totals = { calories: 0, protein: 0, carbs: 0, fat: 0 };
      recipeBuilder.ingredients.forEach(ing => {
        const factor = ing.grams / 100;
        totals.calories += (ing.macros.calories * factor);
        totals.protein += (ing.macros.protein * factor);
        totals.carbs += (ing.macros.carbs * factor);
        totals.fat += (ing.macros.fat * factor);
      });

      const newRecipe = {
        name,
        calories: Math.round(totals.calories),
        protein: Math.round(totals.protein),
        carbs: Math.round(totals.carbs),
        fat: Math.round(totals.fat),
        emoji: '🥘',
        category: 'Personalizada',
        ingredients: recipeBuilder.ingredients.map(ing => `${ing.grams}g ${ing.name}`)
      };

      saveUserRecipe(newRecipe);
      showToast('Receta creada y guardada', 'success');
      
      // Cleanup
      document.getElementById('recipeName').value = '';
      recipeBuilder.ingredients = [];
      closeCreateRecipeModal();
      renderRecipesTab();
    }
    function addRecipeDirectly(id) {
      const r = getAllRecipes().find(x => x.id === id);
      if (!r) return;
      addFoodEntry({ mealType: activeMealType, name: r.name, calories: r.calories, protein: r.protein, carbs: r.carbs, fat: r.fat }, viewingDateStr);
      closeAddFoodModal();
      renderPage();
      showToast(`${r.name} añadido a ${activeMealType}`, 'success');
    }

    // ── 7. Daily Summary
    function showDailySummary() {
      const log = getLogForDate(viewingDateStr);
      const totals = getDailyTotals(log);
      const user = getUser();
      const waterGoal = calculateWaterGoal(user);
      const waterCurrent = getWaterIntake(viewingDateStr);
      
      const pctCal = pct(totals.calories, targets.calories);
      const pctPro = pct(totals.protein, targets.protein);
      const pctCar = pct(totals.carbs, targets.carbs);
      const pctFat = pct(totals.fat, targets.fat);
      const pctWater = pct(waterCurrent, waterGoal);
      
      const fDate = formatDate(viewingDateStr);
      document.getElementById('summaryTitle').textContent = viewingDateStr === todayKey() ? 'Hoy'
        : fDate.split(',')[0].charAt(0).toUpperCase() + fDate.split(',')[0].slice(1);
        
      document.getElementById('val-cal').textContent = totals.calories;
      document.getElementById('val-pro').textContent = totals.protein;
      document.getElementById('val-car').textContent = totals.carbs;
      document.getElementById('val-fat').textContent = totals.fat;
      document.getElementById('val-water').textContent = waterCurrent;
      
      document.getElementById('dailySummaryModal').classList.add('active');
      const cCirc = 251.2;
      ['cal', 'pro', 'car', 'fat', 'water'].forEach(id => {
        const el = document.getElementById('ring-' + id);
        if (el) el.style.strokeDashoffset = cCirc;
      });
      
      setTimeout(() => {
        if (document.getElementById('ring-car')) document.getElementById('ring-car').style.strokeDashoffset = cCirc - (cCirc * Math.min(100, pctCar) / 100);
        if (document.getElementById('ring-fat')) document.getElementById('ring-fat').style.strokeDashoffset = cCirc - (cCirc * Math.min(100, pctFat) / 100);
        if (document.getElementById('ring-pro')) document.getElementById('ring-pro').style.strokeDashoffset = cCirc - (cCirc * Math.min(100, pctPro) / 100);
        if (document.getElementById('ring-cal')) document.getElementById('ring-cal').style.strokeDashoffset = cCirc - (cCirc * Math.min(100, pctCal) / 100);
        if (document.getElementById('ring-water')) document.getElementById('ring-water').style.strokeDashoffset = cCirc - (cCirc * Math.min(100, pctWater) / 100);
      }, 100);

      // Update Summary Subtitle with Water
      if (waterCurrent >= waterGoal) {
        document.getElementById('summarySubtitle').innerHTML = `¡Objetivo de hidratación cumplido! 💧 <span style="color:#4ade80;">${waterCurrent}/${waterGoal}</span>`;
      } else {
        document.getElementById('summarySubtitle').innerHTML = `Ingesta de agua: <span style="color:#3b82f6;">${waterCurrent}/${waterGoal} vasos</span>`;
      }
    }

    // ── 8. Calendar Picker
    let _calYear, _calMonth;
    const MONTH_NAMES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    function openCalPicker() {
      const d = new Date(viewingDateStr + 'T12:00:00');
      _calYear = d.getFullYear();
      _calMonth = d.getMonth();
      const overlay = document.getElementById('calPickerOverlay');
      overlay.style.display = 'flex';
      setTimeout(() => document.getElementById('calPickerSheet').style.transform = 'translateY(0)', 10);
      renderCalGrid();
    }

    function closeCalPicker(e) {
      if (e && e.target !== document.getElementById('calPickerOverlay')) return;
      _closeCalPicker();
    }

    function _closeCalPicker() {
      const sheet = document.getElementById('calPickerSheet');
      if (!sheet) return;
      sheet.style.transform = 'translateY(100%)';
      setTimeout(() => { document.getElementById('calPickerOverlay').style.display = 'none'; }, 380);
    }

    function shiftCalMonth(dir) {
      _calMonth += dir;
      if (_calMonth > 11) { _calMonth = 0; _calYear++; }
      if (_calMonth < 0) { _calMonth = 11; _calYear--; }
      renderCalGrid();
    }

    function jumpToday() { goToDate(todayKey()); }

    function renderCalGrid() {
      const today = todayKey();
      const logs = getLogs();
      document.getElementById('calMonthLabel').textContent = `${MONTH_NAMES[_calMonth]} ${_calYear}`;

      const firstDay = new Date(_calYear, _calMonth, 1);
      const daysInMonth = new Date(_calYear, _calMonth + 1, 0).getDate();
      const startOffset = (firstDay.getDay() + 6) % 7; // Mon=0

      let html = '';
      for (let i = 0; i < startOffset; i++) html += '<div></div>';

      for (let d = 1; d <= daysInMonth; d++) {
        const key = `${_calYear}-${String(_calMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isFuture = key > today;
        const isToday = key === today;
        const isViewing = key === viewingDateStr;
        const hasLog = logs[key] && logs[key].entries.length > 0;
        const score = hasLog ? calculateDayCompletion(key) : 0;

        const bg = isViewing ? 'var(--primary)' : isToday ? 'var(--primary-100)' : 'transparent';
        const color = isViewing ? '#fff' : isToday ? 'var(--primary)' : isFuture ? 'var(--gray-300)' : 'var(--text-main)';
        const border = isToday && !isViewing ? '2px solid var(--primary)' : '2px solid transparent';
        const dotColor = score >= 70 ? 'var(--green-500)' : 'var(--primary-300)';

        html += `<div onclick="${isFuture ? '' : `goToDate('${key}')`}"
          style="aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
                 border-radius:50%;background:${bg};color:${color};border:${border};
                 cursor:${isFuture ? 'default' : 'pointer'};font-size:13px;font-weight:${isToday || isViewing ? '800' : '600'};
                 position:relative;">
          ${d}
          ${hasLog && !isViewing ? `<div style="width:5px;height:5px;border-radius:50%;background:${dotColor};position:absolute;bottom:3px;"></div>` : ''}
        </div>`;
      }
      document.getElementById('calGrid').innerHTML = html;

      const nextAllFuture = new Date(_calYear, _calMonth + 1, 1) > new Date();
      document.getElementById('calNextMonth').style.opacity = nextAllFuture ? '0.3' : '1';
      document.getElementById('calNextMonth').style.pointerEvents = nextAllFuture ? 'none' : 'auto';
    }

    function promptDayWeight() {
      const currentW = getWeightForDate(viewingDateStr);
      let dateLabel = 'Hoy';
      if (viewingDateStr !== todayKey()) {
        const fDate = formatDate(viewingDateStr);
        dateLabel = fDate.split(',')[0].charAt(0).toUpperCase() + fDate.split(',')[0].slice(1) + ' ' + fDate.split(' ')[1];
      }
      
      const w = prompt(`Ingresa tu peso para el ${dateLabel} (kg):`, currentW);
      if (w) {
        const normalizedW = w.replace(',', '.');
        if (!isNaN(parseFloat(normalizedW))) {
          const newWeight = parseFloat(normalizedW);
          addWeightEntry(newWeight, viewingDateStr);
          
          if (viewingDateStr >= todayKey()) {
             const user = getUser();
             saveUser({ ...user, weight: newWeight });
          }
          
          showToast('Peso registrado', 'success');
          renderPage();
        }
      }
    }

    syncFromDbToLocal().then(() => { 
      renderPage(); 
      initSidebar('dashboard');
      
      // Inicializar Geolocalización si es la primera vez
      const user = getUser();
      if (user && !user.countryCode) {
        detectUserCountry()
          .then(({ countryName, countryCode }) => {
            showToast(`NutriMax ajustado para ${countryName} ${getCountryFlag(countryCode)}`, 'success');
            renderPage(); 
            initSidebar('dashboard');
          })
          .catch(e => {
            console.warn("Geolocalización saltada:", e);
          });
      }
    });

    window.addEventListener('load', () => {
      const splash = document.getElementById('appSplashScreen');
      if (!splash) return;
      
      // We show it for at least 1 second on the first load of the session
      if (sessionStorage.getItem('splashShown') === 'true') {
        splash.style.transition = 'none';
        splash.classList.add('hidden');
      } else {
        setTimeout(() => { 
          splash.classList.add('hidden'); 
          sessionStorage.setItem('splashShown', 'true'); 
        }, 1000);
      }
    });

  </script>
</body>

</html>
