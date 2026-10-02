<?php
/**
 * eliminar-ing.php — DELETE /api/v1/eliminar-ing
 * Ruta protegida: requiere JWT válido.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

// Extraemos el usuario autenticado para verificar propiedad del recurso
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    Response::error('No se proporcionó el ID del ingrediente', 400);
}

$model  = new IngredienteModel();
$force  = isset($data['force']) ? (bool) $data['force'] : false;
$result = $model->delete((int) $data['id'], $userId, $force);

if ($result['success']) {
    Response::noContent();
} else {
    // Si requiere confirmación porque hay recetas afectadas, devolvemos 409 Conflict
    if (isset($result['require_force']) && $result['require_force']) {
        Response::error($result['message'], 409, ['affected_recipes' => $result['affected_recipes']]);
    }
    
    // 403 si falla la comprobación de propiedad
    $code = (strpos($result['message'], 'permisos') !== false || strpos($result['message'], 'globales') !== false) ? 403 : 500;
    Response::error($result['message'], $code);
}
