<?php
require __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['id'])) {
    $id = $data['id'];

    // Preparamos la consulta para eliminar por el ID
    $stmt = $conexion->prepare("DELETE FROM ingredientes WHERE ID = ?");
    $stmt->bind_param("i", $id); // 'i' significa que esperamos un entero (integer)

    if($stmt->execute()) {
        echo json_encode(["status" => "success", "mensaje" => "Ingrediente eliminado"]);
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Error al intentar eliminar"]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["status" => "error", "mensaje" => "No se proporcionó el ID del ingrediente"]);
}

$conexion->close();
?>
