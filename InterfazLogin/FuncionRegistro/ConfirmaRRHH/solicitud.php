<?php
session_start();
require('../../conexion.php');

// Verificar si hay datos en la sesión
if (isset($_SESSION['datos_solicitud'])) {
    $nombre = $_SESSION['datos_solicitud']['nombre'];
    $apellido = $_SESSION['datos_solicitud']['apellido'];
    $correo = $_SESSION['datos_solicitud']['correo'];
    $dni = $_SESSION['datos_solicitud']['dni'];
} else {
    // Si no hay sesión, recuperar el DNI de la URL
    if (!isset($_GET['dni']) || empty($_GET['dni'])) {
        die("Error: No hay datos disponibles.");
    }

    $dni = $_GET['dni'];

    // Conexión a la base de datos
    $conn = new mysqli($servidor, $usuario, $contrasena, $baseDatos);

    // Buscar datos en la BD
    $stmt = $conn->prepare("SELECT nombre, apellido, correo FROM wp_employees WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($nombre, $apellido, $correo);
        $stmt->fetch();
    } else {
        die("Error: No se encontraron datos para este DNI.");
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud</title>
    <link rel="stylesheet" href="solicitud.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../FuncionLogin/alertas.js"></script>
</head>
<body>

    <div class="container">
        <h2 class="title">Solicitud de: <?php echo htmlspecialchars($nombre . " " . $apellido); ?></h2>
        <form id="solicitudForm" action="procesar_solicitud.php" method="POST">
            <div class="info-box">
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombre . " " . $apellido); ?></p>
                <p><strong>DNI:</strong> <?php echo htmlspecialchars($dni); ?></p>
                <p><strong>Correo:</strong> <?php echo htmlspecialchars($correo); ?></p>

                <input type="hidden" name="dni" value="<?php echo htmlspecialchars($dni); ?>">

                <div class="input-group">
                    <label for="cargo"><strong>Asignar Cargo:</strong></label>
                    <select id="cargo" name="cargo" required>
                        <option value="" disabled selected>Seleccione el cargo</option>
                        <option value="Gestor de Créditos">Gestor de Créditos</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Soporte">Soporte</option>
                    </select>
                </div>
            </div>

            <div class="buttons">
                <button type="button" onclick="confirmarSolicitud('aprobar')" class="approve">Aprobar</button>
                <button type="button" onclick="confirmarSolicitud('rechazar')" class="reject">Rechazar</button>
            </div>
        </form>
    </div>

    <script>
        function confirmarSolicitud(accion) {
            let mensaje = (accion === 'aprobar') 
                ? '¿Estás seguro de aprobar esta solicitud?' 
                : '¿Estás seguro de rechazar esta solicitud?';

            Swal.fire({
                title: 'Confirmación',
                text: mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.getElementById("solicitudForm");
                    let inputAccion = document.createElement("input");
                    inputAccion.type = "hidden";
                    inputAccion.name = "accion";
                    inputAccion.value = accion;
                    form.appendChild(inputAccion);
                    form.submit();
                }
            });
        }

        // Mostrar alertas desde la URL usando alerta.js
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has("success")) {
                mostrarAlerta("success", urlParams.get("success"), "../../home.html");
            } else if (urlParams.has("error")) {
                mostrarAlerta("error", urlParams.get("error"));
            }
        });
    </script>

</body>
</html>
