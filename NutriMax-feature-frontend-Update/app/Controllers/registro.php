<?php
// Permitir solicitudes desde cualquier origen y definir formato JSON para la respuesta
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir la conexión a la base de datos
include __DIR__ . '/../../config/conexion.php';

// Leer el JSON entrante desde la petición del frontend
$data = json_decode(file_get_contents("php://input"));

// Verificar que al menos el email haya sido enviado
if(isset($data->email)) {
    // Sanitizar y preparar las variables para prevenir inyecciones SQL
    $nombre     = mysqli_real_escape_string($conexion, $data->name);
    $email      = mysqli_real_escape_string($conexion, $data->email);
    // Encriptar la contraseña usando el algoritmo por defecto de PHP (Bcrypt)
    $password   = password_hash($data->password, PASSWORD_DEFAULT); 
    $sexo       = mysqli_real_escape_string($conexion, $data->sex);
    $nacimiento = mysqli_real_escape_string($conexion, $data->birthDate);
    $peso       = (float)$data->weight;
    $altura     = (float)$data->height;
    
    // Valores por defecto para nuevos usuarios
    $actividad  = 3; 
    $objetivo   = 'definition';

    // Verificar si el correo ya existe en la base de datos
    $verificar = mysqli_query($conexion, "SELECT * FROM users WHERE Email='$email'");

    if (mysqli_num_rows($verificar) > 0) {
        // Si el usuario ya existe, devolver un error
        echo json_encode(["status" => "error", "message" => "Este correo ya está registrado"]);
    } else {
        // Insertar el nuevo usuario en la tabla 'users'
        $query = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm , act_fisica, objetivo) 
                  VALUES ('$nombre', '$email', '$password', '$nacimiento', '$sexo', '$peso', '$altura', '$actividad', '$objetivo')";
        
        if (mysqli_query($conexion, $query)) {
            // Devolver éxito para que el frontend pueda iniciar sesión automáticamente
            echo json_encode(["status" => "success"]);
        } else {
            // Error en la inserción (ej. fallo de la base de datos)
            echo json_encode(["status" => "error", "message" => "Error interno en BD: " . mysqli_error($conexion)]);
        }
    }
} else {
    // Faltan campos obligatorios en el JSON recibido
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
}
?>
