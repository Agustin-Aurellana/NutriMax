<?php

/**
 * Auth.php — Middleware de autenticación JWT
 *
 * Uso en controladores protegidos:
 *   $user = Auth::requireAuth();
 *   // Si llega hasta aquí, el token es válido y $user tiene el payload.
 */
require_once __DIR__ . '/JWT.php';
require_once __DIR__ . '/Response.php';

class Auth
{
    /**
     * Extrae y valida el JWT del header Authorization: Bearer <token>.
     * Si el token es inválido o no existe, responde 401 y detiene la ejecución.
     *
     * @return array El payload del token (contiene id, email, name del usuario).
     */
    public static function requireAuth(): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Algunos servidores (Apache mod_rewrite) no exponen HTTP_AUTHORIZATION por defecto.
        // Este fallback usa getallheaders() como alternativa.
        if (empty($authHeader)) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        // El header debe tener el formato "Bearer <token>"
        if (!str_starts_with($authHeader, 'Bearer ')) {
            Response::error('No autenticado: token no proporcionado', 401);
        }

        $token   = substr($authHeader, 7); // Quitar "Bearer "
        $payload = JWT::validate($token);

        if ($payload === null) {
            Response::error('Token inválido o expirado', 401);
        }

        return $payload;
    }

    /**
     * Igual que requireAuth() pero no corta la ejecución.
     * Útil para rutas semi-protegidas o para extraer info del usuario opcionalmente.
     *
     * @return array|null Payload del token o null si no está autenticado.
     */
    public static function optionalAuth(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader)) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!str_starts_with($authHeader, 'Bearer ')) return null;

        $token = substr($authHeader, 7);
        return JWT::validate($token);
    }
}
