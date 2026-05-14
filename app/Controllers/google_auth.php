<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include __DIR__ . '/../../config/conexion.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->credential)) {
    echo json_encode(["status" => "error", "message" => "Faltan datos de acceso"]);
    exit;
}

$jwt        = $data->credential;
$verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
$response   = @file_get_contents($verify_url);

if ($response === FALSE) {
    echo json_encode(["status" => "error", "message" => "Token de Google inválido"]);
    exit;
}

$payload = json_decode($response);

if (!isset($payload->email)) {
    echo json_encode(["status" => "error", "message" => "No se pudo obtener el email de Google"]);
    exit;
}

$email  = mysqli_real_escape_string($conexion, $payload->email);
$nombre = mysqli_real_escape_string($conexion, $payload->name);

$sql    = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conexion, $sql);

if (mysqli_num_rows($result) > 0) {
    // ── Usuario existente: login normal ──
    $user = mysqli_fetch_assoc($result);
    echo json_encode(["status" => "success", "user" => $user]);
    exit;
}

// ── Usuario nuevo: intentar registro completo con datos del Wizard ──
if (isset($data->biometricData)) {
    $b = $data->biometricData;

    $VALID_ACTIVITIES = ['sedentary','light','moderate','active','very_active'];
    $VALID_GOALS      = ['definition','volume','maintenance','recomp','custom'];

    $nacimiento = isset($b->birthDate)     ? mysqli_real_escape_string($conexion, $b->birthDate)     : null;
    $sexo       = isset($b->sex)           ? mysqli_real_escape_string($conexion, $b->sex)           : 'male';
    $peso       = isset($b->weight)        ? (float)$b->weight                                       : 70;
    $altura     = isset($b->height)        ? (float)$b->height                                       : 170;
    $actividad  = (isset($b->activityLevel) && in_array($b->activityLevel, $VALID_ACTIVITIES))
                    ? mysqli_real_escape_string($conexion, $b->activityLevel) : 'moderate';
    $objetivo   = (isset($b->goal) && in_array($b->goal, $VALID_GOALS))
                    ? mysqli_real_escape_string($conexion, $b->goal) : 'maintenance';

    $nacStr = $nacimiento ? "'$nacimiento'" : "NULL";

    $query = "INSERT INTO users (name, email, nacimiento, genero, peso, altura_cm, act_fisica, objetivo)
              VALUES ('$nombre','$email',$nacStr,'$sexo','$peso','$altura','$actividad','$objetivo')";

    if (mysqli_query($conexion, $query)) {
        $newId  = mysqli_insert_id($conexion);
        $newRes = mysqli_query($conexion, "SELECT * FROM users WHERE ID_USER=$newId");
        $newUser = mysqli_fetch_assoc($newRes);
        echo json_encode(["status" => "success", "user" => $newUser, "isNew" => true]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error BD: " . mysqli_error($conexion)]);
    }
} else {
    // Sin datos biométricos → pedir al frontend que complete el registro
    echo json_encode([
        "status"       => "incomplete",
        "message"      => "Faltan datos físicos para completar el registro",
        "partial_user" => ["name" => $nombre, "email" => $email]
    ]);
}
?>
