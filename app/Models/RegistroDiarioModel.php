<?php

/**
 * RegistroDiario.php — Modelo de la entidad `registro_diario`
 *
 * Gestiona la tabla `registro_diario`, que representa la "sesión diaria"
 * del usuario. Cada fila es un día único por usuario y actúa como cabecera
 * a la que se anclan las comidas consumidas (tabla `comidas_consumidas`).
 *
 * Esquema de la tabla:
 *   - ID_REG     varchar(36) PK  → UUID generado por MySQL (DEFAULT uuid())
 *   - ID_USER    varchar(36)     → FK a `users`
 *   - fecha      date            → Una fila por día por usuario
 *   - peso       float           → Peso registrado ese día (kg)
 *   - cant_vasos int             → Cantidad de vasos de agua consumidos
 *
 * Responsabilidades:
 *   - getOrCreate()    → Garantiza que exista el registro del día; lo crea si no.
 *   - getByFecha()     → Busca el registro de una fecha concreta.
 *   - updatePeso()     → Actualiza el campo `peso` de un registro existente.
 *   - getHistory()     → Historial de pesos para el gráfico de Stats.
 */
require_once __DIR__ . '/Database.php';

class RegistroDiarioModel
{
    /** @var mysqli Conexión compartida obtenida a través del singleton Database */
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // =========================================================
    // LECTURA (READ)
    // =========================================================

