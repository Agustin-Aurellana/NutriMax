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
  <a href="#-arquitectura">Arquitectura</a> •
  <a href="#-tecnologías">Tecnologías</a> •
  <a href="docs/es/">Documentación</a>
</p>

---

> [!IMPORTANT]
> **NutriMax** es una plataforma nutricional integral diseñada con una arquitectura MVC limpia orientada a API, combinando un robusto backend en PHP con un frontend PWA en JavaScript Vanilla extremadamente rápido.

**NutriMax** centraliza el seguimiento nutricional en una interfaz premium y minimalista. Permite a los usuarios calcular su TDEE, registrar comidas, gestionar recetas y consultar a un Coach de IA las 24 horas del día.

Para obtener documentación detallada, consulte la [carpeta docs](docs/es/index.md).

---

## ✨ Características

- 🎯 **Control de Macronutrientes**: Visualiza tu progreso diario mediante un sistema de anillos dinámicos.
- 🤖 **Coach Nutricional con IA**: Chat interactivo integrado para resolver dudas sobre alimentación.
- 📦 **PWA (Progressive Web App)**: Instalable en dispositivos móviles con soporte Offline First gracias a Service Workers.
- 🔐 **Autenticación Segura**: Sistema de registro e inicio de sesión seguro basado en JWT.
- ⚙️ **Orientado a API**: Arquitectura de backend desacoplada que sirve endpoints JSON RESTful.

---

## 🏗️ Arquitectura

NutriMax utiliza un patrón **MVC (Modelo-Vista-Controlador)** mezclado con un enfoque **Orientado a API**. 

- **Backend**: Construido en PHP puro, exponiendo APIs REST bajo `/api/v1/`.
- **Frontend**: JavaScript Vanilla que consume las APIs, utilizando `localStorage` e `IndexedDB` para la persistencia y el almacenamiento en caché del lado del cliente.

Para más detalles sobre la arquitectura, consulte la [Referencia Técnica](docs/es/reference/api.md).

---

## ⚙️ Tecnologías

- **Frontend**: HTML5, CSS3 (Vanilla + Variables), JavaScript ES6+ (Vanilla), Service Workers.
- **Backend**: PHP 8+ (Lógica core, Auth, Endpoints de API).
- **Base de Datos**: MySQL (Accedida mediante un Wrapper Singleton de `mysqli`).
- **Patrón de Arquitectura**: MVC + API Front Controller.

---

## 📖 Documentación

Para instrucciones sobre cómo instalar, configurar y usar NutriMax, consulte la documentación:
1. [Bienvenida y Descripción General](docs/es/index.md)
2. [Guía de Inicio](docs/es/01-getting-started.md)
3. [Guía de Instalación](docs/es/02-installation.md)
4. [Referencia Técnica](docs/es/reference/api.md)
