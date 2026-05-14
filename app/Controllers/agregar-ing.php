<?php
/**
 * agregar-ing.php — POST /api/v1/agregar-ing
 * Ruta protegida: requiere JWT válido.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

Auth::requireAuth();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['kcals'])) {
    Response::error('Faltan datos obligatorios', 400);
}

$model  = new IngredienteModel();
$result = $model->create($data);

if ($result['success']) {
    Response::success(['id' => $result['id']], 201, $result['message']);
} else {
    Response::error($result['message'], 500);
}
