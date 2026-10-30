<?php

/**
 * conexion.php — Conexión PDO a PostgreSQL (Aiven Cloud)
 *
 * Lee las credenciales desde variables de entorno (.env cargado por el
 * bootstrap de la aplicación) y devuelve un objeto PDO configurado con:
 *   - Driver pgsql
 *   - SSL requerido (sslmode=require), obligatorio en Aiven
 *   - Modo de errores EXCEPTION para capturar fallos con try/catch
 *   - FETCH_ASSOC por defecto, compatible con el frontend existente
 *
 * NOTA: Este archivo es un punto de entrada legado mantenido por
 * retrocompatibilidad. Los Modelos deben usar Database::getConnection().
 */

// Construimos el DSN (Data Source Name) para PostgreSQL con los valores del .env.
// sslmode=require es obligatorio para conectarse a Aiven desde fuera de su red.
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $_ENV['DB_HOST']    ?? 'localhost',
    $_ENV['DB_PORT']    ?? '5432',
    $_ENV['DB_NAME']    ?? 'defaultdb',
    $_ENV['DB_SSLMODE'] ?? 'require'
);

try {
    // Creamos la instancia PDO con las opciones mínimas de seguridad y rendimiento
    $conexion = new PDO(
        $dsn,
        $_ENV['DB_USER'] ?? 'avnadmin',
        $_ENV['DB_PASS'] ?? '',
        [
            // Lanzar excepciones ante cualquier error SQL (nunca devolver false silencioso)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Devolver filas como arrays asociativos por defecto
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Deshabilitar emulación de prepared statements para mayor seguridad y rendimiento
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // En producción nunca exponer el mensaje de error al cliente.
    // Aquí lo mantenemos visible para el entorno de desarrollo.
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error de conexión a la base de datos: ' . $e->getMessage(),
    ]);
    exit;
}
