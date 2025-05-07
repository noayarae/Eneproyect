<?php
// Configuración de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos necesarios
require '../conexion.php';
require '../FuncionRegistro/ConfirmaRRHH/enviarCorreoValidacion.php';

// Definir las expresiones regulares (añade esto al principio)
define('REGEX_NOMBRE_APELLIDO', '/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/');
define('REGEX_DNI', '/^\d{8}$/');
define('REGEX_CORREO', '/^[^\s@]+@[^\s@]+\.[^\s@]+$/');
define('REGEX_USUARIO', '/^(?=.*[@$!%*?&._-])[a-zA-Z0-9@$!%*?&._-]{3,}$/');
define('REGEX_PASSWORD', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#$])[A-Za-z\d@$!%*?&#$]{10,}$/');

// Definir tipo de contenido como JSON
header('Content-Type: application/json');

try {
    // Verificar método HTTP
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Método no permitido", 405);
    }

    // Obtener y limpiar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    $repass = trim($_POST['repass'] ?? '');

    $cargo = "Usuario";
    $estado = "pendiente";

    // Validación del nombre
    if (!preg_match(REGEX_NOMBRE_APELLIDO, $nombre)) {
        echo json_encode([
            'success' => false,
            'error' => "El nombre contiene un carácter no válido. Por favor, verifique",
            'field' => 'nombre'
        ]);
        exit;
    }
    
    // Validación del apellido
    if (!preg_match(REGEX_NOMBRE_APELLIDO, $apellido)) {
        echo json_encode([
            'success' => false,
            'error' => "El apellido contiene un carácter no válido. Por favor, verifique",
            'field' => 'apellido'
        ]);
        exit;
    }

    // Validación del DNI
    if (!preg_match(REGEX_DNI, $dni)) {
        echo json_encode([
            'success' => false,
            'error' => "DNI no válido. Por favor verifique e intente de nuevo",
            'field' => 'dni'
        ]);
        exit;
    }
    
    // Validación del correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || !preg_match(REGEX_CORREO, $correo)) {
        echo json_encode([
            'success' => false,
            'error' => "Correo no válido. Verifica el formato e intente de nuevo.",
            'field' => 'correo'
        ]);
        exit;
    }

    // Validación del usuario
    if (!preg_match(REGEX_USUARIO, $usuario)) {
        echo json_encode([
            'success' => false,
            'error' => "El usuario debe tener al menos 3 caracteres e incluir un carácter especial (@$!%*?&._-).",
            'field' => 'usuario'
        ]);
        exit;
    }

    // Validación de la contraseña
    if (!preg_match(REGEX_PASSWORD, $password)) {
        echo json_encode([
            'success' => false,
            'error' => "La contraseña debe tener al menos 10 caracteres, una mayúscula, una minúscula, un número y un carácter especial (@$!*?&).",
            'field' => 'pass'
        ]);
        exit;
    }

    // Verificar que el usuario no contenga %
    if (strpos($usuario, '%') !== false) {
        echo json_encode([
            'success' => false,
            'error' => "El símbolo % no está permitido en el usuario",
            'field' => 'usuario'
        ]);
        exit;
    }

    // Verificar que las contraseñas coincidan
    if ($password !== $repass) {
        echo json_encode([
            'success' => false,
            'error' => "Las contraseñas no coinciden. Verifique e intente nuevamente",
            'field' => 'repass'
        ]);
        exit;
    }

    // Verificación de duplicados
    $stmt = $conn->prepare("SELECT CASE 
        WHEN dni = ? THEN 'dni'
        WHEN Correo = ? THEN 'correo'
        WHEN Usuario = ? THEN 'usuario'
        END AS campo_duplicado
        FROM wp_employees WHERE dni = ? OR Correo = ? OR Usuario = ? LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Error preparando la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("ssssss", $dni, $correo, $usuario, $dni, $correo, $usuario);
    
    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando la consulta: " . $stmt->error);
    }
    
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        $campo_duplicado = $row['campo_duplicado'];
        
        $mensajes_error = [
            'dni' => "El DNI ya está registrado.",
            'correo' => "El correo electrónico ya está registrado.",
            'usuario' => "Este usuario ya está registrado. Prueba con otro nombre de usuario."
        ];
        
        // Construir URL para redirección con parámetros
        $redirect_params = [
            'error' => $mensajes_error[$campo_duplicado],
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'correo' => $correo,
            'usuario' => $usuario
        ];
        
        // Construir URL de redirección
        $redirect_url = "/InterfazLogin/FuncionRegistro/registro.html?" . http_build_query($redirect_params);
        
        echo json_encode([
            'success' => false,
            'error' => $mensajes_error[$campo_duplicado],
            'field' => $campo_duplicado,
            'redirect' => $redirect_url
        ]);
        exit;
    }

    // Registro
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO wp_employees 
        (Nombre, Apellido, dni, Correo, Cargo, Usuario, Password, reset_token, token_expiration, estado, fecha_de_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, NOW())");

    if (!$stmt) {
        throw new Exception("Error preparando la consulta de inserción: " . $conn->error);
    }

    $stmt->bind_param("ssssssss", $nombre, $apellido, $dni, $correo, $cargo, $usuario, $hashedPassword, $estado);

    if (!$stmt->execute()) {
        throw new Exception("Error al registrar el usuario en la base de datos: " . $stmt->error);
    }

    // Enviar correo
    $emailResult = enviarCorreoValidacion($nombre, $apellido, $dni, $correo);
    
    echo json_encode([
        'success' => true,
        'message' => "Solicitud de registro enviada. Espere a que un administrador apruebe su cuenta.",
        'emailSent' => $emailResult
    ]);

} catch (Exception $e) {
    // Capturar cualquier error no manejado
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>