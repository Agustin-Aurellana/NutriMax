<?php

/**
 * IngredienteModel.php — Modelo de la entidad 'ingredientes'
 *
 * Centraliza TODA la interacción SQL relacionada con ingredientes/alimentos.
 * Reemplaza las queries inline de agregar-ing.php y eliminar-ing.php.
 */
require_once __DIR__ . '/Database.php';

class IngredienteModel
{
    /** @var mysqli Conexión compartida via el singleton Database */
    private mysqli $db;

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
        $name    = $data['name'];
        $kcals   = $data['kcals'];

        // Si no se envían macronutrientes, se asume 0 por defecto
        $prot    = isset($data['prot'])    ? $data['prot']    : 0;
        $carbo   = isset($data['carbo'])   ? $data['carbo']   : 0;
        $gras    = isset($data['gras'])    ? $data['gras']    : 0;

        // El ingrediente puede ser público (null) o pertenecer a un usuario específico
        $id_user = isset($data['ID_USER']) ? $data['ID_USER'] : null;

        // ── Deduplicación profesional y estricta:
        // Verificamos si ya existe un ingrediente con el mismo nombre (insensible a mayúsculas
        // y espacios marginales), ya sea global del sistema (ID_USER IS NULL) o creado previamente
        // por este usuario (ID_USER = $id_user). Esto evita que se cree 'manzana' si ya existe 'Manzana'.
        $checkStmt = mysqli_prepare(
            $this->db,
            "SELECT ID, name FROM ingredientes 
             WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) 
               AND (ID_USER IS NULL OR ID_USER = ?) 
             LIMIT 1"
        );
        if ($checkStmt) {
            $searchUserId = $id_user ?? '';
            mysqli_stmt_bind_param($checkStmt, "ss", $name, $searchUserId);
            mysqli_stmt_execute($checkStmt);
            $res = mysqli_stmt_get_result($checkStmt);
            $existing = mysqli_fetch_assoc($res);
            mysqli_stmt_close($checkStmt);

            if ($existing) {
                // Ya existe en la base de datos: evitamos duplicación y notificamos con claridad
                return [
                    'success'   => false,
                    'id'        => (int) $existing['ID'],
                    'message'   => "El ingrediente ya existe en la base de datos como '{$existing['name']}'.",
                    'duplicate' => true
                ];
            }
        }

        $stmt = mysqli_prepare(
            $this->db,
            "INSERT INTO ingredientes (name, kcals, prot, carbo, gras, ID_USER) VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'id' => null, 'message' => 'Error al preparar la consulta'];
        }

        // s=string, d=double/decimal, s=string (id puede ser NULL)
        mysqli_stmt_bind_param($stmt, "sdddds", $name, $kcals, $prot, $carbo, $gras, $id_user);

        if (mysqli_stmt_execute($stmt)) {
            $insert_id = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'id' => $insert_id, 'message' => 'Ingrediente guardado correctamente'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'id' => null, 'message' => 'Error al ejecutar la consulta'];
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
        // Consultamos ingredientes públicos o del usuario actual
        $sql = "SELECT ID, name, kcals, prot, carbo, gras, ID_USER 
                FROM ingredientes 
                WHERE (ID_USER IS NULL OR ID_USER = ?)";
        
        $params = [$userId];
        $types  = 's';

        // Filtro opcional por coincidencia de nombre
        if (!empty($query)) {
            $sql     .= " AND name LIKE ?";
            $params[] = '%' . $query . '%';
            $types   .= 's';
        }

        // Ordenamos alfabéticamente por consistencia
        $sql .= " ORDER BY name ASC";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $ingredients = [];

        while ($row = mysqli_fetch_assoc($result)) {
            // Mapeamos los campos a los nombres requeridos por el cliente (frontend)
            $ingredients[] = [
                'id'       => (int)$row['ID'],
                'name'     => $row['name'],
                'calories' => (float)$row['kcals'],
                'protein'  => (float)$row['prot'],
                'carbs'    => (float)$row['carbo'],
                'fat'      => (float)$row['gras'],
                'is_custom'=> $row['ID_USER'] !== null
            ];
        }

        mysqli_stmt_close($stmt);
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
        $checkStmt = mysqli_prepare($this->db, "SELECT ID_USER FROM ingredientes WHERE ID = ?");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "i", $id);
            mysqli_stmt_execute($checkStmt);
            $res = mysqli_stmt_get_result($checkStmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($checkStmt);

            if (!$row) {
                return ['success' => false, 'message' => 'El ingrediente no existe'];
            }
            if ($row['ID_USER'] === null) {
                return ['success' => false, 'message' => 'No se pueden editar ingredientes globales del sistema'];
            }
            if ($row['ID_USER'] !== $userId) {
                return ['success' => false, 'message' => 'No tienes permisos para editar este ingrediente'];
            }
        }

        $name  = $data['name'];
        $kcals = $data['kcals'];
        $prot  = isset($data['prot'])  ? $data['prot']  : 0;
        $carbo = isset($data['carbo']) ? $data['carbo'] : 0;
        $gras  = isset($data['gras'])  ? $data['gras']  : 0;

        // UPDATE apuntando al ID único: nunca crea un nuevo registro
        // Tipos: s=name, d=kcals, d=prot, d=carbo, d=gras, i=ID, s=ID_USER
        $stmt = mysqli_prepare(
            $this->db,
            "UPDATE ingredientes SET name = ?, kcals = ?, prot = ?, carbo = ?, gras = ?
              WHERE ID = ? AND ID_USER = ?"
        );

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta de actualización'];
        }

        mysqli_stmt_bind_param($stmt, "sddddis", $name, $kcals, $prot, $carbo, $gras, $id, $userId);

        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected > 0) {
                return ['success' => true, 'message' => 'Ingrediente actualizado correctamente'];
            }
            return ['success' => false, 'message' => 'No se realizaron cambios'];
        }

        $error = mysqli_error($this->db);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Error al actualizar: ' . $error];
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
        $checkStmt = mysqli_prepare($this->db, "SELECT ID_USER FROM ingredientes WHERE ID = ?");
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "i", $id);
            mysqli_stmt_execute($checkStmt);
            $res = mysqli_stmt_get_result($checkStmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($checkStmt);

            if (!$row) {
                return ['success' => false, 'message' => 'El ingrediente no existe'];
            }
            if ($row['ID_USER'] === null) {
                return ['success' => false, 'message' => 'No se pueden eliminar ingredientes globales del sistema'];
            }
            if ($row['ID_USER'] !== $userId) {
                return ['success' => false, 'message' => 'No tienes permisos para eliminar este ingrediente'];
            }
        }

        // 2. Limpiar la tabla de unión para que la FK en recetas_ingredientes no falle
        $stmtIng = mysqli_prepare($this->db, "DELETE FROM recetas_ingredientes WHERE ID_Ingred = ?");
        if ($stmtIng) {
            mysqli_stmt_bind_param($stmtIng, "i", $id);
            mysqli_stmt_execute($stmtIng);
            mysqli_stmt_close($stmtIng);
        }

        // 3. Eliminar el ingrediente de la tabla principal
        $stmt = mysqli_prepare($this->db, "DELETE FROM ingredientes WHERE ID = ? AND ID_USER = ?");

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta'];
        }

        // i = integer, s = string
        mysqli_stmt_bind_param($stmt, "is", $id, $userId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Ingrediente eliminado correctamente'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Error al intentar eliminar'];
    }
}
