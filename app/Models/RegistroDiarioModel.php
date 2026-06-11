<?php

/**
 * RegistroDiarioModel.php — Modelo para gestionar el diario de comidas consumidas.
 *
 * Centraliza la interacción SQL con las tablas 'registro_diario' (cabecera del día)
 * y 'comidas_consumidas' (detalle de recetas ingeridas).
 */
require_once __DIR__ . '/Database.php';

class RegistroDiarioModel
{
    /** @var mysqli Conexión a la base de datos MySQL */
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtiene el ID_REG del registro diario del usuario para una fecha determinada.
     * Si no existe, crea un nuevo registro diario (cabecera) y retorna su nuevo UUID.
     *
     * @param string $userId UUID del usuario autenticado.
     * @param string $fecha  Fecha en formato YYYY-MM-DD.
     * @return string UUID del registro diario (ID_REG).
     */
    public function getOrCreateRegistro(string $userId, string $fecha): string
    {
        // 1. Intentar buscar el registro diario existente para este usuario y fecha
        $stmt = mysqli_prepare($this->db, "SELECT ID_REG FROM registro_diario WHERE ID_USER = ? AND fecha = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $userId, $fecha);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($row) {
                // Si ya existe, retornamos su ID directamente
                return $row['ID_REG'];
            }
        }

        // 2. Si no existe, generamos un UUID v4 para la clave primaria ID_REG
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // 3. Insertamos el nuevo registro diario (con peso en NULL inicialmente)
        $insertStmt = mysqli_prepare($this->db, "INSERT INTO registro_diario (ID_REG, ID_USER, fecha, peso) VALUES (?, ?, ?, NULL)");
        if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, "sss", $uuid, $userId, $fecha);
            mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);
        }

        return $uuid;
    }

    /**
     * Registra una receta consumida en el diario del usuario.
     *
     * @param string $userId   UUID del usuario.
     * @param string $fecha    Fecha en formato YYYY-MM-DD.
     * @param string $recetaId UUID de la receta consumida.
     * @param string $tipo     Tipo de comida (Desayuno, Almuerzo, Cena, Snacks).
     * @param float  $porcion  Porción consumida (multiplicador, por defecto 1.0).
     * @return array ['success' => bool, 'id' => int|null, 'message' => string]
     */
    public function addRecetaConsumida(string $userId, string $fecha, string $recetaId, string $tipo, float $porcion = 1.0): array
    {
        // Obtenemos o creamos la cabecera del registro diario del día
        $idReg = $this->getOrCreateRegistro($userId, $fecha);

        $stmt = mysqli_prepare(
            $this->db,
            "INSERT INTO comidas_consumidas (ID_REG, ID_RECETA, tipo, porcion) VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            return ['success' => false, 'id' => null, 'message' => 'Error al preparar la consulta de inserción'];
        }

        mysqli_stmt_bind_param($stmt, "sssd", $idReg, $recetaId, $tipo, $porcion);

        if (mysqli_stmt_execute($stmt)) {
            $insertId = mysqli_insert_id($this->db);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'id' => $insertId, 'message' => 'Receta registrada en el diario correctamente'];
        }

        $error = mysqli_error($this->db);
        mysqli_stmt_close($stmt);
        return ['success' => false, 'id' => null, 'message' => 'Error al registrar la receta en la base de datos: ' . $error];
    }

    /**
     * Recupera todas las recetas consumidas por el usuario en una fecha específica.
     * Realiza JOINs con recetas e ingredientes para calcular en tiempo real los macros consumidos.
     *
     * @param string $userId UUID del usuario.
     * @param string $fecha  Fecha en formato YYYY-MM-DD.
     * @return array Lista de recetas consumidas con sus macros y metadatos.
     */
    public function getRecetasConsumidas(string $userId, string $fecha): array
    {
        $sql = "SELECT 
                    cc.ID_Comidas AS id,
                    cc.ID_RECETA AS recipe_id,
                    cc.tipo AS mealType,
                    cc.porcion,
                    r.name,
                    r.emoji,
                    COALESCE(SUM(i.kcals * ri.Cant_gr / 100), 0) * cc.porcion AS calories,
                    COALESCE(SUM(i.prot * ri.Cant_gr / 100), 0) * cc.porcion AS protein,
                    COALESCE(SUM(i.carbo * ri.Cant_gr / 100), 0) * cc.porcion AS carbs,
                    COALESCE(SUM(i.gras * ri.Cant_gr / 100), 0) * cc.porcion AS fat
                FROM comidas_consumidas cc
                JOIN registro_diario rd ON cc.ID_REG = rd.ID_REG
                JOIN recetas r ON cc.ID_RECETA = r.ID_RECETA
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
                LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
                WHERE rd.ID_USER = ? AND rd.fecha = ?
                GROUP BY cc.ID_Comidas
                ORDER BY cc.ID_Comidas ASC";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, "ss", $userId, $fecha);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $comidas = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comidas[] = [
                'id'        => (int) $row['id'],
                'recipe_id' => $row['recipe_id'],
                'mealType'  => $row['mealType'],
                'porcion'   => (float) $row['porcion'],
                'name'      => $row['name'],
                'emoji'     => $row['emoji'],
                'calories'  => Math_round_or_float($row['calories']),
                'protein'   => Math_round_or_float($row['protein']),
                'carbs'     => Math_round_or_float($row['carbs']),
                'fat'       => Math_round_or_float($row['fat'])
            ];
        }

        mysqli_stmt_close($stmt);
        return $comidas;
    }

    /**
     * Elimina un registro de comida consumida (receta) del diario del usuario.
     * Valida la propiedad mediante el JOIN con registro_diario para que un usuario
     * no pueda borrar el log de otro.
     *
     * @param int    $idComida ID del registro en comidas_consumidas.
     * @param string $userId   UUID del usuario que solicita el borrado.
     * @return bool True si se eliminó con éxito, de lo contrario False.
     */
    public function deleteRecetaConsumida(int $idComida, string $userId): bool
    {
        // DELETE multi-tabla seguro para validar propiedad de forma atómica
        $sql = "DELETE cc FROM comidas_consumidas cc
                JOIN registro_diario rd ON cc.ID_REG = rd.ID_REG
                WHERE cc.ID_Comidas = ? AND rd.ID_USER = ?";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "is", $idComida, $userId);
        $success = mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $success && $affected > 0;
    }
}

/**
 * Función auxiliar para redondear valores nutricionales de forma limpia
 * para el consumo de la API REST del cliente.
 */
function Math_round_or_float($val)
{
    return (float) round($val, 1);
}
