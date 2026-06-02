<?php

/**
 * RegistroDiarioModel.php — Modelo de la entidad 'registro_diario'
 *
 * Centraliza TODAS las operaciones SQL relacionadas con los registros diarios.
 * Por el momento, gestiona la persistencia de la cantidad de vasos de agua
 * consumidos por día para cada usuario.
 *
 * Cumple con el patrón arquitectónico del equipo: las consultas SQL y la preparación
 * de sentencias se concentran en el modelo, aislando al controlador de la capa de datos.
 */
require_once __DIR__ . '/Database.php';

class RegistroDiarioModel
{
    /** @var mysqli Conexión compartida obtenida a través del singleton Database */
    private mysqli $db;

    public function __construct()
    {
        // Obtenemos la conexión única reutilizando la instancia del singleton
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene el registro diario de un usuario para una fecha específica.
     *
     * @param string $userId UUID del usuario autenticado.
     * @param string $fecha Fecha en formato 'YYYY-MM-DD'.
     * @return array|null El registro diario como array asociativo o null si no existe.
     */
    public function get(string $userId, string $fecha): ?array
    {
        // Consulta segura utilizando sentencias preparadas de mysqli
        $sql = "SELECT * FROM registro_diario WHERE ID_USER = ? AND fecha = ?";
        $stmt = mysqli_prepare($this->db, $sql);

        if (!$stmt) {
            return null;
        }

        // Vinculamos los parámetros para evitar inyección SQL (s = string)
        mysqli_stmt_bind_param($stmt, 'ss', $userId, $fecha);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    /**
     * Guarda la cantidad de vasos de agua para un usuario en una fecha específica.
     * Realiza un UPDATE si el registro diario ya existe para ese día, o un INSERT
     * en caso contrario, asignando un UUID único.
     *
     * @param string $userId UUID del usuario.
     * @param string $fecha Fecha en formato 'YYYY-MM-DD'.
     * @param int $cantVasos Cantidad de vasos a registrar.
     * @return array Resultado de la operación: ['success' => bool, 'message' => string]
     */
    public function saveWater(string $userId, string $fecha, int $cantVasos): array
    {
        // Validamos la cantidad de vasos para evitar ingresos inválidos o negativos
        $cantVasos = max(0, $cantVasos);

        // Verificamos si ya existe una fila creada para este usuario y fecha
        $existing = $this->get($userId, $fecha);

        if ($existing) {
            // Actualizamos la fila existente
            $sql = "UPDATE registro_diario SET cant_vasos = ? WHERE ID_REG = ?";
            $stmt = mysqli_prepare($this->db, $sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Error al preparar la actualización de agua'];
            }

            // 'i' para entero, 's' para string (UUID)
            mysqli_stmt_bind_param($stmt, 'is', $cantVasos, $existing['ID_REG']);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                return ['success' => true, 'message' => 'Consumo de agua actualizado correctamente'];
            }
            return ['success' => false, 'message' => 'Error al ejecutar la actualización en la BD'];
        } else {
            // Creamos un nuevo registro diario.
            // Generamos un UUID v4 en PHP de forma robusta para mantener la consistencia con el diseño del resto de las tablas.
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $sql = "INSERT INTO registro_diario (ID_REG, ID_USER, fecha, cant_vasos) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->db, $sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Error al preparar la creación del registro'];
            }

            // 's' para strings (UUID, fecha), 'i' para entero
            mysqli_stmt_bind_param($stmt, 'sssi', $uuid, $userId, $fecha, $cantVasos);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                return ['success' => true, 'message' => 'Registro creado y agua guardada correctamente'];
            }
            return ['success' => false, 'message' => 'Error al crear el registro diario en la BD'];
        }
    }
}
