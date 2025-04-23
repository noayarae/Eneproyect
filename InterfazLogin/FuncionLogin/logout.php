<?php
session_start();
session_destroy(); // Cerrar sesión
header("Location: login.html"); // Redirigir a la página de inicio de sesión
exit();
?>
