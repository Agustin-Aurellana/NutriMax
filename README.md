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
  <a href="#-architecture">Architecture</a> •
  <a href="#-tech-stack">Tech Stack</a> •
  <a href="docs/en/">Documentation</a>
</p>

---

> [!IMPORTANT]
> **NutriMax** is a comprehensive nutritional platform designed with a clean API-driven MVC architecture, combining a robust PHP backend with a blazingly fast Vanilla JS Progressive Web App (PWA) frontend.

**NutriMax** centralizes nutritional tracking within a premium, minimalist interface. It allows users to calculate their TDEE, log meals, manage recipes, and consult a 24/7 AI Coach.

For detailed documentation, please refer to the [docs folder](docs/en/index.md).

---

## ✨ Features

- 🎯 **Macronutrient Control**: Visualize your daily progress through a dynamic ring system.
- 🤖 **AI Nutritional Coach**: Integrated interactive chat to resolve dietary doubts.
- 📦 **PWA (Progressive Web App)**: Installable on mobile devices with Offline First support via Service Workers.
- 🔐 **Secure Authentication**: JWT-based secure login and registration system.
- ⚙️ **API-Driven**: Decoupled backend architecture serving RESTful JSON endpoints.

---

## 🏗️ Architecture

NutriMax uses an **MVC (Model-View-Controller)** pattern mixed with an **API-Driven** approach. 

- **Backend**: Built in pure PHP, exposing REST APIs under `/api/v1/`.
- **Frontend**: Vanilla JS consuming the APIs, utilizing `localStorage` and `IndexedDB` for client-side persistence and caching.

For more details on the architecture, see the [Reference Documentation](docs/en/reference/api.md).

---

## ⚙️ Tech Stack

- **Frontend**: HTML5, CSS3 (Vanilla + Variables), JavaScript ES6+ (Vanilla), Service Workers.
- **Backend**: PHP 8+ (Core logic, Auth, API endpoints).
- **Database**: MySQL (Accessed via `mysqli` Wrapper Singleton).
- **Architecture Pattern**: MVC + API Front Controller.

---

## 📖 Documentation

For instructions on how to install, configure, and use NutriMax, check the documentation:
1. [Welcome & Overview](docs/en/index.md)
2. [Getting Started](docs/en/01-getting-started.md)
3. [Installation Guide](docs/en/02-installation.md)
4. [Technical Reference](docs/en/reference/api.md)
