<?php
/**
 * RateLimiter.php — Core/RateLimiter
 *
 * Resuelve: CP-SEC-07 — Fuerza bruta en endpoint de login.
 * Resuelve: CP-SEC-08 — Peticiones masivas sin control de tasa en la API (Gateway).
 *
 * Implementa control de tasa (Rate Limiting) sin base de datos relacional
 * para proteger el servidor y la base de datos MySQL contra saturación y fuerza bruta.
 *
 * Estrategias de almacenamiento:
 *   - Opción A: APCu (memoria compartida, preferida en producción si está habilitada)
 *   - Opción B: Filesystem (archivos con bloqueo atómico flock, fallback universal robusto)
 *
 * Capas de protección:
 *   1. Gateway Rate Limiting (todas las rutas /api/v1/*):
 *      - Por IP: Máximo 60 peticiones en una ventana de 60 segundos.
 *      - Por Sesión: Máximo 60 peticiones en una ventana de 60 segundos (JWT, Cookie, Header).
 *      - Tras superar el límite, responde HTTP 429 Too Many Requests con Retry-After.
 *   2. Login Brute-Force Rate Limiting (específico de /api/v1/login):
 *      - Por Email: Máximo 5 intentos fallidos en una ventana de 60 segundos.
 *      - Por IP: Máximo 5 intentos en una ventana de 60 segundos.
 */
class RateLimiter
{
    // ── Configuración de Gateway (/api/v1/*) ──────────────────────────────────
    // Permite uso legítimo intensivo (~5-10 peticiones por carga de página)
    // mientras corta de inmediato ráfagas masivas (ej. 100 req/3s) antes de saturar MySQL.
    public const LIMIT_PER_IP         = 60; // Peticiones máximas por IP en la ventana
    public const LIMIT_PER_SESSION    = 60; // Peticiones máximas por Sesión en la ventana
    public const WINDOW_SECONDS       = 60; // Ventana deslizante en segundos

    // ── Configuración de Login Fuerza Bruta (CP-SEC-07) ───────────────────────
    public const MAX_LOGIN_ATTEMPTS   = 5;  // Intentos permitidos en login
    public const LOGIN_WINDOW_SECONDS = 60; // Ventana para login en segundos

    // ── Prefijos de clave para evitar colisiones entre capas y tipos de dato ──
    private const PREFIX_GW_IP       = 'gw_ip_';
    private const PREFIX_GW_SESS     = 'gw_sess_';
    private const PREFIX_LOGIN_IP    = 'login_ip_';
    private const PREFIX_LOGIN_EMAIL = 'login_em_';

    // ── Directorio para almacenamiento Filesystem (Opción B) ──────────────────
    private const FS_CACHE_DIR = __DIR__ . '/../../cache/rate_limit/';

    // =========================================================================
    //  GATEWAY RATE LIMITING (Centralizado en index.php)
    // =========================================================================

    /**
     * Aplica el límite de tasa centralizado en el Gateway para cualquier petición de la API.
     * Evalúa tanto el límite por IP como el límite por Sesión (si existe sesión activa).
     *
     * Se invoca en index.php ANTES de cargar los controladores o conectar con MySQL,
     * garantizando que ráfagas de 100 peticiones en 3 segundos sean interceptadas
     * sin consumir recursos de la base de datos relacional.
     *
     * @param string      $ip        Dirección IP del cliente.
     * @param string|null $sessionId Identificador único de sesión (opcional).
     */
    public static function checkGateway(string $ip, ?string $sessionId = null): void
    {
        // 1. Rate Limiting por IP (siempre activo para todas las peticiones a la API)
        self::hit(
            self::PREFIX_GW_IP . hash('sha256', $ip),
            self::LIMIT_PER_IP,
            self::WINDOW_SECONDS,
            'IP',
            $ip
        );

        // 2. Rate Limiting por Sesión (activo si la petición porta token, header o cookie de sesión)
        if ($sessionId !== null && $sessionId !== '') {
            self::hit(
                self::PREFIX_GW_SESS . hash('sha256', $sessionId),
                self::LIMIT_PER_SESSION,
                self::WINDOW_SECONDS,
                'Sesión',
                $sessionId
            );
        }
    }

