<?php
/**
 * login.php — POST /api/v1/login
 *
 * Devuelve un JWT en lugar de iniciar sesión server-side.
 *
 * Seguridad (CP-SEC-07):
 *   Se aplica Rate Limiting ANTES de cualquier lógica de negocio
 *   para mitigar ataques de fuerza bruta.
 *     - Máx. 5 intentos/60s por IP.
 *     - Máx. 5 intentos/60s por Email (hash SHA-256, nunca en texto plano).
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/JWT.php';
require_once __DIR__ . '/../../app/Core/RateLimiter.php';
require_once __DIR__ . '/../../app/Core/Validator.php';
require_once __DIR__ . '/../../app/Core/Mailer.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// ── GUARDIA DE SEGURIDAD: Rate Limiting ───────────────────────────────────────
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Verificar el límite de intentos ANTES de tocar la base de datos.
RateLimiter::checkWithApcu($clientIp);
// ─────────────────────────────────────────────────────────────────────────────

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    Response::error('Faltan datos de acceso', 400);
}

// CP-VAL-01 / CP-VAL-02: Validación defensiva previa de formato de correo
$emailVal = Validator::validateEmail((string)$data->email, false);
if (!$emailVal['valid']) {
    Response::error($emailVal['error'], 400);
}
$cleanEmail = $emailVal['email'];

// ── GUARDIA DE SEGURIDAD: Rate Limiting por Email ─────────────────────────────
RateLimiter::checkEmailWithApcu($cleanEmail);
// ─────────────────────────────────────────────────────────────────────────────

$userModel = new UserModel();
$user      = $userModel->findByEmail($cleanEmail);

if ($user === null) {
    // No resetear el contador aquí: el email inexistente es un intento fallido.
    Response::error('El usuario no existe', 404);
}

if (!password_verify($data->password, $user['clave'])) {
    // No resetear el contador: contraseña incorrecta es un intento fallido.
    Response::error('Contraseña incorrecta', 401);
}

// Comprobación de activación de cuenta: si no está verificado, impedir el acceso
if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
    $newCode = sprintf('%06d', random_int(100000, 999999));
    $newExp  = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $userModel->setVerificationCode($cleanEmail, $newCode, $newExp);
    $mailResult = Mailer::sendVerificationCode($cleanEmail, $user['name'] ?? '', $newCode);

    Response::error('Tu cuenta aún no está verificada. Ingresá el código que enviamos a tu correo.', 403, [
        'requires_verification' => true,
        'email'                 => $cleanEmail,
        'dev_code'              => ($mailResult['mode'] === 'simulated') ? $newCode : null,
        'mode'                  => $mailResult['mode']
    ]);
}

// ── Login EXITOSO: limpiar AMBOS contadores de intentos fallidos ──────────────
// Se limpian tanto el contador de IP como el de Email para que un usuario
// legítimo no quede bloqueado tras varios fallos previos a un login exitoso.
RateLimiter::resetWithApcu($clientIp);
RateLimiter::resetEmailWithApcu($data->email);
// ─────────────────────────────────────────────────────────────────────────────

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
