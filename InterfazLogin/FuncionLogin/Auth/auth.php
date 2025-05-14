<?php
session_start();

function isUserAuthenticated() {
    return isset($_SESSION['usuario']);
}

// Devuelve 1 si el usuario está autenticado, 0 si no lo está
//echo isUserAuthenticated() ? "1" : "0";
if (!isUserAuthenticated()) {
    header("Location: http://localhost/Eneproyect/InterfazLogin/FuncionLogin/login.html");
    exit();    
}

?>