    /**
     * Extrae el identificador de sesión a partir de los headers o cookies disponibles.
     * Inspecciona en orden de precedencia:
     *   1. JWT en Header Authorization: Bearer <token> (extrae ID de usuario o hash del token).
     *   2. Header personalizado de sesión: X-Session-ID o Session-ID.
     *   3. Cookie de sesión: PHPSESSID, session_id o session.
     *   4. Sesión PHP nativa activa si estuviera iniciada.
     *
     * @return string|null Identificador de sesión unificado o null si la petición es anónima.
     */
    public static function extractSessionId(): ?string
    {
        // 1. Header Authorization: Bearer <JWT>
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (empty($authHeader) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!empty($authHeader) && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
                $payload     = json_decode($payloadJson, true);
                if (!empty($payload['id'])) {
                    // Vinculamos la sesión directamente al identificador único del usuario
                    return 'user_' . $payload['id'];
                }
            }
            return 'jwt_' . hash('sha256', $token);
        }

        // 2. Header de sesión personalizado (ej. X-Session-ID o Session-ID)
        if (!empty($_SERVER['HTTP_X_SESSION_ID'])) {
            return 'sess_hdr_' . trim($_SERVER['HTTP_X_SESSION_ID']);
        }
        if (!empty($_SERVER['HTTP_SESSION_ID'])) {
            return 'sess_hdr_' . trim($_SERVER['HTTP_SESSION_ID']);
        }

        // 3. Cookie de sesión (PHPSESSID o cookie 'session')
        if (!empty($_COOKIE['PHPSESSID'])) {
            return 'phpsess_' . trim($_COOKIE['PHPSESSID']);
        }
        if (!empty($_COOKIE['session_id'])) {
            return 'sess_cookie_' . trim($_COOKIE['session_id']);
        }
        if (!empty($_COOKIE['session'])) {
            return 'sess_cookie_' . trim($_COOKIE['session']);
        }

        // 4. Sesión PHP nativa activa
        if (session_status() === PHP_SESSION_ACTIVE && session_id() !== '') {
            return 'phpsess_' . session_id();
        }

