<?php
// Establecer la conexión con la base de datos MySQL (host, usuario, contraseña, nombre de base de datos)
$conexion = mysqli_connect("localhost", "root", "", "nutrimax");

// Verificar si ocurrió un error al intentar conectar y detener la ejecución si falla
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>