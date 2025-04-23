<?php
session_start();
require '../conexion.php';

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $conn->close();
    exit('Método no permitido');
}

// Verificar existencia del token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['mensaje'] = "Token no proporcionado.";
    $_SESSION['tipo'] = "error";
    $conn->close();
    header("Location: ../mensaje.php");
    exit();
}

// Sanitizar el token
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
error_log("Token recibido: " . $token);

try {
    // Verificar token en la base de datos
    $stmt = $conn->prepare("SELECT id, token_expiration FROM wp_employees WHERE reset_token = ? AND token_expiration > NOW()");
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $token);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $token_expiration);
        $stmt->fetch();
        $stmt->close();
        
        error_log("Token válido para usuario ID: $id, expira en: " . $token_expiration);
        
        // Invalidar el token inmediatamente para un solo uso
        $update_stmt = $conn->prepare("UPDATE wp_employees SET reset_token = NULL, token_expiration = NULL WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Error al preparar actualización: " . $conn->error);
        }
        
        $update_stmt->bind_param("i", $id);
        if (!$update_stmt->execute()) {
            throw new Exception("Error al invalidar token: " . $update_stmt->error);
        }
        $update_stmt->close();
        
        // Redirigir a la página de restablecimiento con el token en la URL
        header("Location: /InterfazLogin/FuncionLogin/RestablecerContra/procesar_reset_password.php?token=" . urlencode($token));
        $conn->close();
        exit();
        
    } else {
        throw new Exception("Token inválido o expirado");
    }
    
} catch (Exception $e) {
    error_log("Error en validación de token: " . $e->getMessage());
    $_SESSION['mensaje'] = "El enlace de restablecimiento no es válido o ha expirado.";
    $_SESSION['tipo'] = "error";
    
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    
    $conn->close();
    header("Location: ../mensaje.php");
    exit();
}
?>