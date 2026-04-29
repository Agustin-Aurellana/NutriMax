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
  <title>NutriMax</title>
  <meta name="description"
    content="Controla tus macros, guarda recetas, registra tus comidas y obtén consejos personalizados de tu coach de IA nutricional." />
  <link rel="stylesheet" href="css/styles.css" />
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <style>
    body {
      overflow-x: hidden;
    }

    .auth-card {
      background: var(--white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      padding: 36px 40px;
      width: 100%;
      max-width: 420px;
    }

    .feature-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      background: var(--white);
      border-radius: 99px;
      font-size: 12px;
      font-weight: 600;
      color: var(--gray-700);
      box-shadow: var(--shadow-sm);
      margin: 4px;
    }

    .floating-card {
      position: absolute;
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      padding: 14px 18px;
      border: 1px solid var(--gray-100);
      animation: pulse 2.5s infinite;
    }

    .fc-top {
      top: 60px;
      left: -40px;
      animation-delay: 0s;
    }

    .fc-bottom {
      bottom: 80px;
      right: -40px;
      animation-delay: 1.2s;
    }

    .fc-mid {
      top: 45%;
      right: -60px;
      animation-delay: 0.6s;
    }

    /* Google Sign-In button */
    .btn-google {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      padding: 11px 20px;
      background: var(--white);
      color: var(--gray-800);
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      font-family: inherit;
    }

    .btn-google:hover {
      background: var(--gray-50);
      border-color: var(--gray-300);
      box-shadow: var(--shadow-sm);
    }

    .btn-google svg {
      flex-shrink: 0;
    }
  </style>
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#111827">
  <meta name="view-transition" content="same-origin">
  <!-- Flatpickr for beautiful calendars -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
  <div class="hero">
    <div class="hero-inner">
      <!-- --- Left: Copy + Auth --- -->
      <div>
        <div class="hero-badge">Impulsa tu rendimiento, no tu ego.</div>
        <h1 class="hero-title">
          Tu mejor versión<br />comienza con <span class="accent">NutriMax</span>
        </h1>
        <p class="hero-desc">
          La plataforma inteligente para alcanzar tus objetivos nutricionales. Registro fácil, recetas premium y un
          coach de IA a tu disposición 24/7.
        </p>

          <!-- AUTH CARD -->
        <div class="auth-card">
          <div class="tab-list mb-24">
            <div class="tab-btn active" id="tabLogin" onclick="switchTab('login')">Entrar</div>
            <div class="tab-btn" id="tabRegister" onclick="switchTab('register')">Crear cuenta</div>
          </div>

          <!-- Login -->
          <div class="tab-panel active" id="panelLogin">
            <form class="auth-form" onsubmit="handleLogin(event)">
              <div class="form-group mb-16">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" id="loginEmail" placeholder="tu@email.com" required />
              </div>
              <div class="form-group mb-16">
                <label class="form-label">Contraseña</label>
                <input class="form-input" type="password" id="loginPassword" placeholder="••••••••" required />
              </div>
              <button class="btn btn-primary w-full" type="submit" style="margin-top: 8px;">
                Acceder ahora
              </button>
            </form>

            <div class="divider-text mt-24 mb-24"
              style="text-align:center; color:var(--text-muted); font-size:13px; position:relative;">
              <span style="background:var(--white); padding:0 12px; position:relative; z-index:1;">o continuar
                con</span>
              <div
                style="position:absolute; top:50%; left:0; right:0; height:1px; background:var(--border); z-index:0;">
              </div>
            </div>

            <button type="button" class="btn-google w-full mb-12" id="googleLoginBtn" onclick="handleGoogleCredential()">
              <svg width="20" height="20" viewBox="0 0 48 48">
                <path fill="#EA4335"
                  d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.33 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                <path fill="#4285F4"
                  d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
                <path fill="#FBBC05"
                  d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
                <path fill="#34A853"
                  d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.35-8.16 2.35-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.67 14.62 48 24 48z" />
              </svg>
              Google
            </button>

            <button type="button" class="btn btn-secondary w-full" onclick="demoLogin()">
              Probar demo gratuita
            </button>
          </div>

          <!-- Register -->
          <div class="tab-panel" id="panelRegister">
            <form class="auth-form" onsubmit="handleRegister(event)">
              <div class="form-group mb-12">
                <label class="form-label">Nombre completo</label>
                <input class="form-input" type="text" id="regName" placeholder="Tu nombre" required />
              </div>
              <div class="form-group mb-12">
                <label class="form-label">Email</label>
                <input class="form-input" type="email" id="regEmail" placeholder="tu@email.com" required />
              </div>
              <div class="form-group mb-12">
                <label class="form-label">Contraseña</label>
                <input class="form-input" type="password" id="regPassword" placeholder="Mínimo 6 caracteres" required
                  minlength="6" />
              </div>
              <div class="grid-2 mb-12" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">Fecha de nac.</label>
                  <input class="form-input" type="date" id="regBirthDate" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Sexo</label>
                  <select class="form-select form-low" id="regSex" required>
                    <option value="">...</option>
                    <option value="male">Masc.</option>
                    <option value="female">Fem.</option>
                  </select>
                </div>
              </div>
              <div class="grid-2 mb-16" style="gap:12px;">
                <div class="form-group">
                  <label class="form-label">Peso (kg)</label>
                  <input class="form-input form-low" type="number" id="regWeight" placeholder="70" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Altura (cm)</label>
                  <input class="form-input form-low" type="number" id="regHeight" placeholder="175" required />
                </div>
              </div>
              <button class="btn btn-primary w-full" type="submit">
                Empieza ahora
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- --- Right: Visual --- -->
      <div class="hero-visual">
        <div style="position:relative;">

          <!-- Floating indicators -->
          <div class="card"
            style="position:absolute; top:-20px; left:-80px; width:180px; z-index:5; animation: pulse 3s infinite;">
            <div style="font-size:11px;font-weight:800;color:var(--text-muted);margin-bottom:8px;">CONSUMO HOY</div>
            <div style="font-size:28px;font-weight:900;">1,840 <span
                style="font-size:14px;color:var(--text-muted);font-weight:500;">kcal</span></div>
            <div class="progress-bar-wrap mt-8">
              <div class="progress-bar-fill" style="width:75%"></div>
            </div>
          </div>

          <div class="card"
            style="position:absolute; bottom:60px; right:-60px; width:200px; z-index:5; animation: pulse 3s infinite 1.5s;">
            <div style="font-size:11px;font-weight:800;color:var(--primary);margin-bottom:4px;">✨ COACH IA</div>
            <div style="font-size:13px;font-weight:500;line-height:1.4;">"¡Gran avance! Has cumplido el 90% de tu meta
              proteica hoy."</div>
          </div>

          <!-- Phone mockup -->
          <div class="hero-phone-mock">
            <div class="phone-status-bar" style="background:transparent; color: #1e293b; padding: 12px 24px;">
              <span>9:41</span><span>🔋 📶</span>
            </div>
            <div class="phone-content" style="padding: 24px;">
              <div
                style="font-size:16px;font-weight:900;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
                Dashboard
              </div>

              <div class="card" style="padding: 20px; text-align:center; margin-bottom: 20px;">
                <div style="font-size:32px;font-weight:900;color:var(--primary);">1,420</div>
                <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">kcal
                  restantes</div>
                <div style="margin-top: 15px; height: 100px; display:flex; align-items:center; justify-content:center;">
                  <!-- Simple SVG Circle -->
                  <svg width="80" height="80" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--gray-100)" stroke-width="10" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--primary)" stroke-width="10"
                      stroke-dasharray="251" stroke-dashoffset="60" stroke-linecap="round"
                      transform="rotate(-90 50 50)" />
                  </svg>
                </div>
              </div>

              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="card" style="padding:12px; text-align:center;">
                  <div style="font-size:10px;font-weight:700;color:var(--text-muted);">PROT</div>
                  <div style="font-size:16px;font-weight:800;">142g</div>
                </div>
                <div class="card" style="padding:12px; text-align:center;">
                  <div style="font-size:10px;font-weight:700;color:var(--text-muted);">CARBS</div>
                  <div style="font-size:16px;font-weight:800;">195g</div>
                </div>
              </div>

              <div style="margin-top: 20px;">
                <div style="font-size:12px;font-weight:800;margin-bottom:10px;">COMIDAS</div>
                <div class="sidebar-user" style="margin: 0 0 8px 0; background: var(--gray-50); padding: 10px;">
                  <div style="font-size:16px;">🥗</div>
                  <div class="user-info">
                    <div class="user-name" style="font-size:12px;">Ensalada Verde</div>
                    <div class="user-goal" style="font-size:10px;">420 kcal</div>
                  </div>
                </div>
                <div class="sidebar-user" style="margin: 0; background: var(--gray-50); padding: 10px;">
                  <div style="font-size:16px;">🍗</div>
                  <div class="user-info">
                    <div class="user-name" style="font-size:12px;">Pollo Grillé</div>
                    <div class="user-goal" style="font-size:10px;">520 kcal</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <script src="js/config.local.js"></script>
  <script src="js/app.js"></script>
  <script>
    // Check if already logged in
    if (isLoggedIn()) window.location.href = 'dashboard.php';

    initFlatpickr("#regBirthDate");

    /* --- Auth Handlers for Database Integration --- */
    function handleGoogleCredential(response) {
      console.log("Google Credential logic implementation starting here.");
      showToast('Integración con Google en desarrollo.', 'default');
    }

    function switchTab(tab) {
      document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
      document.getElementById('tabRegister').classList.toggle('active', tab === 'register');
      document.getElementById('panelLogin').classList.toggle('active', tab === 'login');
      document.getElementById('panelRegister').classList.toggle('active', tab === 'register');
    }

    function handleLogin(e) {
      e.preventDefault();
      const email = document.getElementById('loginEmail').value.trim();
      const password = document.getElementById('loginPassword').value;
      
      if (email === 'demo@nutriai.com' || email === 'demo@nutrimax.com') {
        demoLogin();
        return;
      }
      
      fetch('login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
      })
      .then(res => res.json())
      .then(data => {
          if (data.status === 'success') {
              const u = data.user;
              
              // Calcular edad
              let age = 25;
              if (u.nacimiento) {
                  const bd = new Date(u.nacimiento);
                  const td = new Date();
                  age = td.getFullYear() - bd.getFullYear();
                  if (td.getMonth() < bd.getMonth() || (td.getMonth() === bd.getMonth() && td.getDate() < bd.getDate())) age--;
              }
              
              const loggedUser = {
                  id: u.ID_USER,
                  name: u.name,
                  email: u.email,
                  sex: u.genero,
                  age: age,
                  birthDate: u.nacimiento,
                  weight: parseFloat(u.peso),
                  height: parseFloat(u.altura_cm),
                  activityLevel: u.act_fisica,
                  goal: u.objetivo,
                  savedRecipes: []
              };
              
              saveUser(loggedUser);
              
              const tdee = calculateTDEE(loggedUser);
              const macros = calculateMacros(tdee, loggedUser.goal, loggedUser.weight);
              saveGoals({ tdee, goal: loggedUser.goal, targets: macros });
              
              showToast('¡Bienvenido!', 'success');
              setTimeout(() => window.location.href = 'dashboard.php', 800);
          } else {
              showToast(data.message || 'Error al iniciar sesión', 'error');
          }
      })
      .catch(err => {
          showToast('Error de conexión', 'error');
          console.error(err);
      });
    }

    function handleRegister(e) {
      e.preventDefault();
      
      const name = document.getElementById('regName').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const password = document.getElementById('regPassword').value;
      const birthDate = document.getElementById('regBirthDate').value;
      const sex = document.getElementById('regSex').value;
      const weight = parseFloat(document.getElementById('regWeight').value);
      const height = parseFloat(document.getElementById('regHeight').value);
      
      if (!birthDate) {
        showToast('Por favor ingresa tu fecha de nacimiento', 'error');
        return;
      }
      
      // Calculate age
      const dob = new Date(birthDate);
      const diffMs = Date.now() - dob.getTime();
      const ageDt = new Date(diffMs); 
      const age = Math.abs(ageDt.getUTCFullYear() - 1970);
      
      const newUser = {
        name,
        email,
        password,
        sex,
        age,
        birthDate,
        weight,
        height,
        activityLevel: 'moderate',
        goal: 'maintenance',
        savedRecipes: []
      };
      
      // Enviar datos al Backend
      fetch('registro', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify(newUser)
      })
      .then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
              // Eliminar contraseña por seguridad antes de guardar en localStorage
              delete newUser.password;
              
              saveUser(newUser);
              
              const tdee = calculateTDEE(newUser);
              const macros = calculateMacros(tdee, newUser.goal, newUser.weight);
              saveGoals({ tdee, goal: newUser.goal, targets: macros });
              
              showToast('¡Cuenta creada exitosamente!', 'success');
              setTimeout(() => window.location.href = 'dashboard.php', 800);
          } else {
              showToast(data.message || 'Error al crear la cuenta', 'error');
          }
      })
      .catch(error => {
          showToast('Error de conexión con el servidor', 'error');
          console.error('Error:', error);
      });
    }

    function demoLogin() {
      const demo = { 
        name: 'Demo', 
        email: 'demo@nutrimax.com', 
        sex: 'male', 
        age: 26, 
        birthDate: '2000-01-01', 
        weight: 75, 
        height: 178, 
        activityLevel: 'moderate', 
        goal: 'definition', 
        savedRecipes: [] 
      };
      saveUser(demo);
      
      // Seed demo food log for today
      const logs = getLogs();
      const key = todayKey();
      if (!logs[key] || !logs[key].entries.length) {
        logs[key] = {
          date: key, entries: [
            { id: 1001, mealType: 'Desayuno', name: 'Avena con Mantequilla de Maní', calories: 420, protein: 16, carbs: 58, fat: 12 },
            { id: 1002, mealType: 'Almuerzo', name: 'Bowl de Atún con Quinoa', calories: 420, protein: 42, carbs: 32, fat: 10 },
            { id: 1003, mealType: 'Snack', name: 'Yogur Griego con Berries', calories: 220, protein: 18, carbs: 24, fat: 4 },
          ]
        };
        store.set(KEYS.LOGS, logs);
      }
      
      // Seed goals
      const tdee = calculateTDEE(demo);
      const macros = calculateMacros(tdee, demo.goal, demo.weight);
      saveGoals({ tdee, goal: demo.goal, targets: macros });
      
      showToast('Entrando con cuenta demo...', 'success');
      setTimeout(() => window.location.href = 'dashboard.php', 800);
    }
  </script>
</body>

</html>
