<?php
session_start();
require '../conexion.php';

use SendGrid\Mail\Mail;

header('Content-Type: text/html; charset=UTF-8'); // Configurar UTF-8

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && !empty($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = "Correo inválido.";
        $_SESSION['tipo'] = "error";
        header("Location: mensaje.php");
        exit();
    }

    // Configurar UTF-8 en la base de datos
    $conn->set_charset("utf8mb4");

    // Buscar usuario por correo
    $stmt = $conn->prepare("SELECT id, usuario FROM wp_employees WHERE correo = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $token = bin2hex(random_bytes(50));

        // Guardar token en la base de datos
        $stmt = $conn->prepare("UPDATE wp_employees SET reset_token = ?, token_expiration = NOW() + INTERVAL 1 HOUR WHERE id = ?");
        $stmt->bind_param("si", $token, $row['id']);
        $stmt->execute();

        // Enlace de restablecimiento
        $reset_link = "http://localhost:3000/InterfazLogin/FuncionLogin/ValidaYDirec.php?token=$token";

        // Obtener API Key de SendGrid
        $sendgridApiKey = getenv("SENDGRID_API_KEY");
        if (!$sendgridApiKey) {
            $_SESSION['mensaje'] = "Error: API Key de SendGrid no configurada.";
            $_SESSION['tipo'] = "error";
            header("Location: mensaje.php");
            exit();
        }

        // Enviar correo con SendGrid
        $email_sendgrid = new Mail();
        $email_sendgrid->setFrom("recursoshumanos@eneproyect.com", "Soporte");
        $email_sendgrid->setSubject("Restablece tu contraseña");
        $email_sendgrid->addTo($email, $row['usuario']);
        $email_sendgrid->addContent("text/html", "Hola {$row['usuario']},<br><br>Haz clic aquí para restablecer tu contraseña: <a href='$reset_link'>$reset_link</a>");
        $email_sendgrid->addContent("text/plain", "Hola {$row['usuario']},\n\nRestablece tu contraseña en: $reset_link");

        $sendgrid = new \SendGrid($sendgridApiKey);
        try {
            $response = $sendgrid->send($email_sendgrid);
            if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
                $_SESSION['mensaje'] = "Error al enviar el correo: " . $response->body();
                $_SESSION['tipo'] = "error";
                header("Location: mensaje.php");
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al enviar el correo: " . $e->getMessage();
            $_SESSION['tipo'] = "error";
            header("Location: mensaje.php");
            exit();
        }

        $_SESSION['mensaje'] = "Correo enviado con éxito.";
        $_SESSION['tipo'] = "success";
        header("Location: mensaje.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "Correo no válido. Verifique el formato e intente de nuevo";
        $_SESSION['tipo'] = "warning";
        header("Location: mensaje.php");
        exit();
    }
}
$conn->close();
?>
