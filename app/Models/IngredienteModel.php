<?php

/**
 * IngredienteModel.php — Modelo de la entidad 'ingredientes'
 *
 * Centraliza TODA la interacción SQL relacionada con ingredientes/alimentos.
 * Reemplaza las queries inline de agregar-ing.php y eliminar-ing.php.
 *
 * Migración mysqli → PDO/PostgreSQL:
 *   - $this->db es ahora PDO; prepare() devuelve PDOStatement
 *   - execute() recibe array de parámetros (sin bind_param ni tipos)
 *   - COLLATE utf8mb4_general_ci (MySQL) → ILIKE o LOWER() en PostgreSQL
 *   - mysqli_insert_id() → lastInsertId() de PDO (requiere RETURNING o sequence)
 *     En PostgreSQL usamos "INSERT ... RETURNING ID" para obtener el ID generado
 *   - Tipos explícitos (i, s, d) eliminados; PDO infiere automáticamente
 */
require_once __DIR__ . '/Database.php';

class IngredienteModel
{
    /** @var PDO Conexión compartida via el singleton Database */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // =========================================================
    // CREACIÓN (CREATE)
    // =========================================================

    /**
     * Inserta un nuevo ingrediente en la base de datos.
     * Usa prepared statements para evitar inyecciones SQL.
     *
     * @param array $data Datos del ingrediente: name, kcals, prot, carbo, gras, ID_USER (opcional).
     * @return array ['success' => bool, 'id' => int|null, 'message' => string]
     */
    public function create(array $data): array
    {
        $name  = trim($data['name']);

        // Clampeamos a los límites máximos del esquema para evitar truncamiento silencioso.
        // El controlador ya rechazó valores fuera de rango con HTTP 422; esto es defensa en profundidad.
        $kcals = max(-9999.0, min(9999.0,  (float)($data['kcals'] ?? 0)));
        $prot  = max(0.0,     min(1000.0,  (float)($data['prot']  ?? 0)));
        $carbo = max(0.0,     min(1000.0,  (float)($data['carbo'] ?? 0)));
        $gras  = max(0.0,     min(1000.0,  (float)($data['gras']  ?? 0)));

        // El ingrediente puede ser público (null) o pertenecer a un usuario específico
        $id_user = isset($data['ID_USER']) ? $data['ID_USER'] : null;

        // ✅ Deduplicación profesional y estricta:
        // Verificamos si ya existe un ingrediente con el mismo nombre (insensible a mayúsculas
        // y espacios marginales). En PostgreSQL usamos ILIKE en lugar de COLLATE utf8mb4.
        $checkStmt = $this->db->prepare(
            "SELECT ID, name FROM ingredientes
             WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
               AND (ID_USER IS NULL OR ID_USER = ?)
             LIMIT 1"
        );
        $searchUserId = $id_user ?? '';
        $checkStmt->execute([$name, $searchUserId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Ya existe en la base de datos: evitamos duplicación y notificamos con claridad
            return [
                'success'   => false,
                'id'        => (int) $existing['ID'],
                'message'   => "El ingrediente ya existe en la base de datos como '{$existing['name']}'.",
                'duplicate' => true,
            ];
        }

        try {
            // En PostgreSQL usamos RETURNING ID para obtener el ID generado por la secuencia/serial
            $stmt = $this->db->prepare(
                "INSERT INTO ingredientes (name, kcals, prot, carbo, gras, ID_USER)
                 VALUES (?, ?, ?, ?, ?, ?)
                 RETURNING ID"
            );
            $stmt->execute([$name, $kcals, $prot, $carbo, $gras, $id_user]);
            $row = $stmt->fetch();
            $insert_id = $row ? (int) $row['id'] : null;

            return ['success' => true, 'id' => $insert_id, 'message' => 'Ingrediente guardado correctamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'id' => null, 'message' => 'Error al ejecutar la consulta: ' . $e->getMessage()];
        }
    }

    // =========================================================
    // LECTURA / BÚSQUEDA (READ)
    // =========================================================

    /**
     * Recupera todos los ingredientes que el usuario puede ver.
     * Esto incluye tanto los ingredientes globales (sembrados/públicos, ID_USER es NULL)
     * como los ingredientes personalizados que este usuario específico haya creado.
     * Se devuelven las claves adaptadas al estándar camelCase esperado por el frontend.
     *
     * @param string $userId UUID del usuario autenticado para filtrar sus ingredientes.
     * @param string|null $query Término de búsqueda opcional para filtrar por coincidencia de nombre.
     * @return array Lista de ingredientes mapeados.
     */
    public function getAll(string $userId, ?string $query = null): array
    {
        // Consultamos ingredientes públicos o del usuario actual.
        // En PostgreSQL usamos ILIKE para búsqueda case-insensitive (equivale a COLLATE utf8mb4_general_ci de MySQL)
        $sql    = "SELECT ID, name, kcals, prot, carbo, gras, ID_USER
                   FROM ingredientes
                   WHERE (ID_USER IS NULL OR ID_USER = ?)";
        $params = [$userId];

        if (!empty($query)) {
            // ILIKE en PostgreSQL = LIKE case-insensitive sin necesidad de COLLATE
            $likeTerm  = '%' . $query . '%';
            $sql      .= " AND name ILIKE ?";
            $params[]  = $likeTerm;

            // Prioridad de ordenamiento:
            // 1. Ingredientes cuyo nombre empieza con el término buscado (más relevantes)
            // 2. El resto de coincidencias (contiene el término)
            // 3. Desempate alfabético en cada grupo
            $startTerm = $query . '%';
            $sql      .= " ORDER BY
                            CASE WHEN name ILIKE ? THEN 0 ELSE 1 END ASC,
                            name ASC";
            $params[]  = $startTerm;
        } else {
            // Sin filtro: orden puramente alfabético e insensible a mayúsculas
            $sql .= " ORDER BY name ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $ingredients = [];
        while ($row = $stmt->fetch()) {
            // Mapeamos los campos a los nombres requeridos por el cliente (frontend)
            $ingredients[] = [
                'id'        => (int)$row['ID'],
                'name'      => $row['name'],
                'calories'  => (float)$row['kcals'],
                'protein'   => (float)$row['prot'],
                'carbs'     => (float)$row['carbo'],
                'fat'       => (float)$row['gras'],
                'is_custom' => $row['ID_USER'] !== null,
            ];
        }

        return $ingredients;
    }

    // =========================================================
    // ACTUALIZACIÓN (UPDATE)
    // =========================================================

    /**
     * Actualiza los datos nutricionales de un ingrediente existente del usuario.
     * Solo se permite modificar ingredientes propios (ID_USER = $userId).
     * Los ingredientes globales (ID_USER IS NULL) son inmutables desde esta vía.
     *
     * @param int    $id     ID del ingrediente a actualizar.
     * @param string $userId UUID del usuario propietario.
     * @param array  $data   Nuevos valores: name, kcals, prot, carbo, gras.
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, string $userId, array $data): array
    {
        // Verificamos que el ingrediente existe y pertenece al usuario antes de actualizar
        $checkStmt = $this->db->prepare("SELECT ID_USER FROM ingredientes WHERE ID = ?");
        $checkStmt->execute([$id]);
        $row = $checkStmt->fetch();

        if (!$row) {
            return ['success' => false, 'message' => 'El ingrediente no existe'];
        }
        if ($row['ID_USER'] === null) {
            return ['success' => false, 'message' => 'No se pueden editar ingredientes globales del sistema'];
        }
        if ($row['ID_USER'] !== $userId) {
            return ['success' => false, 'message' => 'No tienes permisos para editar este ingrediente'];
        }

        $name  = trim($data['name']);

        // Clampeamos a los límites del esquema — defensa en profundidad
        // (el controlador ya rechazó valores extremos con HTTP 422).
        $kcals = max(-9999.0, min(9999.0,  (float)($data['kcals'] ?? 0)));
        $prot  = max(0.0,     min(1000.0,  (float)($data['prot']  ?? 0)));
        $carbo = max(0.0,     min(1000.0,  (float)($data['carbo'] ?? 0)));
        $gras  = max(0.0,     min(1000.0,  (float)($data['gras']  ?? 0)));

        try {
            $stmt = $this->db->prepare(
                "UPDATE ingredientes SET name = ?, kcals = ?, prot = ?, carbo = ?, gras = ?
                  WHERE ID = ? AND ID_USER = ?"
            );
            $stmt->execute([$name, $kcals, $prot, $carbo, $gras, $id, $userId]);
            $affected = $stmt->rowCount();
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
        }

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Ingrediente actualizado correctamente'];
        }
        return ['success' => false, 'message' => 'No se realizaron cambios'];
    }

    // =========================================================
    // ELIMINACIÓN (DELETE)
    // =========================================================

    /**
     * Elimina un ingrediente por su ID.
     * Para evitar violaciones de integridad referencial (claves foráneas) en la base de datos,
     * primero eliminamos todas las asociaciones existentes del ingrediente en recetas_ingredientes,
     * ya que la tabla de unión no cuenta con eliminación en cascada (ON DELETE CASCADE) por defecto.
     * Solo se permite eliminar ingredientes que pertenecen al usuario (ID_USER = $userId).
     * Los ingredientes globales (ID_USER es NULL) no se pueden eliminar mediante esta vía.
     *
     * @param int    $id     ID del ingrediente a eliminar.
     * @param string $userId UUID del usuario que realiza la petición.
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id, string $userId): array
    {
        // 1. Verificar propiedad del ingrediente para prevenir borrado no autorizado o de globales
        $checkStmt = $this->db->prepare("SELECT ID_USER FROM ingredientes WHERE ID = ?");
        $checkStmt->execute([$id]);
        $row = $checkStmt->fetch();

        if (!$row) {
            return ['success' => false, 'message' => 'El ingrediente no existe'];
        }
        if ($row['ID_USER'] === null) {
            return ['success' => false, 'message' => 'No se pueden eliminar ingredientes globales del sistema'];
        }
        if ($row['ID_USER'] !== $userId) {
            return ['success' => false, 'message' => 'No tienes permisos para eliminar este ingrediente'];
        }

        try {
            // 2. Limpiar la tabla de unión para que la FK en recetas_ingredientes no falle
            $stmtIng = $this->db->prepare("DELETE FROM recetas_ingredientes WHERE ID_Ingred = ?");
            $stmtIng->execute([$id]);

            // 3. Eliminar el ingrediente de la tabla principal
            $stmt = $this->db->prepare("DELETE FROM ingredientes WHERE ID = ? AND ID_USER = ?");
            $stmt->execute([$id, $userId]);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al intentar eliminar: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Ingrediente eliminado correctamente'];
    }
}
