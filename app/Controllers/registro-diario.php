<?php

/**
 * registro-diario.php — /api/v1/registro-diario
 *
 * Controlador multi-método para la gestión del registro diario de peso y agua.
 * Cada registro representa una "sesión del día" del usuario, que actúa
 * como cabecera para las comidas consumidas.
 *
 * Rutas gestionadas:
 *   GET  /api/v1/registro-diario               → Historial de pesos (últimos 30 días)
 *   GET  /api/v1/registro-diario?fecha=YYYY-MM-DD → Registro de una fecha específica
 *   POST /api/v1/registro-diario               → Obtener o crear el registro del día
 *   PUT  /api/v1/registro-diario               → Actualizar el peso o agua del registro
 *
 * Todas las rutas están protegidas por JWT (Auth::requireAuth).
 */

require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';

// ── Autenticar: si el token es inválido, la ejecución se detiene aquí ──
$authUser = Auth::requireAuth();
$userId   = $authUser['id']; // UUID extraído del payload JWT

$model  = new RegistroDiarioModel();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: Historial de pesos o registro de una fecha concreta ──
    case 'GET':
        $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

        if ($fecha) {
            // Buscar el registro de un día específico
            $registro = $model->getByFecha($userId, $fecha);

            if ($registro) {
                Response::success(['registro' => $registro], 200);
            } else {
                // No hay registro para esa fecha → devolvemos vacío (no error)
                Response::success(['registro' => null], 200);
            }
        } else {
            // Sin filtro de fecha → retornar historial completo de pesos para el gráfico
            $limit   = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 30;
            $history = $model->getHistory($userId, $limit);
            Response::success(['history' => $history], 200);
        }
        break;

    // ── POST: Obtener o crear el registro del día (idempotente) ──
    case 'POST':
        $data = json_decode(Request::body(), true);

        // `fecha` es obligatorio; `peso` es opcional
        if (empty($data['fecha'])) {
            Response::error('El campo fecha es obligatorio (formato YYYY-MM-DD)', 400);
        }

        // Validar formato de fecha
        $fecha = trim($data['fecha']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Response::error('Formato de fecha inválido. Usar YYYY-MM-DD', 400);
        }

        // El peso es opcional en la creación; si se provee, debe cumplir el rango lógico (20–400 kg)
        $peso = (isset($data['peso']) && $data['peso'] !== '' && $data['peso'] !== null) ? (float) $data['peso'] : null;
        if ($peso !== null && ($peso < 20 || $peso > 400)) {
            Response::error('Ingresa un peso válido entre 20 kg y 400 kg', 400);
        }

        $result = $model->getOrCreate($userId, $fecha, $peso);

        if ($result['success']) {
            $statusCode = $result['created'] ? 201 : 200;
            Response::success(
                ['id' => $result['id'], 'data' => $result['data'], 'created' => $result['created']],
                $statusCode,
                $result['message']
            );
        }

        Response::error($result['message'], 500);
        break;

    // ── PUT: Actualizar el peso o agua de un registro existente ──
    case 'PUT':
        $data = json_decode(Request::body(), true);

        // Validar que lleguen los campos necesarios
        if (empty($data['id'])) {
            Response::error('El campo id (ID_REG) es obligatorio', 400);
        }

        if (isset($data['peso'])) {
            $peso = (float) $data['peso'];
            if ($peso < 20 || $peso > 400) {
                Response::error('Ingresa un peso válido entre 20 kg y 400 kg', 400);
            }
            $result = $model->updatePeso($data['id'], $userId, $peso);
        } elseif (isset($data['agua'])) {
            $result = $model->updateWaterById($userId, $data['id'], (int) $data['agua']);
        } else {
            Response::error('Se requiere el campo peso o agua', 400);
            exit;
        }

        if ($result['success']) {
            Response::success(null, 200, $result['message']);
        }

        // 400 si falló validación o 403 si no tiene permisos
        $errorCode = strpos($result['message'], 'permisos') !== false ? 403 : 400;
        Response::error($result['message'], $errorCode);
        break;

    default:
        Response::error('Método no permitido', 405);
}
