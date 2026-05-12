<h1 align="center">
  <br>
  NutriMax
  <br>
</h1>

<h4 align="center">Un ecosistema de gestión nutricional inteligente con asistencia de IA.</h4>

<p align="center">
  <em>Leer en otros idiomas: <a href="README.md">Inglés</a>, <a href="README-es.md">Español</a></em>
</p>

<p align="center">
  <a href="#-características">Características</a> •
  <a href="#-cómo-funciona">Cómo Funciona</a> •
  <a href="#-instalación-y-uso">Instalación y Uso</a> •
  <a href="#-arquitectura">Arquitectura</a> •
  <a href="#-tecnologías">Tecnologías</a>
</p>

---

> [!IMPORTANT]
> **NutriMax** no es solo un contador de calorías; es un ecosistema PWA diseñado para optimizar tu rendimiento físico y salud mediante un control preciso de macronutrientes y asistencia de IA.

**NutriMax** es una plataforma web de alto rendimiento que centraliza el seguimiento nutricional en una interfaz premium y minimalista. Permite a los usuarios calcular su TDEE (Gasto Energético Diario Total), registrar comidas mediante una base de datos global profesional (Edamam/USDA), gestionar un recetario personal inteligente y consultar a un Coach de IA 24/7 para obtener consejos personalizados.

Diseñado bajo principios de **Clean Architecture**, el proyecto garantiza una experiencia fluida tanto en escritorio como en dispositivos móviles, funcionando como una aplicación nativa gracias a su implementación de Service Workers y persistencia local robusta con IndexedDB.

---

## ✨ Características

- 🎯 **Control de Macronutrientes**: Visualiza tu progreso diario mediante un sistema de anillos dinámicos (SVG/Canvas) que rastrean proteínas, carbohidratos, grasas y calorías restantes en tiempo real.
- 🤖 **Coach Nutricional con IA**: Chat interactivo integrado para resolver dudas sobre alimentación, sugerir sustitutos o planificar comidas basándose en tus objetivos.
- 📦 **PWA (Progressive Web App)**: Instalable en dispositivos móviles con soporte **Offline First** gracias a Service Workers, permitiendo el acceso a tus registros sin conexión a internet.
- 🔍 **Búsqueda Global y Scanner**: Integra APIs profesionales (Edamam, OpenFoodFacts, USDA) y un escáner de códigos de barras (Html5-QRCode) para un registro de alimentos ultra rápido.
- 🥗 **Gestión de Recetas**: Crea, guarda y filtra recetas basadas en etiquetas de objetivos (Volumen, Definición, Mantenimiento).
- 🌓 **Premium Dark Mode**: Interfaz dinámica que se adapta a las preferencias del sistema, construida exclusivamente con Variables CSS y diseño de alta gama.

---

## 🚀 Cómo Funciona

NutriMax utiliza una arquitectura desacoplada basada en eventos para gestionar el estado de salud del usuario:

### 1. Motor de Cálculo calórico (TDEE)
Utiliza la ecuación de **Mifflin-St Jeor** combinada con multiplicadores de actividad física para establecer metas calóricas dinámicas. Los macros se auto-ajustan según el objetivo seleccionado (definición, volumen, recomposición o mantenimiento).

### 2. Sincronización Local & Persistencia
El sistema implementa una capa dual de persistencia: **LocalStorage** para acceso instantáneo y **IndexedDB** como respaldo asíncrono. Un sistema de cola (`idbQueue`) asegura que los datos se guarden en segundo plano sin bloquear el hilo principal de la UI.

### 3. Service Worker & Caché
Implementa una estrategia **Network-First con fallback a Caché**. Esto garantiza que el usuario siempre vea la versión más reciente si hay internet, pero mantiene la funcionalidad crítica (registros y dashboard) disponible incluso en modo avión.

---

## 💻 Instalación y Uso

### Prerrequisitos
Al ser un proyecto Vanilla JS, no requiere servidores complejos ni compilación. Cualquier navegador web moderno es suficiente.

### Pasos
1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```
2. **Configuración de APIs**:
   Si deseas usar las APIs de búsqueda externa, añade tus credenciales en `src/js/config.local.js`.
3. **Ejecución**:
   Simplemente abre el archivo `index.html` de la raíz en tu navegador.
   ```bash
   start index.html
   ```

---

## 🏗️ Arquitectura

El proyecto sigue una estructura modular y limpia para facilitar el mantenimiento:

```text
NutriMax/
├── assets/
│   └── img/                # Recursos visuales, logos e iconos de PWA
├── src/
│   ├── css/                # Design System global (CSS Variables)
│   ├── js/                 # Lógica core, módulos de IA y persistence.js
│   ├── index.html          # Sistema de autenticación y landing
│   ├── dashboard.html      # Panel de control principal
│   └── ...                 # Módulos: goals, recipes, stats, chat
├── index.html              # Redirección inteligente al código fuente
├── manifest.json           # Configuración para instalación en móviles
└── sw.js                   # Service Worker y gestión de Offline
```

---

## ⚙️ Tecnologías

- **[HTML5](https://developer.mozilla.org/es/docs/Web/HTML)** para estructura semántica y accesible.
- **[CSS3 (Vanilla)](https://developer.mozilla.org/es/docs/Web/CSS)** con variables modernas y Flexbox/Grid para diseño premium.
- **[JavaScript ES6+](https://developer.mozilla.org/es/docs/Web/JavaScript)** para lógica asíncrona y manipulación reactiva del DOM.
- **[Service Workers](https://developer.mozilla.org/es/docs/Web/API/Service_Worker_API)** para capacidades PWA y offline.
- **[IndexedDB](https://developer.mozilla.org/es/docs/Web/API/IndexedDB_API)** para almacenamiento local de alto volumen.

---
> Proyecto desarrollado con enfoque en UX/UI de alto nivel, rendimiento optimizado y arquitectura de código limpio.
