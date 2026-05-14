<?php
/**
 * registro.php — Controlador de registro de nuevos usuarios
 *
 * Responsabilidad: Validar el JSON entrante, hashear la contraseña
 * y delegar la inserción en la BD al UserModel.
 * Ya NO contiene ninguna query SQL directa.
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../Models/UserModel.php';

// Leer el JSON enviado desde el frontend
$data = json_decode(file_get_contents("php://input"));

// Validar que los campos mínimos estén presentes
if (!isset($data->email) || !isset($data->password)) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

// Hashear la contraseña aquí (en el Controlador), antes de pasarla al Modelo.
// El Modelo NO debe conocer contraseñas en texto plano.
$passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

$userModel = new UserModel();

// Pasar un array limpio al Modelo para su inserción
$result = $userModel->create([
    'name'      => $data->name      ?? '',
    'email'     => $data->email,
    'password'  => $passwordHash,
    'sex'       => $data->sex       ?? '',
    'birthDate' => $data->birthDate ?? '',
    'weight'    => $data->weight    ?? 0,
    'height'    => $data->height    ?? 0,
]);

// El Modelo retorna un array ['success' => bool, 'message' => string]
if ($result['success']) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $result['message']]);
}
