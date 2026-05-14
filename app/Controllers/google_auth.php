<?php
/**
 * google_auth.php — Controlador de autenticación con Google
 *
 * Responsabilidad: Validar el token de Google, extraer datos del payload
 * y delegar la lógica de "buscar o crear usuario" al UserModel.
 * Ya NO contiene ninguna query SQL directa.
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../Models/UserModel.php';

// Recibir los datos enviados por el frontend
$data = json_decode(file_get_contents("php://input"));

// Verificar que se haya recibido la credencial JWT de Google
if (!isset($data->credential)) {
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
    exit;
}

$jwt = $data->credential;

// Validar el token directamente en el endpoint oficial de Google
$verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
$response   = @file_get_contents($verify_url);

// Si la validación falla (token expirado, malformado, etc.)
if ($response === false) {
    echo json_encode(["status" => "error", "message" => "Token de Google inválido"]);
    exit;
}

$payload = json_decode($response);

// Verificar que el payload contenga un email válido
if (!isset($payload->email)) {
    echo json_encode(["status" => "error", "message" => "No se pudo obtener el email de Google"]);
    exit;
}

$userModel = new UserModel();

// Delegar la lógica de "buscar o crear" al Modelo
$result = $userModel->findOrCreateGoogle(
    (string) $payload->email,
    (string) ($payload->name ?? '')
);

// El Modelo retorna directamente la estructura de respuesta para este caso de uso
echo json_encode($result);
