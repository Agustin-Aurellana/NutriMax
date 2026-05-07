<?php
// Establecer la conexión con la base de datos MySQL (host, usuario, contraseña, nombre de base de datos)
$conexion = mysqli_connect("localhost", "root", "", "nutrimax");

// Si la conexión falla, se detiene el script y muestra el error
if (!$conexion) { 
    die("No pude conectar: " . mysqli_connect_error()); 
}
?>