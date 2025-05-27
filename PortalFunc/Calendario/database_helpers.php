<?php
function clienteExiste($conn, $dni, $usuarioId) {
    $stmt = $conn->prepare("SELECT 1 FROM clientes WHERE dni = ? AND usuario_id = ?");
    $stmt->bind_param("ss", $dni, $usuarioId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function eventoPerteneceAUsuario($conn, $eventoId, $usuarioId) {
    $stmt = $conn->prepare("SELECT 1 FROM eventos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("is", $eventoId, $usuarioId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function validarFormatoFechaHora($fecha, $hora) {
    return DateTime::createFromFormat('Y-m-d', $fecha) !== false && 
           DateTime::createFromFormat('H:i', $hora) !== false;
}
?>