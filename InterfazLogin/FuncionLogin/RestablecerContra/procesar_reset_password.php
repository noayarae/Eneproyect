<?php
require('../../conexion.php');
require_once('funciones.php');

session_start(); // Iniciar sesión para acceder a $_SESSION['User_ID']

// Función para mostrar alertas con redirección opcional
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

// Incluir SweetAlert en el HTML
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Asegurar que el token se recibe antes de usarlo
$token = $_POST['token'] ?? $_GET['token'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($token)) {
    // Obtener el nombre del usuario si está logueado
    $idUsuario = $_SESSION['User_ID'] ?? null;
    $nombreUsuario = $idUsuario ? hallarNombre($idUsuario) : "Usuario Desconocido";

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validar que las contraseñas coincidan
    if ($new_password !== $confirm_password) {
        show_alert("Las contraseñas no coinciden.", "error");
    }

    // Validaciones de seguridad para la nueva contraseña
    if (strlen($new_password) < 10) {
        show_alert("La contraseña debe tener al menos 10 caracteres.", "error");
    } elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password)) {
        show_alert("La contraseña debe tener al menos una letra mayúscula y una minúscula.", "error");
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        show_alert("La contraseña debe tener al menos un carácter especial.", "error");
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        show_alert("La contraseña debe tener al menos un número.", "error");
    }

    // Buscar el usuario con el token
    $stmt = $conn->prepare("SELECT id, correo, password FROM wp_employees WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        show_alert("Token no válido o ha expirado.", "error","/InterfazLogin/FuncionLogin/login.html");
    }
    
    $row = $result->fetch_assoc();
    $user_id = $row['id'];

    // Verificar si la nueva contraseña es igual a la anterior
    if (password_verify($new_password, $row['password'])) {
        show_alert("La nueva contraseña no puede ser igual a la anterior.", "error");
    }

    // Hashear la nueva contraseña y actualizar la base de datos
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE wp_employees SET password = ?, reset_token = NULL, token_expiration = NULL WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        show_alert("Tu contraseña ha sido restablecida exitosamente.", "success", "https://eneproyect.com/InterfazLogin/FuncionLogin/login.html");
    } else {
        show_alert("Hubo un error al actualizar la contraseña.", "error");
    }

    $stmt->close();
    $conn->close();
} else {
    show_alert("No se ha recibido el token en el formulario.", "error");
}
?>