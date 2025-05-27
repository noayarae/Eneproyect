<?php
session_start();
require '../../conexion.php';

// Configuración de errores (diferente para producción/desarrollo)
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Configuración según entorno (desarrollo/producción)
$isProduction = false; // Cambiar a true en producción
ini_set('display_errors', $isProduction ? 0 : 1);

// Incluir SweetAlert
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

function showAlert($message, $type, $redirect = null) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: '" . ($type == "success" ? "¡Éxito!" : "¡Error!") . "',
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

// Validar CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("Intento de CSRF detectado. IP: " . $_SERVER['REMOTE_ADDR']);
    showAlert("Solicitud inválida. Por favor recargue la página e intente nuevamente.", "error");
}

// Validar límite de intentos
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 0;
}

if ($_SESSION['reset_attempts'] > 3) {
    error_log("Límite de intentos excedido. IP: " . $_SERVER['REMOTE_ADDR'] . ", User ID: " . ($_SESSION['reset_user_id'] ?? 'N/A'));
    showAlert("Demasiados intentos fallidos. Por favor espere 15 minutos o solicite un nuevo enlace.", "error");
}

// Obtener el token de la sesión
if (!isset($_SESSION['reset_token'])) {
    error_log("Intento de reset sin token. IP: " . $_SERVER['REMOTE_ADDR']);
    showAlert("Enlace inválido o expirado. Solicite un nuevo enlace de recuperación.", "error", "/InterfazLogin/FuncionLogin/login.html");
}

$token = $_SESSION['reset_token'];
$user_id = $_SESSION['reset_user_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['reset_attempts']++;
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validar coincidencia de contraseñas
    if ($new_password !== $confirm_password) {
        error_log("Contraseñas no coinciden. User ID: " . ($user_id ?? 'N/A'));
        showAlert("Las contraseñas ingresadas no coinciden.", "error");
    }

    // Validar fortaleza de contraseña
    $errors = [];
    if (strlen($new_password) < 10) {
        $errors[] = "La contraseña debe tener al menos 10 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password)) {
        $errors[] = "Debe contener mayúsculas y minúsculas.";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "Debe contener al menos un número.";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $errors[] = "Debe contener al menos un carácter especial.";
    }

    if (!empty($errors)) {
        error_log("Contraseña débil. User ID: " . ($user_id ?? 'N/A') . ", Errores: " . implode(", ", $errors));
        showAlert("La contraseña no cumple con los requisitos de seguridad.", "error");
    }

    try {
        // Verificar token válido
        $stmt = $conn->prepare("SELECT id, password FROM registro_prelim_trabajadores WHERE id = ? AND reset_token = ? AND token_expiration > NOW()");
        if (!$stmt) {
            throw new Exception("Error en preparación de consulta: " . $conn->error);
        }
        
        $stmt->bind_param("is", $user_id, $token);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            error_log("Token inválido o expirado. User ID: " . ($user_id ?? 'N/A') . ", Token: " . $token);
            showAlert("Enlace inválido o expirado. Solicite un nuevo enlace de recuperación.", "error", "/InterfazLogin/FuncionLogin/login.html");
        }

        $row = $result->fetch_assoc();

        // Verificar que no sea la misma contraseña
        if (password_verify($new_password, $row['password'])) {
            error_log("Intento de usar contraseña anterior. User ID: " . $user_id);
            showAlert("La nueva contraseña no puede ser igual a la anterior.", "error");
        }

        // Actualizar contraseña y último cambio
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE registro_prelim_trabajadores SET 
                                     password = ?, 
                                     reset_token = NULL, 
                                     token_expiration = NULL,
                                     ultimo_cambio_pass = NOW() 
                                     WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Error en preparación de actualización: " . $conn->error);
        }
        
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Error al actualizar contraseña: " . $update_stmt->error);
        }

        if ($update_stmt->affected_rows > 0) {
            // Limpiar la sesión después de éxito
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_token_expiration']);
            unset($_SESSION['reset_attempts']);
            unset($_SESSION['csrf_token']);
            
            // Registrar éxito
            error_log("Contraseña actualizada exitosamente. User ID: " . $user_id);
            
            showAlert("Contraseña actualizada exitosamente. Ahora puede iniciar sesión con su nueva contraseña.", "success", "https://eneproyect.com/InterfazLogin/FuncionLogin/login.html");
        } else {
            throw new Exception("Ninguna fila afectada al actualizar contraseña");
        }

        $stmt->close();
        $update_stmt->close();
        
    } catch (Exception $e) {
        error_log("Error en reset de contraseña: " . $e->getMessage() . " - User ID: " . ($user_id ?? 'N/A'));
        showAlert("Ocurrió un error al procesar su solicitud. Por favor intente nuevamente.", "error");
    } finally {
        $conn->close();
    }
} else {
    error_log("Método no permitido: " . $_SERVER["REQUEST_METHOD"] . " - IP: " . $_SERVER['REMOTE_ADDR']);
    showAlert("Método de solicitud no permitido.", "error", "/InterfazLogin/FuncionLogin/login.html");
}
?>