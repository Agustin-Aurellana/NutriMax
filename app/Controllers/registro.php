<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include __DIR__ . '/../../config/conexion.php';

// Leemos el JSON entrante desde Javascript
$data = json_decode(file_get_contents("php://input"));

if(isset($data->email)) {
    $nombre     = mysqli_real_escape_string($conexion, $data->name);
    $email      = mysqli_real_escape_string($conexion, $data->email);
    $password   = mysqli_real_escape_string($conexion, $data->password); 
    $sexo       = mysqli_real_escape_string($conexion, $data->sex);
    
    // CAMBIO 1: Ahora capturamos el 'birthDate' en lugar del 'age'
    $nacimiento = mysqli_real_escape_string($conexion, $data->birthDate);
    
    $peso       = (float)$data->weight;
    $altura     = (float)$data->height;
    $actividad  = 'moderate'; // Valor por defecto
    $objetivo   = 'definition'; // Valor por defecto

    // Consultar si el mail ya existe
    $verificar = mysqli_query($conexion, "SELECT * FROM users WHERE Email='$email'");

    if (mysqli_num_rows($verificar) > 0) {
        echo json_encode(["status" => "error", "message" => "Este correo ya está registrado"]);
    } else {
        // CAMBIO 2: Reemplazamos '$edad' por '$nacimiento' en el VALUES
        $query = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm , act_fisica, objetivo) 
                  VALUES ('$nombre', '$email', '$password', '$nacimiento', '$sexo', '$peso', '$altura', '$actividad', '$objetivo')";
        
        if (mysqli_query($conexion, $query)) {
            // Devolvemos success para que el Frontend inicie sesión automáticamente
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error interno en BD: " . mysqli_error($conexion)]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
}
?>
