<?php
/**
 * agregar-ing.php — Controlador para agregar un ingrediente
 *
 * Responsabilidad: Validar la petición y delegar la inserción al IngredienteModel.
 * Ya NO contiene ninguna query SQL directa.
 */
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require_once __DIR__ . '/../Models/IngredienteModel.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validar que los campos obligatorios estén presentes
if (!isset($data['name']) || !isset($data['kcals'])) {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos obligatorios"]);
    exit;
}

$model  = new IngredienteModel();
$result = $model->create($data);

if ($result['success']) {
    echo json_encode(["status" => "success", "id" => $result['id'], "mensaje" => $result['message']]);
} else {
    echo json_encode(["status" => "error", "mensaje" => $result['message']]);
}
