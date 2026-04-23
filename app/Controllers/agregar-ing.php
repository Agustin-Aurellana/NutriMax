<?php
// Incluimos tu archivo de conexión
require __DIR__ . '/../../config/conexion.php';

// Configuramos la cabecera para devolver JSON (lo que espera JavaScript)
header('Content-Type: application/json');

// Obtenemos los datos que enviará el JavaScript mediante fetch()
$data = json_decode(file_get_contents("php://input"), true);

// Verificamos que al menos el nombre y las calorías estén presentes
if(isset($data['name']) && isset($data['kcals'])) {
    $name = $data['name'];
    $kcals = $data['kcals'];
    // Si no vienen los macros, los ponemos en 0 por defecto
    $prot = isset($data['prot']) ? $data['prot'] : 0;
    $carbo = isset($data['carbo']) ? $data['carbo'] : 0;
    $gras = isset($data['gras']) ? $data['gras'] : 0;
    
    // Si el usuario está logueado, pasas su ID; de lo contrario, queda nulo
    $id_user = isset($data['ID_USER']) ? $data['ID_USER'] : null;

    // Preparamos la consulta SQL para evitar inyecciones SQL (seguridad)
    $stmt = $conexion->prepare("INSERT INTO ingredientes (name, kcals, prot, carbo, gras, ID_USER) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Asignamos los valores (s = string, d = double/decimal)
    $stmt->bind_param("sdddds", $name, $kcals, $prot, $carbo, $gras, $id_user);

    if($stmt->execute()) {
        // Éxito: Devolvemos el ID que se acaba de crear
        echo json_encode(["status" => "success", "id" => $conexion->insert_id, "mensaje" => "Ingrediente guardado correctamente"]);
    } else {
        // Error al guardar
        echo json_encode(["status" => "error", "mensaje" => "Error al ejecutar la consulta"]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos obligatorios"]);
}

$conexion->close();
?>
