<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require '../../InterfazLogin/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Habilitar errores para desarrollo (desactivar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

try {
    // Consulta más robusta con manejo de errores
    $query = "
        SELECT 
            e.id,
            CONCAT(c.nombre, ' ', c.apellidos) AS title,  -- Nombre completo como título
            CONCAT(e.fecha_inicio, 'T', e.hora_inicio) AS start,
            DATE_ADD(
                CONCAT(e.fecha_inicio, ' ', e.hora_inicio),
                INTERVAL e.duracion MINUTE
            ) AS end,
            c.dni AS clientId,
            e.tipo AS type,
            e.notas AS notes,
            CONCAT(c.nombre, ' ', c.apellidos) AS clientName
        FROM eventos e
        JOIN clientes c ON e.cliente_id = c.dni
        WHERE e.usuario_id = ?
        ORDER BY e.fecha_inicio, e.hora_inicio
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("s", $_SESSION['usuario']);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $eventos = [];
    
    while ($row = $result->fetch_assoc()) {
        $eventos[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'start' => $row['start'],
            'end' => $row['end'],
            'extendedProps' => [
                'clientId' => $row['clientId'],
                'type' => $row['type'],
                'notes' => $row['notes'],
                'clientName' => $row['clientName']
            ]
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $eventos
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en el servidor',
        'details' => $e->getMessage()
    ]);
}
?>