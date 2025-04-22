<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require '../../InterfazLogin/conexion.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autorizado']);
    exit();
}

try {
    // Consulta preparada para obtener clientes del usuario
    $stmt = $conn->prepare("
        SELECT 
            dni,
            CONCAT(nombre, ' ', apellidos, ' - ', dni ) AS nombre_completo,
            nombre,
            apellidos
        FROM clientes 
        WHERE gestor = ?
        ORDER BY nombre, apellidos
    ");
    
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $result = $stmt->get_result();

    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = [
            'dni' => $row['dni'],
            'name' => $row['nombre_completo'],
            'nombre' => $row['nombre'],
            'apellidos' => $row['apellidos']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $clientes]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al obtener clientes',
        'details' => $e->getMessage()
    ]);
}

$conn->close();
?>