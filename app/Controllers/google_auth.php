<?php
// Permitir solicitudes CORS y configurar la respuesta en formato JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir el archivo de conexión a la base de datos
include __DIR__ . '/../../config/conexion.php'; 

// Recibir los datos enviados por el frontend (fetch API)
$data = json_decode(file_get_contents("php://input"));

// Verificar si se ha recibido la credencial (JWT) de Google
if(isset($data->credential)) {
    $jwt = $data->credential;
    
    // Validar el token directamente mediante el endpoint oficial de Google
    $verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
    $response = @file_get_contents($verify_url);
    
    // Si la validación falla (ej. token expirado o falso), devolver un error
    if ($response === FALSE) {
        echo json_encode(["status" => "error", "message" => "Token de Google inválido"]);
        exit;
    }

    // Decodificar los datos del perfil de Google
    $payload = json_decode($response);

    // Verificar si el token proporciona un email válido
    if (isset($payload->email)) {
        // Sanitizar los datos recibidos de Google
        $email = mysqli_real_escape_string($conexion, $payload->email);
        $nombre = mysqli_real_escape_string($conexion, $payload->name);
        
        // Buscar en la base de datos si el usuario ya está registrado
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($result) > 0) {
            // El usuario ya existe en NutriMax: Iniciar sesión devolviendo sus datos
            $user = mysqli_fetch_assoc($result);
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            // El usuario no existe en NutriMax
            // Se devuelven los datos básicos obtenidos de Google para que el frontend pida 
            // los datos restantes (peso, altura, etc.) en un formulario.
            echo json_encode([
                "status" => "incomplete", 
                "message" => "Faltan datos físicos para completar el registro",
                "partial_user" => [
                    "name" => $nombre, 
                    "email" => $email
                ]
            ]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No se pudo obtener el email de Google"]);
    }
} else {
    // La petición no incluye la credencial requerida
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
}
?>
