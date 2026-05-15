<?php
// Conexión OOP a MySQL — compatible con prepare(), bind_param() y close()
$conexion = new mysqli("localhost", "root", "", "nutrimax");

// Verificar si ocurrió un error al intentar conectar y detener la ejecución si falla
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Forzar charset UTF-8 para evitar problemas con tildes y caracteres especiales
$conexion->set_charset("utf8mb4");
?>