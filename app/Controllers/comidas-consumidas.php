<?php
/**
 * comidas-consumidas.php — /api/v1/comidas-consumidas
 *
 * Controlador del diario alimenticio para persistir recetas consumidas.
 * Rutas gestionadas:
 *   GET    /api/v1/comidas-consumidas?fecha=YYYY-MM-DD -> Obtener recetas logueadas del día.
 *   POST   /api/v1/comidas-consumidas                  -> Guardar una receta consumida.
 *   DELETE /api/v1/comidas-consumidas                  -> Eliminar una receta consumida.
 */

// Requerimos Response para estandarizar las respuestas JSON y los códigos HTTP
require_once __DIR__ . '/../../app/Core/Response.php';

// Requerimos Auth para forzar la validación de la sesión JWT del usuario
require_once __DIR__ . '/../../app/Core/Auth.php';

// Requerimos el modelo RegistroDiarioModel para operar sobre la base de datos
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';

// Todas las operaciones de este diario requieren autenticación obligatoria
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

$model  = new RegistroDiarioModel();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: Obtiene todas las recetas registradas en el diario del día solicitado ──
    case 'GET':
        // Sanitizamos y extraemos el parámetro fecha (?fecha=YYYY-MM-DD)
        $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Fecha inválida o no proporcionada. Se requiere formato YYYY-MM-DD.', 400);
        }

        // Consultamos la BD mediante el modelo
        $comidas = $model->getRecetasConsumidas($userId, $fecha);
        Response::success(['entries' => $comidas], 200);
        break;

    // ── POST: Añade una nueva receta consumida al diario del usuario ──
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // Validamos que se envíen todos los datos requeridos para registrar la ingesta
        if (empty($data['recipe_id']) || empty($data['mealType']) || empty($data['fecha'])) {
            Response::error('Faltan datos obligatorios (recipe_id, mealType o fecha)', 400);
        }

        $recipeId = $data['recipe_id'];
        $mealType = $data['mealType'];
        $fecha    = $data['fecha'];
        $porcion  = isset($data['porcion']) ? (float)$data['porcion'] : 1.0;

        // Validamos formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Fecha con formato inválido. Debe ser YYYY-MM-DD.', 400);
        }

        // Insertamos el registro de consumo de receta en la base de datos
        $result = $model->addRecetaConsumida($userId, $fecha, $recipeId, $mealType, $porcion);

        if ($result['success']) {
            Response::success(['id' => $result['id']], 201, $result['message']);
        }

        Response::error($result['message'], 500);
        break;

    // ── DELETE: Remueve una receta consumida del diario diario del usuario ──
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        // Validamos que se pase el ID del registro que se va a borrar
        if (empty($data['id'])) {
            Response::error('Falta el ID del registro a eliminar', 400);
        }

        $idComida = (int) $data['id'];

        // Solicitamos la eliminación y validamos la pertenencia del usuario dentro del modelo
        $success = $model->deleteRecetaConsumida($idComida, $userId);

        if ($success) {
            Response::success(null, 200, 'Consumo de receta eliminado correctamente');
        }

        Response::error('No se pudo encontrar el registro o no tienes permisos para eliminarlo', 403);
        break;

    default:
        Response::error('Método no permitido', 405);
}
