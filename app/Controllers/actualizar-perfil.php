<?php
/**
 * actualizar-perfil.php — Controlador de actualización de perfil
 *
 * Método: PUT /api/v1/actualizar-perfil
 * Body:   { "email", "name", "birthDate", "sex", "weight", "activityLevel", "goal", "height" }
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'])) {
    Response::error('Datos inválidos o incompletos', 400);
}

// Mapear niveles de actividad textuales → int (lógica de transformación del Controlador)
$activity_map = [
    'sedentary'  => 0, 'sedentario' => 0,
    'light'      => 2, 'ligero'     => 2,
    'moderate'   => 4, 'moderado'   => 4,
    'active'     => 6, 'activo'     => 6,
    'veryactive' => 7, 'muy activo' => 7,
];

$nivel_texto    = strtolower($data['activityLevel'] ?? '');
$act_fisica_int = $activity_map[$nivel_texto] ?? 0;

$userModel = new UserModel();
$result    = $userModel->updateProfile($data['email'], [
    'name'          => $data['name']      ?? '',
    'birthDate'     => $data['birthDate'] ?? '',
    'sex'           => $data['sex']       ?? '',
    'weight'        => $data['weight']    ?? 0,
    'activityLevel' => $act_fisica_int,
    'goal'          => $data['goal']      ?? '',
    'height'        => $data['height']    ?? 0,
]);

if ($result['success']) {
    Response::success(null, 200, $result['message']);
} else {
    Response::error($result['message'], 500);
}
