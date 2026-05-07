<?php
// Incluir el archivo de conexión a la base de datos
require __DIR__ . '/../../config/conexion.php';

// Configurar la cabecera para devolver respuestas en formato JSON
header('Content-Type: application/json');

// Obtener y decodificar el cuerpo de la petición (datos del nuevo ingrediente)
$data = json_decode(file_get_contents("php://input"), true);

// Validar que al menos se proporcionen el nombre y las calorías del ingrediente
if(isset($data['name']) && isset($data['kcals'])) {
    $name = $data['name'];
    $kcals = $data['kcals'];
    
    // Si no se envían los macronutrientes, se asume 0 por defecto
    $prot = isset($data['prot']) ? $data['prot'] : 0;
    $carbo = isset($data['carbo']) ? $data['carbo'] : 0;
    $gras = isset($data['gras']) ? $data['gras'] : 0;
    
    // Asociar el ingrediente a un usuario si está logueado, de lo contrario será nulo (público)
    $id_user = isset($data['ID_USER']) ? $data['ID_USER'] : null;

    // Preparar la consulta SQL de inserción para evitar inyecciones SQL
    $stmt = $conexion->prepare("INSERT INTO ingredientes (name, kcals, prot, carbo, gras, ID_USER) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Vincular los parámetros a la consulta:
    // s = string (nombre), d = double/decimal (kcals, macros), s = string (id de usuario)
    $stmt->bind_param("sdddds", $name, $kcals, $prot, $carbo, $gras, $id_user);

    if($stmt->execute()) {
        // La inserción fue exitosa: Devolver el ID generado automáticamente
        echo json_encode(["status" => "success", "id" => $conexion->insert_id, "mensaje" => "Ingrediente guardado correctamente"]);
    } else {
        // Ocurrió un error al ejecutar la inserción
        echo json_encode(["status" => "error", "mensaje" => "Error al ejecutar la consulta"]);
    }
    
    // Cerrar el statement para liberar recursos
    $stmt->close();
} else {
    // Faltan datos obligatorios para crear el ingrediente
    echo json_encode(["status" => "error", "mensaje" => "Faltan datos obligatorios"]);
}

// Cerrar la conexión a la base de datos
$conexion->close();
?>
