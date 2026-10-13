<?php
/**
 * agregar-ing.php — POST /api/v1/agregar-ing
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

// Extraemos el usuario autenticado para asociarlo con su nuevo ingrediente
$authUser = Auth::requireAuth();

$data = json_decode(Request::body(), true);

// Validamos todos los campos macro-nutricionales requeridos
if (!isset($data['name']) || !isset($data['kcals']) || !isset($data['protein']) || !isset($data['carbs']) || !isset($data['fat'])) {
    Response::error('Faltan datos obligatorios (nombre o macronutrientes)', 400);
}

// Límites de validación: los valores ingresados son totales de la PORCIÓN del usuario,
// no por 100g. Se usan límites amplios pero físicamente imposibles de superar en condiciones
// normales, para filtrar únicamente errores de tipeo (ej. un 0 de más).
const KCAL_MAX  = 99999;  // kcal por porción: suficiente para cualquier alimento real
const MACRO_MAX = 99999;  // g por porción: suficiente para cualquier alimento real

$kcals   = (float) $data['kcals'];
$protein = (float) $data['protein'];
$carbs   = (float) $data['carbs'];
$fat     = (float) $data['fat'];

if ($protein < 0 || $carbs < 0 || $fat < 0) {
    Response::error('Las proteínas, carbohidratos y grasas no pueden ser valores negativos', 422);
}
if ($kcals < -KCAL_MAX || $kcals > KCAL_MAX) {
    Response::error('Las calorías deben estar entre -' . KCAL_MAX . ' y ' . KCAL_MAX . ' kcal', 422);
}
if ($protein > MACRO_MAX || $carbs > MACRO_MAX || $fat > MACRO_MAX) {
    Response::error('Los macronutrientes no pueden superar ' . MACRO_MAX . ' g', 422);
}

// Mapeamos las claves del frontend a las esperadas por el modelo para evitar que se guarden en 0
$data['prot']    = $protein;
$data['carbo']   = $carbs;
$data['gras']    = $fat;
$data['kcals']   = $kcals;
$data['ID_USER'] = $authUser['id']; // Guardamos como personalizado de este usuario

$model  = new IngredienteModel();
$result = $model->create($data);

if ($result['success']) {
    Response::success([
        'id' => $result['id']
    ], 201, $result['message']);
} else {
    // Si ya existe (duplicate = true), devolvemos HTTP 409 (Conflict) en lugar de error de servidor
    $statusCode = !empty($result['duplicate']) ? 409 : 500;
    Response::error($result['message'], $statusCode);
}
