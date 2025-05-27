<?php
function enviarCorreoResultado($nombre, $apellido, $correo, $estado, $comentarios = '') {
    // Cargar autoload de SendGrid (ajusta la ruta según tu estructura local)
    require 'vendor/autoload.php';

    // Obtener API Key de variable de entorno local

    $sendgridApiKey = getenv("SENDGRID_API_KEY");
    if (!$sendgridApiKey) {
        error_log("No se encontró la API Key de SendGrid.");
        return false;
    }

    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
    $email->setSubject("Resultado de tu solicitud de registro");
    $email->addTo($correo, "$nombre $apellido");
    
    $mensaje = $estado === 'aprobado' 
        ? "<h2>¡Felicidades $nombre!</h2><p>Tu solicitud ha sido aprobada.</p>"
        : "<h2>Lo sentimos $nombre</h2><p>Tu solicitud ha sido rechazada.</p>";
    
    if (!empty($comentarios)) {
        $mensaje .= "<p><strong>Comentarios:</strong> $comentarios</p>";
    }
    
    $email->addContent("text/html", $mensaje);

    $sendgrid = new \SendGrid($sendgridApiKey);
    try {
        $response = $sendgrid->send($email);
        return $response->statusCode() >= 200 && $response->statusCode() < 300;
    } catch (Exception $e) {
        error_log("Error enviando correo: " . $e->getMessage());
        return false;
    }
}
?>
