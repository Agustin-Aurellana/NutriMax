<?php
/**
 * comidas-consumidas.php — /api/v1/comidas-consumidas
 *
 * Controlador del diario alimenticio para persistir recetas consumidas.
 * Rutas gestionadas:
 *   GET    /api/v1/comidas-consumidas?fecha=YYYY-MM-DD -> Obtener recetas logueadas del día.
 *   POST   /api/v1/comidas-consumidas                  -> Guardar una receta consumida.
 *   DELETE /api/v1/comidas-consumidas                  -> Eliminar una receta consumida.
 */

// Requerimos Response para estandarizar las respuestas JSON y los códigos HTTP
require_once __DIR__ . '/../../app/Core/Response.php';

// Requerimos Auth para forzar la validación de la sesión JWT del usuario
require_once __DIR__ . '/../../app/Core/Auth.php';

// Requerimos el modelo RegistroDiarioModel para operar sobre la base de datos
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';

// Todas las operaciones de este diario requieren autenticación obligatoria
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

$model  = new RegistroDiarioModel();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: Obtiene el ID_REG del día y todas las recetas registradas en él ──
    case 'GET':
        // Sanitizamos y extraemos el parámetro fecha (?fecha=YYYY-MM-DD)
        $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Fecha inválida o no proporcionada. Se requiere formato YYYY-MM-DD.', 400);
        }

        // Obtenemos (o creamos) el ID_REG para este usuario y fecha.
        // Lo devolvemos al front para que lo cachee en localStorage y lo use en los POSTs del día.
        $idReg   = $model->getOrCreateRegistro($userId, $fecha);
        $comidas = $model->getRecetasConsumidas($userId, $fecha);

        Response::success(['id_reg' => $idReg, 'entries' => $comidas], 200);
        break;

    // ── POST: Añade una nueva comida consumida (receta o alimento manual) al diario ──
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // Resolvemos el ID_REG: provisto directamente por el front o resuelto vía fecha como fallback
        $idReg = $data['ID_REG'] ?? null;
        if (!$idReg && !empty($data['fecha'])) {
            $idReg = $model->getOrCreateRegistro($userId, $data['fecha']);
        }

        $tipoComida = $data['tipo_comida'] ?? $data['mealType'] ?? null;
        if (empty($idReg) || empty($tipoComida)) {
            Response::error('Faltan datos obligatorios (ID_REG y tipo_comida)', 400);
        }

        $porcion = isset($data['porcion']) ? (float)$data['porcion'] : 1.0;

        // Caso 1: Consumo de una receta existente (vía ID_RECETA)
        if (!empty($data['ID_RECETA'])) {
            $recetaId = $data['ID_RECETA'];
            $result   = $model->addRecetaConsumidaByReg($idReg, $recetaId, $tipoComida, $porcion);

            if ($result['success']) {
                Response::success(['id' => $result['id']], 201, $result['message']);
            }
            Response::error($result['message'], 500);
        }

        // Caso 2: Ingreso de un alimento manual con sus macronutrientes directos
        // Soportamos claves en español (según criterio de aceptación: Nombre, Kcal, Proteínas, Carbohidratos, Grasas)
        // y claves estándar en inglés/camelCase para máxima compatibilidad con el frontend y pruebas.
        $name  = trim($data['Nombre'] ?? $data['name'] ?? $data['nombre'] ?? '');
        $kcals = isset($data['Kcal']) ? (float)$data['Kcal'] : (isset($data['kcals']) ? (float)$data['kcals'] : (isset($data['calories']) ? (float)$data['calories'] : null));
        $prot  = isset($data['Proteínas']) ? (float)$data['Proteínas'] : (isset($data['Proteinas']) ? (float)$data['Proteinas'] : (isset($data['protein']) ? (float)$data['protein'] : (isset($data['prot']) ? (float)$data['prot'] : null)));
        $carbo = isset($data['Carbohidratos']) ? (float)$data['Carbohidratos'] : (isset($data['carbs']) ? (float)$data['carbs'] : (isset($data['carbo']) ? (float)$data['carbo'] : null));
        $gras  = isset($data['Grasas']) ? (float)$data['Grasas'] : (isset($data['fat']) ? (float)$data['fat'] : (isset($data['gras']) ? (float)$data['gras'] : null));

        if ($name === '' || $kcals === null || $prot === null || $carbo === null || $gras === null) {
            Response::error('Faltan datos obligatorios del alimento (se requiere Nombre, Kcal, Proteínas, Carbohidratos y Grasas o un ID_RECETA)', 400);
        }

        // Persistimos en la base de datos siguiendo la arquitectura relacional (ingredientes -> recetas -> recetas_ingredientes -> comidas_consumidas)
        $result = $model->addComidaManualRelacional(
            $userId,
            $idReg,
            $tipoComida,
            $name,
            $kcals,
            $prot,
            $carbo,
            $gras,
            $porcion
        );

        if ($result['success']) {
            Response::success(['id' => $result['id']], 201, $result['message']);
        }

        Response::error($result['message'], 500);
        break;

    // ── DELETE: Remueve una receta consumida del diario diario del usuario ──
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        // Validamos que se pase el ID del registro que se va a borrar
        if (empty($data['id'])) {
            Response::error('Falta el ID del registro a eliminar', 400);
        }

        $idComida = (int) $data['id'];

        // Solicitamos la eliminación y validamos la pertenencia del usuario dentro del modelo
        $success = $model->deleteRecetaConsumida($idComida, $userId);

        if ($success) {
            Response::success(null, 200, 'Consumo de receta eliminado correctamente');
        }

        Response::error('No se pudo encontrar el registro o no tienes permisos para eliminarlo', 403);
        break;

    default:
        Response::error('Método no permitido', 405);
}
