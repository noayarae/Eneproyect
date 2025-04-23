<?php
session_start();
require '../conexion.php';

// Verificar conexión a la base de datos
if (!$conn) {
    $_SESSION['mensaje'] = "Error de conexión a la base de datos.";
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

// Verificar sesión
if (!isset($_SESSION['dni']) || !isset($_SESSION['usuario'])) {
    $_SESSION['mensaje'] = "Acceso no autorizado.";
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

$dni = $_SESSION['dni'];

// Primera validación: estado de la cuenta
$stmt = $conn->prepare("SELECT estado FROM wp_employees WHERE dni = ?");
if (!$stmt) {
    $_SESSION['mensaje'] = "Error en la base de datos: " . $conn->error;
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

$stmt->bind_param("s", $dni);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "Usuario no encontrado.";
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

$row = $result->fetch_assoc();
$estado = $row['estado'];

if ($estado !== 'aprobado') {
    $_SESSION['mensaje'] = "Tu cuenta aún está en proceso de verificación.";
    $_SESSION['tipo'] = "warning";
    $_SESSION['redirect'] = "https://eneproyect.com/";
    header("Location: mensaje.php");
    exit();
}

// Segunda validación: datos personales
$stmt2 = $conn->prepare("SELECT telefono, direccion FROM wp_datos_usuarios WHERE dni = ?");
if (!$stmt2) {
    $_SESSION['mensaje'] = "Error en la base de datos (segunda consulta): " . $conn->error;
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

$stmt2->bind_param("s", $dni);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows === 0) {
    // No hay datos registrados
    $_SESSION['mensaje'] = "Por favor completa tus datos personales.";
    $_SESSION['tipo'] = "warning";
    $_SESSION['redirect'] = "llenar_datos.html";
    header("Location: mensaje.php");
    exit();
}

$row2 = $result2->fetch_assoc();

if (empty($row2['telefono']) || empty($row2['direccion'])) {
    // Usar SweetAlert a través de mensaje.php en lugar de echo directo
    $_SESSION['sweet_alert'] = [
        'title' => 'Datos incompletos',
        'text' => 'Tus datos no están del todo completos.',
        'icon' => 'warning',
        'confirmButtonText' => 'Llenar datos',
        'cancelButtonText' => 'Salir',
        'confirmAction' => 'llenar_datos.html',
        'cancelAction' => 'logout.php'
    ];
    header("Location: mensaje.php");
    exit();
}

// Todo está correcto
$_SESSION['telefono'] = $row2['telefono'];
$_SESSION['mensaje'] = "Inicio de sesión exitoso. Redirigiendo...";
$_SESSION['tipo'] = "success";
$_SESSION['redirect'] = "/InterfazLogin/home.php";
header("Location: mensaje.php");

// Cerrar conexiones
$stmt->close();
if (isset($stmt2)) $stmt2->close();
$conn->close();
exit();
?>