<?php
/**
 * actualizar-perfil.php — GET /api/v1/actualizar-perfil | PUT /api/v1/actualizar-perfil
 * Ruta protegida: requiere JWT válido en Authorization header.
 *
 * GET: Devuelve el perfil completo del usuario autenticado (sin clave) desde MySQL.
 * PUT: Actualiza el perfil en MySQL y sincroniza automáticamente el peso del día en registro_diario.
 */
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/RegistroDiarioModel.php';

// Verificar JWT — si falla, Auth::requireAuth() responde 401 y detiene la ejecución
$authUser = Auth::requireAuth();
$userModel = new UserModel();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Obtener perfil activo del usuario autenticado ──
if ($method === 'GET') {
    $user = $userModel->findByEmail($authUser['email']);

    if (!$user) {
        Response::error('Usuario no encontrado', 404);
    }

    // Por seguridad, remover hash de contraseña antes de responder
    unset($user['clave']);

    Response::success(['user' => $user], 200, 'Perfil obtenido correctamente');
}

// ── PUT: Actualizar perfil y sincronizar peso en BD ──
if ($method !== 'PUT') {
    Response::error('Método no permitido', 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    Response::error('Datos inválidos o cuerpo de solicitud vacío', 400);
}

// Determinar el email objetivo: por seguridad, debe coincidir con el token
$targetEmail = $data['email'] ?? $authUser['email'];

if ($targetEmail !== $authUser['email']) {
    Response::error('No autorizado para modificar este perfil', 403);
}

// Recuperar el registro actual para permitir actualizaciones parciales sin perder datos previos
$existingUser = $userModel->findByEmail($targetEmail);

if (!$existingUser) {
    Response::error('Usuario no encontrado', 404);
}

// CP-REG-21: Validación de longitud para evitar desbordamiento en varchar(50) de MySQL
if (isset($data['name']) && mb_strlen(trim($data['name']), 'UTF-8') > 50) {
    Response::error('El nombre no puede superar los 50 caracteres', 400);
}

$activity_map = [
    'sedentary'  => 0, 'sedentario' => 0,
    'light'      => 2, 'ligero'     => 2,
    'moderate'   => 4, 'moderado'   => 4,
    'active'     => 6, 'activo'     => 6,
    'veryactive' => 7, 'very_active' => 7, 'muy activo' => 7,
];

// Mapeo o preservación del nivel de actividad física
if (isset($data['activityLevel'])) {
    $nivel_texto    = strtolower((string)$data['activityLevel']);
    $act_fisica_int = $activity_map[$nivel_texto] ?? (is_numeric($data['activityLevel']) ? (int)$data['activityLevel'] : (int)($existingUser['act_fisica'] ?? 0));
} else {
    $act_fisica_int = (int)($existingUser['act_fisica'] ?? 0);
}

// Validación y fusión del peso (soporta tanto actualización completa como atómica)
$weight = isset($data['weight']) ? (float)$data['weight'] : (float)($existingUser['peso'] ?? 0);
if ($weight < 0 || (isset($data['weight']) && ($weight < 20 || $weight > 400))) {
    Response::error('El peso debe estar entre 20 kg y 400 kg', 400);
}

$height = isset($data['height']) ? (float)$data['height'] : (float)($existingUser['altura_cm'] ?? 0);
if ($height < 0) {
    Response::error('La altura no puede ser negativa', 400);
}

$name      = isset($data['name']) ? trim($data['name']) : ($existingUser['name'] ?? '');
$birthDate = isset($data['birthDate']) ? $data['birthDate'] : ($existingUser['nacimiento'] ?? '');
$sex       = isset($data['sex']) ? $data['sex'] : ($existingUser['genero'] ?? 'M');
$goal      = isset($data['goal']) ? $data['goal'] : ($existingUser['objetivo'] ?? 'maintenance');

// Actualizar tabla 'users' en MySQL
$result = $userModel->updateProfile($targetEmail, [
    'name'          => $name,
    'birthDate'     => $birthDate,
    'sex'           => $sex,
    'weight'        => $weight,
    'activityLevel' => $act_fisica_int,
    'goal'          => $goal,
    'height'        => $height,
]);

if (!$result['success']) {
    Response::error($result['message'], 500);
}

// ── Sincronización cruzada: actualizar registro_diario con el nuevo peso ──
// Garantiza que el peso configurado en Goals o Perfil quede persistido en la
// tabla registro_diario para la fecha de hoy, alimentando inmediatamente Stats y Dashboard.
if ($weight > 0 && !empty($existingUser['ID_USER'])) {
    $registroModel = new RegistroDiarioModel();
    $today = date('Y-m-d');
    $regResult = $registroModel->getOrCreate($existingUser['ID_USER'], $today, $weight);
    if ($regResult['success'] && !$regResult['created']) {
        $registroModel->updatePeso($regResult['id'], $existingUser['ID_USER'], $weight);
    }
}

Response::success([
    'email'  => $targetEmail,
    'weight' => $weight
], 200, $result['message']);

