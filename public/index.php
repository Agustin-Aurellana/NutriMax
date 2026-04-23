<?php
/**
 * NutriMax - Front Controller
 * Este archivo centraliza todas las peticiones y redirige a Controllers o Views.
 */

// Configuración de visualización de errores (útil para desarrollo)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener la ruta de la petición
$request = $_SERVER['REQUEST_URI'];
$basePath = str_replace('/public/index.php', '', $_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', $request);
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