        return null;
    }

    // =========================================================================
    //  LOGIN BRUTE-FORCE RATE LIMITING (CP-SEC-07)
    // =========================================================================

    /**
     * Verifica el límite de intentos por Email en el endpoint de login.
     * El email se normaliza a minúsculas y se hashea con SHA-256 para no
     * exponer datos personales en la caché ni en nombres de archivo.
     *
     * @param string $email Correo ingresado en el login.
     */
    public static function checkEmailWithApcu(string $email): void
    {
        $normalized = strtolower(trim($email));
        $hash       = hash('sha256', $normalized);
        self::hit(
            self::PREFIX_LOGIN_EMAIL . $hash,
            self::MAX_LOGIN_ATTEMPTS,
            self::LOGIN_WINDOW_SECONDS,
            'Email',
            $hash
        );
    }

    /**
     * Verifica el límite de intentos de login por IP.
     *
     * @param string $ip Dirección IP del cliente.
     */
    public static function checkWithApcu(string $ip): void
    {
        self::hit(
            self::PREFIX_LOGIN_IP . hash('sha256', $ip),
            self::MAX_LOGIN_ATTEMPTS,
            self::LOGIN_WINDOW_SECONDS,
            'IP (Login)',
            $ip
        );
    }

    /**
     * Resetea el contador de login por IP tras un login exitoso.
     *
     * @param string $ip
     */
    public static function resetWithApcu(string $ip): void
    {
        self::deleteKey(self::PREFIX_LOGIN_IP . hash('sha256', $ip));
    }

    /**
     * Resetea el contador de login por Email tras un login exitoso.
     *
     * @param string $email
     */
    public static function resetEmailWithApcu(string $email): void
    {
        $normalized = strtolower(trim($email));
        self::deleteKey(self::PREFIX_LOGIN_EMAIL . hash('sha256', $normalized));
    }

    // =========================================================================
    //  MÉTODOS DE COMPATIBILIDAD HACIA ATRÁS
    // =========================================================================

    public static function checkAuthenticatedRequest(string $ip, string $userId): void
    {
        self::checkGateway($ip, $userId);
    }

    public static function checkWithFilesystem(string $ip): void
    {
        self::checkWithApcu($ip);
    }

    public static function checkEmailWithFilesystem(string $email): void
    {
        self::checkEmailWithApcu($email);
    }

    public static function resetWithFilesystem(string $ip): void
    {
        self::resetWithApcu($ip);
    }

    public static function resetEmailWithFilesystem(string $email): void
    {
        self::resetEmailWithApcu($email);
    }

    // =========================================================================
    //  MOTOR INTERNO DE RATE LIMITING (APCu + Filesystem con bloqueo atómico)
    // =========================================================================

    /**
     * Despacha la verificación al backend disponible (APCu en RAM o Filesystem).
     *
     * @param string $key           Clave única del contador.
     * @param int    $maxRequests   Peticiones máximas permitidas.
     * @param int    $windowSeconds Duración de la ventana temporal.
     * @param string $label         Etiqueta para logs y diagnóstico.
     * @param string $logId         Identificador para registro en log.
     */
    private static function hit(
        string $key,
        int    $maxRequests,
        int    $windowSeconds,
        string $label,
        string $logId
    ): void {
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            self::hitApcu($key, $maxRequests, $windowSeconds, $label, $logId);
        } else {
            self::hitFilesystem($key, $maxRequests, $windowSeconds, $label, $logId);
        }
    }

    /**
     * Implementación atómica con APCu en memoria compartida.
     */
    private static function hitApcu(
        string $key,
        int    $maxRequests,
        int    $windowSeconds,
        string $label,
        string $logId
    ): void {
        $count = apcu_fetch($key, $found);

        if (!$found) {
            apcu_store($key, 1, $windowSeconds);
            return;
        }

        if ($count >= $maxRequests) {
            self::sendTooManyRequests($label, $logId, $windowSeconds);
        }

        apcu_inc($key);
    }

    /**
     * Implementación en Filesystem con bloqueo exclusivo atómico (flock LOCK_EX).
     *
     * Resuelve problemas de concurrencia y bloqueos de archivos en Windows:
     * Al usar fopen con modo 'c+' y mantener LOCK_EX durante la lectura, modificación
     * y truncado/escritura, no existen condiciones de carrera ni llamadas conflictivas a
     * rename/unlink sobre archivos abiertos en Windows.
     */
    private static function hitFilesystem(
        string $key,
        int    $maxRequests,
        int    $windowSeconds,
        string $label,
        string $logId
    ): void {
        $cacheDir = self::FS_CACHE_DIR;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0750, true);
            file_put_contents($cacheDir . '.htaccess', "Deny from all\n");
        }

        // Sanitizamos la clave para que sea un nombre de archivo seguro
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $file    = $cacheDir . $safeKey . '.json';

        $handle = fopen($file, 'c+');
        if ($handle === false) {
            // Fail-open seguro en caso de fallo crítico de permisos de SO
            return;
        }

        // Bloqueo exclusivo: todas las demás peticiones concurrentes esperan aquí
        flock($handle, LOCK_EX);

        $filesize = filesize($file);
        $content  = ($filesize > 0) ? fread($handle, $filesize) : '';
        $data     = !empty($content) ? json_decode($content, true) : null;
        $now      = time();

        // Si expiró la ventana de tiempo o el archivo no tiene formato válido, reiniciamos
        if (!is_array($data) || !isset($data['attempts'], $data['window_start']) || ($now - $data['window_start']) >= $windowSeconds) {
            $data = ['attempts' => 0, 'window_start' => $now];
        }

        // Si superó el límite, calculamos el tiempo restante y abortamos con 429
        if ($data['attempts'] >= $maxRequests) {
            $retryAfter = max(1, $windowSeconds - ($now - $data['window_start']));
            flock($handle, LOCK_UN);
            fclose($handle);
            self::sendTooManyRequests($label, $logId, $retryAfter);
        }

        $data['attempts']++;

        // Sobrescribir de forma atómica truncando el fichero ya bloqueado
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data));
        fflush($handle);

        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Elimina una clave de la caché (APCu o archivo JSON).
     */
    private static function deleteKey(string $key): void
    {
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            apcu_delete($key);
        } else {
            $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
            $file    = self::FS_CACHE_DIR . $safeKey . '.json';
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Emite la respuesta HTTP 429 Too Many Requests y finaliza la ejecución inmediatamente.
     *
     * Incluye:
     *   - Código HTTP 429
     *   - Cabecera estándar Retry-After (RFC 6585)
     *   - Cabeceras CORS para no romper clientes web/SPA
     *   - Cuerpo JSON con status 429 y código descriptivo
     */
    private static function sendTooManyRequests(
        string $label,
        string $logId,
        int    $retryAfter = self::WINDOW_SECONDS
    ): void {
        http_response_code(429);
        header('Content-Type: application/json; charset=UTF-8');
        header('Retry-After: ' . $retryAfter);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-ID');

        error_log(sprintf(
            '[NutriMax][RateLimiter] %s bloqueado/a: %s | Límite superado | Retry-After: %ds',
            $label,
            $logId,
            $retryAfter
        ));

        echo json_encode([
            'success' => false,
            'status'  => 429,
            'message' => 'Demasiadas solicitudes. Límite de tasa excedido. Por favor, espera ' . $retryAfter . ' segundos antes de intentarlo nuevamente.',
            'code'    => 'RATE_LIMIT_EXCEEDED',
            'data'    => [
                'retry_after' => $retryAfter,
            ],
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}
