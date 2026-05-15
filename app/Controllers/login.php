<?php
/**
 * login.php — POST /api/v1/login
 * Devuelve un JWT en lugar de iniciar sesión server-side.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/JWT.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    Response::error('Faltan datos de acceso', 400);
}

$userModel = new UserModel();
$user      = $userModel->findByEmail($data->email);

if ($user === null) {
    Response::error('El usuario no existe', 404);
}

if (!password_verify($data->password, $user['clave'])) {
    Response::error('Contraseña incorrecta', 401);
}

// Remover la clave del objeto antes de enviarlo al frontend
unset($user['clave']);

// Generar JWT con datos mínimos en el payload (no incluir datos sensibles)
$token = JWT::generate([
    'id'    => $user['ID_USER'],
    'email' => $user['email'],
    'name'  => $user['name'],
]);

// Devolver tanto el token como los datos del usuario para que el frontend
// pueda inicializar el estado local sin hacer una segunda petición
Response::success([
    'token' => $token,
    'user'  => $user,
], 200, 'Autenticación exitosa');
