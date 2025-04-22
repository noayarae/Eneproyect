<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require '../../InterfazLogin/conexion.php';
require 'database_helpers.php';

header('Content-Type: application/json');

// 1. Validar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autorizado']);
    exit();
}

// 2. Solo aceptar método DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// 3. Obtener ID del evento
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['eventId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Se requiere eventId']);
    exit();
}

// 4. Validar propiedad del evento
if (!eventoPerteneceAUsuario($conn, $input['eventId'], $_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos sobre este evento']);
    exit();
}

// 5. Eliminar el evento
try {
    $stmt = $conn->prepare("DELETE FROM eventos WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("is", $input['eventId'], $_SESSION['usuario']);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Evento eliminado correctamente',
            'deletedId' => $input['eventId']
        ]);
    } else {
        throw new Exception("Error al eliminar: " . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al eliminar evento',
        'details' => $e->getMessage()
    ]);
}
?>