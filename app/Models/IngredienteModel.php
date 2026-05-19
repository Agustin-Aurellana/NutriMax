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
    // ELIMINACIÓN (DELETE)
    // =========================================================

    /**
     * Elimina un ingrediente por su ID.
     *
     * @param int $id ID del ingrediente a eliminar.
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id): array
    {
        $stmt = mysqli_prepare($this->db, "DELETE FROM ingredientes WHERE ID = ?");

        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la consulta'];
        }

        // i = integer
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Ingrediente eliminado correctamente'];
        }

        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Error al intentar eliminar'];
    }
}
