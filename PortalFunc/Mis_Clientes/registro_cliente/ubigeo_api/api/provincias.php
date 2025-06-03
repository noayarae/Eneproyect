<?php
header('Content-Type: application/json');
$provincias = json_decode(file_get_contents('../data/provincias.json'), true);

$departamento_id = $_GET['departamento_id'] ?? null;

if ($departamento_id) {
    $filtrado = array_filter($provincias, fn($prov) => $prov['departamento_id'] === $departamento_id);
    echo json_encode(array_values($filtrado));
} else {
    echo json_encode($provincias);
}