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
  <title>Objetivos — NutriMax</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    .result-macro-card {
      background: var(--white);
      border-radius: var(--radius);
      border: 1px solid var(--gray-100);
      padding: 20px;
      text-align: center;
      transition: var(--transition);
      animation: fadeInUp .4s ease both;
    }

    .result-macro-card .rm-icon {
      font-size: 32px;
      margin-bottom: 8px;
    }

    .result-macro-card .rm-val {
      font-size: 32px;
      font-weight: 900;
      letter-spacing: -1px;
    }

    .result-macro-card .rm-label {
      font-size: 12px;
      font-weight: 600;
      color: #111827;
      text-transform: uppercase;
    }

    .activity-option {
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      border: 1.5px solid var(--gray-200);
      cursor: pointer;
      transition: var(--transition);
      font-size: 13px;
      font-weight: 500;
    }

    .activity-option:hover {
      border-color: var(--green-300);
      background: var(--green-50);
    }

    .activity-option.selected {
      border-color: var(--green-500);
      background: var(--green-50);
      color: var(--green-700);
      font-weight: 700;
    }

    .macro-pct-bar {
      height: 10px;
      border-radius: 99px;
      margin-top: 6px;
    }
  </style>
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#111827">
  <meta name="view-transition" content="same-origin">
  <!-- Flatpickr for beautiful calendars -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>

  <div class="app-shell">
    <aside class="sidebar" id="sidebar"></aside>
    <main class="main-content">
      <div class="page-header mb-32">
        <h1 class="page-title">Tu Perfil & Metas</h1>
        <p class="page-subtitle">Personaliza tu nutrición para optimizar tus resultados</p>
      </div>

      <div class="grid-2-1" style="gap:24px; align-items:start;">
        <!-- ─── LEFT: Form ─── -->
        <div style="display:flex; flex-direction:column; gap:24px;">
          <!-- Profile card -->
          <div class="card">
            <div class="card-title">Datos Biométricos</div>
            <div class="card-subtitle">Utilizamos Harris-Benedict para estimar tu TMB</div>
            <div style="display:flex; flex-direction:column; gap:20px; margin-top:20px;">
              <div class="form-group">
                <label class="form-label">Nombre Completo</label>
                <input class="form-input" type="text" id="profName" placeholder="Tu nombre" />
              </div>
              <div class="grid-2" style="gap:16px;">
                <div class="form-group">
                  <label class="form-label">Fecha de nac.</label>
                  <input class="form-input" type="date" id="profBirthDate" />
                </div>
                <div class="form-group">
                  <label class="form-label">Sexo Biológico</label>
                  <select class="form-select" id="profSex">
                    <option value="male">Masculino</option>
                    <option value="female">Femenino</option>
                  </select>
                </div>
              </div>
              <div class="grid-2" style="gap:16px;">
                <div class="form-group">
                  <label class="form-label">Peso Actual (kg)</label>
                  <input class="form-input" type="number" id="profWeight" placeholder="75" />
                </div>
                <div class="form-group">
                  <label class="form-label">Altura (cm)</label>
                  <input class="form-input" type="number" id="profHeight" placeholder="180" />
                </div>
              </div>
            </div>
          </div>

          <!-- Activity level -->
          <div class="card">
            <div class="card-title">Nivel de Actividad Diaria</div>
            <div class="card-subtitle">¿Cuánto te mueves habitualmente?</div>
            <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;" id="activityOptions"></div>
          </div>
        </div>

        <!-- ─── RIGHT: Goal + Action ─── -->
        <div style="display:flex; flex-direction:column; gap:24px;">
          <div class="card">
            <div class="card-title">Selecciona tu Objetivo</div>
            <div class="card-subtitle">Adaptaremos tus macros a esta meta</div>
            <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;" id="goalOptions"></div>

            <!-- Custom Inputs -->
            <div id="customGoalInputs"
              style="display:none; margin-top:16px; flex-direction:column; gap:12px; border-top:1px solid var(--border); padding-top:16px;">
              <div class="card-title" style="font-size:14px;">Define tus propios macros</div>
              <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label" style="font-size:12px;">Calorías (kcal)</label>
                  <input class="form-input" type="number" id="cgCals" placeholder="2000"
                    style="padding:8px 12px; font-size:14px;" />
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-size:12px;">Proteína (g)</label>
                  <input class="form-input" type="number" id="cgProt" placeholder="150"
                    style="padding:8px 12px; font-size:14px;" />
                </div>
              </div>
              <div class="grid-2" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label" style="font-size:12px;">Carbos (g)</label>
                  <input class="form-input" type="number" id="cgCarbs" placeholder="200"
                    style="padding:8px 12px; font-size:14px;" />
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-size:12px;">Grasas (g)</label>
                  <input class="form-input" type="number" id="cgFat" placeholder="65"
                    style="padding:8px 12px; font-size:14px;" />
                </div>
              </div>
            </div>
          </div>

          <button class="btn btn-primary btn-lg w-full" id="calcBtn" onclick="calculate()">
            Calcular Mi Plan
          </button>

          <!-- Results -->
          <div id="resultsSection" style="display:none;">
            <div class="card" style="background:var(--primary); color:white; border:none; margin-bottom:16px;">
              <div style="text-align:center; padding:10px 0;">
                <div
                  style="font-size:12px; font-weight:700; opacity:0.8; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                  TU TDEE ESTIMADO</div>
                <div style="font-size:48px; font-weight:900; line-height:1;" id="tdeeVal">0</div>
                <div style="font-size:14px; margin-top:4px; opacity:0.9;">kcal / día quemadas</div>
              </div>
            </div>

            <div class="card mb-16" style="padding:24px;">
              <div
                style="font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:16px; text-transform:uppercase;">
                PLAN DE MACRONUTRIENTES</div>
              <div class="grid-2" id="macroResultCards" style="gap:12px; margin-bottom:24px;"></div>

              <div id="macroPctBars" style="display:flex; flex-direction:column; gap:16px;"></div>

              <button class="btn btn-primary w-full mt-24" onclick="saveGoalsAndNotify()"
                style="justify-content:center; height:48px;">
                Guardar Configuración
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <script src="js/app.js"></script>
  <script>
    window.currentActivePage = 'goals';
    if (!requireAuth()) throw new Error('not logged in');
    initSidebar('goals');

    initFlatpickr("#profBirthDate");

    let selectedActivity = 'moderate';
    let selectedGoal = 'maintenance';

    // ── Selection State ──
    function selectActivity(key) {
      selectedActivity = key;
      renderActivityOptions();
    }
    function selectGoal(key) {
      selectedGoal = key;
      renderGoalOptions();
      document.getElementById('customGoalInputs').style.display = (key === 'custom') ? 'flex' : 'none';
    }

    // ── Rendering ──
    function renderActivityOptions() {
      document.getElementById('activityOptions').innerHTML = Object.entries(ACTIVITY_MULTIPLIERS).map(([key, val]) => {
        const isSelected = key === selectedActivity;
        return `
    <div class="option-card ${isSelected ? 'selected' : ''}" onclick="selectActivity('${key}')">
      <div class="option-header">
        <div class="option-title">${val.label.split('(')[0].trim()}</div>
        <div class="option-check">${isSelected ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' : ''}</div>
      </div>
      <div class="option-desc">${val.label.includes('(') ? val.label.match(/\(([^)]+)\)/)[1] : ''}</div>
    </div>`;
      }).join('');
    }

    function renderGoalOptions() {
      const goalDetails = {
        definition: { desc: 'Déficit calórico para reducir grasa corporal.' },
        volume: { desc: 'Superávit controlado para ganar masa muscular.' },
        maintenance: { desc: 'Calorías de equilibrio para mantener tu estado.' },
        recomp: { desc: 'Recomposición: quemar grasa y ganar músculo.' },
        custom: { desc: 'Ingresa manualmente tus calorías y macronutrientes preferidos.' }
      };
      document.getElementById('goalOptions').innerHTML = Object.entries(GOALS_CONFIG).map(([key, cfg]) => {
        const isSelected = key === selectedGoal;
        const det = goalDetails[key];
        return `
    <div class="option-card ${isSelected ? 'selected' : ''}" onclick="selectGoal('${key}')">
      <div class="option-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <div class="option-title">${cfg.label}</div>
        </div>
        <div class="option-check">${isSelected ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>' : ''}</div>
      </div>
      <div class="option-desc">${det.desc}</div>
    </div>`;
      }).join('');
    }

    // Init Form
    syncFromDbToLocal().then(() => {
      const user = getUser();
      if (user) {
        document.getElementById('profName').value = user.name ?? '';
        if (user.birthDate) { 
          const el = document.getElementById('profBirthDate');
          if (el._flatpickr) el._flatpickr.setDate(user.birthDate);
        }
        document.getElementById('profSex').value = user.sex ?? 'male';
        document.getElementById('profWeight').value = user.weight ?? '';
        document.getElementById('profHeight').value = user.height ?? '';
        selectedActivity = user.activityLevel ?? 'moderate';
        selectedGoal = user.goal ?? 'maintenance';
      }
      // Load custom targets if any
      const savedG = getGoals();
      if (savedG && savedG.goal === 'custom' && savedG.targets) {
        document.getElementById('cgCals').value = savedG.targets.calories || '';
        document.getElementById('cgProt').value = savedG.targets.protein || '';
        document.getElementById('cgCarbs').value = savedG.targets.carbs || '';
        document.getElementById('cgFat').value = savedG.targets.fat || '';
      }

      renderActivityOptions();
      renderGoalOptions();
      if (selectedGoal === 'custom') {
        document.getElementById('customGoalInputs').style.display = 'flex';
      }
    });

    // ── Logic ──
    function calculate() {
      let age = 25;
      const bDateStr = document.getElementById('profBirthDate').value;
      if (bDateStr) {
        const bd = new Date(bDateStr);
        const td = new Date();
        age = td.getFullYear() - bd.getFullYear();
        if (td.getMonth() < bd.getMonth() || (td.getMonth() === bd.getMonth() && td.getDate() < bd.getDate())) age--;
      }
      const sex = document.getElementById('profSex').value;
      const weight = parseFloat(document.getElementById('profWeight').value);
      const height = parseFloat(document.getElementById('profHeight').value);

      if (selectedGoal !== 'custom') {
        if (!bDateStr || !weight || !height) { showToast('Ingresa todos tus datos biométricos', 'error'); return; }
        if (age < 10 || age > 100) { showToast('Fecha de nacimiento inválida (10-100 años)', 'error'); return; }
        if (weight < 20 || weight > 300) { showToast('Peso inválido (20-300 kg)', 'error'); return; }
        if (height < 50 || height > 250) { showToast('Altura inválida (50-250 cm)', 'error'); return; }
      } else {
        if (!document.getElementById('cgCals').value || !document.getElementById('cgProt').value) {
          showToast('Ingresa tus calorías y proteínas', 'error'); return;
        }
      }

      const profile = { age: age || 25, sex: sex || 'male', weight: weight || 70, height: height || 170, activityLevel: selectedActivity };
      const tdee = calculateTDEE(profile);

      let customTargets = null;
      if (selectedGoal === 'custom') {
        customTargets = {
          calories: parseInt(document.getElementById('cgCals').value) || 2000,
          protein: parseInt(document.getElementById('cgProt').value) || 150,
          carbs: parseInt(document.getElementById('cgCarbs').value) || 200,
          fat: parseInt(document.getElementById('cgFat').value) || 65
        };
      }

      const macros = calculateMacros(tdee, selectedGoal, weight || 70, customTargets);

      document.getElementById('tdeeVal').textContent = tdee;

      const macroRows = [
        { label: 'Calorías', val: macros.calories, unit: 'kcal', cls: 'primary' },
        { label: 'Proteína', val: macros.protein, unit: 'g', cls: 'protein' },
        { label: 'Carbos', val: macros.carbs, unit: 'g', cls: 'carbs' },
        { label: 'Grasas', val: macros.fat, unit: 'g', cls: 'fat' },
      ];

      document.getElementById('macroResultCards').innerHTML = macroRows.map((m, i) => `
    <div class="stat-card ${m.cls === 'primary' ? 'primary' : ''}" style="animation-delay:${i * 0.05}s;">
      <div class="stat-label">${m.label}</div>
      <div class="stat-value" style="font-size:22px;">${m.val}<span class="stat-unit" style="font-size:12px;">${m.unit}</span></div>
    </div>`).join('');

      const totalCals = macros.calories;
      const data = [
        { label: 'Proteína', color: 'var(--primary)', g: macros.protein, pct: ((macros.protein * 4) / totalCals) * 100 },
        { label: 'Carbos', color: 'var(--text-main)', g: macros.carbs, pct: ((macros.carbs * 4) / totalCals) * 100 },
        { label: 'Grasas', color: 'var(--text-muted)', g: macros.fat, pct: ((macros.fat * 9) / totalCals) * 100 },
      ];

      document.getElementById('macroPctBars').innerHTML = data.map(m => `
    <div class="macro-progress-item">
      <div class="flex justify-between items-center mb-8">
        <span class="form-label" style="font-size:12px; margin:0;">${m.label} (${Math.round(m.pct)}%)</span>
        <span class="text-sm font-bold" style="color:var(--text-main);">${m.g}g</span>
      </div>
      <div class="progress-bar-wrap" style="height:8px; background:var(--bg-app);">
        <div class="progress-bar-fill" style="width:${m.pct}%; background:${m.color};"></div>
      </div>
    </div>`).join('');

      document.getElementById('resultsSection').style.display = 'block';
      setTimeout(() => document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    }

    function saveGoalsAndNotify() {
      let age = 25;
      const birthDateStr = document.getElementById('profBirthDate').value;
      if (birthDateStr) {
        const bd = new Date(birthDateStr);
        const td = new Date();
        age = td.getFullYear() - bd.getFullYear();
        if (td.getMonth() < bd.getMonth() || (td.getMonth() === bd.getMonth() && td.getDate() < bd.getDate())) age--;
      }
      const sex = document.getElementById('profSex').value;
      const weight = parseFloat(document.getElementById('profWeight').value);
      const height = parseFloat(document.getElementById('profHeight').value);

      const tdee = calculateTDEE({ age: age || 25, sex: sex || 'male', weight: weight || 70, height: height || 170, activityLevel: selectedActivity });

      let customTargets = null;
      if (selectedGoal === 'custom') {
        customTargets = {
          calories: parseInt(document.getElementById('cgCals').value) || 2000,
          protein: parseInt(document.getElementById('cgProt').value) || 150,
          carbs: parseInt(document.getElementById('cgCarbs').value) || 200,
          fat: parseInt(document.getElementById('cgFat').value) || 65
        };
      }

      const macros = calculateMacros(tdee, selectedGoal, weight || 70, customTargets);

      saveGoals({ tdee, goal: selectedGoal, targets: macros });

      const updated = {
        ...getUser(),
        name: document.getElementById('profName').value.trim() || getUser()?.name,
        age, birthDate: birthDateStr, sex, weight, height,
        activityLevel: selectedActivity,
        goal: selectedGoal
      };
      saveUser(updated);

      const accs = store.get('nutriai_accounts', []);
      const idx = accs.findIndex(a => a.email === updated.email);
      if (idx !== -1) { accs[idx] = updated; store.set('nutriai_accounts', accs); }

      initSidebar('goals');
      showToast('¡Configuración guardada con éxito!', 'success');
    }

    if (user?.weight && user?.height && user?.age) setTimeout(calculate, 300);
  </script>
</body>

</html>
