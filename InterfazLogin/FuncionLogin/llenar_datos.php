<?php
require '../conexion.php'; // Conexión a MySQL

session_start();
if (!isset($_SESSION['dni'])) {
    echo json_encode(["status" => "error", "message" => "No tienes autorización para hacer esto."]);
    exit;
}

$dni = $_SESSION['dni'];
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$direccion = $_POST['direccion'] ?? null;

// Validar que todos los campos estén llenos
if (!$fecha_nacimiento || !$telefono || !$direccion) {
    echo json_encode(["status" => "error", "message" => "Todos los campos son obligatorios."]);
    exit;
}

// Validar que la fecha no sea futura
$fechaActual = date("Y-m-d"); // Obtiene la fecha actual en formato "YYYY-MM-DD"

if ($fecha_nacimiento > $fechaActual) {
    echo json_encode(["status" => "error", "message" => "La fecha de nacimiento no puede ser en el futuro."]);
    exit;
}

// Expresión regular para validar el teléfono con o sin prefijo internacional
$telefonoRegex = '/^(\+\d{1,3}\s?)?\d{9}$/';

if (!preg_match($telefonoRegex, $telefono)) {
    echo json_encode(["status" => "error", "message" => "El teléfono debe tener 9 dígitos y puede incluir un prefijo internacional (ejemplo: +51 912345678 o 912345678)."]);
    exit;
}

// Preparar la consulta para evitar inyección SQL
$query = "UPDATE wp_datos_usuarios SET fecha_nacimiento = ?, telefono = ?, direccion = ? WHERE dni = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $fecha_nacimiento, $telefono, $direccion, $dni);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Datos actualizados correctamente."]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al actualizar los datos."]);
}

$stmt->close();
$conn->close();
?>
