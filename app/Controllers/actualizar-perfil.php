<?php
/**
 * actualizar-perfil.php — Controlador de actualización de perfil
 *
 * Responsabilidad: Validar el JSON entrante, convertir el nivel de actividad
 * de texto a número y delegar la actualización en la BD al UserModel.
 * Ya NO contiene ninguna query SQL directa ni lógica de conexión.
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../Models/UserModel.php';

// Obtener y decodificar el JSON enviado desde el frontend
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'])) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos o incompletos"]);
    exit;
}

// Mapear niveles de actividad textuales a su representación numérica en la BD.
// Esta lógica de transformación pertenece al Controlador, no a la BD ni al Modelo.
$activity_map = [
    'sedentary'  => 0, 'sedentario' => 0,
    'light'      => 2, 'ligero'     => 2,
    'moderate'   => 4, 'moderado'   => 4,
    'active'     => 6, 'activo'     => 6,
    'veryactive' => 7, 'muy activo' => 7,
];

$nivel_texto    = strtolower($data['activityLevel'] ?? '');
$act_fisica_int = $activity_map[$nivel_texto] ?? 0;

$userModel = new UserModel();

// Delegar la actualización al Modelo con los datos ya procesados
$result = $userModel->updateProfile($data['email'], [
    'name'          => $data['name']      ?? '',
    'birthDate'     => $data['birthDate'] ?? '',
    'sex'           => $data['sex']       ?? '',
    'weight'        => $data['weight']    ?? 0,
    'activityLevel' => $act_fisica_int,
    'goal'          => $data['goal']      ?? '',
    'height'        => $data['height']    ?? 0,
]);

if ($result['success']) {
    echo json_encode(["status" => "success", "message" => $result['message']]);
} else {
    echo json_encode(["status" => "error", "message" => $result['message']]);
}
