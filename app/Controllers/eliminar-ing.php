<?php
/**
 * eliminar-ing.php — Controlador para eliminar un ingrediente
 *
 * Responsabilidad: Validar que se haya enviado un ID válido y delegar
 * la eliminación al IngredienteModel.
 * Ya NO contiene ninguna query SQL directa.
 */
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require_once __DIR__ . '/../Models/IngredienteModel.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que se haya enviado un ID numérico
if (!isset($data['id'])) {
    echo json_encode(["status" => "error", "mensaje" => "No se proporcionó el ID del ingrediente"]);
    exit;
}

$model  = new IngredienteModel();
$result = $model->delete((int) $data['id']);

if ($result['success']) {
    echo json_encode(["status" => "success", "mensaje" => $result['message']]);
} else {
    echo json_encode(["status" => "error", "mensaje" => $result['message']]);
}
