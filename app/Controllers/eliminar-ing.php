<?php
/**
 * eliminar-ing.php — Controlador para eliminar un ingrediente
 *
 * Método: DELETE /api/v1/eliminar-ing
 * Body:   { "id": <int> }
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    Response::error('No se proporcionó el ID del ingrediente', 400);
}

$model  = new IngredienteModel();
$result = $model->delete((int) $data['id']);

if ($result['success']) {
    Response::noContent(); // 204 — éxito sin payload
} else {
    Response::error($result['message'], 500);
}
