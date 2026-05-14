<?php
/**
 * google_auth.php — Controlador de autenticación con Google
 *
 * Método: POST /api/v1/google-auth
 * Body:   { "credential": "<JWT de Google>" }
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->credential)) {
    Response::error('Faltan datos de acceso', 400);
}

// Validar el token en el endpoint oficial de Google
$verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $data->credential;
$response   = @file_get_contents($verify_url);

if ($response === false) {
    Response::error('Token de Google inválido', 401);
}

$payload = json_decode($response);

if (!isset($payload->email)) {
    Response::error('No se pudo obtener el email de Google', 401);
}

$userModel = new UserModel();
$result    = $userModel->findOrCreateGoogle(
    (string) $payload->email,
    (string) ($payload->name ?? '')
);

// findOrCreateGoogle retorna status "success" o "incomplete"
if ($result['status'] === 'success') {
    unset($result['user']['clave']); // No exponer el hash
    Response::success($result['user'], 200, 'Autenticación con Google exitosa');
} else {
    // "incomplete": el usuario existe en Google pero no en NutriMax aún
    Response::success($result['partial_user'], 206, $result['message']);
}
