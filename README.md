<h1 align="center">
  <br>
  NutriMax
  <br>
</h1>

<h4 align="center">A premium nutritional management ecosystem powered by AI assistance.</h4>

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
> **NutriMax** is a high-performance PWA ecosystem designed to optimize your health through precise macronutrient control, smart recipe management, and real-time AI guidance.

**NutriMax** centralizes nutritional tracking within a professional, minimalist interface. It allows users to calculate their TDEE, log meals using global databases, manage a personal recipe book, and consult a 24/7 AI Coach.

Built with an **MVC-inspired architecture**, the project ensures stability and scalability while maintaining a seamless native-like experience via Service Workers and IndexedDB.

---

## ✨ Features

- 🎯 **Macronutrient Tracking**: Real-time visualization of proteins, carbs, fats, and calories via a dynamic dashboard.
- 🤖 **AI Nutritional Coach**: Integrated interactive chat for dietary advice and meal planning.
- 🥗 **Recipe Library**: Over 500 recipes categorized by goal (Bulking, Cutting, Maintenance).
- 📦 **PWA (Progressive Web App)**: Installable on mobile with **Offline-First** support.
- 🔍 **Professional Search**: Integration with Edamam/USDA APIs for precise food logging.
- 🌓 **High-End Design**: Glassmorphism aesthetic with a dynamic Dark/Light mode system.
- 📈 **Progress Stats**: Visual history of your nutritional evolution.

---

## 🚀 How It Works

NutriMax uses a modern stack to manage health data:

### 1. Caloric Engine
Uses the **Mifflin-St Jeor** equation to set targets based on user metrics and activity levels.

### 2. Front Controller Routing
All requests are handled by `public/index.php`, which routes to the appropriate **Controller** or **View**, ensuring clean URLs and structured navigation.

### 3. Hybrid Persistence
Data is kept in sync using **LocalStorage** for speed and **IndexedDB** for high-volume offline storage, with a backend structure prepared for database integration.

---

## 💻 Installation & Usage

### Prerequisites
- **PHP 7.4+** (Recommended: Laragon, XAMPP, or local PHP server).
- **MySQL** (If using database features).

### Steps
1. **Clone the repository**:
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```
2. **Server Setup**:
   Point your local server's document root to the `public/` folder.
   
   If using the PHP built-in server:
   ```bash
   php -S localhost:8000 -t public
   ```
3. **Database (Optional)**:
   Import the schemas located in the `sql/` directory if you intend to use the database backend.

---

## 🏗️ Architecture

```text
NutriMax/
├── app/                    # Backend Logic & Views
│   ├── Controllers/        # Request handlers
│   └── Views/              # UI Templates (index, auth, dashboard, etc.)
├── config/                 # App configurations
├── public/                 # Document Root (Publicly accessible)
│   ├── assets/             # Images, logos, and PWA icons
│   ├── css/                # Design System (styles.css)
│   ├── js/                 # Client-side modules and persistence
│   └── index.php           # Front Controller Entry Point
├── sql/                    # Database schemas and migrations
├── manifest.json           # PWA metadata
└── sw.js                   # Service Worker for Offline support
```

---

## ⚙️ Tech Stack

- **[PHP](https://www.php.net/)** for server-side routing and logic.
- **[Vanilla JS / ES6+](https://developer.mozilla.org/en-US/docs/Web/JavaScript)** for reactive UI and async modules.
- **[CSS3 (Vanilla)](https://developer.mozilla.org/en-US/docs/Web/CSS)** with custom variables and Glassmorphism.
- **[Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)** for offline capabilities.
- **[IndexedDB](https://developer.mozilla.org/en-US/docs/Web/API/IndexedDB_API)** for local persistence.

---
> Project designed with a focus on high-level UX/UI and clean code architecture.
