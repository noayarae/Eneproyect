<?php
session_start();
require '../conexion.php';

// Configurar charset para seguridad
$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['pass']);

    // Validaciones básicas
    if (empty($usuario) || empty($password)) {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
        $_SESSION['tipo'] = "error";
        header("Location: mensaje.php");
        exit();
    }

    // Consulta preparada
    $stmt = $conn->prepare("SELECT id, nombre, apellido, correo, cargo, usuario, dni, password FROM wp_employees WHERE usuario = ?");
    
    if (!$stmt) {
        $_SESSION['mensaje'] = "Error en la base de datos. Inténtalo más tarde.";
        $_SESSION['tipo'] = "error";
        header("Location: mensaje.php");
        exit();
    }

    $stmt->bind_param("s", $usuario);
    
    if (!$stmt->execute()) {
        $_SESSION['mensaje'] = "Error al verificar credenciales.";
        $_SESSION['tipo'] = "error";
        header("Location: mensaje.php");
        exit();
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verificar si la contraseña necesita rehash
        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            // Aquí podrías actualizar el hash en la base de datos
        }

        if (password_verify($password, $row['password'])) {
            // Autenticación exitosa
            $_SESSION['id'] = $row['id'];
            $_SESSION['dni'] = $row['dni'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['correo'] = $row['correo'];
            $_SESSION['cargo'] = $row['cargo'];
            $_SESSION['nombre'] = $row['nombre'] . ' ' . $row['apellido'];
            
            header("Location: validar_acceso.php");
            exit();
        }
    }

    // Mensaje genérico para no revelar información
    $_SESSION['mensaje'] = "Credenciales incorrectas. Inténtalo de nuevo.";
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

$conn->close();
?>