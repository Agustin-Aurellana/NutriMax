<?php
/**
 * NutriMax — Front Controller (v3)
 *
 * Flujo:
 *  1. OPTIONS → preflight CORS.
 *  2. /api/v1/* → Rate Limiting → Payload Guard → Controlador PHP.
 *  3. Cualquier otra ruta → Servir el archivo .html desde public/.
 *  4. Fallback → index.html (login / landing).
 *
 * Seguridad:
 *   Rate limiting aplicado CENTRALMENTE antes de despachar al controlador.
 *   Payload size guard: rechaza bodies > 64 KB con HTTP 413.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/RateLimiter.php';
require_once __DIR__ . '/../app/Core/Request.php';

// Evaluación centralizada de CORS y preflight OPTIONS antes del enrutamiento
Response::handleCors();

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
        'login'              => __DIR__ . '/../app/Controllers/login.php',
        'registro'           => __DIR__ . '/../app/Controllers/registro.php',
        'google-auth'        => __DIR__ . '/../app/Controllers/google_auth.php',
        'actualizar-perfil'  => __DIR__ . '/../app/Controllers/actualizar-perfil.php',
        'agregar-ing'        => __DIR__ . '/../app/Controllers/agregar-ing.php',
        'editar-ing'         => __DIR__ . '/../app/Controllers/editar-ing.php',
        'eliminar-ing'       => __DIR__ . '/../app/Controllers/eliminar-ing.php',
        'recetas'            => __DIR__ . '/../app/Controllers/recetas.php',
        'registro-diario'    => __DIR__ . '/../app/Controllers/registro-diario.php',
        'ingredientes'       => __DIR__ . '/../app/Controllers/ingredientes.php',
        'comidas-consumidas' => __DIR__ . '/../app/Controllers/comidas-consumidas.php',
    ];

    if (!isset($apiRoutes[$resource]) || !file_exists($apiRoutes[$resource])) {
        Response::error('Endpoint no encontrado: /api/v1/' . htmlspecialchars($resource), 404);
    }

    // ── RATE LIMITING CENTRALIZADO ────────────────────────────────────────────
    $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }
    $sessionId = RateLimiter::extractSessionId();
    RateLimiter::checkGateway($clientIp, $sessionId);
    // ─────────────────────────────────────────────────────────────────────────

    // ── PAYLOAD SIZE GUARD ────────────────────────────────────────────────────
    // Rechaza peticiones cuyo body supere 64 KB para prevenir saturación de
    // memoria y ataques de payload masivo (strings de 100k+ caracteres, etc.).
    //
    // Estrategia dual:
    //  1. Content-Length header (O(1)): rechazo inmediato sin leer el body.
    //  2. Lectura real del body (POST/PUT/PATCH): captura clientes que omiten
    //     el header. El body se cachea en Request::body() via $GLOBALS para que
    //     los controladores puedan leerlo sin necesidad de re-acceder php://input.
    define('MAX_PAYLOAD_BYTES', 65536); // 64 KB

    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : -1;
    if ($contentLength > MAX_PAYLOAD_BYTES) {
        // Rechazo sin consumir el body — el header ya delata el tamaño
        Response::error('Payload demasiado grande. Máximo permitido: 64 KB.', 413);
    }

    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'], true)) {
        // Leemos el body UNA vez aquí y lo cacheamos.
        // php://input es de solo lectura una vez en PHP; al consumirlo aquí,
        // los controladores recibirían vacío si volvieran a leer el stream.
        // Request::body() retorna siempre el valor cacheado en $GLOBALS['_RAW_BODY'].
        $rawBody = file_get_contents('php://input');
        if (strlen($rawBody) > MAX_PAYLOAD_BYTES) {
            Response::error('Payload demasiado grande. Máximo permitido: 64 KB.', 413);
        }
        $GLOBALS['_RAW_BODY'] = $rawBody;
    }
    // ─────────────────────────────────────────────────────────────────────────

    require_once $apiRoutes[$resource];
    exit;
}


// ── Rama de Vistas .html ──
$page = str_replace(['.php', '.html'], '', trim($route, '/'));
if (empty($page)) $page = 'index';

$viewRoutes = [
    'index'     => __DIR__ . '/../app/Views/index.html',
    'auth'      => __DIR__ . '/../app/Views/auth.html',
    'dashboard' => __DIR__ . '/../app/Views/dashboard.html',
    'food-log'  => __DIR__ . '/../app/Views/food-log.html',
    'goals'     => __DIR__ . '/../app/Views/goals.html',
    'stats'     => __DIR__ . '/../app/Views/stats.html',
    'ai-coach'  => __DIR__ . '/../app/Views/ai-coach.html',
    'recipes'   => __DIR__ . '/../app/Views/recipes.html',
];

if (isset($viewRoutes[$page]) && file_exists($viewRoutes[$page])) {
    readfile($viewRoutes[$page]);
} else {
    readfile(__DIR__ . '/../app/Views/index.html');
}
