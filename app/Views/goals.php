<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>Objetivos — NutriMax</title>
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    :root {
      --wizard-card-bg: rgba(255, 255, 255, 0.03);
      --wizard-border: rgba(255, 255, 255, 0.08);
    }
    
    [data-theme="light"] {
      --wizard-card-bg: rgba(0, 0, 0, 0.02);
      --wizard-border: rgba(0, 0, 0, 0.08);
    }

    .wizard-container {
      max-width: 600px;
      margin: 0 auto;
      padding: 10px;
      height: calc(100vh - 100px);
      display: flex;
      flex-direction: column;
    }

    .wizard-header { margin-bottom: 15px; text-align: center; }
    .wizard-title { font-size: 20px; font-weight: 900; margin: 0; }
    .wizard-subtitle { font-size: 12px; color: var(--text-muted); opacity: 0.8; }

    /* Stepper Moderno y Compacto */
    .wizard-steps {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 20px;
    }
    .step-dot {
      width: 32px;
      height: 6px;
      border-radius: 10px;
      background: var(--wizard-border);
      transition: all 0.3s ease;
    }
    .step-dot.active { background: var(--primary); width: 48px; }
    .step-dot.completed { background: var(--primary-200); }

    /* Paneles de Contenido */
    .wizard-panel {
      display: none;
      flex: 1;
      animation: slideUp .4s cubic-bezier(0.4, 0, 0.2, 1) both;
    }
    .wizard-panel.active { display: flex; flex-direction: column; }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Selectores Visuales Optimizados */
    .visual-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }
    .visual-grid.full { grid-template-columns: 1fr; }

    /* Forzar siempre 2 columnas para el sexo */
    .sex-grid {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr) !important;
      gap: 10px;
    }

    .v-card {
      background: var(--wizard-card-bg);
      border: 1px solid var(--wizard-border);
      border-radius: 16px;
      padding: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
    }
    .v-card:hover { border-color: var(--primary-200); background: rgba(var(--primary-rgb), 0.05); }
    .v-card.active { border-color: var(--primary); background: rgba(var(--primary-rgb), 0.1); }
    
    .v-card.active::after {
      content: '✓';
      position: absolute;
      top: 8px;
      right: 12px;
      color: var(--primary);
      font-weight: bold;
      font-size: 14px;
    }

    .v-icon-wrap {
      width: 40px;
      height: 40px;
      background: var(--wizard-border);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    .v-card.active .v-icon-wrap { background: var(--primary); color: white; }

    .v-info { flex: 1; }
    .v-label { font-size: 13px; font-weight: 700; display: block; margin-bottom: 2px; }
    .v-sub { font-size: 10px; color: var(--text-muted); }

    /* Inputs de Biometría Compactos */
    .bio-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    .input-pill {
      background: var(--wizard-card-bg);
      border: 1px solid var(--wizard-border);
      border-radius: 12px;
      padding: 10px 15px;
      color: var(--text-main);
      width: 100%;
      font-size: 14px;
      transition: border 0.3s;
    }
    .input-pill:focus { border-color: var(--primary); outline: none; }

    /* DISEÑO PREMIUM RESULTADOS (REDISEÑO TOTAL) */
    .result-dashboard {
      background: linear-gradient(165deg, #1e1e2e 0%, #11111b 100%);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 28px;
      padding: 30px 20px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .result-dashboard::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle at center, rgba(var(--primary-rgb), 0.15) 0%, transparent 40%);
      animation: rotateGlow 10s linear infinite;
    }

    @keyframes rotateGlow {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .res-header { position: relative; z-index: 1; }
    .res-label { font-size: 11px; font-weight: 800; letter-spacing: 2px; color: var(--primary); text-transform: uppercase; margin-bottom: 5px; display: block; }
    .res-main-val { font-size: 56px; font-weight: 950; color: white; letter-spacing: -3px; line-height: 1; margin-bottom: 5px; }
    .res-sub { font-size: 13px; color: rgba(255, 255, 255, 0.5); font-weight: 500; }

    .macros-container {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin: 30px 0;
      position: relative;
      z-index: 1;
    }

    .m-card {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 15px 10px;
      text-align: center;
      transition: transform 0.3s ease;
    }
    .m-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.07); }

    .m-card .m-icon { font-size: 20px; margin-bottom: 8px; display: block; }
    .m-card .m-number { font-size: 22px; font-weight: 900; color: white; display: block; }
    .m-card .m-name { font-size: 10px; font-weight: 800; color: rgba(255, 255, 255, 0.4); text-transform: uppercase; margin-top: 2px; }

    .composition-bar {
      height: 12px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      display: flex;
      overflow: hidden;
      margin-bottom: 25px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .strategy-note {
      background: rgba(var(--primary-rgb), 0.1);
      border-radius: 16px;
      padding: 12px 15px;
      display: flex;
      align-items: center;
      gap: 12px;
      text-align: left;
      margin-bottom: 10px;
      position: relative;
      z-index: 1;
    }
    .strategy-note span { font-size: 11px; line-height: 1.4; color: rgba(255, 255, 255, 0.8); font-weight: 500; }

    .btn-activate {
      background: var(--primary);
      color: white;
      border: none;
      height: 56px;
      border-radius: 18px;
      font-size: 15px;
      font-weight: 900;
      width: 100%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 15px 30px rgba(var(--primary-rgb), 0.3);
      transition: all 0.3s ease;
      position: relative;
      z-index: 1;
    }
    .btn-activate:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(var(--primary-rgb), 0.4); }

    /* Controles */
    .nav-buttons {
      display: flex;
      gap: 10px;
      margin-top: auto;
      padding: 15px 0;
    }
    .btn-nav {
      flex: 1;
      height: 48px;
      border-radius: 14px;
      font-weight: 800;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-next { background: var(--primary); color: white; border: none; }
    .btn-back { background: var(--wizard-card-bg); border: 1px solid var(--wizard-border); color: var(--text-main); }
    
    @media (max-width: 480px) {
      .visual-grid { grid-template-columns: 1fr; }
      .v-card { padding: 10px 8px; flex-direction: column; text-align: center; gap: 4px; }
      .v-icon-wrap { width: 32px; height: 32px; font-size: 16px; }
      .v-label { font-size: 12px; }
      .v-card.active::after { top: 4px; right: 8px; font-size: 12px; }
      .bio-grid { grid-template-columns: 1fr; }
      .wizard-container { height: auto; min-height: 100vh; }
    }
  </style>
</head>
<body>

  <div class="app-shell">
    <aside class="sidebar" id="sidebar"></aside>
    <main class="main-content">
      <div class="wizard-container">
        
        <header class="wizard-header">
          <h1 class="wizard-title">Configuración NutriMax</h1>
          <p class="wizard-subtitle">Ajustemos tu plan al milímetro</p>
        </header>

        <div class="wizard-steps" id="stepper">
          <div class="step-dot active"></div>
          <div class="step-dot"></div>
          <div class="step-dot"></div>
          <div class="step-dot"></div>
        </div>

        <!-- PASO 1: BIO -->
        <div class="wizard-panel active" id="panel1">
          <div style="margin-bottom: 15px;">
            <label class="v-sub" style="margin-left: 5px;">NOMBRE COMPLETO</label>
            <input class="input-pill" type="text" id="profName" placeholder="Ej: Juan Pérez" />
          </div>

          <label class="v-sub" style="margin-left: 5px; margin-bottom: 8px; display: block;">SEXO BIOLÓGICO</label>
          <div class="sex-grid" style="margin-bottom: 20px;">
            <div class="v-card" id="sexMale" onclick="setSex('male')">
              <div class="v-icon-wrap">♂️</div>
            </div>
            <div class="v-card" id="sexFemale" onclick="setSex('female')">
              <div class="v-icon-wrap">♀️</div>
            </div>
          </div>

          <div class="bio-grid">
            <div>
              <label class="v-sub">PESO (KG)</label>
              <input class="input-pill" type="number" id="profWeight" placeholder="70" />
            </div>
            <div>
              <label class="v-sub">ALTURA (CM)</label>
              <input class="input-pill" type="number" id="profHeight" placeholder="175" />
            </div>
            <div>
              <label class="v-sub">NACIMIENTO</label>
              <input class="input-pill" type="date" id="profBirthDate" />
            </div>
          </div>
          <select id="profSex" style="display:none;"><option value="male">male</option><option value="female">female</option></select>
        </div>

        <!-- PASO 2: ACTIVIDAD -->
        <div class="wizard-panel" id="panel2">
          <div class="visual-grid" id="activityGrid" style="grid-template-columns: 1fr;">
            <!-- Render dinámico -->
          </div>
        </div>

        <!-- PASO 3: OBJETIVO -->
        <div class="wizard-panel" id="panel3">
          <div class="visual-grid" id="goalGrid">
            <!-- Render dinámico -->
          </div>
          <div id="customGoalInputs" style="display:none; margin-top:15px; grid-template-columns: 1fr 1fr; gap:8px;">
            <input class="input-pill" type="number" id="cgCals" placeholder="Kcal" />
            <input class="input-pill" type="number" id="cgProt" placeholder="Prot (g)" />
            <input class="input-pill" type="number" id="cgCarbs" placeholder="Carb (g)" />
            <input class="input-pill" type="number" id="cgFat" placeholder="Grasa (g)" />
          </div>
        </div>

        <!-- PASO 4: RESULTADO -->
        <div class="wizard-panel" id="panel4">
          <div class="result-dashboard">
            <div class="res-header">
              <span class="res-label">Tu Nuevo Plan Nutricional</span>
              <div class="res-main-val" id="tdeeVal">0</div>
              <div class="res-sub">Kilocalorías por día para tu objetivo</div>
            </div>
            
            <div class="macros-container">
              <div class="m-card" style="border-top: 3px solid #818cf8;">
                <span class="m-icon">🥩</span>
                <span class="m-number" id="resProt">0g</span>
                <span class="m-name">Proteína</span>
              </div>
              <div class="m-card" style="border-top: 3px solid #fbbf24;">
                <span class="m-icon">🍝</span>
                <span class="m-number" id="resCarbs">0g</span>
                <span class="m-name">Carbos</span>
              </div>
              <div class="m-card" style="border-top: 3px solid #f87171;">
                <span class="m-icon">🥑</span>
                <span class="m-number" id="resFat">0g</span>
                <span class="m-name">Grasas</span>
              </div>
            </div>

            <div class="composition-bar" id="macroBarsCompact"></div>

            <div class="strategy-note">
              <div style="font-size: 20px;">💡</div>
              <span id="strategyAdvice">Calculando la mejor estrategia para optimizar tu rendimiento y composición corporal...</span>
            </div>

            <button class="btn-activate" onclick="saveGoalsAndNotify()">
              Confirmar y Activar Plan 🚀
            </button>
          </div>
        </div>

        <div class="nav-buttons">
          <button class="btn-nav btn-back" id="btnBack" onclick="changeStep(-1)" style="display:none;">Atrás</button>
          <button class="btn-nav btn-next" id="btnNext" onclick="changeStep(1)">Siguiente</button>
        </div>

      </div>
    </main>
  </div>

  <script src="js/config.local.js"></script>
  <script src="js/app.js"></script>
  <script>
    window.currentActivePage = 'goals';
    requireAuth();
    initSidebar('goals');

    let currentStep = 1;
    let selectedSex = 'male';
    let selectedActivity = 'moderate';
    let selectedGoal = 'maintenance';

    // FIX: Claves correctas para coincidir con ACTIVITY_MULTIPLIERS en app.js
    const ACTIVITY_ICONS = { 
      sedentary: '🪑', 
      light: '🚶', 
      moderate: '🏋️', 
      active: '⚡', 
      very_active: '🔥' 
    };
    
    const GOAL_ICONS = { 
      definition: '📉', 
      volume: '📈', 
      maintenance: '⚖️', 
      recomp: '🔄', 
      custom: '⚙️' 
    };

    function changeStep(dir) {
      if (dir === 1 && !validateCurrentStep()) return;
      const newStep = currentStep + dir;
      if (newStep < 1 || newStep > 4) return;

      document.getElementById(`panel${currentStep}`).classList.remove('active');
      currentStep = newStep;
      document.getElementById(`panel${currentStep}`).classList.add('active');

      // Actualizar Stepper
      const dots = document.querySelectorAll('.step-dot');
      dots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx + 1 === currentStep);
        dot.classList.toggle('completed', idx + 1 < currentStep);
      });

      document.getElementById('btnBack').style.display = (currentStep === 1) ? 'none' : 'flex';
      document.getElementById('btnNext').style.display = (currentStep === 4) ? 'none' : 'flex';

      if (currentStep === 4) calculate();
    }

    function validateCurrentStep() {
      if (currentStep === 1) {
        if (!document.getElementById('profName').value) { showToast('Falta tu nombre','warning'); return false; }
        if (!document.getElementById('profWeight').value) { showToast('Falta tu peso','warning'); return false; }
      }
      return true;
    }

    function setSex(val) {
      selectedSex = val;
      document.getElementById('profSex').value = val;
      document.getElementById('sexMale').classList.toggle('active', val === 'male');
      document.getElementById('sexFemale').classList.toggle('active', val === 'female');
    }

    function setActivity(key) { selectedActivity = key; renderActivityOptions(); }
    function setGoal(key) { selectedGoal = key; renderGoalOptions(); document.getElementById('customGoalInputs').style.display = (key === 'custom') ? 'grid' : 'none'; }

    function renderActivityOptions() {
      document.getElementById('activityGrid').innerHTML = Object.entries(ACTIVITY_MULTIPLIERS).map(([key, val]) => {
        const isSelected = key === selectedActivity;
        return `<div class="v-card ${isSelected ? 'active' : ''}" onclick="setActivity('${key}')">
          <div class="v-icon-wrap">${ACTIVITY_ICONS[key] || '💪'}</div>
          <div class="v-info"><span class="v-label">${val.label.split('(')[0]}</span><span class="v-sub">${val.label.match(/\(([^)]+)\)/)?.[1] || ''}</span></div>
        </div>`;
      }).join('');
    }

    function renderGoalOptions() {
      const details = { definition: 'Déficit calórico', volume: 'Superávit calórico', maintenance: 'Equilibrio', recomp: 'Grasa x Músculo', custom: 'Manual' };
      document.getElementById('goalGrid').innerHTML = Object.entries(GOALS_CONFIG).map(([key, cfg]) => {
        const isSelected = key === selectedGoal;
        return `<div class="v-card ${isSelected ? 'active' : ''}" onclick="setGoal('${key}')">
          <div class="v-icon-wrap">${GOAL_ICONS[key] || '🎯'}</div>
          <div class="v-info"><span class="v-label">${cfg.label}</span><span class="v-sub">${details[key]}</span></div>
        </div>`;
      }).join('');
    }

    function calculate() {
      const weight = parseFloat(document.getElementById('profWeight').value) || 70;
      const height = parseFloat(document.getElementById('profHeight').value) || 170;
      let age = 25;
      const bDate = document.getElementById('profBirthDate').value;
      if (bDate) age = new Date().getFullYear() - new Date(bDate).getFullYear();

      const tdee = calculateTDEE({ weight, height, age, sex: selectedSex, activityLevel: selectedActivity });
      let manual = (selectedGoal === 'custom') ? { calories: parseInt(document.getElementById('cgCals').value), protein: parseInt(document.getElementById('cgProt').value), carbs: parseInt(document.getElementById('cgCarbs').value), fat: parseInt(document.getElementById('cgFat').value) } : null;
      
      const macros = calculateMacros(tdee, selectedGoal, weight, manual);
      
      document.getElementById('tdeeVal').textContent = macros.calories;
      document.getElementById('resProt').textContent = macros.protein + 'g';
      document.getElementById('resCarbs').textContent = macros.carbs + 'g';
      document.getElementById('resFat').textContent = macros.fat + 'g';

      const total = macros.calories;
      const pcts = [
        { c: '#818cf8', p: Math.round((macros.protein*4/total)*100) },
        { c: '#fbbf24', p: Math.round((macros.carbs*4/total)*100) },
        { c: '#f87171', p: Math.round((macros.fat*9/total)*100) }
      ];
      document.getElementById('macroBarsCompact').innerHTML = pcts.map(p => `<div style="width:${p.p}%; background:${p.c}; transition:width 0.8s ease-in-out;"></div>`).join('');

      // Recomendación dinámica según objetivo
      const advices = {
        definition: "Prioriza el entrenamiento de fuerza y el consumo de fibra para mantener saciedad durante este déficit.",
        volume: "Asegúrate de llegar a tus calorías diarias; la constancia en el superávit es la clave del crecimiento.",
        maintenance: "Tu plan busca equilibrio metabólico. Ajusta según niveles de energía semanales.",
        recomp: "Foco total en proteína y entrenamiento intenso. Es el camino más difícil pero más estético.",
        custom: "Plan manual activado. Sigue tus parámetros según tu estrategia personal."
      };
      document.getElementById('strategyAdvice').textContent = advices[selectedGoal] || advices.maintenance;
    }

    function saveGoalsAndNotify() {
      const weight = parseFloat(document.getElementById('profWeight').value);
      const height = parseFloat(document.getElementById('profHeight').value);
      const name = document.getElementById('profName').value;
      const bDate = document.getElementById('profBirthDate').value;
      
      let age = 25;
      if (bDate) age = new Date().getFullYear() - new Date(bDate).getFullYear();

      const tdee = calculateTDEE({ weight, height, age, sex: selectedSex, activityLevel: selectedActivity });
      const macros = calculateMacros(tdee, selectedGoal, weight, (selectedGoal === 'custom' ? { calories: parseInt(document.getElementById('cgCals').value), protein: parseInt(document.getElementById('cgProt').value), carbs: parseInt(document.getElementById('cgCarbs').value), fat: parseInt(document.getElementById('cgFat').value) } : null));

      const updated = { ...getUser(), name, age, birthDate: bDate, sex: selectedSex, weight, height, activityLevel: selectedActivity, goal: selectedGoal };
      
      fetch('actualizar-perfil', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(updated) })
      .then(res => res.json()).then(data => {
        if (data.status === 'success') {
          saveUser(updated);
          saveGoals({ tdee, goal: selectedGoal, targets: macros });
          showToast('Plan Nutricional Activado', 'success');
          setTimeout(() => location.href = 'dashboard', 1000);
        }
      });
    }

    syncFromDbToLocal().then(() => {
      const u = getUser();
      if (u) {
        document.getElementById('profName').value = u.name || '';
        document.getElementById('profWeight').value = u.weight || '';
        document.getElementById('profHeight').value = u.height || '';
        document.getElementById('profBirthDate').value = u.birthDate || '';
        setSex(u.sex || 'male');
        selectedActivity = u.activityLevel || 'moderate';
        selectedGoal = u.goal || 'maintenance';
      }
      renderActivityOptions(); renderGoalOptions();
    });
  </script>
</body>
</html>
