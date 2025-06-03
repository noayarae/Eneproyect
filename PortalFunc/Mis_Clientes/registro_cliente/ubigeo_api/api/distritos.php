<?php
header('Content-Type: application/json');
$distritos = json_decode(file_get_contents('../data/distritos.json'), true);

$provincia_id = $_GET['provincia_id'] ?? null;

if ($provincia_id) {
    $filtrado = array_filter($distritos, fn($dist) => $dist['provincia_id'] === $provincia_id);
    echo json_encode(array_values($filtrado));
} else {
    echo json_encode($distritos);
}