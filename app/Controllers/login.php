<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'conexion.php'; 

$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password)) {
    $email = mysqli_real_escape_string($conexion, $data->email);
    $password = mysqli_real_escape_string($conexion, $data->password);
    
    // 1. Buscamos al usuario usando la columna 'email'
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // 2. Comparamos la contraseña usando tu columna 'clave'
        if ($user['clave'] === $password) { 
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "El usuario no existe"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
}
?>