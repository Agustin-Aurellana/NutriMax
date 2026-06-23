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
        // Usamos LEFT JOIN con ingredientes para calcular la suma de macros en base a Cant_gr
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
                    CASE WHEN r.ID_USER = ? THEN 1 ELSE 0 END AS is_custom,
                    COALESCE(SUM(i.kcals * ri.Cant_gr / 100), 0) AS calories,
                    COALESCE(SUM(i.prot * ri.Cant_gr / 100), 0) AS protein,
                    COALESCE(SUM(i.carbo * ri.Cant_gr / 100), 0) AS carbs,
                    COALESCE(SUM(i.gras * ri.Cant_gr / 100), 0) AS fat
                FROM recetas r
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
                LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
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

        // Agrupamos por ID_RECETA para poder calcular las sumas agregadas por cada receta
        $sql .= " GROUP BY r.ID_RECETA";

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
            // Asignamos claves adicionales para total compatibilidad frontend (id, custom y macros tipados)
            $row['id']        = $row['ID_RECETA'];
            $row['custom']    = $row['is_custom'];
            $row['calories']  = (float)$row['calories'];
            $row['protein']   = (float)$row['protein'];
            $row['carbs']     = (float)$row['carbs'];
            $row['fat']       = (float)$row['fat'];
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

        // Usamos una transacción para asegurar consistencia al insertar en la cabecera y en la tabla de relaciones
        mysqli_begin_transaction($this->db);

        $sql = "INSERT INTO recetas
                    (ID_RECETA, ID_USER, name, emoji, descrip, instr, porciones, dieta)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            mysqli_rollback($this->db);
            return ['success' => false, 'id' => null, 'message' => 'Error al preparar la consulta de la receta'];
        }

        $emoji     = $data['emoji']     ?? '🍽️';
        $descrip   = $data['descrip']   ?? null;
        $instr     = $data['instr']     ?? null;
        $porciones = isset($data['porciones']) ? (float) $data['porciones'] : 1.0;
        $dieta     = $data['dieta']     ?? null;

        mysqli_stmt_bind_param($stmt, 'ssssssds',
            $uuid, $userId, $data['name'], $emoji, $descrip, $instr, $porciones, $dieta
        );

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_error($this->db);
            mysqli_stmt_close($stmt);
            mysqli_rollback($this->db);
            return ['success' => false, 'id' => null, 'message' => 'Error al crear la receta: ' . $error];
        }
        mysqli_stmt_close($stmt);

        // Si se proveen ingredientes estructurados, los guardamos en recetas_ingredientes
        if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
            foreach ($data['ingredients'] as $ing) {
                $ingId = (int)$ing['id'];
                $grams = (int)$ing['grams'];

                $stmtIng = mysqli_prepare($this->db, 
                    "INSERT INTO recetas_ingredientes (ID_RECETA, ID_Ingred, Cant_gr) VALUES (?, ?, ?)"
                );

                if (!$stmtIng) {
                    $error = mysqli_error($this->db);
                    mysqli_rollback($this->db);
                    return ['success' => false, 'id' => null, 'message' => 'Error al preparar inserción de ingredientes: ' . $error];
                }

                mysqli_stmt_bind_param($stmtIng, 'sii', $uuid, $ingId, $grams);
                
                if (!mysqli_stmt_execute($stmtIng)) {
                    $error = mysqli_error($this->db);
                    mysqli_stmt_close($stmtIng);
                    mysqli_rollback($this->db);
                    return ['success' => false, 'id' => null, 'message' => 'Error al guardar los ingredientes de la receta: ' . $error];
                }
                mysqli_stmt_close($stmtIng);
            }
        }

        // Todo correcto: guardamos los cambios
        mysqli_commit($this->db);
        return ['success' => true, 'id' => $uuid, 'message' => 'Receta creada correctamente'];
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
        // Iniciamos transacción para asegurar eliminación atómica
        mysqli_begin_transaction($this->db);

        // 1. Eliminamos las asociaciones de ingredientes asociadas a esta receta
        $stmtIng = mysqli_prepare($this->db, "DELETE FROM recetas_ingredientes WHERE ID_RECETA = ?");
        if ($stmtIng) {
            mysqli_stmt_bind_param($stmtIng, 's', $recetaId);
            if (!mysqli_stmt_execute($stmtIng)) {
                mysqli_rollback($this->db);
                mysqli_stmt_close($stmtIng);
                return ['success' => false, 'message' => 'Error al limpiar los ingredientes de la receta'];
            }
            mysqli_stmt_close($stmtIng);
        }

        // 2. Eliminamos la receta de la tabla principal (restringido al creador)
        $stmt = mysqli_prepare($this->db,
            "DELETE FROM recetas WHERE ID_RECETA = ? AND ID_USER = ?"
        );

        if (!$stmt) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Error al preparar la consulta de eliminación'];
        }

        mysqli_stmt_bind_param($stmt, 'ss', $recetaId, $userId);
        mysqli_stmt_execute($stmt);

        // affected_rows = 0 significa que la receta no existe o no le pertenece
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            mysqli_commit($this->db);
            return ['success' => true, 'message' => 'Receta eliminada correctamente'];
        }

        mysqli_rollback($this->db);
        return ['success' => false, 'message' => 'Receta no encontrada o sin permisos para eliminar'];
    }
}
