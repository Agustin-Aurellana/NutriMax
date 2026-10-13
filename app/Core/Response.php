<?php

/**
 * Response.php — Helper estático para respuestas JSON estandarizadas
 *
 * Todos los controladores de la API deben usar esta clase para garantizar
 * que cada respuesta HTTP tenga SIEMPRE la misma estructura:
 *
 *   {
 *     "status":  "success" | "error",
 *     "data":    { ... } | null,
 *     "message": "..." | null
 *   }
 *
 * Esto le permite al frontend asumir una interfaz predecible sin importar
 * qué endpoint consultó.
 */
class Response
{
    /**
     * Retorna la lista blanca de orígenes (Origin) autorizados para interactuar con la API.
     * Permite configuración dinámica en producción mediante la variable de entorno ALLOWED_ORIGINS
     * (separada por comas) y ofrece un fallback seguro para entornos de desarrollo local.
     *
     * @return array<string> Lista de URLs de origen permitidas.
     */
    public static function getAllowedOrigins(): array
    {
        $envOrigins = $_ENV['ALLOWED_ORIGINS'] ?? getenv('ALLOWED_ORIGINS');
        if (!empty($envOrigins)) {
            return array_map('trim', explode(',', $envOrigins));
        }

        // Dominios autorizados por defecto para desarrollo local
        return [
            'http://localhost',
            'http://127.0.0.1',
            'http://localhost:80',
            'http://localhost:8080',
            'http://localhost:3000',
            'http://localhost:5173',
            'http://127.0.0.1:5500', // VS Code Live Server
            'http://localhost:5500',
        ];
    }

    /**
     * Evalúa la cabecera HTTP Origin y aplica las políticas de CORS.
     *
     * Reglas aplicadas:
     * 1. Si no existe la cabecera Origin (petición del mismo origen, navegación directa o cliente CLI/cURL),
     *    se permite continuar normalmente para no romper el funcionamiento estándar.
     * 2. Si la cabecera Origin existe pero NO pertenece a la lista blanca, la petición es RECHAZADA
     *    inmediatamente con un código HTTP 403 Forbidden.
     * 3. Si la cabecera Origin es válida, se emiten las cabeceras CORS específicas para ese origen
     *    (evitando comodines '*') y se manejan las peticiones preflight (OPTIONS) respondiendo 204.
     */
    public static function handleCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        // Si la petición no tiene cabecera Origin, es del mismo origen o petición interna
        if ($origin === null) {
            // Manejar preflight OPTIONS huérfano si existiera
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(204);
                exit;
            }
            return;
        }

        $originClean    = rtrim($origin, '/');
        $allowedOrigins = self::getAllowedOrigins();

        // Verificar si el origen recibido coincide con algún dominio autorizado en la lista blanca
        $isAuthorized = false;
        foreach ($allowedOrigins as $allowed) {
            if ($originClean === rtrim($allowed, '/')) {
                $isAuthorized = true;
                break;
            }
        }

        // Rechazo estricto si el origen no está autorizado
        if (!$isAuthorized) {
            self::error('Origen no autorizado por la política CORS.', 403);
        }

        // Inyectar cabeceras CORS para el origen validado
        self::setCorsHeaders($origin);

        // Si es una petición preflight (OPTIONS), finalizamos aquí con 204 No Content
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Aplica las cabeceras CORS correspondientes.
     * En lugar del comodín '*', refleja el origen específico siempre que esté en la lista blanca
     * e incluye la cabecera 'Vary: Origin' para que los proxies y navegadores gestionen el caché correctamente.
     *
     * @param string|null $origin Origen a configurar (opcional; si es null, se lee de $_SERVER['HTTP_ORIGIN']).
     */
    public static function setCorsHeaders(?string $origin = null): void
    {
        $origin = $origin ?? ($_SERVER['HTTP_ORIGIN'] ?? null);

        if ($origin !== null) {
            $originClean    = rtrim($origin, '/');
            $allowedOrigins = self::getAllowedOrigins();

            $isAuthorized = false;
            foreach ($allowedOrigins as $allowed) {
                if ($originClean === rtrim($allowed, '/')) {
                    $isAuthorized = true;
                    break;
                }
            }

            if ($isAuthorized) {
                header("Access-Control-Allow-Origin: {$origin}");
                header('Access-Control-Allow-Credentials: true');
                header('Vary: Origin');
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-ID');
    }

    /**
     * Envía una respuesta JSON de éxito y termina la ejecución.
     *
     * @param mixed $data    Payload de la respuesta (array, objeto, null).
     * @param int   $code    Código HTTP (200 por defecto).
     * @param string|null $message Mensaje descriptivo opcional.
     */
    public static function success(mixed $data = null, int $code = 200, ?string $message = null): void
    {
        self::send(['status' => 'success', 'data' => $data, 'message' => $message], $code);
    }

    /**
     * Envía una respuesta JSON de error y termina la ejecución.
     *
     * @param string $message Descripción del error.
     * @param int    $code    Código HTTP (400 por defecto).
     * @param mixed  $data    Datos adicionales de contexto (opcional).
     */
    public static function error(string $message, int $code = 400, mixed $data = null): void
    {
        self::send(['status' => 'error', 'data' => $data, 'message' => $message], $code);
    }

    /**
     * Emite el código HTTP, los headers y el JSON codificado, luego sale.
     *
     * @param array $body Cuerpo de la respuesta.
     * @param int   $code Código de estado HTTP.
     */
    private static function send(array $body, int $code): void
    {
        // Configurar headers antes de cualquier output
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        self::setCorsHeaders();

        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit; // Garantizamos que nada más se imprima después de la respuesta
    }

    /**
     * Responde un 204 No Content (para DELETE o acciones sin payload).
     * También termina la ejecución.
     */
    public static function noContent(): void
    {
        http_response_code(204);
        self::setCorsHeaders();
        exit;
    }

    /**
     * Compatibilidad hacia atrás: delega en handleCors().
     */
    public static function handlePreflight(): void
    {
        self::handleCors();
    }
}
