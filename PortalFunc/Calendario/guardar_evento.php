<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require '../../InterfazLogin/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Configuración de errores (activar en desarrollo, desactivar en producción)
ini_set('display_errors', 0);
error_reporting(0);

// 1. Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Usuario no autorizado']));
}

// 2. Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// 3. Obtener y validar datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE || !$input) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Datos JSON inválidos']));
}

// 4. Validar campos requeridos
$requiredFields = ['clientId', 'type', 'startDate', 'startTime', 'duration'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => "Campo requerido faltante: $field"]));
    }
}

try {
    // 5. Sanitizar y validar datos
    $clienteDni = trim($input['clientId']);
    $tipoEvento = trim($input['type']); // No forzar lowercase para mantener consistencia
    $fechaInicio = trim($input['startDate']);
    $horaInicio = trim($input['startTime']);
    $duracion = (int)$input['duration'];
    $notas = isset($input['notes']) ? trim($input['notes']) : null; // Permitir null
    $eventId = isset($input['eventId']) ? trim($input['eventId']) : null;
    
    // 6. Validar formatos básicos
    if (empty($tipoEvento)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => "El tipo de evento no puede estar vacío"]));
    }
    
    // 7. Para actualizaciones, primero obtener el evento actual y mezclar datos
    if ($eventId) {
        $stmt = $conn->prepare("SELECT * FROM eventos WHERE id = ? AND usuario_id = ? LIMIT 1");
        $stmt->bind_param("ss", $eventId, $_SESSION['usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentEventData = $result->fetch_assoc();
        $stmt->close();
        
        if (!$currentEventData) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'Evento no encontrado']));
        }
        
        // Solo actualizar campos que han cambiado realmente
        $clienteDni = !empty($clienteDni) ? $clienteDni : $currentEventData['cliente_id'];
        $tipoEvento = !empty($tipoEvento) ? $tipoEvento : $currentEventData['tipo'];
        $fechaInicio = !empty($fechaInicio) ? $fechaInicio : $currentEventData['fecha_inicio'];
        $horaInicio = !empty($horaInicio) ? $horaInicio : $currentEventData['hora_inicio'];
        $duracion = !empty($duracion) ? $duracion : $currentEventData['duracion'];
        $notas = isset($input['notes']) ? trim($input['notes']) : $currentEventData['notas'];
    }

    // 8. Preparar consulta (actualizada para verificar cambios)
    if ($eventId) {
        // Verificar si realmente hay cambios para evitar actualizaciones innecesarias
        $hasChanges = 
            $clienteDni !== $currentEventData['cliente_id'] ||
            $tipoEvento !== $currentEventData['tipo'] ||
            $fechaInicio !== $currentEventData['fecha_inicio'] ||
            $horaInicio !== $currentEventData['hora_inicio'] ||
            $duracion != $currentEventData['duracion'] ||
            $notas != $currentEventData['notas'];
        
        if (!$hasChanges) {
            echo json_encode([
                'success' => true,
                'eventId' => $eventId,
                'message' => 'No se detectaron cambios',
                'noChanges' => true
            ]);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE eventos SET
                cliente_id = ?,
                tipo = ?,
                fecha_inicio = ?,
                hora_inicio = ?,
                duracion = ?,
                notas = ?,
                fecha_actualizacion = NOW()
            WHERE id = ? AND usuario_id = ?
        ");
        $stmt->bind_param(
            "ssssssis",
            $clienteDni,
            $tipoEvento,
            $fechaInicio,
            $horaInicio,
            $duracion,
            $notas,
            $eventId,
            $_SESSION['usuario']
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO eventos (
                usuario_id,
                cliente_id,
                tipo,
                fecha_inicio,
                hora_inicio,
                duracion,
                notas,
                fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param(
            "sssssis",
            $_SESSION['usuario'],
            $clienteDni,
            $tipoEvento, 
            $fechaInicio,
            $horaInicio,
            $duracion,
            $notas
        );
    }
    
    // 9. Ejecutar y responder
    if (!$stmt->execute()) {
        throw new Exception("Error en la base de datos: " . $stmt->error);
    }
    
    $affectedId = $eventId ? $eventId : $stmt->insert_id;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'eventId' => $affectedId,
        'message' => $eventId ? 'Evento actualizado' : 'Evento creado',
        'clientData' => [
            'dni' => $clienteDni,
            'nombre' => $cliente['nombre'],
            'apellidos' => $cliente['apellidos'],
            'type' => $tipoEvento // <-- Devuelve el tipo original
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>