    /**
     * Busca el registro diario de un usuario para una fecha específica.
     *
     * @param string $userId UUID del usuario autenticado.
     * @param string $fecha  Fecha en formato 'YYYY-MM-DD'.
     * @return array|null    Registro encontrado o null si no existe.
     */
    public function getByFecha(string $userId, string $fecha): ?array
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT ID_REG, ID_USER, fecha, peso
               FROM registro_diario
              WHERE ID_USER = ? AND fecha = ?
              LIMIT 1"
        );

        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, 'ss', $userId, $fecha);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row    = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }

    /**
     * Devuelve el historial de pesos del usuario, ordenado cronológicamente.
     * Se usa para alimentar el gráfico de evolución de peso en Stats.
     *
     * Solo se retornan registros que tengan un peso registrado (peso IS NOT NULL).
     *
     * @param string $userId UUID del usuario.
     * @param int    $limit  Máximo de registros a retornar (default: 30).
     * @return array         Lista de ['fecha', 'peso'] ordenada por fecha ASC.
     */
    public function getHistory(string $userId, int $limit = 30): array
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT fecha, peso
               FROM registro_diario
              WHERE ID_USER = ?
                AND peso IS NOT NULL
              ORDER BY fecha DESC
              LIMIT ?"
        );

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'si', $userId, $limit);
        mysqli_stmt_execute($stmt);

        $result  = mysqli_stmt_get_result($stmt);
        $history = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $history[] = [
                'fecha' => $row['fecha'],
                'peso'  => (float) $row['peso'],
            ];
        }

        mysqli_stmt_close($stmt);
        
        // Invertimos el arreglo para devolverlo en orden cronológico ascendente
        return array_reverse($history);
    }

    // =========================================================
    // CREACIÓN / UPSERT (CREATE / GET-OR-CREATE)
    // =========================================================

    /**
     * Garantiza que exista un registro diario para el usuario y la fecha dada.
     *
     * Lógica:
     *   1. Busca el registro del día.
     *   2. Si existe, lo retorna tal cual.
     *   3. Si no existe, lo crea con el peso proporcionado (puede ser null).
     *
     * El UUID lo genera MySQL mediante DEFAULT uuid() en la definición de la tabla,
     * por lo que usamos INSERT IGNORE para evitar duplicados en race conditions.
     *
     * @param string     $userId UUID del usuario.
     * @param string     $fecha  Fecha en formato 'YYYY-MM-DD'.
     * @param float|null $peso   Peso inicial en kg (opcional).
     * @return array             ['success' => bool, 'id' => string|null, 'created' => bool, 'message' => string]
     */
    public function getOrCreate(string $userId, string $fecha, ?float $peso = null): array
    {
        // 1. Intentar encontrar registro existente
        $existing = $this->getByFecha($userId, $fecha);
        if ($existing) {
            return [
                'success' => true,
                'id'      => $existing['ID_REG'],
                'created' => false,
                'message' => 'Registro existente recuperado',
                'data'    => $existing,
            ];
        }

        // 2. Crear nuevo registro
        // Generamos UUID en PHP para poder retornarlo inmediatamente
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stmt = mysqli_prepare(
            $this->db,
            "INSERT INTO registro_diario (ID_REG, ID_USER, fecha, peso)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            return [
                'success' => false,
                'id'      => null,
                'created' => false,
                'message' => 'Error al preparar la consulta: ' . mysqli_error($this->db),
            ];
        }

        // El tipo 'd' permite enviar NULL para el campo float `peso`
        mysqli_stmt_bind_param($stmt, 'sssd', $uuid, $userId, $fecha, $peso);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$ok) {
            // Puede ser un duplicado por race condition → intentar leer de nuevo
            $existing = $this->getByFecha($userId, $fecha);
            if ($existing) {
                return [
                    'success' => true,
                    'id'      => $existing['ID_REG'],
                    'created' => false,
                    'message' => 'Registro existente recuperado tras colisión',
                    'data'    => $existing,
                ];
            }
            return [
                'success' => false,
                'id'      => null,
                'created' => false,
                'message' => 'Error al crear el registro: ' . mysqli_error($this->db),
            ];
        }

        return [
            'success' => true,
            'id'      => $uuid,
            'created' => true,
            'message' => 'Registro diario creado correctamente',
            'data'    => ['ID_REG' => $uuid, 'ID_USER' => $userId, 'fecha' => $fecha, 'peso' => $peso],
        ];
    }

    /**
     * Obtiene el ID_REG del registro diario del usuario para una fecha determinada.
     * Si no existe, crea un nuevo registro diario (cabecera) y retorna su nuevo UUID.
     * (Wrapper por retrocompatibilidad con la API de recetas consumidas)
     *
     * @param string $userId UUID del usuario autenticado.
     * @param string $fecha  Fecha en formato YYYY-MM-DD.
     * @return string UUID del registro diario (ID_REG).
     */
    public function getOrCreateRegistro(string $userId, string $fecha): string
    {
        $res = $this->getOrCreate($userId, $fecha);
        return $res['id'];
    }

    // =========================================================
    // ACTUALIZACIÓN (UPDATE)
    // =========================================================

    /**
     * Actualiza el peso registrado en un registro diario.
     *
     * La cláusula AND ID_USER = ? evita que un usuario modifique
     * el registro de otro (propiedad garantizada en el modelo).
     *
     * @param string $regId  UUID del registro a actualizar.
     * @param string $userId UUID del usuario propietario.
     * @param float  $peso   Nuevo peso en kg.
     * @return array         ['success' => bool, 'message' => string]
     */
    public function updatePeso(string $regId, string $userId, float $peso): array
    {
        // Validación básica de rango
        if ($peso < 20 || $peso > 300) {
            return [
                'success' => false,
                'message' => 'Peso fuera de rango válido (20–300 kg)',
            ];
        }

        $stmt = mysqli_prepare(
            $this->db,
            "UPDATE registro_diario
                SET peso = ?
              WHERE ID_REG = ? AND ID_USER = ?"
        );

        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Error al preparar la consulta',
            ];
        }

        mysqli_stmt_bind_param($stmt, 'dss', $peso, $regId, $userId);
        mysqli_stmt_execute($stmt);

        // affected_rows = 0 → no existe o no pertenece al usuario
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Peso actualizado correctamente'];
        }

        return ['success' => false, 'message' => 'Registro no encontrado o sin permisos para modificar'];
    }

    /**
     * Actualiza directamente la cantidad de vasos de agua utilizando el ID_REG.
     *
     * @param string $userId UUID del usuario.
     * @param string $idReg UUID del registro diario.
     * @param int $cantVasos Cantidad de vasos a actualizar.
     * @return array Resultado de la operación: ['success' => bool, 'message' => string]
     */
    public function updateWaterById(string $userId, string $idReg, int $cantVasos): array
    {
        $cantVasos = max(0, $cantVasos);

        $sql = "UPDATE registro_diario SET cant_vasos = ? WHERE ID_REG = ? AND ID_USER = ?";
        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Error al preparar la actualización por ID'];
        }

        // 'i' para entero, 's' para string
        mysqli_stmt_bind_param($stmt, 'iss', $cantVasos, $idReg, $userId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            return ['success' => true, 'message' => 'Consumo de agua actualizado correctamente (PUT)'];
        }
        return ['success' => false, 'message' => 'Error al ejecutar la actualización por ID'];
    }

    // =========================================================
    // COMIDAS CONSUMIDAS (RECETAS)
    // =========================================================

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
     * Registra una receta consumida en el diario del usuario usando directamente el ID_REG.
     *
     * @param string $idReg    UUID del registro diario.
     * @param string $recetaId UUID de la receta consumida.
     * @param string $tipo     Tipo de comida (Desayuno, Almuerzo, Cena, Snacks).
     * @param float  $porcion  Porción consumida (multiplicador, por defecto 1.0).
     * @return array ['success' => bool, 'id' => int|null, 'message' => string]
     */
    public function addRecetaConsumidaByReg(string $idReg, string $recetaId, string $tipo, float $porcion = 1.0): array
    {
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

    // =========================================================
    // DÍAS PERFECTOS — Evaluación de Macros
    // =========================================================

    /**
     * Calcula los totales reales de macronutrientes consumidos en un día.
     *
     * Replica la misma lógica de JOINs que getRecetasConsumidas() pero agrega
     * los resultados en una sola fila sumada, para comparar contra el objetivo.
     *
     * @param string $userId UUID del usuario.
     * @param string $fecha  Fecha en formato YYYY-MM-DD.
     * @return array|null ['calories', 'protein', 'carbs', 'fat'] o null si no hay datos.
     */
    public function getTotalesMacros(string $userId, string $fecha): ?array
    {
        $sql = "SELECT
                    COALESCE(SUM(COALESCE(i.kcals  * ri.Cant_gr / 100, 0) * cc.porcion), 0) AS calories,
                    COALESCE(SUM(COALESCE(i.prot   * ri.Cant_gr / 100, 0) * cc.porcion), 0) AS protein,
                    COALESCE(SUM(COALESCE(i.carbo  * ri.Cant_gr / 100, 0) * cc.porcion), 0) AS carbs,
                    COALESCE(SUM(COALESCE(i.gras   * ri.Cant_gr / 100, 0) * cc.porcion), 0) AS fat
                FROM comidas_consumidas cc
                JOIN registro_diario   rd ON cc.ID_REG   = rd.ID_REG
                JOIN recetas           r  ON cc.ID_RECETA = r.ID_RECETA
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA  = ri.ID_RECETA
                LEFT JOIN ingredientes         i  ON ri.ID_Ingred = i.ID
                WHERE rd.ID_USER = ? AND rd.fecha = ?";

        $stmt = mysqli_prepare($this->db, $sql);
        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, 'ss', $userId, $fecha);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row    = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) return null;

        return [
            'calories' => round((float) $row['calories'], 1),
            'protein'  => round((float) $row['protein'],  1),
            'carbs'    => round((float) $row['carbs'],    1),
            'fat'      => round((float) $row['fat'],      1),
        ];
    }

    /**
     * Marca un registro_diario como ya evaluado para la lógica de días perfectos.
     *
     * Este flag de idempotencia garantiza que el contador no se incremente
     * más de una vez para el mismo día, sin importar cuántas veces se llame al endpoint.
     *
     * @param string $userId UUID del usuario.
     * @param string $fecha  Fecha en formato YYYY-MM-DD.
     * @return bool True si se marcó correctamente.
     */
    /**
     * Marca un registro_diario como evaluado y guarda el flag es_perfecto.
     *
     * @param string $userId    UUID del usuario.
     * @param string $fecha     Fecha en formato YYYY-MM-DD.
     * @param bool   $esPerfecto Si el día cumplió con la regla de ±10%.
     * @return bool True si se marcó correctamente.
     */
    public function marcarEvaluado(string $userId, string $fecha, bool $esPerfecto = false): bool
    {
        $flagPerfecto = $esPerfecto ? 1 : 0;
        $stmt = mysqli_prepare(
            $this->db,
            "UPDATE registro_diario SET evaluado = 1, es_perfecto = ? WHERE ID_USER = ? AND fecha = ?"
        );

        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, 'iss', $flagPerfecto, $userId, $fecha);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }

    /**
     * Obtiene el estado de evaluación ('evaluado' y 'es_perfecto') de una fecha.
     *
     * @param string $userId UUID del usuario.
     * @param string $fecha  Fecha YYYY-MM-DD.
     * @return array|null ['evaluado' => int, 'es_perfecto' => int] o null si no existe.
     */
    public function getEstadoDia(string $userId, string $fecha): ?array
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT evaluado, es_perfecto FROM registro_diario WHERE ID_USER = ? AND fecha = ? LIMIT 1"
        );

        if (!$stmt) return null;

        mysqli_stmt_bind_param($stmt, 'ss', $userId, $fecha);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) return null;

        return [
            'evaluado'    => (int) ($row['evaluado'] ?? 0),
            'es_perfecto' => (int) ($row['es_perfecto'] ?? 0),
        ];
    }

    /**
     * Retorna todas las fechas del usuario que aún no fueron evaluadas
     * y que son estrictamente anteriores a la fecha de hoy.
     *
     * @param string $userId  UUID del usuario.
     * @param string $hoy     Fecha de corte (YYYY-MM-DD), excluida de los resultados.
     * @param int    $limite  Máximo de días a retornar (default: 90).
     * @return array Array de strings en formato 'YYYY-MM-DD', ordenadas ASC.
     */
    public function getFechasPendientes(string $userId, string $hoy, int $limite = 90): array
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT fecha
               FROM registro_diario
              WHERE ID_USER = ?
                AND evaluado = 0
                AND fecha    < ?
              ORDER BY fecha ASC
              LIMIT ?"
        );

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'ssi', $userId, $hoy, $limite);
        mysqli_stmt_execute($stmt);

        $result  = mysqli_stmt_get_result($stmt);
        $fechas  = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $fechas[] = $row['fecha'];
        }

        mysqli_stmt_close($stmt);
        return $fechas;
    }

    /**
     * Retorna todas las fechas registradas del usuario anteriores a hoy
     * para el recálculo completo de la historia.
     *
     * @param string $userId UUID del usuario.
     * @param string $hoy    Fecha de corte (YYYY-MM-DD).
     * @param int    $limite Máximo de días (default: 90).
     * @return array
     */
    public function getTodasFechas(string $userId, string $hoy, int $limite = 90): array
    {
        $stmt = mysqli_prepare(
            $this->db,
            "SELECT fecha
               FROM registro_diario
              WHERE ID_USER = ?
                AND fecha    < ?
              ORDER BY fecha ASC
              LIMIT ?"
        );

        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, 'ssi', $userId, $hoy, $limite);
        mysqli_stmt_execute($stmt);

        $result  = mysqli_stmt_get_result($stmt);
        $fechas  = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $fechas[] = $row['fecha'];
        }

        mysqli_stmt_close($stmt);
        return $fechas;
    }

    /**
     * Evalúa si los macronutrientes reales consumidos se encuentran dentro del rango ±10%
     * respecto a los objetivos diarios establecidos.
     *
     * Regla: objetivo * 0.90 <= real <= objetivo * 1.10 para los 4 macros.
     *
     * @param array $reales ['calories', 'protein', 'carbs', 'fat']
     * @param float $tCals  Objetivo calórico
     * @param float $tProt  Objetivo de proteína (g)
     * @param float $tCarb  Objetivo de carbohidratos (g)
     * @param float $tFat   Objetivo de grasas (g)
     * @return bool True si todos los macros cumplen con la tolerancia ±10%.
     */
    public static function evaluarRegla(array $reales, float $tCals, float $tProt, float $tCarb, float $tFat): bool
    {
        if ($tCals <= 0 || $tProt <= 0 || $tCarb <= 0 || $tFat <= 0) {
            return false;
        }

        $cals = (float)($reales['calories'] ?? 0);
        $prot = (float)($reales['protein'] ?? 0);
        $carb = (float)($reales['carbs'] ?? 0);
        $fat  = (float)($reales['fat'] ?? 0);

        $checks = [
            [$cals, $tCals],
            [$prot, $tProt],
            [$carb, $tCarb],
            [$fat,  $tFat],
        ];

        foreach ($checks as [$real, $objetivo]) {
            $min = round($objetivo * 0.90, 4);
            $max = round($objetivo * 1.10, 4);
            $val = round($real, 4);

            if ($val < $min || $val > $max) {
                return false;
            }
        }

        return true;
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
