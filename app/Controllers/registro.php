<?php
/**
 * registro.php — Controlador de registro de nuevos usuarios
 *
 * Método: POST /api/v1/registro
 * Body:   { "name", "email", "password", "sex", "birthDate", "weight", "height" }
 *
 * Flujo:
 *   1. Validación defensiva de formato, TLD y existencia de dominio (CP-VAL-01, 02, 03).
 *   2. Creación del usuario con estado no verificado (is_verified = 0).
 *   3. Emisión de código OTP de 6 dígitos enviado por Mailer (modo híbrido: SMTP real o simulado).
 *   4. Retorno con bandera requires_verification = true. NO emite JWT hasta verificar el correo.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Validator.php';
require_once __DIR__ . '/../../app/Core/Mailer.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    Response::error('Datos incompletos', 400);
}

// ── CP-VAL-01 / 02 / 03: Validación defensiva estricta de correo electrónico ──
// Valida TLD obligatorio (.com, .net, etc.), reglas de Gmail y resolución DNS de servidores de correo
$emailValidation = Validator::validateEmail((string)$data->email, true);
if (!$emailValidation['valid']) {
    Response::error($emailValidation['error'], 400);
}
$cleanEmail = $emailValidation['email'];

// Validación de contraseña mínima
if (strlen($data->password) < 8) {
    Response::error('La contraseña debe tener al menos 8 caracteres', 400);
}

// CP-REG-21: Validación de longitud en backend para evitar excepciones 500 por sobreflujo en columna varchar(50)
$cleanName = isset($data->name) ? trim((string)$data->name) : '';
if (mb_strlen($cleanName, 'UTF-8') > 50) {
    Response::error('El nombre no puede superar los 50 caracteres', 400);
}

if (isset($data->weight) && (float)$data->weight < 0) {
    Response::error('El peso no puede ser negativo', 400);
}

if (isset($data->height) && (float)$data->height < 0) {
    Response::error('La altura no puede ser negativa', 400);
}

// Generar código OTP criptográficamente seguro de 6 dígitos
$otpCode = sprintf('%06d', random_int(100000, 999999));
$otpExp  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// El hash de la contraseña se hace en el Controlador antes de persistirlo
$passwordHash = password_hash($data->password, PASSWORD_DEFAULT);

$userModel = new UserModel();
$result    = $userModel->create([
    'name'                 => $cleanName,
    'email'                => $cleanEmail,
    'password'             => $passwordHash,
    'sex'                  => $data->sex       ?? '',
    'birthDate'            => $data->birthDate ?? '',
    'weight'               => $data->weight    ?? 0,
    'height'               => $data->height    ?? 0,
    'is_verified'          => 0,
    'verification_code'    => $otpCode,
    'verification_expires' => $otpExp,
]);

if ($result['success']) {
    // Enviar el correo con el código de verificación OTP
    $mailResult = Mailer::sendVerificationCode($cleanEmail, $cleanName, $otpCode);

    // Respondemos indicando que la cuenta requiere verificación antes de iniciar sesión.
    // En modo simulado (QA/local), se incluye 'dev_code' para permitir pruebas automáticas y manuales.
    Response::success([
        'requires_verification' => true,
        'email'                 => $cleanEmail,
        'dev_code'              => ($mailResult['mode'] === 'simulated') ? $otpCode : null,
        'mode'                  => $mailResult['mode'],
    ], 200, 'Código de verificación generado. Revisa tu correo electrónico para activar tu cuenta.');
} else {
    Response::error($result['message'], 409);
}
