<?php
require '../conexion.php';
require '../FuncionRegistro/ConfirmaRRHH/enviarCorreoValidacion.php';
session_start();

// Redirigir con mensaje de error
function redirect_with_error($error) {
    header("Location: /InterfazLogin/FuncionRegistro/registro.html?error=" . urlencode($error));
    exit();
}

function redirect_with_success($success) {
    header("Location: /InterfazLogin/FuncionRegistro/registro.html?success=" . urlencode($success) );
    exit();
}

// Procesar solo si es método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo = trim($_POST['correo']);
    $dni = trim($_POST['dni']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['pass']);
    $repass = trim($_POST['repass']);

    $cargo = "Usuario";
    $estado = "pendiente";

    // Validaciones en backend (por seguridad)
    if (!preg_match("/^\d{8}$/", $dni)) {
        redirect_with_error("DNI inválido, debe tener 8 dígitos numéricos.");
    }
    
    if ($password !== $repass) {
        redirect_with_error("Las contraseñas no coinciden.");
    }

    // Verificar si el usuario, el correo o el DNI ya existen
    $stmt = $conn->prepare("
        SELECT 'usuario' AS tipo FROM wp_employees WHERE Usuario = ?
        UNION 
        SELECT 'correo' AS tipo FROM wp_employees WHERE Correo = ?
        UNION
        SELECT 'dni' AS tipo FROM wp_employees WHERE dni = ?
    ");
    $stmt->bind_param("sss", $usuario, $correo, $dni); 
    $stmt->execute();
    $resultado = $stmt->get_result();

    $duplicado = false;
    while ($row = $resultado->fetch_assoc()) {
        $duplicado = true;
        $error_msg = match ($row['tipo']) {
            'usuario' => "El nombre de usuario ya está registrado.",
            'correo' => "El correo electrónico ya está registrado.",
            'dni' => "El DNI ya está registrado.",
            default => "Error desconocido."
        };
        break; // Salimos del bucle
    }

    $stmt->close(); 

    if ($duplicado) {
        redirect_with_error($error_msg);
    }

    // Hashear la contraseña y registrar el usuario
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO wp_employees (Nombre, Apellido, dni, Correo, Cargo, Usuario, Password, reset_token, token_expiration, estado, fecha_de_registro) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, NOW())");

    $stmt->bind_param("ssssssss", $nombre, $apellido, $dni, $correo, $cargo, $usuario, $hashedPassword, $estado);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        //enviar correo al administrador
        //include '../FuncionRegistro/ConfirmaRRHH/enviarCorreoValidacion.php';
        if (!enviarCorreoValidacion($nombre, $apellido, $dni, $correo)) {
            redirect_with_error("Registro exitoso, pero hubo un error al enviar la notificación.");
        }
        redirect_with_success("Solicitud de registro enviada. Espere a que un administrador apruebe su cuenta.");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        redirect_with_error("Error al registrar el usuario.");
    }
} else {
    redirect_with_error("Método no permitido.");
}
?>
