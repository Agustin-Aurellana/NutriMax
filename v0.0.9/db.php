<?php
$conexion = mysqli_connect("localhost", "root", "", "nutrimax");
// Si falla, que nos avise
if (!$conexion) { die("No pude conectar: " . mysqli_connect_error()); }
?>