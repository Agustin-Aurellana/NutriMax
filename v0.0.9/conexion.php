<?php
$conexion = mysqli_connect("localhost", "root", "", "nutrimax");

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>