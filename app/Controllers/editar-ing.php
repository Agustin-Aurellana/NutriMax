<?php
/**
 * editar-ing.php — PUT /api/v1/editar-ing
 *
 * Controlador del endpoint para actualizar un ingrediente personalizado existente.
 * Ejecuta un UPDATE referenciando el ID único del ingrediente, garantizando que
 * nunca se crea un registro duplicado al editar.
 *
 * Reglas de negocio:
 *   - Solo el propietario (ID_USER = usuario autenticado) puede editar su ingrediente.
 *   - Los ingredientes globales (ID_USER IS NULL) son inmutables desde esta vía.
 *   - El ID del ingrediente debe existir y pertenecer al usuario autenticado.
 */
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Models/IngredienteModel.php';

// Este endpoint solo acepta PUT (semántica correcta para actualización parcial/total de un recurso)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

// Extraemos el usuario autenticado para validar la propiedad del ingrediente en el modelo
$authUser = Auth::requireAuth();
$userId   = $authUser['id'];

$data = json_decode(Request::body(), true);

// Validamos que se provea el ID del ingrediente a editar
if (empty($data['id'])) {
    Response::error('Falta el campo id del ingrediente a editar', 400);
}

// Validamos que se provean los campos nutricionales obligatorios
if (!isset($data['name']) || !isset($data['kcals']) || !isset($data['protein']) || !isset($data['carbs']) || !isset($data['fat'])) {
    Response::error('Faltan datos obligatorios (nombre o macronutrientes)', 400);
}

$ingId = (int) $data['id'];

// Mapeamos las claves del frontend (camelCase) al formato interno del modelo
$payload = [
    'name'  => trim($data['name']),
    'kcals' => (float) $data['kcals'],
    'prot'  => (float) $data['protein'],
    'carbo' => (float) $data['carbs'],
    'gras'  => (float) $data['fat'],
];

if (empty($payload['name'])) {
    Response::error('El nombre del ingrediente no puede estar vacío', 400);
}

// Rangos máximos compatibles con MySQL DECIMAL — mismos límites que agregar-ing.php
// para garantizar consistencia en todo el ciclo de vida del ingrediente.
if ($payload['prot'] < 0 || $payload['carbo'] < 0 || $payload['gras'] < 0) {
    Response::error('Las proteínas, carbohidratos y grasas no pueden ser valores negativos', 422);
}
if ($payload['kcals'] < -9999 || $payload['kcals'] > 9999) {
    Response::error('Las calorías deben estar entre -9999 y 9999 kcal', 422);
}
if ($payload['prot'] > 1000 || $payload['carbo'] > 1000 || $payload['gras'] > 1000) {
    Response::error('Los macronutrientes no pueden superar 1000 g por 100g', 422);
}

$model  = new IngredienteModel();
$result = $model->update($ingId, $userId, $payload);

if ($result['success']) {
    Response::success(null, 200, $result['message']);
} else {
    // Distinguimos entre "sin permisos" (403) y errores de servidor (500)
    $code = str_contains($result['message'], 'permisos') ? 403 : 500;
    Response::error($result['message'], $code);
}
