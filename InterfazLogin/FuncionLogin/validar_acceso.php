<?php
session_start();
require '../conexion.php';

error_log("INICIO validar_acceso.php - Datos SESIÓN: ".print_r($_SESSION, true));

// Verificación mejorada de sesión
if (!isset($_SESSION['User_ID'])) {  // Usar User_ID que es más único
    $_SESSION['mensaje'] = "Sesión no iniciada o expirada.";
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

// Verificar conexión a la base de datos
if (!$conn) {
    $_SESSION['mensaje'] = "Error de conexión a la base de datos.";
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$dni = $_SESSION['dni'];

// Primera validación: estado de la cuenta
$stmt = $conn->prepare("SELECT estado FROM wp_employees WHERE dni = ?");
if (!$stmt) {
    $_SESSION['mensaje'] = "Error en la base de datos: " . $conn->error;
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$stmt->bind_param("s", $dni);
if (!$stmt->execute()) {
    $_SESSION['mensaje'] = "Error al verificar estado de cuenta.";
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "Usuario no encontrado.";
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$row = $result->fetch_assoc();
$estado = $row['estado'];

if ($estado !== 'aprobado') {
    $_SESSION['mensaje'] = "Tu cuenta aún está en proceso de verificación.";
    $_SESSION['tipo'] = "warning";
    $_SESSION['redirect'] = "logout.php"; 
    header("Location: mensaje.php");
    exit();
}

// Segunda validación: datos personales
$stmt2 = $conn->prepare("SELECT telefono, direccion FROM wp_datos_usuarios WHERE dni = ?");
if (!$stmt2) {
    $_SESSION['mensaje'] = "Error en la base de datos (segunda consulta): " . $conn->error;
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$stmt2->bind_param("s", $dni);
if (!$stmt2->execute()) {
    $_SESSION['mensaje'] = "Error al verificar datos personales.";
    $_SESSION['tipo'] = "error";
    $_SESSION['redirect'] = "login.php";
    header("Location: mensaje.php");
    exit();
}

$result2 = $stmt2->get_result();

if ($result2->num_rows === 0) {
    $_SESSION['sweet_alert'] = [
        'title' => 'Datos requeridos',
        'text' => 'Debes completar tus datos personales para continuar.',
        'icon' => 'warning',
        'confirmButtonText' => 'Completar datos',
        'cancelButtonText' => 'Cerrar sesión',
        'confirmAction' => 'llenar_datos.html',
        'cancelAction' => 'logout.php'
    ];
    header("Location: mensaje.php");
    exit();
}

$row2 = $result2->fetch_assoc();

if (empty($row2['telefono']) || empty($row2['direccion'])) {
    $_SESSION['sweet_alert'] = [
        'title' => 'Datos incompletos',
        'text' => 'Por favor completa todos tus datos personales.',
        'icon' => 'warning',
        'confirmButtonText' => 'Completar ahora',
        'cancelButtonText' => 'Más tarde',
        'confirmAction' => 'llenar_datos.html',
        'cancelAction' => 'login.html' 
    ];
    header("Location: mensaje.php");
    exit();
}

// Si todo está correcto, redirigir directamente al home
$_SESSION['telefono'] = $row2['telefono'];
$_SESSION['direccion'] = $row2['direccion'];

// Cerrar recursos
$stmt->close();
$stmt2->close();
$conn->close();

// Redirigir DIRECTAMENTE al home sin pasar por mensaje.php
header("Location: ../home.php");
exit();
?>