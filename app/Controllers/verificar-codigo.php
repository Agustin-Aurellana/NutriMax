<?php
/**
 * verificar-codigo.php — POST /api/v1/verificar-codigo
 *
 * Responsabilidad:
 *   Validar el código OTP de 6 dígitos ingresado por el usuario.
 *   Una vez confirmado, marca la cuenta como activa (is_verified = 1),
 *   emite el token JWT y retorna la información completa del usuario.
 */

require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/JWT.php';
require_once __DIR__ . '/../../app/Core/Validator.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->code)) {
    Response::error('Faltan datos requeridos (email y código)', 400);
}

$emailVal = Validator::validateEmail((string)$data->email, false);
if (!$emailVal['valid']) {
    Response::error($emailVal['error'], 400);
}
$cleanEmail = $emailVal['email'];
$code = trim((string)$data->code);

if (strlen($code) !== 6 || !ctype_digit($code)) {
    Response::error('El código de verificación debe contener exactamente 6 dígitos numéricos', 400);
}

$userModel = new UserModel();
$result    = $userModel->verifyAccount($cleanEmail, $code);

if (!$result['success']) {
    Response::error($result['message'], 400);
}

$user = $result['user'];
unset($user['clave'], $user['verification_code'], $user['verification_expires']);

// Generar JWT para el usuario recién verificado
$token = JWT::generate([
    'id'    => $user['ID_USER'],
    'email' => $user['email'],
    'name'  => $user['name'] ?? '',
]);

Response::success([
    'token' => $token,
    'user'  => [
        'ID_USER'   => $user['ID_USER'],
        'email'     => $user['email'],
        'name'      => $user['name'] ?? '',
        'genero'    => !empty($user['genero']) ? strtoupper(substr($user['genero'], 0, 1)) : 'M',
        'nacimiento'=> $user['nacimiento'] ?? '',
        'peso'      => (float) ($user['peso'] ?? 0),
        'altura_cm' => (float) ($user['altura_cm'] ?? 0),
        'act_fisica'=> $user['act_fisica'] ?? 'moderate',
        'objetivo'  => $user['objetivo'] ?? 'maintenance'
    ]
], 200, '¡Cuenta verificada exitosamente!');
