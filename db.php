<?php

require_once __DIR__ . '/config/env.php';

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME');
$port = (int) (getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die('Error de conexion: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

?>
 