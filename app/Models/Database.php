<?php

/**
 * Database.php — Wrapper singleton PDO para PostgreSQL
 *
 * Encapsula la conexión a la base de datos en un único punto de entrada.
 * Todos los Modelos obtienen la conexión a través de esta clase para evitar
 * múltiples instancias de conexión y acoplamientos directos a config/conexion.php.
 *
 * Patrón: Singleton — garantiza que solo exista UNA conexión activa en todo el
 * ciclo de vida de cada petición HTTP.
 *
 * Cambio de mysqli → PDO/PostgreSQL:
 *   - Driver: pgsql (Aiven Cloud con SSL obligatorio)
 *   - API: PDO con prepared statements nativos (?  o  :named)
 *   - Sin bind_param() por tipos: PDO infiere tipos automáticamente
 */
class Database
{
    /** @var PDO|null La única instancia de la conexión */
    private static ?PDO $connection = null;

    /**
     * Retorna la conexión PDO activa, creándola si aún no existe.
     *
     * @return PDO Objeto de conexión listo para usar.
     * @throws RuntimeException Si la conexión falla (detiene la ejecución en producción).
     */
    public static function getConnection(): PDO
    {
        // Si la conexión aún no fue creada, la inicializamos
        if (self::$connection === null) {
            // Construimos el DSN con los valores del .env cargado en el bootstrap
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
                $_ENV['DB_HOST']    ?? 'localhost',
                $_ENV['DB_PORT']    ?? '5432',
                $_ENV['DB_NAME']    ?? 'defaultdb',
                $_ENV['DB_SSLMODE'] ?? 'require'
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $_ENV['DB_USER'] ?? 'avnadmin',
                    $_ENV['DB_PASS'] ?? '',
                    [
                        // Lanzar excepciones ante cualquier error SQL
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        // Devolver filas como arrays asociativos (compatible con el frontend)
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        // Deshabilitar emulación para usar prepared statements nativos de PostgreSQL
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                // En producción, no exponer el mensaje de error al cliente;
                // aquí lo mantenemos visible para el entorno de desarrollo.
                http_response_code(500);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Error de conexión a la base de datos: ' . $e->getMessage(),
                ]);
                exit;
            }
        }

        return self::$connection;
    }

    /**
     * Cierra la conexión activa y resetea la instancia singleton.
     * Útil para liberar recursos al final de scripts de larga ejecución.
     */
    public static function close(): void
    {
        // En PDO, cerrar la conexión se hace anulando la referencia
        self::$connection = null;
    }

    /**
     * Prevenimos instanciación directa y clonación.
     * Esta clase solo se usa a través de sus métodos estáticos.
     */
    private function __construct() {}
    private function __clone() {}
}
