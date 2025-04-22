<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require '../../InterfazLogin/conexion.php';
require 'database_helpers.php'; // Para usar las funciones de validación

header('Content-Type: application/json');

// 1. Validar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autorizado']);
    exit();
}

// 2. Solo aceptar método PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// 3. Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos JSON inválidos']);
    exit();
}

// 4. Validar campos requeridos
$camposRequeridos = ['eventId', 'clientId', 'type', 'startDate', 'startTime', 'duration'];
foreach ($camposRequeridos as $campo) {
    if (empty($input[$campo])) {
        http_response_code(400);
        echo json_encode(['error' => "Falta el campo requerido: $campo"]);
        exit();
    }
}

// 5. Validar que el evento pertenece al usuario
if (!eventoPerteneceAUsuario($conn, $input['eventId'], $_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos sobre este evento']);
    exit();
}

// 6. Validar que el cliente existe
if (!clienteExiste($conn, $input['clientId'], $_SESSION['usuario'])) {
    http_response_code(404);
    echo json_encode(['error' => 'El cliente no existe']);
    exit();
}

// 7. Actualizar el evento
try {
    $stmt = $conn->prepare("
        UPDATE eventos SET
            cliente_id = ?,
            tipo = ?,
            fecha_inicio = ?,
            hora_inicio = ?,
            duracion = ?,
            notas = ?
        WHERE id = ? AND usuario_id = ?
    ");
    
    $stmt->bind_param(
        "sssssisi",
        $input['clientId'],
        $input['type'],
        $input['startDate'],
        $input['startTime'],
        $input['duration'],
        $input['notes'] ?? '',
        $input['eventId'],
        $_SESSION['usuario']
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Evento actualizado correctamente',
            'updatedId' => $input['eventId']
        ]);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al actualizar evento',
        'details' => $e->getMessage()
    ]);
}
?>