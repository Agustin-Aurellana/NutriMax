<?php

/**
 * dias-perfectos.php — POST /api/v1/dias-perfectos
 *
 * Evalúa si un día pasado fue un "Día Perfecto" para el usuario autenticado.
 * Un día es perfecto si todos los macronutrientes consumidos están dentro del
 * rango [objetivo × 0.90, objetivo × 1.10] (tolerancia ±10%).
 *
 * Modos de operación:
 *   POST /api/v1/dias-perfectos
 *     Body: { "fecha": "YYYY-MM-DD", "targets": { "calories": n, "protein": n, "carbs": n, "fat": n } }
 *     Evalúa un único día. Idempotente: no suma si ya fue evaluado.
 *
 *   POST /api/v1/dias-perfectos?batch=1
 *     Body: { "targets": { "calories": n, "protein": n, "carbs": n, "fat": n } }
 *     Evalúa todos los días pasados pendientes (hasta 90) en un solo request.
 *
 * Protecciones:
 *   - Requiere JWT válido.
 *   - No evalúa el día actual (solo días < hoy).
 *   - No permite que targets tengan valores 0 o negativos (evita división por cero).
 */

require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';
require_once __DIR__ . '/../Models/UserModel.php';

// ── Autenticar: detiene la ejecución si el token es inválido ──
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

// Solo aceptamos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

// ── Validar targets (requeridos en ambos modos) ──
$targets = $data['targets'] ?? null;
if (!$targets || !is_array($targets)) {
    Response::error('El campo targets es obligatorio (calories, protein, carbs, fat)', 400);
}

// Asegurar tipos y proteger contra divisiones por cero
$tCals = (float) ($targets['calories'] ?? 0);
$tProt = (float) ($targets['protein']  ?? 0);
$tCarb = (float) ($targets['carbs']    ?? 0);
$tFat  = (float) ($targets['fat']      ?? 0);

if ($tCals <= 0 || $tProt <= 0 || $tCarb <= 0 || $tFat <= 0) {
    Response::error('Los objetivos (targets) deben ser mayores a cero para todos los macros', 400);
}

$registroModel = new RegistroDiarioModel();
$userModel     = new UserModel();
$hoy           = date('Y-m-d');

// ────────────────────────────────────────
// MODO BATCH: ?batch=1
// Evalúa todo el historial de días pasados (hasta 90 días)
// ────────────────────────────────────────
if (isset($_GET['batch']) && $_GET['batch'] === '1') {
    $fechas = $registroModel->getTodasFechas($userId, $hoy, 90);

    $totalEvaluados = 0;
    $totalPerfectos = 0;

    foreach ($fechas as $fecha) {
        $reales = $registroModel->getTotalesMacros($userId, $fecha);
        $totalEvaluados++;

        // Si no hay datos de consumo ese día, no es perfecto
        if (!$reales || $reales['calories'] === 0.0) {
            $registroModel->marcarEvaluado($userId, $fecha, false);
            continue;
        }

        $esPerfecto = evaluarRegla($reales, $tCals, $tProt, $tCarb, $tFat);
        $registroModel->marcarEvaluado($userId, $fecha, $esPerfecto);

        if ($esPerfecto) {
            $totalPerfectos++;
        }
    }

    // Sincronizamos el total exacto acumulado de días perfectos
    $userModel->setDiasPerfectos($userId, $totalPerfectos);
    $nuevoTotal = $totalPerfectos;

    Response::success([
        'evaluados'   => $totalEvaluados,
        'perfectos'   => $totalPerfectos,
        'nuevo_total' => $nuevoTotal,
    ], 200, "Batch completado: {$totalEvaluados} días evaluados, {$totalPerfectos} perfectos");
    exit;
}

// ────────────────────────────────────────
// MODO INDIVIDUAL: evalúa una sola fecha
// ────────────────────────────────────────
$fecha = trim($data['fecha'] ?? '');

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    Response::error('Formato de fecha inválido. Usar YYYY-MM-DD', 400);
}

