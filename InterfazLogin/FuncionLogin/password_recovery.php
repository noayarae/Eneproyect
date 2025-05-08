<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use SendGrid\Mail\Mail;

// Configuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$conn->set_charset("utf8mb4");

// Determinar la acción
$action = $_GET['action'] ?? ($_POST['action'] ?? 'request');

try {
    switch ($action) {
        case 'request':
            handlePasswordRequest();
            break;
        case 'validate':
            validateToken();
            break;
        default:
            throw new Exception("Acción no válida");
    }
} catch (Exception $e) {
    error_log("Error en password_recovery.php: " . $e->getMessage());
    $_SESSION['mensaje'] = $e->getMessage();
    $_SESSION['tipo'] = "error";
    header("Location: mensaje.php");
    exit();
}

function handlePasswordRequest() {
    global $conn;
    
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Método no permitido");
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo inválido");
    }

    // Buscar usuario por correo
    $stmt = $conn->prepare("SELECT id, usuario FROM wp_employees WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$row = $result->fetch_assoc()) {
        throw new Exception("Correo no registrado en el sistema");
    }

    $token = bin2hex(random_bytes(50));

    // Guardar token en la base de datos
    $stmt = $conn->prepare("UPDATE wp_employees SET reset_token = ?, token_expiration = NOW() + INTERVAL 12 HOUR WHERE id = ?");
    $stmt->bind_param("si", $token, $row['id']);
    $stmt->execute();

    // Enviar correo con el token
    sendResetEmail($email, $row['usuario'], $token);
    
    $_SESSION['mensaje'] = "Se ha enviado un correo con instrucciones para restablecer tu contraseña";
    $_SESSION['tipo'] = "success";
    header("Location: mensaje.php");
    exit();
}

function validateToken() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Método no permitido");
    }

    $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
    
    if (!$token || strlen($token) !== 100) {
        throw new Exception("Token inválido");
    }

    // Verificar token en la base de datos
    $stmt = $conn->prepare("SELECT id, token_expiration FROM wp_employees WHERE reset_token = ? AND token_expiration > NOW()");
    
    if (!$stmt) {
        throw new Exception("Error en el sistema. Intente más tarde");
    }

    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        throw new Exception("El enlace de restablecimiento ha expirado o no es válido");
    }

    $stmt->bind_result($id, $token_expiration);
    $stmt->fetch();
    $stmt->close();
    
    // Guardar información en sesión
    $_SESSION['reset_token'] = $token;
    $_SESSION['reset_user_id'] = $id;
    $_SESSION['reset_token_expiration'] = $token_expiration;
    
    // Redirigir a la página de restablecimiento
    header("Location: /InterfazLogin/FuncionLogin/RestablecerContra/procesar_reset_password.html");
    exit();
}

function sendResetEmail($email, $username, $token) {
    $sendgridApiKey = getenv("SENDGRID_API_KEY");
    if (!$sendgridApiKey) {
        throw new Exception("Error en el sistema. Intente más tarde");
    }

    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/InterfazLogin/FuncionLogin/password_recovery.php?action=validate&token=$token";

    $email_sendgrid = new Mail();
    $email_sendgrid->setFrom("recursoshumanos@eneproyect.com", "Soporte");
    $email_sendgrid->setSubject("Restablece tu contraseña");
    $email_sendgrid->addTo($email, $username);
    
    // Contenido HTML del correo
    $html_content = "<!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .button { 
                background-color: #4CAF50; 
                border: none; 
                color: white; 
                padding: 10px 20px; 
                text-align: center; 
                text-decoration: none; 
                display: inline-block; 
                font-size: 16px; 
                margin: 10px 0; 
                cursor: pointer; 
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <p>Hola $username,</p>
        <p>Haz clic en el siguiente botón para restablecer tu contraseña:</p>
        <a href='$reset_link' class='button'>Restablecer Contraseña</a>
        <p>Si no solicitaste este cambio, por favor ignora este correo.</p>
        <p>El enlace expirará en 12 horas.</p>
    </body>
    </html>";
    
    $email_sendgrid->addContent("text/html", $html_content);
    $email_sendgrid->addContent("text/plain", 
        "Hola $username,\n\nRestablece tu contraseña en: $reset_link\n\nEl enlace expirará en 12 horas.");

    $sendgrid = new \SendGrid($sendgridApiKey);
    $response = $sendgrid->send($email_sendgrid);
    
    if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
        throw new Exception("Error al enviar el correo. Intente nuevamente más tarde");
    }
}
?>