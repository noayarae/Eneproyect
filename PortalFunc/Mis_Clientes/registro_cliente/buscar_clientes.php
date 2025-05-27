<?php
require '../../../InterfazLogin/conexion.php';
require(__DIR__ . "/..//../../InterfazLogin/FuncionLogin/Auth/auth.php");

$usuario = $_SESSION['usuario'];
$cargo = $_SESSION['cargo'];

$dni = isset($_POST['dni']) ? $_POST['dni'] : '';
$nombre = isset($_POST['nombre']) ? $_POST['nombre'] : '';
$apellidos = isset($_POST['apellidos']) ? $_POST['apellidos'] : '';

$sql = "SELECT nombre, apellidos, dni, telefono, monto, saldo, fecha_clave, accion_fecha_clave FROM clientes WHERE gestor= '$usuario'";
if ($dni != '') {
    $sql .= " AND dni='$dni'";
}
if ($nombre != '') {
    $sql .= " AND nombre LIKE '%$nombre%'";
}
if ($apellidos != '') {
    $sql .= " AND apellidos LIKE '%$apellidos%'";
}
$sql .= " ORDER BY nombre ASC, apellidos ASC";

$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();

// Enviar datos como JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
