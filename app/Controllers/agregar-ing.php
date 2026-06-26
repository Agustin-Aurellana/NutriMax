<?php
/**
 * agregar-ing.php — POST /api/v1/agregar-ing
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// Extraemos el usuario autenticado para asociarlo con su nuevo ingrediente
$authUser = Auth::requireAuth();

$data = json_decode(file_get_contents("php://input"), true);

// Validamos todos los campos macro-nutricionales requeridos
if (!isset($data['name']) || !isset($data['kcals']) || !isset($data['protein']) || !isset($data['carbs']) || !isset($data['fat'])) {
    Response::error('Faltan datos obligatorios (nombre o macronutrientes)', 400);
}

// Mapeamos las claves del frontend a las esperadas por el modelo para evitar que se guarden en 0
$data['prot']    = $data['protein'];
$data['carbo']   = $data['carbs'];
$data['gras']    = $data['fat'];
$data['ID_USER'] = $authUser['id']; // Guardamos como personalizado de este usuario

$model  = new IngredienteModel();
$result = $model->create($data);

if ($result['success']) {
    Response::success(['id' => $result['id']], 201, $result['message']);
} else {
    Response::error($result['message'], 500);
}
