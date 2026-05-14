<?php
/**
 * NutriMax — Front Controller (v3)
 *
 * Flujo:
 *  1. OPTIONS → preflight CORS.
 *  2. /api/v1/* → Controlador PHP correspondiente.
 *  3. Cualquier otra ruta → Servir el archivo .html desde public/.
 *  4. Fallback → index.html (login / landing).
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Core/Response.php';
Response::handlePreflight();

// ── Normalizar ruta ──
$requestUri = str_replace('\\', '/', urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
$baseDir    = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($baseDir === '\\' || $baseDir === '') $baseDir = '/';

if ($baseDir !== '/' && strpos($requestUri, $baseDir) === 0) {
    $route = substr($requestUri, strlen($baseDir));
} else {
    $route = $requestUri;
}
$route = '/' . trim($route, '/');

// ── Rama API /api/v1/* ──
if (strpos($route, '/api/v1/') === 0) {
    $resource = explode('/', trim(substr($route, strlen('/api/v1/')), '/'))[0] ?? '';

    $apiRoutes = [
        'login'             => __DIR__ . '/../app/Controllers/login.php',
        'registro'          => __DIR__ . '/../app/Controllers/registro.php',
        'google-auth'       => __DIR__ . '/../app/Controllers/google_auth.php',
        'actualizar-perfil' => __DIR__ . '/../app/Controllers/actualizar-perfil.php',
        'agregar-ing'       => __DIR__ . '/../app/Controllers/agregar-ing.php',
        'eliminar-ing'      => __DIR__ . '/../app/Controllers/eliminar-ing.php',
    ];

    if (isset($apiRoutes[$resource]) && file_exists($apiRoutes[$resource])) {
        require_once $apiRoutes[$resource];
    } else {
        Response::error('Endpoint no encontrado: /api/v1/' . htmlspecialchars($resource), 404);
    }
    exit;
}

// ── Rama de Vistas .html (Sesión 4) ──
// Los archivos .html viven directamente en public/ y son servidos como estáticos.
// El enrutador solo actúa de fallback: si el archivo existe en public/, el servidor
// web ya lo sirvió antes de llegar aquí (gracias al .htaccess con !-f).
// Si llega aquí, es porque el archivo no fue encontrado directamente → servimos index.html.

$page = str_replace(['.php', '.html'], '', trim($route, '/'));
if (empty($page)) $page = 'index';

// Mapa explícito: nombre de ruta → archivo HTML en app/Views/
$viewRoutes = [
    'index'     => __DIR__ . '/../app/Views/index.html',
    'dashboard' => __DIR__ . '/../app/Views/dashboard.html',
    'food-log'  => __DIR__ . '/../app/Views/food-log.html',
    'goals'     => __DIR__ . '/../app/Views/goals.html',
    'stats'     => __DIR__ . '/../app/Views/stats.html',
    'ai-coach'  => __DIR__ . '/../app/Views/ai-coach.html',
    'recipes'   => __DIR__ . '/../app/Views/recipes.html',
];

if (isset($viewRoutes[$page]) && file_exists($viewRoutes[$page])) {
    readfile($viewRoutes[$page]); // Servir el archivo HTML desde app/Views/
} else {
    readfile(__DIR__ . '/../app/Views/index.html'); // Fallback
}
