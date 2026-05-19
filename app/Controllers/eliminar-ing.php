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

Auth::requireAuth();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    Response::error('No se proporcionó el ID del ingrediente', 400);
}

$model  = new IngredienteModel();
$result = $model->delete((int) $data['id']);

if ($result['success']) {
    Response::noContent();
} else {
    Response::error($result['message'], 500);
}
