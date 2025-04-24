<?php
session_start();

// Depuración - Ver todo el contenido de la sesión
error_log("Contenido de SESIÓN en mensaje.php: ".print_r($_SESSION, true));

// Si hay configuración específica de sweet_alert, úsala
if (isset($_SESSION['sweet_alert']) && is_array($_SESSION['sweet_alert'])) {
    $sweet_alert = $_SESSION['sweet_alert'];
    unset($_SESSION['sweet_alert']);
} else {
    // Configuración por defecto
    $mensaje = $_SESSION['mensaje'] ?? "Ocurrió un error no especificado.";
    $tipo = $_SESSION['tipo'] ?? "error";
    $redirect = $_SESSION['redirect'] ?? "login.html";
    
    $sweet_alert = [
        'title' => ($tipo == 'success') ? 'Éxito' : 'Error',
        'text' => $mensaje,
        'icon' => $tipo,
        'redirect' => $redirect
    ];
    
    // Limpiar sesión después de obtener el mensaje
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo']);
    unset($_SESSION['redirect']);
}
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
        // Usar json_encode para escapar correctamente todas las variables
        const sweetConfig = <?php echo json_encode($sweet_alert); ?>;
        
        // Configuración básica de la alerta
        const swalConfig = {
            icon: sweetConfig.icon || 'info',
            title: sweetConfig.title || 'Mensaje',
            text: sweetConfig.text || 'Mensaje del sistema',
            allowOutsideClick: false  // Evita que la alerta se cierre al hacer clic fuera
        };
        
        // Solo agregar timer si NO es formulario de datos personales
        if (!sweetConfig.confirmAction || !sweetConfig.confirmAction.includes('llenar_datos')) {
            swalConfig.timer = sweetConfig.timer || 1500;
        }
        
        // Configurar botones según las opciones disponibles
        if (sweetConfig.confirmButtonText) {
            swalConfig.showConfirmButton = true;
            swalConfig.confirmButtonText = sweetConfig.confirmButtonText;
        } else {
            swalConfig.showConfirmButton = true;
            swalConfig.confirmButtonText = 'Aceptar';
        }
        
        // Agregar botón de cancelar si existe
        if (sweetConfig.cancelButtonText) {
            swalConfig.showCancelButton = true;
            swalConfig.cancelButtonText = sweetConfig.cancelButtonText;
        }
        
        // Mostrar la alerta
        Swal.fire(swalConfig).then((result) => {
            if (result.isConfirmed && sweetConfig.confirmAction) {
                window.location.href = sweetConfig.confirmAction;
            } else if (result.isDismissed && sweetConfig.cancelAction) {
                window.location.href = sweetConfig.cancelAction;
            } else if (sweetConfig.redirect) {
                window.location.href = sweetConfig.redirect;
            }
        });
    </script>
</body>
</html>