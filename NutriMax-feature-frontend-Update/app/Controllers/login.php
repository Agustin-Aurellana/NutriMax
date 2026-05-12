<?php
// Permitir solicitudes desde cualquier origen (CORS) y definir el formato de respuesta como JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Incluir el archivo de conexión a la base de datos
include __DIR__ . '/../../config/conexion.php'; 

// Decodificar el JSON enviado desde el frontend (fetch o axios)
$data = json_decode(file_get_contents("php://input"));

// Verificar que se hayan recibido las credenciales (email y contraseña)
if(isset($data->email) && isset($data->password)) {
    // Escapar los datos para prevenir inyecciones SQL
    $email = mysqli_real_escape_string($conexion, $data->email);
    $password = mysqli_real_escape_string($conexion, $data->password);
    
    // Buscar al usuario en la base de datos utilizando su email
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Comparar la contraseña ingresada con el hash almacenado en la columna 'clave'
        if (password_verify($password, $user['clave'])) { 
            // Las contraseñas coinciden: devolver datos del usuario (sesión exitosa)
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            // Contraseña incorrecta
            echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
        }
    } else {
        // No se encontró ningún usuario con ese email
        echo json_encode(["status" => "error", "message" => "El usuario no existe"]);
    }
} else {
    // La petición no incluye todos los datos requeridos
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
}
?>
