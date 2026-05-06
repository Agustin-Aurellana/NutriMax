<?php
// Incluir el archivo de conexión a la base de datos
require __DIR__ . '/../../config/conexion.php';

// Definir el formato de respuesta como JSON
header('Content-Type: application/json');

// Obtener los datos enviados por la petición
$data = json_decode(file_get_contents("php://input"), true);

// Verificar si se ha proporcionado el ID del ingrediente a eliminar
if(isset($data['id'])) {
    $id = $data['id'];

    // Preparar la consulta SQL DELETE para evitar inyecciones SQL
    $stmt = $conexion->prepare("DELETE FROM ingredientes WHERE ID = ?");
    
    // Vincular el parámetro: 'i' indica que el ID debe ser tratado como un número entero (integer)
    $stmt->bind_param("i", $id); 

    // Ejecutar la eliminación
    if($stmt->execute()) {
        echo json_encode(["status" => "success", "mensaje" => "Ingrediente eliminado"]);
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Error al intentar eliminar"]);
    }
    
    // Cerrar el statement para liberar recursos
    $stmt->close();
} else {
    // Si no se envió un ID, devolver error
    echo json_encode(["status" => "error", "mensaje" => "No se proporcionó el ID del ingrediente"]);
}

// Cerrar la conexión a la base de datos
$conexion->close();
?>
