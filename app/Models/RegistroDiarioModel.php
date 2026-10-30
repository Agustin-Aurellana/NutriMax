<?php

/**
 * RegistroDiarioModel.php — Modelo de la entidad `registro_diario`
 *
 * Gestiona la tabla `registro_diario`, que representa la "sesión diaria"
 * del usuario. Cada fila es un día único por usuario y actúa como cabecera
 * a la que se anclan las comidas consumidas (tabla `comidas_consumidas`).
 *
 * Esquema de la tabla:
 *   - ID_REG     varchar(36) PK  → UUID generado en PHP (antes por MySQL DEFAULT uuid())
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
 *
 * Migración mysqli → PDO/PostgreSQL:
 *   - $this->db es ahora PDO
 *   - prepare/execute/fetch reemplazan toda la API mysqli_*
 *   - beginTransaction/commit/rollBack en lugar de mysqli_begin_transaction/commit/rollback
 *   - DELETE multi-tabla (MySQL) → DELETE ... USING (PostgreSQL) para deleteRecetaConsumida
 *   - mysqli_insert_id() → lastInsertId() o RETURNING (para seriales/secuencias de PostgreSQL)
 *   - INSERT IGNORE (MySQL) → INSERT ... ON CONFLICT DO NOTHING (PostgreSQL)
 *   - rowCount() en lugar de mysqli_num_rows() / mysqli_stmt_affected_rows()
 */
require_once __DIR__ . '/Database.php';

class RegistroDiarioModel
{
    /** @var PDO Conexión compartida obtenida a través del singleton Database */
    private PDO $db;

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
        $stmt = $this->db->prepare(
            "SELECT ID_REG, ID_USER, fecha, peso, cant_vasos
               FROM registro_diario
              WHERE ID_USER = ? AND fecha = ?
              LIMIT 1"
        );
        $stmt->execute([$userId, $fecha]);
        $row = $stmt->fetch();

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
        // En PostgreSQL el parámetro LIMIT puede ser un entero enlazado directamente
        $stmt = $this->db->prepare(
            "SELECT fecha, peso
               FROM registro_diario
              WHERE ID_USER = ?
                AND peso IS NOT NULL
              ORDER BY fecha DESC
              LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);

