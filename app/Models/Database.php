<?php

/**
 * Database.php — Wrapper singleton de mysqli
 *
 * Encapsula la conexión a la base de datos en un único punto de entrada.
 * Todos los Modelos obtendrán la conexión a través de esta clase para evitar
 * múltiples instancias de conexión y acoplamientos directos a config/conexion.php.
 *
 * Patrón: Singleton — garantiza que solo exista UNA conexión activa en todo el ciclo
 * de vida de cada petición HTTP.
 */
class Database
{
    /** @var mysqli|null La única instancia de la conexión */
    private static ?mysqli $connection = null;

    /**
     * Retorna la conexión mysqli activa, creándola si aún no existe.
     *
     * @return mysqli Objeto de conexión listo para usar.
     */
    public static function getConnection(): mysqli
    {
        // Si la conexión aún no fue creada, la inicializamos
        if (self::$connection === null) {
            self::$connection = mysqli_connect(
                $_ENV['DB_HOST']     ?? 'localhost',
                $_ENV['DB_USER']     ?? 'root',
                $_ENV['DB_PASS']     ?? '',
                $_ENV['DB_NAME']     ?? 'nutrimax'
            );

            // Detener la ejecución si la conexión falla
            if (!self::$connection) {
                // En producción, no exponer el mensaje de error al cliente;
                // aquí lo mantenemos visible para el entorno de desarrollo.
                http_response_code(500);
                echo json_encode([
                    "status"  => "error",
                    "message" => "Error de conexión a la base de datos: " . mysqli_connect_error()
                ]);
                exit;
            }

            // Establecer el charset para evitar problemas con caracteres especiales
            mysqli_set_charset(self::$connection, 'utf8mb4');
        }

        return self::$connection;
    }

    /**
     * Cierra la conexión activa y resetea la instancia singleton.
     * Útil para liberar recursos al final de scripts de larga ejecución.
     */
    public static function close(): void
    {
        if (self::$connection !== null) {
            mysqli_close(self::$connection);
            self::$connection = null;
        }
    }

    /**
     * Prevenimos instanciación directa y clonación.
     * Esta clase sólo se usa a través de sus métodos estáticos.
     */
    private function __construct() {}
    private function __clone() {}
}
