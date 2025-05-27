<?php
session_start();
require '../conexion.php';

// Configurar encabezados para prevenir caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

// Verificar existencia del token
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    $_SESSION['mensaje'] = "Token no proporcionado.";
    $_SESSION['tipo'] = "error";
    header("Location: ../mensaje.php");
    exit();
}

// Sanitizar y validar el token
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
if (!$token || strlen($token) !== 100) { // Asumiendo tokens de 100 caracteres
    $_SESSION['mensaje'] = "Formato de token inválido.";
    $_SESSION['tipo'] = "error";
    header("Location: ../mensaje.php");
    exit();
}

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

    if ($stmt->num_rows === 0) {
        throw new Exception("Token inválido o expirado");
    }

    $stmt->bind_result($id, $token_expiration);
    $stmt->fetch();
    $stmt->close();
    
    // Registrar en logs (opcional)
    error_log("Token válido para usuario ID: $id, expira en: $token_expiration");
    
    // Guardar información en sesión
    $_SESSION['reset_token'] = $token;
    $_SESSION['reset_user_id'] = $id;
    $_SESSION['reset_token_expiration'] = $token_expiration;
    
    // Redirigir a la página de restablecimiento
    header("Location: /InterfazLogin/FuncionLogin/RestablecerContra/procesar_reset_password.html");
    exit();
    
} catch (Exception $e) {
    // Registrar error detallado
    error_log("Error en validación de token: " . $e->getMessage());
    
    // Limpiar cualquier token existente en sesión por seguridad
    unset($_SESSION['reset_token']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_token_expiration']);
    
    $_SESSION['mensaje'] = "El enlace de restablecimiento no es válido o ha expirado.";
    $_SESSION['tipo'] = "error";
    header("Location: ../mensaje.php");
    exit();
} finally {
    // Cerrar conexión si existe
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>