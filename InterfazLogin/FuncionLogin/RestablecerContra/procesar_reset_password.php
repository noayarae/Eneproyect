<?php
require('../../conexion.php');
require_once('funciones.php');

session_start();

// Incluir SweetAlert
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

function show_alert($message, $type, $redirect = null) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '" . ($type == "success" ? "¡Éxito!" : "¡Atención!") . "',
                text: '$message',
                icon: '$type',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            }).then(() => {
                " . ($redirect ? "window.location.href = '$redirect';" : "") . "
            });
        });
    </script>";
    exit();
}

// Obtener el token de la sesión (no de POST)
if (!isset($_SESSION['reset_token'])) {
    show_alert("Token no proporcionado o sesión inválida.", "error", "/InterfazLogin/FuncionLogin/login.html");
}

$token = $_SESSION['reset_token'];
$user_id = $_SESSION['reset_user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validar coincidencia de contraseñas
    if ($new_password !== $confirm_password) {
        show_alert("Las contraseñas no coinciden.", "error");
    }

    // Validar fortaleza de contraseña
    $errors = [];
    if (strlen($new_password) < 10) {
        $errors[] = "La contraseña debe tener al menos 10 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password)) {
        $errors[] = "Debe contener al menos una mayúscula y una minúscula.";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Debe contener al menos un número.";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $errors[] = "Debe contener al menos un carácter especial.";
    }

    if (!empty($errors)) {
        show_alert(implode("<br>", $errors), "error");
    }

    // Verificar token válido (usando el user_id de sesión para mayor seguridad)
    $stmt = $conn->prepare("SELECT id, password FROM wp_employees WHERE id = ? AND reset_token = ? AND token_expiration > NOW()");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        show_alert("Token inválido o expirado.", "error", "/InterfazLogin/FuncionLogin/login.html");
    }

    $row = $result->fetch_assoc();

    // Verificar que no sea la misma contraseña
    if (password_verify($new_password, $row['password'])) {
        show_alert("La nueva contraseña no puede ser igual a la anterior.", "error");
    }

    // Actualizar contraseña
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE wp_employees SET password = ?, reset_token = NULL, token_expiration = NULL WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user_id);

    if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
        // Limpiar la sesión después de éxito
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_token_expiration']);
        
        show_alert("Contraseña actualizada exitosamente.", "success", "https://eneproyect.com/InterfazLogin/FuncionLogin/login.html");
    } else {
        show_alert("Error al actualizar la contraseña.", "error");
    }

    $stmt->close();
    $update_stmt->close();
    $conn->close();
} else {
    show_alert("Método no permitido.", "error", "/InterfazLogin/FuncionLogin/login.html");
}
?>