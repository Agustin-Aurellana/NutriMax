<?php
/**
 * login.php — POST /api/v1/login
 *
 * Devuelve un JWT en lugar de iniciar sesión server-side.
 *
 * Seguridad (CP-SEC-07):
 *   Se aplica Rate Limiting ANTES de cualquier lógica de negocio
 *   para mitigar ataques de fuerza bruta. Máx. 5 intentos/60s por IP.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/JWT.php';
require_once __DIR__ . '/../../app/Core/RateLimiter.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// ── GUARDIA DE SEGURIDAD: Rate Limiting ───────────────────────────────────────
//
// Obtenemos la IP real del cliente. En entornos detrás de un proxy/balanceador
// (Nginx, CloudFlare, etc.) la IP real viaja en HTTP_X_FORWARDED_FOR.
// IMPORTANTE: solo confiar en este header si el servidor está DETRÁS de un proxy
// de confianza. En un servidor expuesto directamente a Internet, usar REMOTE_ADDR.
//
// Para producción con proxy inverso, descomentar la línea de X_FORWARDED_FOR
// y comentar la línea de REMOTE_ADDR.
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
// $clientIp = trim(explode(',', $clientIp)[0]); // Tomar solo la primera IP de la cadena.

// Verificar el límite de intentos ANTES de tocar la base de datos.
// Si se supera el límite, este método envía HTTP 429 y llama a exit().
// El método usa APCu si está disponible; si no, usa el Filesystem (fallback automático).
RateLimiter::checkWithApcu($clientIp);
// ─────────────────────────────────────────────────────────────────────────────

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    Response::error('Faltan datos de acceso', 400);
}

$userModel = new UserModel();
$user      = $userModel->findByEmail($data->email);

if ($user === null) {
    // No resetear el contador aquí: el email inexistente es un intento fallido.
    Response::error('El usuario no existe', 404);
}

if (!password_verify($data->password, $user['clave'])) {
    // No resetear el contador: contraseña incorrecta es un intento fallido.
    Response::error('Contraseña incorrecta', 401);
}

// ── Login EXITOSO: limpiar el contador de intentos fallidos ───────────────────
// Si no reseteamos aquí, un usuario legítimo que falló varias veces antes de
// recordar su clave quedaría bloqueado injustamente durante el resto de la ventana.
RateLimiter::resetWithApcu($clientIp);
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
