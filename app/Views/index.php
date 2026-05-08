<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NutriMax – Tu mejor versión comienza aquí</title>
  <meta name="description"
    content="NutriMax te ayuda a controlar tu alimentación, alcanzar tus metas y vivir más sano. Registra tus comidas, sigue tus macros y consulta a tu coach de IA 24/7." />
  <link rel="icon" href="assets/img/launchericon-192x192.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css" />
  <style>
    /* Estilos específicos de la Landing Page que no están en styles.css */

    html {
      scroll-behavior: smooth;
    }

    /* NAV */
    nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 60px;
      background: rgba(var(--bg-app-rgb, 8, 9, 11), 0.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
    }

    [data-theme="light"] nav {
      background: rgba(252, 252, 253, 0.85);
    }

    [data-theme="dark"] nav {
      background: rgba(8, 9, 11, 0.85);
    }

    .nav-logo {
      font-size: 22px;
      font-weight: 900;
      letter-spacing: -0.5px;
      color: var(--text-main);
    }

    .nav-logo span {
      color: var(--primary);
    }

    .nav-cta {
      background: var(--primary);
      color: #fff !important;
      padding: 10px 24px;
      border-radius: 99px;
      font-weight: 700;
      font-size: 14px;
      transition: all .2s;
      border: none;
      cursor: pointer;
    }

    .nav-cta:hover {
      background: var(--primary-600);
      transform: translateY(-1px);
    }

    /* HERO OVERRIDE */
    .landing-hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 120px 24px 80px;
      background: var(--bg-app);
      position: relative;
      overflow: hidden;
    }

    .hero-badge-landing {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(var(--primary-h), var(--primary-s), 50%, 0.15);
      border: 1px solid var(--primary-300);
      color: var(--primary);
      padding: 6px 16px;
      border-radius: 99px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 28px;
    }

    .hero-title-landing {
      font-size: clamp(42px, 7vw, 80px);
      font-weight: 900;
      line-height: 1.05;
      letter-spacing: -2px;
      margin-bottom: 24px;
      color: var(--text-main);
    }

    .hero-title-landing .grad {
      background: linear-gradient(135deg, var(--primary-300), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-sub {
      font-size: 20px;
      color: var(--text-muted);
      max-width: 560px;
      margin: 0 auto 40px;
      line-height: 1.6;
      font-weight: 400;
    }

    .hero-btns {
      display: flex;
      gap: 16px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .hero-stats {
      display: flex;
      gap: 48px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 64px;
      padding-top: 40px;
      border-top: 1px solid var(--border);
    }

    .hero-stat-num {
      font-size: 36px;
      font-weight: 900;
      color: var(--primary);
    }

    .hero-stat-label {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* SECTIONS */
    section {
      padding: 100px 24px;
    }

    .container {
      max-width: 1100px;
      margin: 0 auto;
    }

    .section-label {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: var(--primary);
      margin-bottom: 16px;
    }

    .section-title {
      font-size: clamp(32px, 4vw, 52px);
      font-weight: 900;
      letter-spacing: -1.5px;
      line-height: 1.1;
      margin-bottom: 20px;
      color: var(--text-main);
    }

    .section-sub {
      font-size: 18px;
      color: var(--text-muted);
      max-width: 560px;
      line-height: 1.6;
    }

    /* FEATURES */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
      margin-top: 60px;
    }

    .feature-card-landing {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      transition: all .3s;
      position: relative;
      overflow: hidden;
    }

    .feature-card-landing::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top left, rgba(var(--primary-h), var(--primary-s), 50%, 0.08), transparent 60%);
      opacity: 0;
      transition: opacity .3s;
    }

    .feature-card-landing:hover {
      transform: translateY(-4px);
      border-color: var(--primary-300);
    }

    .feature-card-landing:hover::before {
      opacity: 1;
    }

    .feature-icon-landing {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: var(--primary-100);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 20px;
    }

    .feature-card-landing h3 {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--text-main);
    }

    .feature-card-landing p {
      font-size: 15px;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* HOW IT WORKS */
    .how-section {
      background: var(--gray-50);
    }

    .steps {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 40px;
      margin-top: 60px;
    }

    .step {
      text-align: center;
    }

    .step-num {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-600));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: 900;
      margin: 0 auto 20px;
      color: #fff;
      box-shadow: 0 0 30px rgba(var(--primary-h), var(--primary-s), 42%, 0.4);
    }

    .step h3 {
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 10px;
      color: var(--text-main);
    }

    .step p {
      font-size: 15px;
      color: var(--text-muted);
      line-height: 1.6;
    }

    /* REVIEWS */
    .reviews-section {
      background: var(--bg-app);
    }

    .reviews-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
      margin-top: 60px;
    }

    .review-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 32px;
      transition: all .3s;
    }

    .review-card:hover {
      transform: translateY(-3px);
      border-color: var(--primary-300);
    }

    .review-stars {
      color: #fbbf24;
      font-size: 16px;
      margin-bottom: 16px;
    }

    .review-text {
      font-size: 16px;
      line-height: 1.7;
      color: var(--text-muted);
      margin-bottom: 24px;
      font-style: italic;
    }

    .review-author {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .review-avatar {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 16px;
      color: #fff;
    }

    .review-name {
      font-weight: 700;
      font-size: 14px;
      color: var(--text-main);
    }

    .review-meta {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* MEDICAL DISCLAIMER */
    .disclaimer-landing {
      background: var(--primary-50);
      border: 1px solid var(--primary-100);
      border-radius: var(--radius-lg);
      padding: 40px;
      margin: 0 auto;
      max-width: 800px;
      text-align: center;
    }

    [data-theme="dark"] .disclaimer-landing {
      background: rgba(var(--primary-h), var(--primary-s), 52%, 0.08);
      border-color: rgba(var(--primary-h), var(--primary-s), 52%, 0.2);
    }

    .disclaimer-icon {
      font-size: 40px;
      margin-bottom: 16px;
    }

    .disclaimer-landing h3 {
      font-size: 22px;
      font-weight: 800;
      margin-bottom: 12px;
      color: var(--text-main);
    }

    .disclaimer-landing p {
      font-size: 16px;
      color: var(--text-muted);
      line-height: 1.7;
    }

    /* CTA FINAL */
    .cta-section-landing {
      text-align: center;
      background: var(--primary-50);
      padding: 100px 24px;
    }

    [data-theme="dark"] .cta-section-landing {
      background: rgba(99, 102, 241, 0.05);
      border-top: 1px solid var(--border);
    }

    .cta-section-landing h2 {
      font-size: clamp(36px, 5vw, 60px);
      font-weight: 900;
      letter-spacing: -1.5px;
      margin-bottom: 20px;
      color: var(--text-main);
    }

    .cta-section-landing p {
      font-size: 18px;
      color: var(--text-muted);
      margin-bottom: 40px;
    }

    /* FOOTER */
    footer {
      border-top: 1px solid var(--border);
      padding: 40px 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      color: var(--text-muted);
      font-size: 14px;
      background: var(--bg-app);
    }

    .footer-logo {
      font-size: 18px;
      font-weight: 900;
      color: var(--text-main);
    }

    .footer-logo span {
      color: var(--primary);
    }

    /* ANIMATIONS */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-up {
      opacity: 0;
      animation: fadeUp .7s ease forwards;
    }

    .fade-up-1 {
      animation-delay: .1s;
    }

    .fade-up-2 {
      animation-delay: .2s;
    }

    .fade-up-3 {
      animation-delay: .3s;
    }

    .fade-up-4 {
      animation-delay: .4s;
    }



    /* THEME TOGGLE */
    .theme-toggle {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      background: var(--gray-100);
      color: var(--text-muted);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      border: 1.5px solid var(--border);
      flex-shrink: 0;
    }

    .theme-toggle:hover {
      background: var(--primary-50);
      color: var(--primary);
      border-color: var(--primary-300);
      transform: scale(1.05);
    }

    [data-theme="dark"] .theme-toggle {
      background: var(--gray-800);
      border-color: var(--gray-700);
    }

    .theme-toggle svg {
      transition: transform 0.5s var(--ease-spring);
    }

    .theme-toggle:hover svg {
      transform: rotate(15deg);
    }

    [data-theme="dark"] .sun-icon {
      display: block;
    }

    [data-theme="dark"] .moon-icon {
      display: none;
    }

    body:not([data-theme="dark"]) .sun-icon {
      display: none;
    }

    body:not([data-theme="dark"]) .moon-icon {
      display: block;
    }

    @media (max-width: 768px) {
      nav {
        padding: 16px 24px;
      }

      .hero-stats {
        gap: 32px;
      }

      footer {
        flex-direction: column;
        text-align: center;
      }
    }
  </style>
  <script>
    // Theme initialization to avoid flash
    (function () {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-theme', savedTheme);
      document.addEventListener('DOMContentLoaded', () => {
        document.body.setAttribute('data-theme', savedTheme);
      });
    })();
  </script>
</head>

<body data-theme="light">

  <!-- NAV -->
  <nav>
    <div class="nav-logo">Nutri<span>Max</span></div>
    <div style="display:flex; gap: 16px; align-items:center;">
      <button id="themeToggle" class="theme-toggle" title="Cambiar tema">
        <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"></circle>
          <line x1="12" y1="1" x2="12" y2="3"></line>
          <line x1="12" y1="21" x2="12" y2="23"></line>
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
          <line x1="1" y1="12" x2="3" y2="12"></line>
          <line x1="21" y1="12" x2="23" y2="12"></line>
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
      </button>
      <a href="auth" class="nav-cta">Empezar gratis</a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="landing-hero">
    <div style="position:relative; z-index:1;">
      <div class="hero-badge-landing fade-up fade-up-1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path
            d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
        </svg>
        Tu salud, bajo control
      </div>
      <h1 class="hero-title-landing fade-up fade-up-2">
        Come bien.<br>
        <span class="grad">Vive mejor.</span>
      </h1>
      <p class="hero-sub fade-up fade-up-3">
        NutriMax te ayuda a entender qué comés, cuánto necesitás y cómo mejorar. Sin dietas raras. Sin tecnicismos. Solo
        resultados.
      </p>
      <div class="hero-btns fade-up fade-up-4">
        <a href="auth" class="btn btn-primary btn-lg">Empezar gratis →</a>
        <a href="#como-funciona" class="btn btn-secondary btn-lg">¿Cómo funciona?</a>
      </div>
      <div class="hero-stats fade-up" style="animation-delay:.5s">
        <div>
          <div class="hero-stat-num">10K+</div>
          <div class="hero-stat-label">Usuarios activos</div>
        </div>
        <div>
          <div class="hero-stat-num">500+</div>
          <div class="hero-stat-label">Recetas saludables</div>
        </div>
        <div>
          <div class="hero-stat-num" style="display:flex; align-items:center; gap:8px;">4.9 <svg width="28" height="28"
              viewBox="0 0 24 24" fill="#fbbf24" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg></div>
          <div class="hero-stat-label">Valoración promedio</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="funcionalidades">
    <div class="container">
      <div class="section-label">Funcionalidades</div>
      <h2 class="section-title">Todo lo que necesitás,<br>en un solo lugar</h2>
      <p class="section-sub">Sin apps extra, sin confusión. NutriMax te da las herramientas para tomar el control de tu
        alimentación hoy.</p>
      <div class="features-grid">
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="20" x2="18" y2="10"></line>
              <line x1="12" y1="20" x2="12" y2="4"></line>
              <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
          </div>
          <h3>Seguimiento de calorías y macros</h3>
          <p>Registrá cada comida en segundos. Sabé exactamente cuántas proteínas, carbohidratos y grasas consumís cada
            día.</p>
        </div>
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="10" rx="2"></rect>
              <circle cx="12" cy="5" r="2"></circle>
              <path d="M12 7v4"></path>
              <line x1="8" y1="16" x2="8" y2="16"></line>
              <line x1="16" y1="16" x2="16" y2="16"></line>
            </svg>
          </div>
          <h3>Coach de IA disponible 24/7</h3>
          <p>¿Tenés una duda sobre qué comer? Tu coach nutricional con inteligencia artificial está siempre listo para
            ayudarte.</p>
        </div>
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path>
              <path d="M7 2v20"></path>
              <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path>
            </svg>
          </div>
          <h3>Recetas según tu objetivo</h3>
          <p>Más de 500 recetas organizadas por meta: definición, volumen, mantenimiento. Filtrá por dieta vegana, keto,
            sin gluten y más.</p>
        </div>
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <circle cx="12" cy="12" r="6"></circle>
              <circle cx="12" cy="12" r="2"></circle>
            </svg>
          </div>
          <h3>Metas personalizadas</h3>
          <p>NutriMax calcula exactamente cuántas calorías necesitás según tu cuerpo, tu actividad y tu objetivo. Sin
            adivinar.</p>
        </div>
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
              <polyline points="16 7 22 7 22 13"></polyline>
            </svg>
          </div>
          <h3>Estadísticas de tu progreso</h3>
          <p>Visualizá tu evolución semana a semana. Sabé si estás avanzando hacia tu meta y ajustá si hace falta.</p>
        </div>
        <div class="feature-card-landing">
          <div class="feature-icon-landing">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
              <line x1="12" y1="18" x2="12.01" y2="18"></line>
            </svg>
          </div>
          <h3>Funciona en cualquier dispositivo</h3>
          <p>Usala desde el celular, tablet o computadora. Incluso podés instalarla como app y usarla sin internet.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section id="como-funciona" class="how-section">
    <div class="container">
      <div class="section-label">Cómo funciona</div>
      <h2 class="section-title">Empezá en menos<br>de 2 minutos</h2>
      <p class="section-sub">No hace falta ser nutricionista ni tener experiencia. Si podés usar el celular, podés usar
        NutriMax.</p>
      <div class="steps">
        <div class="step">
          <div class="step-num">1</div>
          <h3>Creá tu cuenta</h3>
          <p>Ingresá tu peso, altura y objetivo. NutriMax calcula todo lo que necesitás saber sobre tu cuerpo.</p>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <h3>Registrá lo que comés</h3>
          <p>Buscá un alimento, elegí una receta o escaneá un código de barras. Rápido y sin complicaciones.</p>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <h3>Seguí tu progreso</h3>
          <p>Mirá tus números del día, revisá tus estadísticas y consultá a tu coach cuando tengas dudas.</p>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <h3>Lográ tus metas</h3>
          <p>Con información clara y herramientas simples, tomar decisiones más sanas se vuelve algo natural.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- REVIEWS -->
  <section id="reseñas" class="reviews-section">
    <div class="container">
      <div class="section-label">Historias reales</div>
      <h2 class="section-title">Ellos ya<br>lo lograron</h2>
      <p class="section-sub">Miles de personas cambiaron su relación con la comida usando NutriMax. Estas son algunas de
        sus historias.</p>
      <div class="reviews-grid">
        <div class="review-card">
          <div class="review-stars" style="display:flex; gap:4px; color:#fbbf24;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
          </div>
          <p class="review-text">"Bajé 8 kilos en 3 meses sin pasar hambre. Por primera vez entendí qué estaba comiendo
            y eso lo cambió todo."</p>
          <div class="review-author">
            <div class="review-avatar">S</div>
            <div>
              <div class="review-name">Sofía M.</div>
              <div class="review-meta">Perdió 8 kg · Buenos Aires</div>
            </div>
          </div>
        </div>
        <div class="review-card">
          <div class="review-stars" style="display:flex; gap:4px; color:#fbbf24;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
          </div>
          <p class="review-text">"Llevo 6 meses entrenando y con NutriMax finalmente empecé a ganar músculo de verdad.
            El coach de IA es increíble."</p>
          <div class="review-author">
            <div class="review-avatar">M</div>
            <div>
              <div class="review-name">Martín R.</div>
              <div class="review-meta">Aumentó 5 kg de músculo · Córdoba</div>
            </div>
          </div>
        </div>
        <div class="review-card">
          <div class="review-stars" style="display:flex; gap:4px; color:#fbbf24;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="none">
              <polygon
                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
            </svg>
          </div>
          <p class="review-text">"Soy vegana y siempre me costó armar platos completos. Las recetas de NutriMax me
            salvaron la vida, son deliciosas."</p>
          <div class="review-author">
            <div class="review-avatar">V</div>
            <div>
              <div class="review-name">Valentina G.</div>
              <div class="review-meta">Dieta vegana equilibrada · Rosario</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- DISCLAIMER -->
  <section style="padding: 40px 24px 80px;">
    <div class="container">
      <div class="disclaimer-landing">
        <div class="disclaimer-icon">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h3>Tu salud primero, siempre</h3>
        <p>NutriMax es una herramienta de apoyo para tu bienestar, no un reemplazo médico. Antes de hacer cambios
          importantes en tu alimentación o si tenés alguna condición de salud, consultá con un médico o nutricionista de
          confianza. La información que te damos es general y no reemplaza el consejo profesional personalizado.</p>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="cta-section-landing">
    <div class="container">
      <h2>¿Listo para empezar?</h2>
      <p>Es gratis. Sin tarjetas. Sin excusas.</p>
      <a href="auth" class="btn btn-primary btn-lg" style="font-size:18px; padding: 18px 48px;">Crear mi cuenta gratis
        →</a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="footer-logo">Nutri<span>Max</span></div>
    <div style="display:flex; align-items:center; gap:6px;">© 2025 NutriMax · Hecho con <svg width="14" height="14"
        viewBox="0 0 24 24" fill="var(--primary)" stroke="none" style="margin-top:2px;">
        <path
          d="M20.42 4.58a5.4 5.4 0 0 0-7.65 0l-.77.78-.77-.78a5.4 5.4 0 0 0-7.65 0C1.46 6.7 1.33 10.28 4 13l8 8 8-8c2.67-2.72 2.54-6.3.42-8.42z" />
      </svg> para tu bienestar</div>
    <div style="display:flex; gap: 24px;">
      <a href="#funcionalidades" style="color:var(--text-muted)">Funcionalidades</a>
      <a href="#como-funciona" style="color:var(--text-muted)">Cómo funciona</a>
      <a href="auth" style="color:var(--primary); font-weight:700;">Entrar →</a>
    </div>
  </footer>

  <script>
    // Theme toggle logic
    const themeToggle = document.getElementById('themeToggle');

    themeToggle.addEventListener('click', () => {
      const currentTheme = document.body.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

      document.body.setAttribute('data-theme', newTheme);
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    });

    // Smooth scroll for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
  </script>

</body>

</html>