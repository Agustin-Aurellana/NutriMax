<?php
/**
 * actualizar-perfil.php — PUT /api/v1/actualizar-perfil
 * Ruta protegida: requiere JWT válido en Authorization header.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Método no permitido', 405);
}

// Verificar JWT — si falla, Auth::requireAuth() responde 401 y sale
$authUser = Auth::requireAuth();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'])) {
    Response::error('Datos inválidos o incompletos', 400);
}

// Seguridad: el email a actualizar debe coincidir con el del token
// Esto evita que un usuario actualice el perfil de otro
if ($data['email'] !== $authUser['email']) {
    Response::error('No autorizado para modificar este perfil', 403);
}

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
