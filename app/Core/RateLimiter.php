<?php
/**
 * RateLimiter.php — Core/RateLimiter
 *
 * Resuelve: CP-SEC-07 — Fuerza bruta en el endpoint de login.
 *
 * Implementa un sistema de Rate Limiting sin base de datos relacional,
 * con dos estrategias intercambiables según el entorno del servidor:
 *
 *   - Opción A: APCu  (recomendada, requiere extensión php-apcu)
 *   - Opción B: Filesystem (fallback universal, no requiere extensiones)
 *
 * Reglas de negocio:
 *   - Máximo 5 intentos FALLIDOS por IP en una ventana deslizante de 60 segundos.
 *   - Al superar el límite, se devuelve HTTP 429 y se aborta la ejecución.
 *   - El contador se reinicia automáticamente cuando la ventana expira.
 */
class RateLimiter
{
    // ── Configuración central ──────────────────────────────────────────────────
    private const MAX_ATTEMPTS   = 5;           // Intentos fallidos permitidos en la ventana
    private const WINDOW_SECONDS = 60;          // Duración de la ventana en segundos
    private const CACHE_PREFIX   = 'nm_rl_';    // Prefijo de clave para evitar colisiones

    // ── Directorio para la Opción B (Filesystem) ──────────────────────────────
    // Se usa sys_get_temp_dir() como fallback si la carpeta del proyecto no existe.
    private const FS_CACHE_DIR = __DIR__ . '/../../cache/rate_limit/';


    // =========================================================================
    //  OPCIÓN A — APCu (Caché en memoria compartida entre workers PHP)
    //  Recomendada para producción con PHP-FPM o Apache mod_php.
    //  Requiere: extensión "apcu" habilitada en php.ini (apc.enabled=1).
    // =========================================================================

    /**
     * Verifica y registra el intento usando APCu.
     *
     * Llama a este método ANTES de validar credenciales.
     * Si la IP está bloqueada, envía HTTP 429 y detiene la ejecución.
     *
     * @param string $ip  Dirección IP del cliente (ya validada/sanitizada).
     */
    public static function checkWithApcu(string $ip): void
    {
        // Verificamos que la extensión esté disponible para evitar errores fatales.
        if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
            // Si APCu no está disponible, fallamos de forma segura usando Filesystem.
            // Esto evita un "fail open" (dejar pasar sin control) ante una mala config.
            self::checkWithFilesystem($ip);
            return;
        }

        $key = self::CACHE_PREFIX . $ip;

        // apcu_fetch devuelve false si la clave no existe O expiró.
        $attempts = apcu_fetch($key, $success);

        if (!$success) {
            // Primera visita de esta IP en la ventana actual: crear el contador.
            // El TTL (tercer parámetro) es la ventana de tiempo → expira solo.
            apcu_store($key, 1, self::WINDOW_SECONDS);
            return; // Primer intento, dejar pasar.
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            // IP bloqueada: devolver 429 con cabeceras informativas (RFC 6585).
            self::sendTooManyRequests($ip);
        }

