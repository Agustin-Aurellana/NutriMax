<?php
/**
 * recetas.php — /api/v1/recetas
 *
 * Controlador multi-método para CRUD de recetas.
 *
 * Rutas gestionadas:
 *   GET    /api/v1/recetas   → Listar recetas globales + propias del usuario
 *   POST   /api/v1/recetas   → Crear receta personalizada
 *   DELETE /api/v1/recetas   → Eliminar receta propia
 *
 * Diseño: las recetas del usuario viven en la misma tabla `recetas`
 * con ID_USER = <uuid>. Las globales tienen ID_USER = NULL.
 * No existe endpoint de "favoritos": una receta o es del usuario o no lo es.
 *
 * Todas las rutas están protegidas por JWT (Auth::requireAuth).
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/RecetaModel.php';

// ── Autenticar: si el token es inválido, la ejecución se detiene aquí ──
$authUser = Auth::requireAuth();
$userId   = $authUser['id']; // UUID extraído del payload JWT

$model  = new RecetaModel();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: Listar todas las recetas visibles para el usuario ──
    case 'GET':
        // Parámetros opcionales de búsqueda vía query string: ?query=pollo&goal=Keto
        $query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        $goal  = filter_input(INPUT_GET, 'goal',  FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        $recipes = $model->getAll($userId, $query, $goal);
        Response::success(['recipes' => $recipes], 200);
        break;

    // ── POST: Crear nueva receta personalizada del usuario ──
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['name']) || trim($data['name']) === '') {
            Response::error('El campo name es obligatorio', 400);
        }

        $result = $model->create($data, $userId);

        if ($result['success']) {
            Response::success(['id' => $result['id']], 201, $result['message']);
        }

        Response::error($result['message'], 500);
        break;

    // ── DELETE: Eliminar receta propia del usuario ──
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            Response::error('Falta el campo id de la receta', 400);
        }

        $result = $model->delete($data['id'], $userId);

        if ($result['success']) {
            Response::success(null, 200, $result['message']);
        }

        // 403 si la receta existe pero no pertenece al usuario autenticado
        Response::error($result['message'], 403);
        break;

    default:
        Response::error('Método no permitido', 405);
}
