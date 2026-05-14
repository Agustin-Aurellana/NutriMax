<?php
/**
 * NutriMax — Front Controller (v2)
 *
 * Flujo de decisión:
 *
 *  1. ¿Es una petición OPTIONS? → Responder preflight CORS y salir.
 *  2. ¿La ruta comienza con /api/v1/? → Enrutar al Controlador correspondiente.
 *  3. ¿La ruta es una vista conocida (.html)? → Servir el archivo HTML estático.
 *  4. Fallback → Servir index.html (página de inicio / login).
 */

// ──────────────────────────────────────────
// 0. Entorno y autoload de Core
// ──────────────────────────────────────────

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Core/Response.php';

// Manejar preflight CORS antes de cualquier otra lógica
Response::handlePreflight();

// ──────────────────────────────────────────
// 1. Normalizar la ruta de la petición
// ──────────────────────────────────────────

// Obtener la ruta y decodificarla (elimina %20, etc.)
$requestUri = str_replace('\\', '/', urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

// Calcular el directorio base (útil si el proyecto corre en un subdirectorio)
$baseDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($baseDir === '\\' || $baseDir === '') $baseDir = '/';

// Eliminar el directorio base de la URI para obtener la ruta relativa
if ($baseDir !== '/' && strpos($requestUri, $baseDir) === 0) {
    $route = substr($requestUri, strlen($baseDir));
} else {
    $route = $requestUri;
}
$route = '/' . trim($route, '/'); // Normalizar: siempre empieza con "/"

// ──────────────────────────────────────────
// 2. Rama de la API REST (/api/v1/*)
// ──────────────────────────────────────────

if (strpos($route, '/api/v1/') === 0) {

    // Extraer el segmento de recurso: /api/v1/{recurso}
    // Ej: "/api/v1/login" → "login"
    $apiPath   = substr($route, strlen('/api/v1/'));
    $segments  = explode('/', trim($apiPath, '/'));
    $resource  = $segments[0] ?? '';   // Primer segmento = nombre del controlador

    // Mapeo explícito de rutas API → archivos de Controlador.
    // Tener este mapa visible aquí es intencional: sirve como documentación
    // de todos los endpoints disponibles en el proyecto.
    $apiRoutes = [
        'login'            => __DIR__ . '/../app/Controllers/login.php',
        'registro'         => __DIR__ . '/../app/Controllers/registro.php',
        'google-auth'      => __DIR__ . '/../app/Controllers/google_auth.php',
        'actualizar-perfil'=> __DIR__ . '/../app/Controllers/actualizar-perfil.php',
        'agregar-ing'      => __DIR__ . '/../app/Controllers/agregar-ing.php',
        'eliminar-ing'     => __DIR__ . '/../app/Controllers/eliminar-ing.php',
    ];

    if (isset($apiRoutes[$resource]) && file_exists($apiRoutes[$resource])) {
        require_once $apiRoutes[$resource];
    } else {
        // Endpoint no encontrado dentro de /api/v1/
        Response::error('Endpoint no encontrado: /api/v1/' . htmlspecialchars($resource), 404);
    }

    exit; // Garantizamos que la ejecución no continúe a la lógica de vistas
}

// ──────────────────────────────────────────
// 3. Rama de Vistas (archivos .html estáticos)
// ──────────────────────────────────────────

// Limpiar la ruta de extensiones para obtener el nombre base de la vista
$page = str_replace(['.php', '.html'], '', trim($route, '/'));

// Ruta por defecto si no se especifica ninguna página
if (empty($page)) {
    $page = 'index';
}

// Mapeo explícito de páginas → archivos HTML.
// En la Sesión 4, estos .php serán convertidos a .html.
// Por ahora, mantenemos compatibilidad con las vistas PHP existentes.
$viewRoutes = [
    'index'       => __DIR__ . '/../app/Views/index.php',
    'dashboard'   => __DIR__ . '/../app/Views/dashboard.php',
    'food-log'    => __DIR__ . '/../app/Views/food-log.php',
    'goals'       => __DIR__ . '/../app/Views/goals.php',
    'stats'       => __DIR__ . '/../app/Views/stats.php',
    'ai-coach'    => __DIR__ . '/../app/Views/ai-coach.php',
    'recipes'     => __DIR__ . '/../app/Views/recipes.php',
];

if (isset($viewRoutes[$page]) && file_exists($viewRoutes[$page])) {
    require_once $viewRoutes[$page];
} else {
    // Fallback: cualquier ruta desconocida sirve el index (login / landing)
    require_once __DIR__ . '/../app/Views/index.php';
}
