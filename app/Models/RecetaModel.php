<?php

/**
 * RecetaModel.php — Modelo de la entidad 'recetas'
 *
 * Centraliza TODA la interacción SQL relacionada con recetas.
 * Opera sobre dos tablas:
 *   - recetas              → receta base (nombre, emoji, descripción, instrucciones)
 *   - recetas_ingredientes → ingredientes de cada receta
 *
 * Diseño adoptado:
 *   - Recetas con ID_USER = NULL  → globales/seed, visibles para todos.
 *   - Recetas con ID_USER = <uuid> → personalizadas del usuario, solo visibles para él.
 *
 * No existe tabla de "favoritos" separada: las recetas del usuario
 * se almacenan directamente en la tabla `recetas` con su ID_USER.
 */
require_once __DIR__ . '/Database.php';

class RecetaModel
{
    /** @var mysqli Conexión compartida via el singleton Database */
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // =========================================================
    // LECTURA (READ)
    // =========================================================

    /**
     * Devuelve TODAS las recetas visibles para un usuario:
     *   - Recetas globales (ID_USER IS NULL)
     *   - Recetas propias del usuario (ID_USER = $userId)
     *
     * Opcionalmente filtra por texto libre en el nombre y/o tipo de dieta.
     *
     * @param string      $userId  UUID del usuario autenticado.
     * @param string|null $query   Texto libre de búsqueda (nombre).
     * @param string|null $goal    Tipo de dieta (ej: 'Keto', 'Vegana').
     * @return array Lista de recetas como arrays asociativos.
     */
    public function getAll(string $userId, ?string $query = null, ?string $goal = null): array
    {
        $sql = "SELECT
                    r.ID_RECETA,
                    r.ID_USER,
                    r.name,
                    r.dieta,
                    r.descrip,
                    r.instr,
                    r.porciones,
                    r.emoji,
                    -- Flag: indica si la receta fue creada por este usuario
                    CASE WHEN r.ID_USER = ? THEN 1 ELSE 0 END AS is_custom
                FROM recetas r
                WHERE (r.ID_USER IS NULL OR r.ID_USER = ?)";

        $params = [$userId, $userId];
        $types  = 'ss';

        // Filtro opcional por nombre
        if (!empty($query)) {
            $sql     .= " AND r.name LIKE ?";
            $params[] = '%' . $query . '%';
            $types   .= 's';
        }

        // Filtro opcional por tipo de dieta
        if (!empty($goal) && $goal !== 'all') {
            $sql     .= " AND r.dieta = ?";
            $params[] = $goal;
            $types   .= 's';
        }

        // Las recetas globales primero, luego las del usuario
        $sql .= " ORDER BY r.ID_USER IS NOT NULL ASC, r.name ASC";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $result  = mysqli_stmt_get_result($stmt);
        $recipes = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $row['is_custom'] = (bool) $row['is_custom'];
            $recipes[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $recipes;
    }

    // =========================================================
    // CREACIÓN (CREATE)
    // =========================================================

    /**
     * Inserta una nueva receta personalizada del usuario en `recetas`.
     * La receta queda vinculada al usuario mediante su ID_USER.
     *
     * @param array  $data   Campos de la receta: name, emoji, descrip, instr, porciones, dieta.
     * @param string $userId UUID del usuario propietario.
     * @return array ['success' => bool, 'id' => string|null, 'message' => string]
     */
    public function create(array $data, string $userId): array
    {
        if (empty($data['name'])) {
            return ['success' => false, 'id' => null, 'message' => 'El nombre de la receta es obligatorio'];
        }

        // Generamos un UUID v4 para mantener consistencia con el esquema existente
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $sql = "INSERT INTO recetas
                    (ID_RECETA, ID_USER, name, emoji, descrip, instr, porciones, dieta)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return ['success' => false, 'id' => null, 'message' => 'Error al preparar la consulta'];
        }

        $emoji     = $data['emoji']     ?? '🍽️';
        $descrip   = $data['descrip']   ?? null;
        $instr     = $data['instr']     ?? null;
        $porciones = isset($data['porciones']) ? (float) $data['porciones'] : 1.0;
        $dieta     = $data['dieta']     ?? null;

        mysqli_stmt_bind_param($stmt, 'ssssssds',
            $uuid, $userId, $data['name'], $emoji, $descrip, $instr, $porciones, $dieta
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'id' => $uuid, 'message' => 'Receta creada correctamente'];
        }

        $error = mysqli_error($this->db);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'id' => null, 'message' => 'Error al crear la receta: ' . $error];
    }

    // =========================================================
    // ELIMINACIÓN (DELETE)
    // =========================================================

    /**
     * Elimina una receta del usuario.
     * La cláusula AND ID_USER = ? garantiza que solo el propietario
     * pueda eliminar su receta. Las recetas globales (ID_USER IS NULL)
     * son intocables desde la API de usuario.
     *
     * @param string $recetaId UUID de la receta a eliminar.
     * @param string $userId   UUID del usuario que solicita la eliminación.
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(string $recetaId, string $userId): array
    {
        $stmt = mysqli_prepare($this->db,
            "DELETE FROM recetas WHERE ID_RECETA = ? AND ID_USER = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta'];
        }

        mysqli_stmt_bind_param($stmt, 'ss', $recetaId, $userId);
        mysqli_stmt_execute($stmt);

        // affected_rows = 0 significa que la receta no existe o no le pertenece
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Receta eliminada correctamente'];
        }

        return ['success' => false, 'message' => 'Receta no encontrada o sin permisos para eliminar'];
    }
}
