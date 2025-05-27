<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('isUserAuthenticated')) {
    function isUserAuthenticated() {
        return isset($_SESSION['usuario']);
    }
}

if (!isUserAuthenticated()) {
    header("Location: http://localhost/Eneproyect/InterfazLogin/FuncionLogin/login.html");
    exit();    
}
?>  