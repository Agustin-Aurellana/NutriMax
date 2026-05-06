<?php
/**
 * NutriMax - Front Controller
 * Este archivo centraliza todas las peticiones y redirige a Controllers o Views.
 */

// Configuración de visualización de errores (útil para desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener la ruta de la petición y decodificarla (por si hay espacios como %20)
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Directorio base donde se ejecuta el script (ej: /NutriMax/public o /)
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
if ($baseDir === '\\') $baseDir = '/';

// Remover el directorio base de la ruta
if (strpos($requestUri, $baseDir) === 0) {
    $route = substr($requestUri, strlen($baseDir));
} else {
    $route = $requestUri;
}
$route = trim($route, '/');

// Limpiar la ruta de extensiones
$page = str_replace(['.php', '.html'], '', $route);

// Ruta por defecto
if (empty($page)) {
    $page = 'index';
}

// 1. Intentar cargar desde Controllers (Lógica)
$controllerFile = __DIR__ . '/../app/Controllers/' . $page . '.php';

// 2. Intentar cargar desde Views (Interfaz)
$viewFile = __DIR__ . '/../app/Views/' . $page . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
} elseif (file_exists($viewFile)) {
    require_once $viewFile;
} else {
    // Página no encontrada - Redirigir al inicio o mostrar 404
    require_once __DIR__ . '/../app/Views/index.php';
}
