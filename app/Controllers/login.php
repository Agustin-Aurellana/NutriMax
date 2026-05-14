<?php
/**
 * login.php — Controlador de autenticación
 *
 * Responsabilidad: Validar la petición HTTP, delegar la lógica de negocio
 * al UserModel y devolver una respuesta JSON estandarizada.
 * Ya NO contiene ninguna query SQL directa.
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Cargar el Modelo de Usuario (que a su vez carga Database.php)
require_once __DIR__ . '/../Models/UserModel.php';

// Decodificar el JSON enviado desde el frontend
$data = json_decode(file_get_contents("php://input"));

// Validar que se hayan recibido las credenciales mínimas
if (!isset($data->email) || !isset($data->password)) {
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
    exit;
}

$userModel = new UserModel();

// Delegar la búsqueda al Modelo — sin SQL en el controlador
$user = $userModel->findByEmail($data->email);

if ($user === null) {
    echo json_encode(["status" => "error", "message" => "El usuario no existe"]);
    exit;
}

// Verificar la contraseña contra el hash almacenado
if (!password_verify($data->password, $user['clave'])) {
    echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
    exit;
}

// Credenciales válidas: retornar los datos del usuario al frontend
echo json_encode(["status" => "success", "user" => $user]);
