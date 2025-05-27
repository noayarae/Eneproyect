<?php
// Configuración de errores más detallada
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

// Iniciar sesión (DEBE ser lo primero, sin output antes)
session_start();

// Registrar inicio del proceso
error_log("[".date('Y-m-d H:i:s')."] === INICIO PROCESAR_LOGIN ===");

require '../conexion.php';

// Configurar charset para seguridad
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error al establecer charset: ".$conn->error);
    die("Error de configuración del sistema");
}

// Verificar método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    error_log("Intento de acceso con método: ".$_SERVER["REQUEST_METHOD"]);
    $_SESSION = [
        'mensaje' => "Método no permitido",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Obtener y sanitizar inputs
$usuario = trim($_POST['usuario'] ?? '');
$password = trim($_POST['pass'] ?? '');

// Validaciones básicas
if (empty($usuario) || empty($password)){
    error_log("Validación fallida: Usuario: '$usuario', Pass: '".str_repeat('*', strlen($password))."'");
    $_SESSION = [
        'mensaje' => "Usuario y contraseña son obligatorios",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Consulta preparada con manejo mejorado de errores
$query = "SELECT id, nombre, apellido, correo, cargo, usuario, dni, password, 
          reset_token, ultimo_cambio_pass, estado 
          FROM wp_employees 
          WHERE usuario = ? LIMIT 1";
$stmt = $conn->prepare($query);
error_log("Preparando consulta para usuario: $usuario");

if (!$stmt) {
    $error = $conn->error;
    error_log("Error en preparación: $error");
    $_SESSION = [
        'mensaje' => "Error temporal del sistema. Intente más tarde.",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Bind y ejecución
$stmt->bind_param("s", $usuario);
if (!$stmt->execute()) {
    $error = $stmt->error;
    error_log("Error en ejecución: $error");
    $_SESSION = [
        'mensaje' => "Credenciales incorrectas. Intente nuevamente",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Procesar resultados
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    error_log("Usuario no encontrado o duplicado: $usuario");
    // Mensaje genérico por seguridad
    $_SESSION = [
        'mensaje' => "Usuario no encontrado. Intente nuevamente",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

$user = $result->fetch_assoc();
error_log("Datos usuario: ".json_encode($user));

// Verificar contraseña
if (!password_verify($password, $user['password'])) {
    error_log("Contraseña incorrecta para usuario: $usuario");
    $_SESSION = [
        'mensaje' => "Contraseña incorrecta. Intente nuevamente",
        'tipo' => "error",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Verificar estado de la cuenta
if ($user['estado'] !== 'aprobado') {
    error_log("Cuenta no aprobada: ".$user['estado']);
    $_SESSION = [
        'mensaje' => "Su cuenta está ".$user['estado'],
        'tipo' => "warning",
        'redirect' => "login.html"
    ];
    header("Location: mensaje.php");
    exit();
}

// Actualizar hash si es necesario
if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $conn->query("UPDATE wp_employees SET password = '".$conn->real_escape_string($newHash)."' WHERE id = ".$user['id']);
}

// Limpiar token de reset si existe
if (!empty($user['reset_token'])) {
    $conn->query("UPDATE wp_employees SET reset_token = NULL, token_expiration = NULL WHERE id = ".$user['id']);
}

// Establecer datos de sesión
$_SESSION = [
    'User_ID' => $user['id'],
    'dni' => $user['dni'],
    'usuario' => $user['usuario'],
    'correo' => $user['correo'],
    'cargo' => $user['cargo'],
    'nombre_completo' => $user['nombre'].' '.$user['apellido'],
    'ultimo_cambio_pass' => $user['ultimo_cambio_pass'],
    'estado' => $user['estado'],
    'logged_in' => true
];

// Registrar acceso
registrar_acceso($user['id']);
$conn->query("UPDATE wp_employees SET last_login = NOW() WHERE id = ".$user['id']);

error_log("Autenticación exitosa para: ".$user['usuario']);
header("Location: validar_acceso.php");
exit();

// Función auxiliar
function registrar_acceso($user_id) {
    global $conn;
    
    $data = [
        'user_id' => $user_id,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido',
        'fecha' => date('Y-m-d H:i:s')
    ];
    
    error_log("Registrando acceso: ".json_encode($data));
    
    $stmt = $conn->prepare("INSERT INTO logs_accesos (user_id, ip, user_agent, fecha) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $data['user_id'], $data['ip'], $data['user_agent'], $data['fecha']);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Error al registrar acceso: ".$conn->error);
    }
}