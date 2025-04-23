<?php
session_start();
require 'vendor/autoload.php'; // Asegúrate de que la ruta es correcta
use SendGrid\Mail\Mail;

function enviarCorreoValidacion($nombre, $apellido, $dni, $correo) {
    // Guardar datos en sesión
    $_SESSION['datos_solicitud'] = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'correo' => $correo,
        'dni' => $dni
    ];
    session_write_close();

    // API Key de SendGrid (debes ponerla en una variable de entorno)
    $sendgridApiKey = getenv("SENDGRID_API_KEY");

    if (!$sendgridApiKey) {
        error_log("No se encontró la API Key de SendGrid.");
        return false;
    }

    $sendgrid = new \SendGrid($sendgridApiKey);

    // **Correo al usuario**
    $emailUser = new Mail();
    $emailUser->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
    $emailUser->setSubject("Solicitud de Activación de Cuenta");
    $emailUser->addTo($correo, "$nombre $apellido");

    $emailUser->addContent("text/html", "
        <h2>Hola $nombre $apellido,</h2>
        <p>Tu solicitud de registro ha sido recibida. Estamos validando tu información y te informaremos cuando tu cuenta esté aprobada.</p>
    ");

    try {
        $responseUser = $sendgrid->send($emailUser);
        if ($responseUser->statusCode() < 200 || $responseUser->statusCode() >= 300) {
            error_log("Error al enviar correo al usuario: " . $responseUser->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("Error al enviar correo al usuario: " . $e->getMessage());
        return false;
    }

    // **Correo al administrador**
    $emailAdmin = new Mail();
    $emailAdmin->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
    $emailAdmin->setSubject("Nueva solicitud de registro");
    $emailAdmin->addTo("recursoshumanos@eneproyect.com", "Administrador");

    $solicitud_link = "http://localhost:3000/InterfazLogin/FuncionRegistro/ConfirmaRRHH/solicitud.php?dni=" . urlencode($dni);
    $emailAdmin->addContent("text/html", "
        <p><strong>Nombre:</strong> $nombre $apellido</p>
        <p><strong>DNI:</strong> $dni</p>
        <p><strong>Correo:</strong> $correo</p>
        <p>Para aprobar o rechazar la solicitud, haz clic en el siguiente enlace:</p>
        <p><a href='$solicitud_link' target='_blank'>$solicitud_link</a></p>
    ");

    try {
        $responseAdmin = $sendgrid->send($emailAdmin);
        if ($responseAdmin->statusCode() < 200 || $responseAdmin->statusCode() >= 300) {
            error_log("Error al enviar correo al administrador: " . $responseAdmin->body());
            return false;
        }
    } catch (Exception $e) {
        error_log("Error al enviar correo al administrador: " . $e->getMessage());
        return false;
    }

    return true;
}
?>
