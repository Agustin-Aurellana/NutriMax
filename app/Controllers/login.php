<?php
/**
 * login.php — Controlador de autenticación
 *
 * Método: POST /api/v1/login
 * Body:   { "email": "...", "password": "..." }
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../Models/UserModel.php';

// Solo aceptamos POST
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

// Remover la clave del payload antes de enviarlo al frontend
unset($user['clave']);

Response::success($user, 200, 'Autenticación exitosa');