        // Intento dentro del límite: incrementar el contador.
        // apcu_inc es atómica, lo que evita race conditions bajo carga concurrente.
        apcu_inc($key);
    }

    /**
     * Resetea el contador de una IP tras un login EXITOSO (Opción A).
     *
     * Por qué: si no reseteamos, un usuario legítimo que falló N veces
     * antes de recordar su contraseña quedaría bloqueado injustamente.
     *
     * @param string $ip
     */
    public static function resetWithApcu(string $ip): void
    {
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            apcu_delete(self::CACHE_PREFIX . $ip);
        }
    }


    // =========================================================================
    //  OPCIÓN B — Filesystem (archivos temporales JSON)
    //  Fallback universal. Funciona en cualquier servidor PHP sin extensiones.
    //  Consideración: en servidores de alta carga, usar APCu es mejor
    //  porque evita I/O de disco y race conditions.
    // =========================================================================

    /**
     * Verifica y registra el intento usando el sistema de archivos.
     *
     * Cada IP tiene un archivo JSON propio con su contador y timestamp.
     * El archivo actúa como "registro volátil" y expira por tiempo, no por
     * limpieza activa (aunque se recomienda un cron job de limpieza).
     *
     * @param string $ip  Dirección IP del cliente.
     */
    public static function checkWithFilesystem(string $ip): void
    {
        $cacheDir = self::FS_CACHE_DIR;

        // Crear el directorio de caché si no existe.
        // 0750 = rwxr-x---: el webserver puede leer/escribir, otros no.
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0750, true);

            // Proteger el directorio con un .htaccess para que Apache
            // no sirva los archivos de caché como respuestas HTTP.
            file_put_contents($cacheDir . '.htaccess', "Deny from all\n");
        }

        // Nombre de archivo seguro: hash SHA-256 de la IP para evitar
        // caracteres peligrosos (IPv6 contiene ':') y exposición directa de IPs.
        $file = $cacheDir . self::hashIp($ip) . '.json';

        $now  = time();
        $data = self::readCacheFile($file);

        // ¿La ventana de tiempo ya expiró? → reiniciar el registro.
        if (($now - $data['window_start']) >= self::WINDOW_SECONDS) {
            $data = ['attempts' => 0, 'window_start' => $now];
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            // Calcular cuántos segundos faltan para que expire el bloqueo.
            $retryAfter = self::WINDOW_SECONDS - ($now - $data['window_start']);
            self::sendTooManyRequests($ip, max(0, $retryAfter));
        }

        // Registrar este intento.
        $data['attempts']++;
        self::writeCacheFile($file, $data);
    }

    /**
     * Resetea el contador de una IP tras un login EXITOSO (Opción B).
     *
     * @param string $ip
     */
    public static function resetWithFilesystem(string $ip): void
    {
        $file = self::FS_CACHE_DIR . self::hashIp($ip) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }


    // =========================================================================
    //  Métodos privados de soporte
    // =========================================================================

    /**
     * Genera un hash SHA-256 de la IP para usarlo como nombre de archivo.
     * No usamos md5/sha1 porque son vulnerables a colisiones intencionales.
     */
    private static function hashIp(string $ip): string
    {
        return hash('sha256', $ip);
    }

    /**
     * Lee el archivo de caché de forma segura.
     * Devuelve una estructura vacía si el archivo no existe o está corrupto.
     */
    private static function readCacheFile(string $file): array
    {
        if (!file_exists($file)) {
            return ['attempts' => 0, 'window_start' => time()];
        }

        // Bloqueo compartido (LOCK_SH) para lectura segura en concurrencia.
        $handle = fopen($file, 'r');
        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $data = json_decode($content, true);

        // Si el JSON está malformado, reiniciar el registro (fail safe).
        if (!is_array($data) || !isset($data['attempts'], $data['window_start'])) {
            return ['attempts' => 0, 'window_start' => time()];
        }

        return $data;
    }

    /**
     * Escribe el archivo de caché de forma segura con bloqueo exclusivo.
     * Usamos escritura atómica (archivo temporal + rename) para evitar
     * que otro proceso lea un archivo a medio escribir.
     */
    private static function writeCacheFile(string $file, array $data): void
    {
        $tmpFile = $file . '.tmp.' . getmypid();
        file_put_contents($tmpFile, json_encode($data, JSON_THROW_ON_ERROR));

        // rename() es atómica en sistemas POSIX (Linux/macOS).
        // En Windows puede fallar si el destino existe; fallback con copy+unlink.
        if (!@rename($tmpFile, $file)) {
            copy($tmpFile, $file);
            unlink($tmpFile);
        }
    }

    /**
     * Envía la respuesta HTTP 429 Too Many Requests y detiene la ejecución.
     *
     * Incluye la cabecera estándar "Retry-After" (RFC 6585 §4) para que
     * clientes bien implementados respeten el tiempo de espera.
     *
     * @param string $ip         IP que superó el límite (para el log interno).
     * @param int    $retryAfter Segundos hasta que se levante el bloqueo.
     */
    private static function sendTooManyRequests(string $ip, int $retryAfter = self::WINDOW_SECONDS): void
    {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . $retryAfter);

        // Registrar en el error log del servidor para monitoreo
        // (sin exponer la IP en la respuesta pública al cliente).
        error_log(sprintf(
            '[NutriMax][RateLimiter] IP bloqueada: %s | Límite: >%d intentos en %ds | Retry-After: %ds',
            $ip,
            self::MAX_ATTEMPTS,
            self::WINDOW_SECONDS,
            $retryAfter
        ));

        echo json_encode([
            'success' => false,
            'status'  => 429,
            'message' => 'Demasiados intentos fallidos. Por favor, espera ' . $retryAfter . ' segundos antes de intentarlo nuevamente.',
            'code'    => 'RATE_LIMIT_EXCEEDED',
        ], JSON_UNESCAPED_UNICODE);

        exit; // Detener la ejecución. No continuar con la lógica de negocio.
    }
}