// No se puede evaluar el día en curso: los datos podrían no estar completos
if ($fecha >= $hoy) {
    Response::error('Solo se pueden evaluar días pasados (fecha < hoy)', 400);
}

// ── Comprobar estado previo del día (idempotencia y detección de cambios) ──
$estadoPrevio = $registroModel->getEstadoDia($userId, $fecha);

// ── Obtener totales reales del día desde la BD ──
$reales = $registroModel->getTotalesMacros($userId, $fecha);

if (!$reales || $reales['calories'] === 0.0) {
    // Si antes era considerado perfecto pero ahora no tiene comidas (p.ej. fueron eliminadas)
    if ($estadoPrevio && $estadoPrevio['es_perfecto'] === 1) {
        $userModel->decrementDiasPerfectos($userId);
    }
    $registroModel->marcarEvaluado($userId, $fecha, false);

    Response::success([
        'es_perfecto'  => false,
        'ya_evaluado'  => ($estadoPrevio && $estadoPrevio['evaluado'] === 1 && $estadoPrevio['es_perfecto'] === 0),
        'nuevo_total'  => $userModel->getDiasPerfectos($userId),
        'razon'        => 'Sin consumo registrado en la BD para esa fecha',
        'macros'       => null,
    ], 200, 'Sin consumo registrado para esa fecha');
}

// ── Aplicar regla de negocio: ±10% en los 4 macros ──
$esPerfecto  = evaluarRegla($reales, $tCals, $tProt, $tCarb, $tFat);
$eraPerfecto = $estadoPrevio ? ($estadoPrevio['es_perfecto'] === 1) : false;
$yaEvaluado  = $estadoPrevio ? ($estadoPrevio['evaluado'] === 1 && $eraPerfecto === $esPerfecto) : false;

// Si cambió su estado (p.ej. el usuario editó comidas en un día pasado)
if ($esPerfecto && !$eraPerfecto) {
    $userModel->incrementDiasPerfectos($userId);
} elseif (!$esPerfecto && $eraPerfecto) {
    $userModel->decrementDiasPerfectos($userId);
}

// Actualizar registro en BD con su estado actual
$registroModel->marcarEvaluado($userId, $fecha, $esPerfecto);

$nuevoTotal = $userModel->getDiasPerfectos($userId);

Response::success([
    'es_perfecto'  => $esPerfecto,
    'ya_evaluado'  => $yaEvaluado,
    'nuevo_total'  => $nuevoTotal,
    'macros'       => [
        'real'     => $reales,
        'objetivo' => [
            'calories' => $tCals,
            'protein'  => $tProt,
            'carbs'    => $tCarb,
            'fat'      => $tFat,
        ],
        // Porcentajes de cumplimiento para debugging en el frontend
        'pct' => [
            'calories' => round($reales['calories'] / $tCals * 100, 1),
            'protein'  => round($reales['protein']  / $tProt * 100, 1),
            'carbs'    => round($reales['carbs']    / $tCarb * 100, 1),
            'fat'      => round($reales['fat']      / $tFat  * 100, 1),
        ],
    ],
], 200, $esPerfecto ? '¡Día Perfecto!' : 'Objetivos no cumplidos en ±10%');

// ─────────────────────────────────────────────────────
// Función pura de evaluación — separada para testabilidad
//
// Un día es perfecto cuando TODOS los macros cumplen:
//   objetivo × 0.90 ≤ real ≤ objetivo × 1.10
//
// @param array $reales    ['calories', 'protein', 'carbs', 'fat']
// @param float $tCals     Objetivo de calorías
// @param float $tProt     Objetivo de proteínas
// @param float $tCarb     Objetivo de carbohidratos
// @param float $tFat      Objetivo de grasas
// @return bool
// ─────────────────────────────────────────────────────
function evaluarRegla(array $reales, float $tCals, float $tProt, float $tCarb, float $tFat): bool
{
    return RegistroDiarioModel::evaluarRegla($reales, $tCals, $tProt, $tCarb, $tFat);
}
