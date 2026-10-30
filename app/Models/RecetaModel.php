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
 *
 * Migración mysqli → PDO/PostgreSQL:
 *   - $this->db es ahora PDO; prepare/execute con arrays de parámetros
 *   - mysqli_begin_transaction/commit/rollback → $this->db->beginTransaction/commit/rollBack
 *   - mysqli_stmt_affected_rows() → $stmt->rowCount()
 *   - HAVING con alias de columnas calculadas: en PostgreSQL hay que repetir la expresión
 *     o usar una subconsulta. Usamos subconsulta para compatibilidad estricta.
 *   - ORDER BY r.ID_USER IS NOT NULL (MySQL) → ORDER BY (r.ID_USER IS NOT NULL) (PostgreSQL, igual)
 */
require_once __DIR__ . '/Database.php';

class RecetaModel
{
    /** @var PDO Conexión compartida via el singleton Database */
    private PDO $db;

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
        // En PostgreSQL el HAVING sobre alias calculados requiere una subconsulta.
        // Envolvemos el SELECT original y filtramos en el nivel externo.
        $innerSql = "SELECT
                    r.ID_RECETA AS id,
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
                    CASE WHEN r.ID_USER = ? THEN 1 ELSE 0 END AS custom,
                    COALESCE(ROUND(SUM(i.kcals * (ri.Cant_gr / 100.0)) / NULLIF(r.porciones, 0)), 0) AS calories,
                    COALESCE(ROUND(SUM(i.prot  * (ri.Cant_gr / 100.0)) / NULLIF(r.porciones, 0), 1), 0) AS protein,
                    COALESCE(ROUND(SUM(i.carbo * (ri.Cant_gr / 100.0)) / NULLIF(r.porciones, 0), 1), 0) AS carbs,
                    COALESCE(ROUND(SUM(i.gras  * (ri.Cant_gr / 100.0)) / NULLIF(r.porciones, 0), 1), 0) AS fat
                FROM recetas r
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
                LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
                WHERE (r.ID_USER IS NULL OR r.ID_USER = ?)
                  AND (r.dieta IS NULL OR r.dieta <> '_manual')";

        $params = [$userId, $userId, $userId];

        // Filtro opcional por nombre (ILIKE = case-insensitive en PostgreSQL)
        if (!empty($query)) {
            $innerSql .= " AND r.name ILIKE ?";
            $params[]  = '%' . $query . '%';
        }

        // Filtro opcional por tipo de dieta
        if (!empty($goal) && $goal !== 'all') {
            $innerSql .= " AND r.dieta = ?";
            $params[]  = $goal;
        }

        $innerSql .= " GROUP BY r.ID_RECETA, r.ID_USER, r.name, r.dieta, r.descrip, r.instr, r.porciones, r.emoji";

        // Envolvemos en subconsulta para poder filtrar con HAVING sobre los alias calculados
        $sql = "SELECT * FROM ($innerSql) AS sub
                WHERE (sub.ID_USER IS NOT NULL)
                   OR (sub.calories > 0 OR sub.protein > 0 OR sub.carbs > 0 OR sub.fat > 0)
                ORDER BY (sub.ID_USER IS NOT NULL) ASC, sub.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $recipes = [];
        while ($row = $stmt->fetch()) {
            $row['is_custom'] = (bool) $row['is_custom'];
            // Asignamos claves adicionales para total compatibilidad frontend
            $row['id']       = $row['ID_RECETA'];
            $row['custom']   = $row['is_custom'];
            $row['calories'] = (float) $row['calories'];
            $row['protein']  = (float) $row['protein'];
            $row['carbs']    = (float) $row['carbs'];
            $row['fat']      = (float) $row['fat'];
            $recipes[] = $row;
        }

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

        $emoji     = $data['emoji']     ?? '🍽️';
        $descrip   = $data['descrip']   ?? null;
        $instr     = $data['instr']     ?? null;
        $porciones = isset($data['porciones']) ? (float) $data['porciones'] : 1.0;
        $dieta     = $data['dieta']     ?? null;

        // Usamos una transacción para asegurar consistencia al insertar en la cabecera y en la tabla de relaciones
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO recetas (ID_RECETA, ID_USER, name, emoji, descrip, instr, porciones, dieta)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$uuid, $userId, $data['name'], $emoji, $descrip, $instr, $porciones, $dieta]);

            // Si se proveen ingredientes estructurados, los guardamos en recetas_ingredientes
            if (!empty($data['ingredients']) && is_array($data['ingredients'])) {
                $stmtIng = $this->db->prepare(
                    "INSERT INTO recetas_ingredientes (ID_RECETA, ID_Ingred, Cant_gr) VALUES (?, ?, ?)"
                );
                foreach ($data['ingredients'] as $ing) {
                    $stmtIng->execute([(string)$uuid, (int)$ing['id'], (int)$ing['grams']]);
                }
            }

            // Todo correcto: guardamos los cambios
            $this->db->commit();
            return ['success' => true, 'id' => $uuid, 'message' => 'Receta creada correctamente'];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'id' => null, 'message' => 'Error al crear la receta: ' . $e->getMessage()];
        }
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
        try {
            $this->db->beginTransaction();

            // 1. Eliminamos las asociaciones de ingredientes asociadas a esta receta
            $stmtIng = $this->db->prepare("DELETE FROM recetas_ingredientes WHERE ID_RECETA = ?");
            $stmtIng->execute([$recetaId]);

            // 2. Eliminamos la receta de la tabla principal (restringido al creador)
            $stmt = $this->db->prepare(
                "DELETE FROM recetas WHERE ID_RECETA = ? AND ID_USER = ?"
            );
            $stmt->execute([$recetaId, $userId]);

            // rowCount() = 0 significa que la receta no existe o no le pertenece
            $affected = $stmt->rowCount();

            if ($affected > 0) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Receta eliminada correctamente'];
            }

            $this->db->rollBack();
            return ['success' => false, 'message' => 'Receta no encontrada o sin permisos para eliminar'];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error al eliminar la receta: ' . $e->getMessage()];
        }
    }
}
