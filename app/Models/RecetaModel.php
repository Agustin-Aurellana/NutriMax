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
                    COALESCE(ROUND(SUM(i.kcals * (ri.Cant_gr / 100)) / COALESCE(NULLIF(r.porciones, 0), 1)), 0) AS calories,
                    COALESCE(ROUND(SUM(i.prot * (ri.Cant_gr / 100)) / COALESCE(NULLIF(r.porciones, 0), 1), 1), 0) AS protein,
                    COALESCE(ROUND(SUM(i.carbo * (ri.Cant_gr / 100)) / COALESCE(NULLIF(r.porciones, 0), 1), 1), 0) AS carbs,
                    COALESCE(ROUND(SUM(i.gras * (ri.Cant_gr / 100)) / COALESCE(NULLIF(r.porciones, 0), 1), 1), 0) AS fat
                FROM recetas r
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
                LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
                WHERE (r.ID_USER IS NULL OR r.ID_USER = ?)
                  AND (r.dieta IS NULL OR r.dieta != '_manual')
                  AND (r.activo = 1 OR r.activo IS NULL)";

        $params = [$userId, $userId, $userId];
        $types  = 'sss';

        // Filtro opcional por nombre
        if (!empty($query)) {
            $sql     .= " AND r.name LIKE ?";
            $params[] = '%' . $query . '%';
            $types   .= 's';
        }

        // Filtro opcional por tipo de dieta (excluimos el valor interno '_manual' de las condiciones visibles)
        if (!empty($goal) && $goal !== 'all') {
            $sql     .= " AND r.dieta = ?";
            $params[] = $goal;
            $types   .= 's';
        }

        // Agrupar por ID de receta, ocultar globales sin ingredientes y ordenar
        $sql .= " GROUP BY r.ID_RECETA 
                  HAVING (r.ID_USER IS NOT NULL) OR (calories > 0 OR protein > 0 OR carbs > 0 OR fat > 0) 
                  ORDER BY r.ID_USER IS NOT NULL ASC, r.name ASC";

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
            $row['custom'] = (bool) $row['custom'];
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
     * Inactiva una receta del usuario (soft-delete).
     * La cláusula AND ID_USER = ? garantiza que solo el propietario
     * pueda inactivar su receta. Las recetas globales (ID_USER IS NULL)
     * son intocables desde la API de usuario.
     *
     * @param string $recetaId UUID de la receta a inactivar.
     * @param string $userId   UUID del usuario que solicita la inactivación.
     * @param bool   $force    Si es true, inactiva también los ingredientes custom usados en esta receta.
     * @return array ['success' => bool, 'message' => string, 'affected_ingredients' => array, 'require_force' => bool]
     */
    public function delete(string $recetaId, string $userId, bool $force = false): array
    {
        // 1. Comprobar si la receta usa ingredientes personalizados de este usuario
        $checkUsageSql = "
            SELECT i.ID, i.name 
            FROM ingredientes i
            JOIN recetas_ingredientes ri ON i.ID = ri.ID_Ingred
            WHERE ri.ID_RECETA = ? AND i.ID_USER = ? AND (i.activo = 1 OR i.activo IS NULL)
        ";
        $usageStmt = mysqli_prepare($this->db, $checkUsageSql);
        $affectedIngredients = [];
        if ($usageStmt) {
            mysqli_stmt_bind_param($usageStmt, "ss", $recetaId, $userId);
            mysqli_stmt_execute($usageStmt);
            $resUsage = mysqli_stmt_get_result($usageStmt);
            while ($ui = mysqli_fetch_assoc($resUsage)) {
                $affectedIngredients[] = $ui;
            }
            mysqli_stmt_close($usageStmt);
        }

        // Si hay ingredientes custom y no se forzó el borrado, requerir confirmación
        if (count($affectedIngredients) > 0 && !$force) {
            $ingNames = array_map(function($i) { return $i['name']; }, $affectedIngredients);
            $namesStr = implode(", ", $ingNames);
            return [
                'success' => false, 
                'message' => "Esta receta usa los siguientes ingredientes personalizados: $namesStr. Si la eliminas, también se eliminarán esos ingredientes. ¿Deseas continuar?",
                'require_force' => true,
                'affected_ingredients' => $affectedIngredients
            ];
        }

        mysqli_begin_transaction($this->db);

        // Si forzamos, inactivamos también los ingredientes custom asociados
        if (count($affectedIngredients) > 0 && $force) {
            foreach ($affectedIngredients as $ai) {
                $ingId = $ai['ID'];
                $stmtIng = mysqli_prepare($this->db, "UPDATE ingredientes SET activo = 0 WHERE ID = ? AND ID_USER = ?");
                if ($stmtIng) {
                    mysqli_stmt_bind_param($stmtIng, "is", $ingId, $userId);
                    mysqli_stmt_execute($stmtIng);
                    mysqli_stmt_close($stmtIng);
                }
            }
        }

        // 2. Inactivar la receta en la tabla principal (restringido al creador)
        $stmt = mysqli_prepare($this->db,
            "UPDATE recetas SET activo = 0 WHERE ID_RECETA = ? AND ID_USER = ?"
        );

        if (!$stmt) {
            mysqli_rollback($this->db);
            return ['success' => false, 'message' => 'Error al preparar la consulta de inactivación'];
        }

        mysqli_stmt_bind_param($stmt, 'ss', $recetaId, $userId);
        mysqli_stmt_execute($stmt);

        // affected_rows = 0 significa que la receta no existe, no le pertenece, o ya estaba inactiva
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            mysqli_commit($this->db);
            return ['success' => true, 'message' => 'Receta inactivada correctamente'];
        }

        mysqli_rollback($this->db);
        return ['success' => false, 'message' => 'Receta no encontrada o sin permisos para eliminar'];
    }
}
