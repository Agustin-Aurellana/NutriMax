<?php
/**
 * google_auth.php — POST /api/v1/google-auth
 * Valida token de Google y devuelve JWT propio si el usuario existe.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/JWT.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->credential)) {
    Response::error('Faltan datos de acceso', 400);
}

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

if ($result['status'] === 'success') {
    unset($result['user']['clave']);

    // Generar JWT para el usuario encontrado
    $token = JWT::generate([
        'id'    => $result['user']['ID_USER'],
        'email' => $result['user']['email'],
        'name'  => $result['user']['name'],
    ]);

    Response::success([
        'token' => $token,
        'user'  => $result['user'],
    ], 200, 'Autenticación con Google exitosa');
} else {
    // Usuario incompleto: todavía no tiene cuenta en NutriMax — no generamos JWT
    Response::success($result['partial_user'], 206, $result['message']);
}
