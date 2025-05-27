<?php
session_start();
require 'vendor/autoload.php';
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

    // Obtener la API Key de SendGrid desde variable de entorno (configúrala en tu entorno local)
    $sendgridApiKey = getenv("SENDGRID_API_KEY");
    
    if (!$sendgridApiKey) {
        error_log("No se encontró la API Key de SendGrid.");
        return false;
    }

    try {
        $sendgrid = new \SendGrid($sendgridApiKey);

        // Correo al usuario
        $emailUser = new Mail();
        $emailUser->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
        $emailUser->setSubject("Solicitud de Activación de Cuenta");
        $emailUser->addTo($correo, "$nombre $apellido");
        $emailUser->addContent("text/html", "
            <h2>Hola $nombre $apellido,</h2>
            <p>Tu solicitud de registro ha sido recibida. Estamos validando tu información y te informaremos cuando tu cuenta esté aprobada.</p>
        ");

        $responseUser = $sendgrid->send($emailUser);
        if ($responseUser->statusCode() < 200 || $responseUser->statusCode() >= 300) {
            error_log("Error al enviar correo al usuario: " . $responseUser->body());
            return false;
        }

        // Correo al administrador
        $emailAdmin = new Mail();
        $emailAdmin->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
        $emailAdmin->setSubject("Nueva solicitud de registro");
        $emailAdmin->addTo("recursoshumanos@eneproyect.com", "Administrador");

        // Link local para pruebas
        $solicitud_link = "http://localhost/Eneproyect/InterfazLogin/FuncionRegistro/ConfirmaRRHH/solicitud.php?dni=" . urlencode($dni);

        $emailAdmin->addContent("text/html", "
            <p><strong>Nombre:</strong> $nombre $apellido</p>
            <p><strong>DNI:</strong> $dni</p>
            <p><strong>Correo:</strong> $correo</p>
            <p>Para aprobar o rechazar la solicitud, haz clic en el siguiente enlace:</p>
            <p><a href='$solicitud_link' target='_blank'>$solicitud_link</a></p>
        ");

        $responseAdmin = $sendgrid->send($emailAdmin);
        if ($responseAdmin->statusCode() < 200 || $responseAdmin->statusCode() >= 300) {
            error_log("Error al enviar correo al administrador: " . $responseAdmin->body());
            return false;
        }

        return true;
    } catch (Exception $e) {
        error_log("Excepción SendGrid: " . $e->getMessage());
        return false;
    }
}
?>
