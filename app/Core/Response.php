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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
        header('Access-Control-Allow-Origin: *');
        exit;
    }

    /**
     * Maneja las peticiones OPTIONS del preflight de CORS.
     * Los browsers modernos envían esta petición antes de POST/PUT/DELETE.
     * Si no se responde correctamente, la petición real será bloqueada.
     */
    public static function handlePreflight(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }
    }
}
