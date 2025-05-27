<?php
require ('../../conexion.php');
require __DIR__ . '/vendor/autoload.php';
use SendGrid\Mail\Mail;

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dni'], $_POST['cargo'], $_POST['accion'])) {
    $dni = trim($_POST['dni']);
    $cargo = trim($_POST['cargo']);
    $accion = trim($_POST['accion']);

    if ($accion === "aprobar" && empty($cargo)) {
        $_SESSION['alerta'] = ["error", "Debe asignar un cargo para aprobar."];
        header("Location: solicitud.php");
        exit();
    }

    $estado = ($accion === "aprobar") ? "aprobado" : "rechazado";
    $cargo = ($accion === "rechazar") ? "Usuario" : $cargo;
    $mensaje = ($accion === "aprobar") ? "Tu solicitud ha sido aprobada. Bienvenido al equipo." : "Tu solicitud ha sido rechazada. Para más información, contáctanos.";

    // Actualizar estado en wp_employees
    $sql = "UPDATE wp_employees SET estado = ?, Cargo = ? WHERE dni = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['alerta'] = ["error", "Error en la consulta: " . $conn->error];
        header("Location: solicitud.php");
        exit();
    }
    $stmt->bind_param("sss", $estado, $cargo, $dni);
    if (!$stmt->execute()) {
        $_SESSION['alerta'] = ["error", "Error al actualizar la solicitud."];
        header("Location: solicitud.php");
        exit();
    }

    // Insertar en wp_datos_usuarios solo si la acción es aprobar
    if ($accion === "aprobar") {
        $sql_insert = "INSERT INTO wp_datos_usuarios (dni) VALUES (?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            $_SESSION['alerta'] = ["error", "Error al preparar la consulta de inserción."];
            header("Location: solicitud.php");
            exit();
        }
        $stmt_insert->bind_param("s", $dni);
        if (!$stmt_insert->execute()) {
            $_SESSION['alerta'] = ["error", "Error al insertar los datos adicionales."];
            header("Location: solicitud.php");
            exit();
        }
    }

    // Obtener datos del usuario
    $sql = "SELECT Nombre, Apellido, Correo FROM wp_employees WHERE dni = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['alerta'] = ["error", "Error en la consulta de usuario."];
        header("Location: solicitud.php");
        exit();
    }
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['alerta'] = ["error", "No se encontró el usuario."];
        header("Location: solicitud.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $correo_usuario = $user['Correo'];
    $db_nombre = $user['Nombre'];
    $apellido = $user['Apellido'];

    // **Enviar correo con SendGrid**
    $sendgridApiKey = getenv("SENDGRID_API_KEY");
    if (!$sendgridApiKey) {
        $_SESSION['alerta'] = ["error", "No se encontró la API Key de SendGrid."];
        header("Location: solicitud.php");
        exit();
    }

    $email = new Mail();
    $email->setFrom("recursoshumanos@eneproyect.com", "Recursos Humanos");
    $email->setSubject("Resultado de tu solicitud");
    $email->addTo($correo_usuario, "$db_nombre $apellido");
    $email->addContent("text/html", "<p>Hola $db_nombre $apellido,</p><p>$mensaje</p><p>Saludos, Recursos Humanos</p>");

    $sendgrid = new \SendGrid($sendgridApiKey);
    try {
        $response = $sendgrid->send($email);
        if ($response->statusCode() < 200 || $response->statusCode() >= 300) {
            $_SESSION['alerta'] = ["error", "Error al enviar el correo: " . $response->body()];
            header("Location: solicitud.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['alerta'] = ["error", "Error al enviar el correo: " . $e->getMessage()];
        header("Location: solicitud.php");
        exit();
    }

    // Redirigir al home después de procesar la solicitud
    header("Location: http://localhost:3000/InterfazLogin/FuncionLogin/login.html");
    exit();
} else {
    $_SESSION['alerta'] = ["error", "Datos incompletos en la solicitud."];
    header("Location: solicitud.php");
    exit();
}
