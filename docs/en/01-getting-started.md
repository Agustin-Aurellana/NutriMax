# Getting Started

## Overview

NutriMax is designed to help users track their nutrition, manage recipes, and communicate with an AI dietary coach. It is built as a PWA, meaning it functions like a native app on mobile devices and offers offline capabilities.

## Codebase Organization

The project follows a standard MVC folder structure, routed through a Front Controller:

```text
NutriMax/
├── app/                  # Application Core (Backend & Views)
│   ├── Controllers/      # API endpoints logic (e.g., login.php, registro.php)
│   ├── Core/             # Base classes (Auth.php, Response.php, JWT.php)
│   ├── Models/           # Database connections and Data Models (Database.php)
│   └── Views/            # HTML templates (index.html, dashboard.html)
├── config/               # Configuration files (DB credentials)
├── docs/                 # Documentation (You are here)
├── public/               # Public document root (Assets, Front Controller)
│   ├── assets/           # Images, icons
│   ├── css/              # Stylesheets
│   ├── js/               # Frontend logic
│   └── index.php         # Front Controller (Main Entry Point)
└── sql/                  # Database schemas and seeders
```

## How Routing Works

All requests are directed to `public/index.php` (usually via `.htaccess` URL rewriting).
- **API Routes**: Any request starting with `/api/v1/` is routed to the corresponding script in `app/Controllers/`.
- **View Routes**: Any other request serves the corresponding `.html` file from `app/Views/` acting as the frontend view.

---
 [← Back to README](../../README.md)
