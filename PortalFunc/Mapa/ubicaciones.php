<?php
require(__DIR__ . "/../../InterfazLogin/conexion.php");

header('Content-Type: application/json');

// Validar y sanitizar parámetros
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$departamento = filter_input(INPUT_GET, 'departamento', FILTER_SANITIZE_STRING);
$provincia = filter_input(INPUT_GET, 'provincia', FILTER_SANITIZE_STRING);

try {
    // Verificar conexión a la base de datos
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conn->connect_error);
    }

    switch ($action) {
        case 'departamentos':
            // Obtener todos los departamentos
            $query = "SELECT DISTINCT departamento FROM ubicaciones_peru ORDER BY departamento";
            $result = $conn->query($query);
            
            if (!$result) {
                throw new Exception("Error al obtener departamentos: " . $conn->error);
            }
            
            $departamentos = [];
            while ($row = $result->fetch_assoc()) {
                $departamentos[] = $row['departamento'];
            }
            
            echo json_encode($departamentos);
            break;
            
        case 'provincias':
            // Validar parámetro departamento
            if (empty($departamento)) {
                throw new Exception('Departamento no especificado');
            }
            
            // Obtener provincias por departamento
            $stmt = $conn->prepare("SELECT DISTINCT provincia FROM ubicaciones_peru WHERE departamento = ? ORDER BY provincia");
            if (!$stmt) {
                throw new Exception("Error al preparar consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $departamento);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar consulta: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $provincias = [];
            while ($row = $result->fetch_assoc()) {
                $provincias[] = $row['provincia'];
            }
            
            echo json_encode($provincias);
            break;
            
        case 'distritos':
            // Validar parámetro provincia
            if (empty($provincia)) {
                throw new Exception('Provincia no especificada');
            }
            
            // Obtener distritos por provincia
            $stmt = $conn->prepare("SELECT DISTINCT distrito FROM ubicaciones_peru WHERE provincia = ? ORDER BY distrito");
            if (!$stmt) {
                throw new Exception("Error al preparar consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $provincia);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar consulta: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $distritos = [];
            while ($row = $result->fetch_assoc()) {
                $distritos[] = $row['distrito'];
            }
            
            echo json_encode($distritos);
            break;
            
        case 'coordenadas':
            // Validar parámetro distrito
            $distrito = filter_input(INPUT_GET, 'distrito', FILTER_SANITIZE_STRING);
            if (empty($distrito)) {
                throw new Exception('Distrito no especificado');
            }
            
            // Obtener coordenadas aproximadas del distrito
            $stmt = $conn->prepare("SELECT latitud, longitud FROM ubicaciones_peru WHERE distrito = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception("Error al preparar consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $distrito);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar consulta: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Distrito no encontrado");
            }
            
            $coordenadas = $result->fetch_assoc();
            echo json_encode([
                'lat' => (float)$coordenadas['latitud'],
                'lon' => (float)$coordenadas['longitud']
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}