        $history = [];
        while ($row = $stmt->fetch()) {
            $history[] = [
                'fecha' => $row['fecha'],
                'peso'  => (float) $row['peso'],
            ];
        }

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
     * El UUID lo generamos en PHP. En lugar de INSERT IGNORE (MySQL),
     * usamos INSERT ... ON CONFLICT DO NOTHING (PostgreSQL) para manejar
     * condiciones de carrera sin excepciones.
     *
     * @param string     $userId UUID del usuario.
     * @param string     $fecha  Fecha en formato 'YYYY-MM-DD'.
     * @param float|null $peso   Peso inicial en kg (opcional).
     * @return array             ['success' => bool, 'id' => string|null, 'created' => bool, 'message' => string]
     */
    public function getOrCreate(string $userId, string $fecha, ?float $peso = null): array
    {
        // Validación defensiva de rango obligatorio (20–400 kg) si se proporciona peso
        if ($peso !== null && ($peso < 20 || $peso > 400)) {
            return [
                'success' => false,
                'id'      => null,
                'created' => false,
                'message' => 'Ingresa un peso válido entre 20 kg y 400 kg',
            ];
        }

        // 1. Intentar encontrar registro existente
        $existing = $this->getByFecha($userId, $fecha);
        if ($existing) {
            // Si el registro ya existía y se especifica un peso no nulo distinto al actual,
            // actualizamos el peso del registro existente para evitar registros con peso desactualizado.
            if ($peso !== null && ($existing['peso'] === null || (float)$existing['peso'] !== (float)$peso)) {
                $this->updatePeso($existing['ID_REG'], $userId, $peso);
                $existing['peso'] = $peso;
            }

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

        try {
            // ON CONFLICT DO NOTHING reemplaza INSERT IGNORE de MySQL para manejar race conditions
            $stmt = $this->db->prepare(
                "INSERT INTO registro_diario (ID_REG, ID_USER, fecha, peso)
                 VALUES (?, ?, ?, ?)
                 ON CONFLICT DO NOTHING"
            );
            $stmt->execute([$uuid, $userId, $fecha, $peso]);
            $affected = $stmt->rowCount();
        } catch (PDOException $e) {
            // Puede ser un duplicado por race condition — intentar leer de nuevo
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
                'message' => 'Error al crear el registro: ' . $e->getMessage(),
            ];
        }

        if ($affected === 0) {
            // ON CONFLICT activado: recuperar el registro que ya existía
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
        // Validación de rango defensiva a nivel de modelo para integridad de datos
        if ($peso < 20 || $peso > 400) {
            return [
                'success' => false,
                'message' => 'Ingresa un peso válido entre 20 kg y 400 kg',
            ];
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE registro_diario
                    SET peso = ?
                  WHERE ID_REG = ? AND ID_USER = ?"
            );
            $stmt->execute([$peso, $regId, $userId]);
            $affected = $stmt->rowCount();
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al preparar la consulta: ' . $e->getMessage()];
        }

        // Si affected > 0, se actualizó la fila con un valor nuevo
        if ($affected > 0) {
            return ['success' => true, 'message' => 'Peso actualizado correctamente'];
        }

        // Si affected === 0, puede ser porque el peso enviado es el mismo que ya estaba en la BD.
        // Verificamos si el registro efectivamente existe y pertenece al usuario.
        $checkStmt = $this->db->prepare(
            "SELECT 1 FROM registro_diario WHERE ID_REG = ? AND ID_USER = ?"
        );
        $checkStmt->execute([$regId, $userId]);
        $exists = ($checkStmt->rowCount() > 0);

        if ($exists) {
            return ['success' => true, 'message' => 'Peso actualizado correctamente'];
        }

        return ['success' => false, 'message' => 'Registro no encontrado o sin permisos para modificar'];
    }

