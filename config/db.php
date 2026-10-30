<?php

/**
 * db.php — Alias legado de la conexión PDO a PostgreSQL
 *
 * Originalmente contenía una conexión procedural mysqli a MySQL local.
 * Ahora delega en Database::getConnection() (singleton PDO/PostgreSQL)
 * para mantener retrocompatibilidad sin duplicar lógica de conexión.
 *
 * Si algún controlador antiguo hacía require_once de este archivo y usaba
 * $conexion directamente, seguirá funcionando: recibe el mismo objeto PDO
 * que devuelve el singleton.
 */
require_once __DIR__ . '/../app/Models/Database.php';

// Exponemos la conexión PDO como $conexion para retrocompatibilidad
$conexion = Database::getConnection();
