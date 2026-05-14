<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include __DIR__ . '/../../config/conexion.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->email)) {
    $nombre     = mysqli_real_escape_string($conexion, $data->name);
    $email      = mysqli_real_escape_string($conexion, $data->email);
    $password   = password_hash($data->password, PASSWORD_DEFAULT);
    $sexo       = mysqli_real_escape_string($conexion, $data->sex);
    $nacimiento = mysqli_real_escape_string($conexion, $data->birthDate);
    $peso       = (float)$data->weight;
    $altura     = (float)$data->height;

    // Recibidos del Wizard (ya no hardcodeados)
    $VALID_ACTIVITIES = ['sedentary','light','moderate','active','very_active'];
    $VALID_GOALS      = ['definition','volume','maintenance','recomp','custom'];

    $actividadRaw = isset($data->activityLevel) ? $data->activityLevel : 'moderate';
    $objetivoRaw  = isset($data->goal)          ? $data->goal          : 'maintenance';

    $actividad = in_array($actividadRaw, $VALID_ACTIVITIES) ? mysqli_real_escape_string($conexion, $actividadRaw) : 'moderate';
    $objetivo  = in_array($objetivoRaw, $VALID_GOALS)      ? mysqli_real_escape_string($conexion, $objetivoRaw)  : 'maintenance';

    $verificar = mysqli_query($conexion, "SELECT ID_USER FROM users WHERE Email='$email'");

    if (mysqli_num_rows($verificar) > 0) {
        echo json_encode(["status" => "error", "message" => "Este correo ya está registrado"]);
    } else {
        $query = "INSERT INTO users (name, email, clave, nacimiento, genero, peso, altura_cm, act_fisica, objetivo)
                  VALUES ('$nombre','$email','$password','$nacimiento','$sexo','$peso','$altura','$actividad','$objetivo')";

        if (mysqli_query($conexion, $query)) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error BD: " . mysqli_error($conexion)]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
}
?>
