<?php
// Configuración de la conexión a la base de datos
$servidor = "localhost";
$usuario = "root";
$contrasena = ""; 
$baseDatos = "";

// Crear la conexión
$conn = new mysqli($servidor, $usuario, $contrasena, $baseDatos);

// Verificar la conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
} 
?>