    /**
     * Actualiza directamente la cantidad de vasos de agua utilizando el ID_REG.
     *
     * @param string $userId    UUID del usuario.
     * @param string $idReg     UUID del registro diario.
     * @param int    $cantVasos Cantidad de vasos a actualizar.
     * @return array Resultado de la operación: ['success' => bool, 'message' => string]
     */
    public function updateWaterById(string $userId, string $idReg, int $cantVasos): array
    {
        $cantVasos = max(0, $cantVasos);

        try {
            $stmt = $this->db->prepare(
                "UPDATE registro_diario SET cant_vasos = ? WHERE ID_REG = ? AND ID_USER = ?"
            );
            $stmt->execute([$cantVasos, $idReg, $userId]);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al ejecutar la actualización por ID: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Consumo de agua actualizado correctamente (PUT)'];
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

        try {
            // RETURNING ID_Comidas obtiene el ID generado por la secuencia de PostgreSQL
            $stmt = $this->db->prepare(
                "INSERT INTO comidas_consumidas (ID_REG, ID_RECETA, tipo, porcion)
                 VALUES (?, ?, ?, ?)
                 RETURNING ID_Comidas"
            );
            $stmt->execute([$idReg, $recetaId, $tipo, $porcion]);
            $row      = $stmt->fetch();
            $insertId = $row ? (int) $row['id_comidas'] : null;

            return ['success' => true, 'id' => $insertId, 'message' => 'Receta registrada en el diario correctamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'id' => null, 'message' => 'Error al registrar la receta en la base de datos: ' . $e->getMessage()];
        }
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
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO comidas_consumidas (ID_REG, ID_RECETA, tipo, porcion)
                 VALUES (?, ?, ?, ?)
                 RETURNING ID_Comidas"
            );
            $stmt->execute([$idReg, $recetaId, $tipo, $porcion]);
            $row      = $stmt->fetch();
            $insertId = $row ? (int) $row['id_comidas'] : null;

            return ['success' => true, 'id' => $insertId, 'message' => 'Receta registrada en el diario correctamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'id' => null, 'message' => 'Error al registrar la receta en la base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Registra un alimento manual en el diario del usuario siguiendo estrictamente el modelo relacional:
     * 1. Inserta el alimento como un ingrediente personalizado en `ingredientes`.
     * 2. Crea una receta asociada en `recetas` (1 porción).
     * 3. Vincula ambos en `recetas_ingredientes` (100g de base para conservar la escala de macros).
     * 4. Registra el consumo diario en `comidas_consumidas`.
     *
     * Se ejecuta dentro de una transacción atómica para asegurar la integridad referencial.
     *
     * @param string $userId  UUID del usuario autenticado.
     * @param string $idReg   UUID del registro diario (cabecera del día).
     * @param string $tipo    Tipo de comida (Desayuno, Almuerzo, Cena, Snack).
     * @param string $name    Nombre del alimento.
     * @param float  $kcals   Calorías totales.
     * @param float  $prot    Gramos de proteína.
     * @param float  $carbo   Gramos de carbohidratos.
     * @param float  $gras    Gramos de grasa.
     * @param float  $porcion Multiplicador de porción (por defecto 1.0).
     * @return array ['success' => bool, 'id' => int|null, 'message' => string]
     */
    public function addComidaManualRelacional(
        string $userId,
        string $idReg,
        string $tipo,
        string $name,
        float $kcals,
        float $prot,
        float $carbo,
        float $gras,
        float $porcion = 1.0
    ): array {
        // Límites estrictos: proteínas, carbohidratos y grasas no pueden ser negativos (>= 0).
        // Las calorías sí pueden ser negativas según los requerimientos del usuario.
        $prot  = max(0.0, $prot);
        $carbo = max(0.0, $carbo);
        $gras  = max(0.0, $gras);

        // Iniciamos transacción para que todas las operaciones sean atómicas e indivisibles
        try {
            $this->db->beginTransaction();

            // ✅ Deduplicación y persistencia inteligente de ingredientes
            // Buscamos si ya existe un ingrediente con este nombre (insensible a mayúsculas y espacios).
            // Priorizamos el ingrediente personalizado del usuario (ID_USER = $userId) sobre uno público.
            $ingId = null;
            $stmtCheck = $this->db->prepare(
                "SELECT ID, ID_USER, kcals, prot, carbo, gras FROM ingredientes
                 WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
                   AND (ID_USER IS NULL OR ID_USER = ?)
                 ORDER BY (ID_USER IS NOT NULL) DESC LIMIT 1"
            );
            $stmtCheck->execute([$name, $userId]);
            $existingIng = $stmtCheck->fetch();

            if ($existingIng) {
                $ingId = (int) $existingIng['ID'];

                // Si el ingrediente le pertenece al usuario autenticado, persistimos cualquier cambio
                // en sus valores macronutricionales para mantener la coherencia del catálogo personal.
                if ($existingIng['ID_USER'] !== null && $existingIng['ID_USER'] === $userId) {
                    $stmtUpd = $this->db->prepare(
                        "UPDATE ingredientes SET kcals = ?, prot = ?, carbo = ?, gras = ?
                          WHERE ID = ? AND ID_USER = ?"
                    );
                    $stmtUpd->execute([$kcals, $prot, $carbo, $gras, $ingId, $userId]);
                }
                // Si el ingrediente es público del sistema (ID_USER IS NULL), se respeta su inmutabilidad
                // y no se modifica la tabla global, reutilizando su ID para vincular la comida consumida.
            }

            // Solo insertamos como nuevo ingrediente si NO existía en el catálogo
            if ($ingId === null) {
                // RETURNING ID obtiene el ID de la secuencia en PostgreSQL
                $stmtIng = $this->db->prepare(
                    "INSERT INTO ingredientes (name, kcals, prot, carbo, gras, ID_USER)
                     VALUES (?, ?, ?, ?, ?, ?)
                     RETURNING ID"
                );
                $stmtIng->execute([$name, $kcals, $prot, $carbo, $gras, $userId]);
                $rowIng = $stmtIng->fetch();
                if (!$rowIng) {
                    throw new Exception("Error al insertar ingrediente: no se obtuvo ID");
                }
                $ingId = (int) $rowIng['id'];
            }

            // ✅ Receta interna marcada como oculta
            // Generamos una receta "wrapper" necesaria para satisfacer la FK de comidas_consumidas.
            // La marcamos con dieta='_manual' para que RecetaModel::getAll() la filtre
            // y nunca aparezca en el listado visible de recetas del usuario.
            $recetaId = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            $stmtRec = $this->db->prepare(
                "INSERT INTO recetas (ID_RECETA, ID_USER, name, emoji, porciones, dieta)
                 VALUES (?, ?, ?, '🍽️', 1.0, '_manual')"
            );
            $stmtRec->execute([$recetaId, $userId, $name]);

            // 3. Vincular receta con el ingrediente en recetas_ingredientes (100g para escala 1:1)
            $cantGr  = 100;
            $stmtRI  = $this->db->prepare(
                "INSERT INTO recetas_ingredientes (ID_RECETA, ID_Ingred, Cant_gr) VALUES (?, ?, ?)"
            );
            $stmtRI->execute([$recetaId, $ingId, $cantGr]);

            // 4. Registrar la comida consumida en comidas_consumidas
            $stmtCC = $this->db->prepare(
                "INSERT INTO comidas_consumidas (ID_REG, ID_RECETA, tipo, porcion)
                 VALUES (?, ?, ?, ?)
                 RETURNING ID_Comidas"
            );
            $stmtCC->execute([$idReg, $recetaId, $tipo, $porcion]);
            $rowCC    = $stmtCC->fetch();
            $comidaId = $rowCC ? (int) $rowCC['id_comidas'] : null;

            // Confirmamos la transacción completa si todos los pasos fueron exitosos
            $this->db->commit();

            return [
                'success' => true,
                'id'      => $comidaId,
                'message' => 'Alimento manual registrado exitosamente en la base de datos',
            ];
        } catch (Exception $e) {
            // Revertimos la transacción ante cualquier fallo para mantener la consistencia
            $this->db->rollBack();
            return [
                'success' => false,
                'id'      => null,
                'message' => $e->getMessage(),
            ];
        }
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
                    COALESCE(SUM(i.kcals * ri.Cant_gr / 100.0), 0) * cc.porcion AS calories,
                    COALESCE(SUM(i.prot  * ri.Cant_gr / 100.0), 0) * cc.porcion AS protein,
                    COALESCE(SUM(i.carbo * ri.Cant_gr / 100.0), 0) * cc.porcion AS carbs,
                    COALESCE(SUM(i.gras  * ri.Cant_gr / 100.0), 0) * cc.porcion AS fat
                FROM comidas_consumidas cc
                JOIN registro_diario rd ON cc.ID_REG = rd.ID_REG
                JOIN recetas r ON cc.ID_RECETA = r.ID_RECETA
                LEFT JOIN recetas_ingredientes ri ON r.ID_RECETA = ri.ID_RECETA
                LEFT JOIN ingredientes i ON ri.ID_Ingred = i.ID
                WHERE rd.ID_USER = ? AND rd.fecha = ?
                GROUP BY cc.ID_Comidas, cc.ID_RECETA, cc.tipo, cc.porcion, r.name, r.emoji
                ORDER BY cc.ID_Comidas ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $fecha]);

        $comidas = [];
        while ($row = $stmt->fetch()) {
            $comidas[] = [
                'id'        => (int) $row['id'],
                'recipe_id' => $row['recipe_id'],
                'mealType'  => $row['mealtype'],
                'porcion'   => (float) $row['porcion'],
                'name'      => $row['name'],
                'emoji'     => $row['emoji'],
                'calories'  => Math_round_or_float($row['calories']),
                'protein'   => Math_round_or_float($row['protein']),
                'carbs'     => Math_round_or_float($row['carbs']),
                'fat'       => Math_round_or_float($row['fat']),
            ];
        }

        return $comidas;
    }

    /**
     * Elimina un registro de comida consumida (receta) del diario del usuario.
     * Valida la propiedad mediante USING con registro_diario en PostgreSQL para que un usuario
     * no pueda borrar el log de otro.
     *
     * @param int    $idComida ID del registro en comidas_consumidas.
     * @param string $userId   UUID del usuario que solicita el borrado.
     * @return bool True si se eliminó con éxito, de lo contrario False.
     */
    public function deleteRecetaConsumida(int $idComida, string $userId): bool
    {
        // En PostgreSQL el DELETE multi-tabla usa USING en lugar de JOIN directo en DELETE
        $sql = "DELETE FROM comidas_consumidas cc
                USING registro_diario rd
                WHERE cc.ID_REG = rd.ID_REG
                  AND cc.ID_Comidas = ?
                  AND rd.ID_USER = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idComida, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // =========================================================
    // HELPERS PRIVADOS
    // =========================================================

    /**
     * Obtiene el ID_USER asociado a un ID_REG de registro_diario.
     */
    private function getUserIdByReg(string $idReg): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT ID_USER FROM registro_diario WHERE ID_REG = ? LIMIT 1"
        );
        $stmt->execute([$idReg]);
        $row = $stmt->fetch();
        return $row['ID_USER'] ?? null;
    }

