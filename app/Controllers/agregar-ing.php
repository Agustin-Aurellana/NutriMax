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

// Validamos límites: negativos y rangos máximos compatibles con MySQL DECIMAL(8,2) / DECIMAL(6,2)
// Valores fuera de rango son físicamente imposibles y causarían truncamientos en DB.
const KCAL_MAX  = 9999;   // kcal/100g: máximo físicamente posible (aceite puro ≈ 900)
const MACRO_MAX = 1000;   // g/100g: imposible superar 100g/100g en macros reales

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
    Response::error('Los macronutrientes no pueden superar ' . MACRO_MAX . ' g por 100g', 422);
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
