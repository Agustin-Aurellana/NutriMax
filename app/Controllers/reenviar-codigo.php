<?php
/**
 * reenviar-codigo.php — POST /api/v1/reenviar-codigo
 *
 * Responsabilidad:
 *   Generar un nuevo código OTP de 6 dígitos para usuarios cuya cuenta
 *   aún no ha sido confirmada, reenviándolo vía Mailer.
 */

require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Validator.php';
require_once __DIR__ . '/../../app/Core/Mailer.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email)) {
    Response::error('El correo electrónico es requerido', 400);
}

$emailVal = Validator::validateEmail((string)$data->email, false);
if (!$emailVal['valid']) {
    Response::error($emailVal['error'], 400);
}
$cleanEmail = $emailVal['email'];

$userModel = new UserModel();
$user      = $userModel->findByEmail($cleanEmail);

if (!$user) {
    Response::error('Usuario no encontrado', 404);
}

if (isset($user['is_verified']) && (int)$user['is_verified'] === 1) {
    Response::error('Esta cuenta ya está verificada. Puedes iniciar sesión directamente.', 400);
}

$newCode = sprintf('%06d', random_int(100000, 999999));
$newExp  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$userModel->setVerificationCode($cleanEmail, $newCode, $newExp);
$mailResult = Mailer::sendVerificationCode($cleanEmail, $user['name'] ?? '', $newCode);

Response::success([
    'email'    => $cleanEmail,
    'dev_code' => ($mailResult['mode'] === 'simulated') ? $newCode : null,
    'mode'     => $mailResult['mode']
], 200, 'Se ha enviado un nuevo código de verificación.');
