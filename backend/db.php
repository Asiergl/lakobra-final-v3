<?php
// db.php
$host = 'localhost'; // En XAMPP siempre es localhost
$user = 'root';      // El usuario por defecto en XAMPP
$pass = '';          // En XAMPP la contraseña suele estar vacía
$db   = 'lakobra';   // PON AQUÍ EL NOMBRE DE TU BASE DE DATOS DE LAKOBRA

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

// Forzar UTF-8 para no tener problemas con acentos
$mysqli->set_charset("utf8mb4");
?>