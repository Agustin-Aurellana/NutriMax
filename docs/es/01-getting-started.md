# Guía de Inicio

## Descripción General

NutriMax está diseñado para ayudar a los usuarios a realizar un seguimiento de su nutrición, gestionar recetas y comunicarse con un coach dietético de IA. Está construido como una PWA, lo que significa que funciona como una aplicación nativa en dispositivos móviles y ofrece capacidades sin conexión.

## Organización del Código

El proyecto sigue una estructura de carpetas MVC estándar, enrutada a través de un Front Controller:

```text
NutriMax/
├── app/                  # Núcleo de la Aplicación (Backend y Vistas)
│   ├── Controllers/      # Lógica de los endpoints de la API (ej. login.php, registro.php)
│   ├── Core/             # Clases base (Auth.php, Response.php, JWT.php)
│   ├── Models/           # Conexiones a bases de datos y Modelos de Datos (Database.php)
│   └── Views/            # Plantillas HTML (index.html, dashboard.html)
├── config/               # Archivos de configuración (credenciales de BD)
├── docs/                 # Documentación (Usted está aquí)
├── public/               # Raíz de documentos públicos (Assets, Front Controller)
│   ├── assets/           # Imágenes, iconos
│   ├── css/              # Hojas de estilo
│   ├── js/               # Lógica del frontend
│   └── index.php         # Front Controller (Punto de entrada principal)
└── sql/                  # Esquemas de base de datos y seeders
```

## Cómo Funciona el Enrutamiento

Todas las solicitudes se dirigen a `public/index.php` (generalmente a través de la reescritura de URL de `.htaccess`).
- **Rutas de API**: Cualquier solicitud que comience con `/api/v1/` se enruta al script correspondiente en `app/Controllers/`.
- **Rutas de Vista**: Cualquier otra solicitud sirve el archivo `.html` correspondiente de `app/Views/` actuando como la vista del frontend.

---
 [← Volver al README](../../README-es.md)
