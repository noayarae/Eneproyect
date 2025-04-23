<?php
require('../../conexion.php');

function hallarNombre($token) {
    global $conn; // Usamos la conexión de `conexion.php`
    $stmt = $conn->prepare("SELECT Usuario FROM wp_employees WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['Usuario'];
    } else {
        return "Usuario";
    }
}
?>
