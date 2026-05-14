<?php
/**
 * registro.php — Controlador de registro de nuevos usuarios
 *
 * Método: POST /api/v1/registro
 * Body:   { "name", "email", "password", "sex", "birthDate", "weight", "height" }
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    Response::error('Datos incompletos', 400);
}

// El hash de la contraseña se hace en el Controlador, antes de pasarlo al Modelo
$passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

$userModel = new UserModel();
$result    = $userModel->create([
    'name'      => $data->name      ?? '',
    'email'     => $data->email,
    'password'  => $passwordHash,
    'sex'       => $data->sex       ?? '',
    'birthDate' => $data->birthDate ?? '',
    'weight'    => $data->weight    ?? 0,
    'height'    => $data->height    ?? 0,
]);

if ($result['success']) {
    Response::success(null, 201, 'Usuario registrado correctamente');
} else {
    Response::error($result['message'], 409);
}