    /**
     * Placeholder para el cálculo de objetivos de macronutrientes.
     * Puede ser reemplazado con la fórmula real basada en el perfil del usuario.
     */
    private function getMacroTargets(string $userId): array
    {
        // Por ahora retornamos objetivos estáticos; en una app real se basaría en el perfil del usuario
        return [
            'protein' => 50.0,  // gramos
            'carbs'   => 250.0,
            'fat'     => 70.0,
        ];
    }

    /**
     * Evalúa si el día asociado al registro $idReg cumple con los objetivos de macronutrientes
     * dentro del margen de ±10% y actualiza la columna `is_perfect`.
     * Si es perfecto, incrementa el contador `dias_perfectos` del usuario.
     */
    public function evaluarDiaPerfecto(string $idReg): bool
    {
        $userId = $this->getUserIdByReg($idReg);
        if (!$userId) return false;

        // Obtener la fecha del registro para usarla en la consulta de recetas
        $stmt = $this->db->prepare(
            "SELECT fecha FROM registro_diario WHERE ID_REG = ? LIMIT 1"
        );
        $stmt->execute([$idReg]);
        $row   = $stmt->fetch();
        $fecha = $row['fecha'] ?? null;
        if (!$fecha) return false;

        // Sumar los macros consumidos en el día
        $comidas = $this->getRecetasConsumidas($userId, $fecha);
        $totals  = ['protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        foreach ($comidas as $c) {
            $totals['protein'] += $c['protein'];
            $totals['carbs']   += $c['carbs'];
            $totals['fat']     += $c['fat'];
        }

        $targets = $this->getMacroTargets($userId);
        $perfect = true;
        foreach (['protein', 'carbs', 'fat'] as $macro) {
            $target = $targets[$macro];
            $actual = $totals[$macro];
            if ($target == 0) {
                // Evitar división por cero — sin objetivo definido, no es perfecto
                $perfect = false;
                break;
            }
            if (abs($actual - $target) > $target * 0.10) {
                $perfect = false;
                break;
            }
        }

        // Actualizar la columna is_perfect en registro_diario
        try {
            $stmtPerf = $this->db->prepare(
                "UPDATE registro_diario SET is_perfect = ? WHERE ID_REG = ?"
            );
            $stmtPerf->execute([$perfect ? 1 : 0, $idReg]);

            // Incrementar el contador de días perfectos si corresponde
            if ($perfect) {
                $stmtUser = $this->db->prepare(
                    "UPDATE users SET dias_perfectos = dias_perfectos + 1 WHERE ID_USER = ?"
                );
                $stmtUser->execute([$userId]);
            }
        } catch (PDOException $e) {
            // Log del error sin interrumpir el flujo
            error_log('[NutriMax][RegistroDiarioModel] evaluarDiaPerfecto error: ' . $e->getMessage());
        }

        return $perfect;
    }
}

/**
 * Función auxiliar para redondear valores nutricionales de forma limpia
 * para el consumo de la API REST del cliente.
 */
function Math_round_or_float($val): float
{
    return (float) round($val, 1);
}
