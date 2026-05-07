<h1 align="center">
  <br>
  NutriMax
  <br>
</h1>

<h4 align="center">A smart nutritional management ecosystem powered by AI assistance.</h4>

<p align="center">
  <em>Read this in other languages: <a href="README.md">English</a>, <a href="README-es.md">Español</a></em>
</p>

<p align="center">
  <a href="#-features">Features</a> •
  <a href="#-how-it-works">How It Works</a> •
  <a href="#-installation--usage">Installation & Usage</a> •
  <a href="#-architecture">Architecture</a> •
  <a href="#-tech-stack">Tech Stack</a>
</p>

---

> [!IMPORTANT]
> **NutriMax** is not just a calorie counter; it is a PWA ecosystem designed to optimize your physical performance and health through precise macronutrient control and AI assistance.

**NutriMax** is a high-performance web platform that centralizes nutritional tracking within a premium, minimalist interface. It allows users to calculate their TDEE (Total Daily Energy Expenditure), log meals using professional global databases (Edamam/USDA), manage a smart personal recipe book, and consult a 24/7 AI Coach for personalized dietary advice.

Designed under **Clean Architecture** principles, the project ensures a seamless experience on both desktop and mobile devices, performing like a native app thanks to its Service Worker implementation and robust local persistence with IndexedDB.

---

## ✨ Features

- 🎯 **Macronutrient Control**: Visualize your daily progress through a dynamic ring system (SVG/Canvas) that tracks proteins, carbs, fats, and remaining calories in real-time.
- 🤖 **AI Nutritional Coach**: Integrated interactive chat to resolve dietary doubts, suggest food substitutes, or plan meals based on your specific goals.
- 📦 **PWA (Progressive Web App)**: Installable on mobile devices with **Offline First** support via Service Workers, allowing access to your logs even without an internet connection.
- 🔍 **Global Search & Scanner**: Integrates professional APIs (Edamam, OpenFoodFacts, USDA) and a high-speed barcode scanner (Html5-QRCode) for ultra-fast food logging.
- 🥗 **Recipe Management**: Create, save, and filter recipes based on goal tags (Bulking, Cutting, Maintenance).
- 🌓 **Premium Dark Mode**: Dynamic interface that adapts to system preferences, built exclusively with CSS Variables and high-end design aesthetics.

---

## 🚀 How It Works

NutriMax utilizes a decoupled, event-driven architecture to manage user health states:

### 1. Caloric Calculation Engine (TDEE)
Uses the **Mifflin-St Jeor** equation combined with physical activity multipliers to set dynamic caloric targets. Macros automatically adjust based on the selected goal (cutting, bulking, recomp, or maintenance).

### 2. Local Sync & Persistence
The system implements a dual persistence layer: **LocalStorage** for instant access and **IndexedDB** as an asynchronous backup. A queue system (`idbQueue`) ensures data is saved in the background without blocking the main UI thread.

### 3. Service Worker & Cache
Implements a **Network-First with Cache fallback** strategy. This ensures users always see the latest version if online, while keeping critical functionality (logs and dashboard) available even in airplane mode.

---

## 💻 Installation & Usage

### Prerequisites
As a Vanilla JS project, it does not require complex servers or compilation. Any modern web browser is sufficient.

### Steps
1. **Clone the repository**:
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```
2. **API Configuration**:
   If you wish to use external search APIs, add your credentials to `src/js/config.local.js`.
3. **Execution**:
   Simply open the root `index.html` file in your browser.
   ```bash
   start index.html
   ```

---

## 🏗️ Architecture

The project follows a modular and clean structure for easy maintenance:

```text
NutriMax/
├── assets/
│   └── img/                # Visual assets, logos, and PWA icons
├── src/
│   ├── css/                # Global Design System (CSS Variables)
│   ├── js/                 # Core logic, AI modules, and persistence.js
│   ├── index.html          # Authentication system and landing page
│   ├── dashboard.html      # Main control panel
│   └── ...                 # Modules: goals, recipes, stats, chat
├── index.html              # Smart redirector to source code
├── manifest.json           # Configuration for mobile installation
└── sw.js                   # Service Worker and Offline management
```

---

## ⚙️ Tech Stack

- **[HTML5](https://developer.mozilla.org/en-US/docs/Web/HTML)** for accessible and semantic structure.
- **[CSS3 (Vanilla)](https://developer.mozilla.org/en-US/docs/Web/CSS)** with modern variables and Flexbox/Grid for premium design.
- **[JavaScript ES6+](https://developer.mozilla.org/en-US/docs/Web/JavaScript)** for asynchronous logic and reactive DOM manipulation.
- **[Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)** for PWA and offline capabilities.
- **[IndexedDB](https://developer.mozilla.org/en-US/docs/Web/API/IndexedDB_API)** for high-volume local storage.

---
> Project developed with a focus on high-level UX/UI, optimized performance, and clean code architecture.
