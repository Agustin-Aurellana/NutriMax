<?php

/**
 * registro-diario.php — /api/v1/registro-diario
 *
 * Controlador multi-método para la gestión del registro diario.
 * Actualmente soporta la consulta (GET) y actualización (POST) del consumo de agua.
 *
 * Rutas gestionadas:
 *   GET  /api/v1/registro-diario?fecha=YYYY-MM-DD  → Obtiene el registro de un día
 *   POST /api/v1/registro-diario                   → Inserta o actualiza el agua de un día
 *
 * Todas las rutas están estrictamente protegidas por JWT (Auth::requireAuth).
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';

// ── Autenticación: Validamos el token JWT y detenemos la ejecución en caso de error ──
$authUser = Auth::requireAuth();
$userId   = $authUser['id']; // UUID del usuario autenticado extraído del payload

$model  = new RegistroDiarioModel();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: Obtener el registro diario (vasos de agua y peso) de una fecha ──
    case 'GET':
        // Sanitizamos y validamos el parámetro de fecha desde la URL
        $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        if (empty($fecha)) {
            Response::error('El parámetro fecha es obligatorio (ej: ?fecha=YYYY-MM-DD)', 400);
        }

        // Expresión regular para validar formato YYYY-MM-DD y evitar entradas corruptas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Formato de fecha inválido. Utilice YYYY-MM-DD', 400);
        }

        // Consultamos en la base de datos a través de la capa del Modelo
        $registro = $model->get($userId, $fecha);

        // Si no existe un registro en la base de datos para este día, retornamos valores por defecto (0 vasos)
        // para que el frontend los reciba correctamente y actualice su estado sin errores de inconsistencia.
        if ($registro === null) {
            Response::success([
                'fecha'      => $fecha,
                'cant_vasos' => 0,
                'peso'       => null
            ], 200);
        }

        // Normalizamos los tipos antes de la salida JSON (PHP recupera números como strings por defecto en algunas extensiones)
        $registro['cant_vasos'] = (int) ($registro['cant_vasos'] ?? 0);
        $registro['peso']       = $registro['peso'] !== null ? (float) $registro['peso'] : null;

        Response::success($registro, 200);
        break;

    // ── POST: Insertar o actualizar la cantidad de vasos de agua para una fecha ──
    case 'POST':
        // Decodificamos el cuerpo de la solicitud JSON enviada por el cliente
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['fecha']) || !isset($data['cant_vasos'])) {
            Response::error('Datos inválidos o incompletos. Se requiere fecha y cant_vasos', 400);
        }

        $fecha     = $data['fecha'];
        $cantVasos = (int) $data['cant_vasos'];

        // Validación de formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Formato de fecha inválido. Utilice YYYY-MM-DD', 400);
        }

        // Validación del límite lógico de consumo de agua (por ejemplo, entre 0 y 100 vasos)
        if ($cantVasos < 0 || $cantVasos > 100) {
            Response::error('Cantidad de vasos inválida. Debe estar entre 0 y 100', 400);
        }

        // Ejecutamos la inserción o actualización mediante el Modelo
        $result = $model->saveWater($userId, $fecha, $cantVasos);

        if ($result['success']) {
            Response::success(['cant_vasos' => $cantVasos], 200, $result['message']);
        }

        Response::error($result['message'], 500);
        break;

    default:
        // Respondemos con código 405 si el cliente intenta métodos no soportados
        Response::error('Método no permitido', 405);
}
