<?php
session_start();
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : "Ocurrió un error.";
$tipo = isset($_SESSION['tipo']) ? $_SESSION['tipo'] : "error";
$redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : "login.html";

// Limpiar sesión después de mostrar el mensaje
unset($_SESSION['mensaje']);
unset($_SESSION['tipo']);
unset($_SESSION['redirect']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensaje</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <script>
        Swal.fire({
            icon: "<?php echo $tipo; ?>",
            title: "<?php echo ($tipo == 'success') ? 'Éxito' : 'Error'; ?>",
            text: "<?php echo $mensaje; ?>",
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "<?php echo $redirect; ?>";
        });
    </script>
</body>
</html>
