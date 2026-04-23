<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php'; 

// Recibimos los datos desde Javascript
$data = json_decode(file_get_contents("php://input"));

if(isset($data->credential)) {
    $jwt = $data->credential;
    
    // Verificamos el token directamente con el endpoint de Google (forma nativa sin dependencias extra)
    $verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
    $response = @file_get_contents($verify_url);
    
    if ($response === FALSE) {
        echo json_encode(["status" => "error", "message" => "Token de Google inválido"]);
        exit;
    }

    $payload = json_decode($response);

    // Verificamos que el token pertenezca a tu Client ID (Por seguridad)
    // if ($payload->aud != "TU_CLIENT_ID_DE_GOOGLE.apps.googleusercontent.com") { die(); }

    if (isset($payload->email)) {
        $email = mysqli_real_escape_string($conexion, $payload->email);
        $nombre = mysqli_real_escape_string($conexion, $payload->name);
        
        // 1. Buscamos si el usuario ya existe en NutriMax
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conexion, $sql);

        if (mysqli_num_rows($result) > 0) {
            // EL USUARIO EXISTE: Iniciar sesión exitosamente
            $user = mysqli_fetch_assoc($result);
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            // EL USUARIO NO EXISTE: Faltan datos como peso y altura
            // Devolvemos la info básica para que el frontend le pida el resto
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
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
}
?>