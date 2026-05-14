<h1 align="center">
  <br>
  NutriMax
  <br>
</h1>

<h4 align="center">Un ecosistema premium de gestión nutricional impulsado por IA.</h4>

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
> **NutriMax** es un ecosistema PWA de alto rendimiento diseñado para optimizar tu salud mediante el control preciso de macronutrientes, gestión inteligente de recetas y guía de IA en tiempo real.

**NutriMax** centraliza el seguimiento nutricional en una interfaz profesional y minimalista. Permite calcular el TDEE, registrar comidas usando bases de datos globales, gestionar un recetario personal y consultar a un Coach de IA las 24 horas.

Construido con una **arquitectura inspirada en MVC**, el proyecto garantiza estabilidad y escalabilidad manteniendo una experiencia fluida similar a una app nativa a través de Service Workers e IndexedDB.

---

## ✨ Características

- 🎯 **Control de Macros**: Visualización en tiempo real de proteínas, carbohidratos, grasas y calorías.
- 🤖 **Coach de IA**: Chat interactivo integrado para consejos dietéticos y planificación de comidas.
- 🥗 **Biblioteca de Recetas**: Más de 500 recetas categorizadas por objetivo (Volumen, Definición, Mantenimiento).
- 📦 **PWA (Progressive Web App)**: Instalable en móviles con soporte **Offline-First**.
- 🔍 **Búsqueda Profesional**: Integración con APIs de Edamam/USDA para registros precisos.
- 🌓 **Diseño de Alta Gama**: Estética Glassmorphism con sistema dinámico de modo Claro/Oscuro.
- 📈 **Estadísticas de Progreso**: Historial visual de tu evolución nutricional.

---

## 🚀 Cómo Funciona

NutriMax utiliza un stack moderno para gestionar los datos de salud:

### 1. Motor Calórico
Utiliza la ecuación de **Mifflin-St Jeor** para establecer metas basadas en métricas del usuario y niveles de actividad.

### 2. Enrutamiento Front Controller
Todas las peticiones son manejadas por `public/index.php`, que redirige al **Controlador** o **Vista** correspondiente, asegurando URLs limpias y navegación estructurada.

### 3. Persistencia Híbrida
Los datos se mantienen sincronizados usando **LocalStorage** para velocidad e **IndexedDB** para almacenamiento offline de alto volumen.

---

## 💻 Instalación y Uso

### Prerrequisitos
- **PHP 7.4+** (Recomendado: Laragon, XAMPP, o servidor PHP local).
- **MySQL** (Si se desea usar la integración con base de datos).

### Pasos
1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/Agustin-Aurellana/NutriMax.git
   cd NutriMax
   ```
2. **Configuración del Servidor**:
   Apunta la raíz de tu servidor local a la carpeta `public/`.
   
   Si usas el servidor embebido de PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
3. **Base de Datos (Opcional)**:
   Importa los esquemas ubicados en el directorio `sql/` si planeas usar el backend de base de datos.

---

## 🏗️ Arquitectura

```text
NutriMax/
├── app/                    # Lógica de Backend y Vistas
│   ├── Controllers/        # Manejadores de peticiones
│   └── Views/              # Plantillas de UI (index, auth, dashboard, etc.)
├── config/                 # Configuraciones de la aplicación
├── public/                 # Raíz del documento (Accesible al público)
│   ├── assets/             # Imágenes, logos e iconos de PWA
│   ├── css/                # Sistema de Diseño (styles.css)
│   ├── js/                 # Módulos de cliente y persistencia
│   └── index.php           # Punto de entrada Front Controller
├── sql/                    # Esquemas de base de datos y migraciones
├── manifest.json           # Metadatos de PWA
└── sw.js                   # Service Worker para soporte Offline
```

---

## ⚙️ Tecnologías

- **[PHP](https://www.php.net/)** para enrutamiento y lógica de servidor.
- **[Vanilla JS / ES6+](https://developer.mozilla.org/es/docs/Web/JavaScript)** para UI reactiva y módulos asíncronos.
- **[CSS3 (Vanilla)](https://developer.mozilla.org/es/docs/Web/CSS)** con variables personalizadas y Glassmorphism.
- **[Service Workers](https://developer.mozilla.org/es/docs/Web/API/Service_Worker_API)** para capacidades PWA.
- **[IndexedDB](https://developer.mozilla.org/es/docs/Web/API/IndexedDB_API)** para persistencia local.

---
> Proyecto diseñado con enfoque en UX/UI de alto nivel y arquitectura de código limpio.
