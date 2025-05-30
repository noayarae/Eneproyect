<?php
require(__DIR__ . '/../../../InterfazLogin/conexion.php');
require(__DIR__ . '/../../../InterfazLogin/FuncionLogin/Auth/auth.php');

//agregar la session
$usuario = $_SESSION['usuario'];
$correo = $_SESSION['correo'];
$cargo = $_SESSION['cargo'];

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // datos cliente
    $dni = $_POST["dni"];
    $nombre = $_POST["nombre"];
    $apellidos = $_POST["apellidos"];
    $telefono = $_POST["telefono"];
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $domicilio1 = $_POST["domicilio1"];
    $referencia1 = $_POST["referencia1"];
    $domicilio2 = isset($_POST["domicilio2"]) ? $_POST["domicilio2"] : '';
    $referencia2 = isset($_POST["referencia2"]) ? $_POST["referencia2"] : '';
    $ocupacion = $_POST["ocupacion"];
    $clasificacion_riesgo = $_POST["clasificacion_riesgo"] ? $_POST["clasificacion_riesgo"] : '';
    $agencia = $_POST["agencia"]; //nuevo
    // nuego de departamento provincia y distrito
    $departamento = $_POST["departamento"] ? $_POST["departamento"] : '';
    $provincia = $_POST["provincia"] ? $_POST["provincia"] : '';
    $distrito = $_POST["distrito"] ? $_POST["distrito"] : '';

    $tipo_credito = $_POST["tipo_credito"]; //nuevo
    $estado = $_POST["estado"]; //nuevo
    $fecha_desembolso = $_POST["fecha_desembolso"];
    $fecha_vencimiento = $_POST["fecha_vencimiento"];
    $monto = $_POST["monto"];
    $saldo = $_POST["saldo"];

    // datos garante 1
    $nombre_garante = $_POST["nombre_garante"] ? $_POST["nombre_garante"] : '';
    $apellidos_garante = $_POST["apellidos_garante"] ? $_POST["apellidos_garante"] : '';
    $dni_garante = $_POST["dni_garante"] ? $_POST["dni_garante"] : '';
    $telefono_garante = $_POST["telefono_garante"] ? $_POST["telefono_garante"] : '';
    $fecha_nacimiento_garante = $_POST["fecha_nacimiento_garante"] ? $_POST["fecha_nacimiento_garante"] : '';
    $domicilio1_garante = $_POST["domicilio1_garante"] ? $_POST["domicilio1_garante"] : '';
    $referencia1_garante = $_POST["referencia1_garante"] ? $_POST["referencia1_garante"] : '';
    $domicilio2_garante = isset($_POST["domicilio2_garante"]) ? $_POST["domicilio2_garante"] : '';
    $referencia2_garante = isset($_POST["referencia2_garante"]) ? $_POST["referencia2_garante"] : '';
    $ocupacion_garante = $_POST["ocupacion_garante"] ? $_POST["ocupacion_garante"] : '';
    $clasificacion_riesgo_garante = $_POST["clasificacion_riesgo_garante"] ? $_POST["clasificacion_riesgo_garante"] : '';
    // datos garante 2
    $nombre_garante_2 = $_POST["nombre_garante_2"] ? $_POST["nombre_garante_2"] : '';
    $apellidos_garante_2 = $_POST["apellidos_garante_2"] ? $_POST["apellidos_garante_2"] : '';
    $dni_garante_2 = $_POST["dni_garante_2"] ? $_POST["dni_garante_2"] : '';
    $telefono_garante_2 = $_POST["telefono_garante_2"] ? $_POST["telefono_garante_2"] : '';
    $fecha_nacimiento_garante_2 = $_POST["fecha_nacimiento_garante_2"] ? $_POST["fecha_nacimiento_garante_2"] : '';
    $domicilio1_garante_2 = $_POST["domicilio1_garante_2"] ? $_POST["domicilio1_garante_2"] : '';
    $referencia1_garante_2 = $_POST["referencia1_garante_2"] ? $_POST["referencia1_garante_2"] : '';
    $domicilio2_garante_2 = isset($_POST["domicilio2_garante_2"]) ? $_POST["domicilio2_garante_2"] : '';
    $referencia2_garante_2 = isset($_POST["referencia2_garante_2"]) ? $_POST["referencia2_garante_2"] : '';
    $ocupacion_garante_2 = $_POST["ocupacion_garante_2"] ? $_POST["ocupacion_garante_2"] : '';
    $clasificacion_riesgo_garante_2 = $_POST["clasificacion_riesgo_garante_2"] ? $_POST["clasificacion_riesgo_garante_2"] : '';
    // datos garante 3
    $nombre_garante_3 = $_POST["nombre_garante_3"] ? $_POST["nombre_garante_3"] : '';
    $apellidos_garante_3 = $_POST["apellidos_garante_3"] ? $_POST["apellidos_garante_3"] : '';
    $dni_garante_3 = $_POST["dni_garante_3"] ? $_POST["dni_garante_3"] : '';
    $telefono_garante_3 = $_POST["telefono_garante_3"] ? $_POST["telefono_garante_3"] : '';
    $fecha_nacimiento_garante_3 = $_POST["fecha_nacimiento_garante_3"] ? $_POST["fecha_nacimiento_garante_3"] : '';
    $domicilio1_garante_3 = $_POST["domicilio1_garante_3"] ? $_POST["domicilio1_garante_3"] : '';
    $referencia1_garante_3 = $_POST["referencia1_garante_3"] ? $_POST["referencia1_garante_3"] : '';
    $domicilio2_garante_3 = isset($_POST["domicilio2_garante_3"]) ? $_POST["domicilio2_garante_3"] : '';
    $referencia2_garante_3 = isset($_POST["referencia2_garante_3"]) ? $_POST["referencia2_garante_3"] : '';
    $ocupacion_garante_3 = $_POST["ocupacion_garante_3"] ? $_POST["ocupacion_garante_3"] : '';
    $clasificacion_riesgo_garante_3 = $_POST["clasificacion_riesgo_garante_3"] ? $_POST["clasificacion_riesgo_garante_3"] : '';

    //dato Fecha Programada
    $fecha_clave = $_POST["fecha_clave"];
    $accion_fecha_clave = $_POST["accion_fecha_clave"];
    // dato personal asignado
    if ($cargo == 'Gestor de Créditos') {
        $gestor = $usuario;
        $supervisor = $_POST["supervisor"];
        $administrador = $_POST["administrador"];
    } elseif ($cargo == 'Supervisor') {
        $gestor = $_POST["gestor"];
        $supervisor = $usuario;
        $administrador = $_POST["administrador"];
    } elseif ($cargo == 'Administrador') {
        $gestor = $_POST["gestor"];
        $supervisor = $_POST["supervisor"];
        $administrador = $usuario;
    } else {
        // Si no tiene un cargo específico, tomar todos los valores del formulario
        $gestor = $_POST["gestor"];
        $supervisor = $_POST["supervisor"];
        $administrador = $_POST["administrador"];
    }

    $sql = "INSERT INTO clientes (
    nombre, apellidos, dni, telefono, fecha_nacimiento, domicilio1, referencia1, domicilio2, referencia2,
    ocupacion, clasificacion_riesgo, agencia, tipo_credito, estado, fecha_desembolso, fecha_vencimiento,
    monto, saldo,
    departamento, provincia, distrito, 
    nombre_garante, apellidos_garante, dni_garante, telefono_garante, fecha_nacimiento_garante,
    domicilio1_garante, referencia1_garante, domicilio2_garante, referencia2_garante,
    ocupacion_garante, clasificacion_riesgo_garante,
    nombre_garante_2, apellidos_garante_2, dni_garante_2, telefono_garante_2, fecha_nacimiento_garante_2,
    domicilio1_garante_2, referencia1_garante_2, domicilio2_garante_2, referencia2_garante_2,
    ocupacion_garante_2, clasificacion_riesgo_garante_2,
    nombre_garante_3, apellidos_garante_3, dni_garante_3, telefono_garante_3, fecha_nacimiento_garante_3,
    domicilio1_garante_3, referencia1_garante_3, domicilio2_garante_3, referencia2_garante_3,
    ocupacion_garante_3, clasificacion_riesgo_garante_3,
    fecha_clave, accion_fecha_clave, gestor, supervisor, administrador) 
    VALUES (
    '$nombre', '$apellidos', '$dni', '$telefono', '$fecha_nacimiento', '$domicilio1', '$referencia1', '$domicilio2', '$referencia2',
    '$ocupacion', '$clasificacion_riesgo', '$agencia', '$tipo_credito', '$estado', '$fecha_desembolso', '$fecha_vencimiento',
    '$monto', '$saldo',
    '$departamento', '$provincia', '$distrito',
    '$nombre_garante', '$apellidos_garante', '$dni_garante', '$telefono_garante', '$fecha_nacimiento_garante',
    '$domicilio1_garante', '$referencia1_garante', '$domicilio2_garante', '$referencia2_garante',
    '$ocupacion_garante', '$clasificacion_riesgo_garante',
    '$nombre_garante_2', '$apellidos_garante_2', '$dni_garante_2', '$telefono_garante_2', '$fecha_nacimiento_garante_2',
    '$domicilio1_garante_2', '$referencia1_garante_2', '$domicilio2_garante_2', '$referencia2_garante_2',
    '$ocupacion_garante_2', '$clasificacion_riesgo_garante_2',
    '$nombre_garante_3', '$apellidos_garante_3', '$dni_garante_3', '$telefono_garante_3', '$fecha_nacimiento_garante_3',
    '$domicilio1_garante_3', '$referencia1_garante_3', '$domicilio2_garante_3', '$referencia2_garante_3',
    '$ocupacion_garante_3', '$clasificacion_riesgo_garante_3',
    '$fecha_clave', '$accion_fecha_clave', '$gestor', '$supervisor', '$administrador')";


    if ($conn->query($sql) === TRUE) {
        // Definir las notas del evento
        $notas_evento = "Acción requerida: " . $accion_fecha_clave .
            "\nCliente: " . $nombre . " " . $apellidos .
            "\nGestor: " . $gestor;

        // Insertar también en el calendario de eventos
        $sql_evento = "INSERT INTO eventos (
        usuario_id, 
        cliente_id, 
        tipo, 
        fecha_inicio, 
        hora_inicio, 
        duracion, 
        notas
    ) VALUES (?, ?, ?, ?, '09:00:00', 60, ?)";
        $stmt = $conn->prepare($sql_evento);

        $tipoEventoMapeado = $accion_fecha_clave;

        if (!$stmt->bind_param(
            "sssss",
            $_SESSION['usuario'],
            $dni,
            $tipoEventoMapeado,
            $fecha_clave,
            $notas_evento
        )) {
            error_log("Error al bindear parámetros: " . $stmt->error);
            $_SESSION['mensaje'] = "Error al preparar registro para calendario";
            header("Location: registrar.php");
            exit();
        }

        if (!$stmt->execute()) {
            error_log("Error en la inserción: " . $stmt->error);
            $_SESSION['mensaje'] = "Error al registrar en calendario: " . $stmt->error;
            header("Location: registrar.php");
            exit();
        }

        $_SESSION['mensaje'] = "Registro exitoso y fecha agregada al calendario";
        header("Location: index.php");
        exit();
    } else {
        $message = "Error: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <link href="css_registrar.css" rel="stylesheet">
</head>

<body class="container mt-3">
    <div class="form-container">
        <form name="registroForm" method="post" action="registrar.php" onsubmit="return validarFormulario()">
            <div class="row">
                <div class="col-md-12">
                    <div class="titulo-seccion">
                        <h5>Información del Cliente</h5>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold">Nombre</label>
                            <input type="text" name="nombre" required class="form-control" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Apellidos</label>
                            <input type="text" name="apellidos" required class="form-control" placeholder="Apellidos">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">DNI</label>
                            <input type="text" name="dni" required class="form-control" placeholder="DNI" onblur="verificarDNI(this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Teléfono</label>
                            <input type="number" name="telefono" required class="form-control" placeholder="Teléfono">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" required class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Ocupación</label>
                        <input type="text" name="ocupacion" required class="form-control" placeholder="Ocupación y Referencias">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 1</label>
                        <input type="text" name="domicilio1" required class="form-control" placeholder="Domicilio 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 1</label>
                        <input type="text" name="referencia1" required class="form-control" placeholder="Referencia 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 2</label>
                        <input type="text" name="domicilio2" class="form-control" placeholder="Domicilio 2" placeholder="Domicilio 2">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 2</label>
                        <input type="text" name="referencia2" class="form-control" placeholder="Referencia 2">
                    </div>
                    <div class="row mb-2">
                        <!-- Agencia -->
                        <div class="col-md-3">
                            <label class="fw-bold">Agencia</label>
                            <select name="agencia" required class="form-control">
                                <option value="" disabled selected>Seleccione una opción</option>
                                <option value="Ayacucho">Ayacucho</option>
                                <option value="Huancayo">Huancayo</option>
                                <option value="Lima">Lima</option>
                            </select>
                        </div>
                        <!-- Departamento -->
                        <div class="col-md-3">
                            <label class="fw-bold">Departamento</label>
                            <select id="departamento" name="departamento" class="form-control" required>
                                <option value="" disabled selected>Seleccione un departamento</option>
                            </select>
                        </div>
                        <!-- Provincia -->
                        <div class="col-md-3">
                            <label class="fw-bold">Provincia</label>
                            <select id="provincia" name="provincia" class="form-control" required disabled>
                                <option value="" disabled selected>Seleccione una provincia</option>
                            </select>
                        </div>
                        <!-- Distrito -->
                        <div class="col-md-3">
                            <label class="fw-bold">Distrito</label>
                            <select id="distrito" name="distrito" class="form-control" required disabled>
                                <option value="" disabled selected>Seleccione un distrito</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold">Tipo Crédito</label>
                            <select name="tipo_credito" required class="form-control">
                                <option value="" disabled selected>Seleccione una opción</option>
                                <option value="Diario">Diario</option>
                                <option value="Semanal">Semanal</option>
                                <option value="Mensual">Mensual</option>
                                <option value="Anual">Anual</option>
                            </select>
                        </div>
                        <!-- <div class="col-md-4">
                            <label class="fw-bold">Clas. Riesgo</label>
                            <select name="clasificacion_riesgo" required class="form-control">
                                <option value="" disabled selected>Seleccione una opción</option>
                                <option value="NOR">NOR</option>
                                <option value="CPP">CPP</option>
                                <option value="DEF">DEF</option>
                                <option value="PER">PER</option>
                            </select>
                        </div> -->
                        <div class="col-md-6">
                            <label class="fw-bold">Estado</label>
                            <select name="estado" required class="form-control">
                                <option value="" disabled selected>Estado</option>
                                <option value="Vigente">Vigente</option>
                                <option value="No vigente">No vigente</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label class="fw-bold">Monto Créd.</label>
                            <input type="number" name="monto" required step="0.01" class="form-control" placeholder="Monto Créd.">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">Fecha Desemb.</label>
                            <input type="date" name="fecha_desembolso" required class="form-control" placeholder="Fecha Desemb.">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">Saldo Créd.</label>
                            <input type="number" name="saldo" required step="0.01" class="form-control" placeholder="Saldo Créd.">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold">Fec. Vencim.</label>
                            <input type="date" name="fecha_vencimiento" required class="form-control">
                        </div>
                    </div>
                </div>
                <!-- para que aparesca aval -->
                <div id="boton-agregar-garante1" class="mb-1">
                    <button type="button" class="btn btn-primarya" onclick="mostrarGarante(1)">Agregar Aval 1</button>
                </div>
                <!-- Garante 1 (oculto inicialmente) -->
                <div id="garante1" class="col-md-12" style="display: none;">
                    <div class="titulo-seccion">
                        <h5>Información del Aval 1</h5>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold">Nombre</label>
                            <input type="text" name="nombre_garante" class="form-control" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Apellidos</label>
                            <input type="text" name="apellidos_garante" class="form-control" placeholder="Apellidos">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">DNI</label>
                            <input type="text" name="dni_garante" class="form-control" placeholder="DNI">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Teléfono</label>
                            <input type="number" name="telefono_garante" class="form-control" placeholder="Teléfono">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento_garante" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Ocupación</label>
                        <input type="text" name="ocupacion_garante" class="form-control" placeholder="Ocupación">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 1</label>
                        <input type="text" name="domicilio1_garante" class="form-control" placeholder="Domicilio 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 1</label>
                        <input type="text" name="referencia1_garante" class="form-control" placeholder="Referencia 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 2</label>
                        <input type="text" name="domicilio2_garante" class="form-control" placeholder="Domicilio 2">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 2</label>
                        <input type="text" name="referencia2_garante" class="form-control" placeholder="Referencia 2">
                    </div>
                    <!-- <div class="mb-2">
                        <label class="fw-bold">Clasificación de Riesgo</label>
                        <select name="clasificacion_riesgo_garante" class="form-control">
                            <option value="" disabled selected>Seleccione una opción</option>
                            <option value="NOR">NOR</option>
                            <option value="CPP">CPP</option>
                            <option value="DEF">DEF</option>
                            <option value="PER">PER</option>
                        </select>
                    </div> -->
                    <div class="mt-2">
                        <div class="d-flex justify-content-end ">
                            <button type="button" class="btn btn-primarya" onclick="mostrarGarante(2)">Agregar Aval 2</button>
                            <button type="button" class="btn btn-danger" onclick="cancelarGarante(1)">Cancelar Aval</button>
                        </div>
                    </div>
                </div>
                <!-- Garante 2 (oculto inicialmente) -->
                <div id="garante2" class="col-md-12 mt-1" style="display: none;">
                    <div class="titulo-seccion">
                        <h5>Información del Aval 2</h5>
                    </div>
                    <!-- Mismos campos, nombres con _garante_2 -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold">Nombre</label>
                            <input type="text" name="nombre_garante_2" class="form-control" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Apellidos</label>
                            <input type="text" name="apellidos_garante_2" class="form-control" placeholder="Apellidos">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">DNI</label>
                            <input type="text" name="dni_garante_2" class="form-control" placeholder="DNI">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Teléfono</label>
                            <input type="number" name="telefono_garante_2" class="form-control" placeholder="Teléfono">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento_garante_2" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Ocupación</label>
                        <input type="text" name="ocupacion_garante_2" class="form-control" placeholder="Ocupación">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 1</label>
                        <input type="text" name="domicilio1_garante_2" class="form-control" placeholder="Domicilio 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 1</label>
                        <input type="text" name="referencia1_garante_2" class="form-control" placeholder="Referencia 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 2</label>
                        <input type="text" name="domicilio2_garante_2" class="form-control" placeholder="Domicilio 2">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 2</label>
                        <input type="text" name="referencia2_garante_2" class="form-control" placeholder="Referencia 2">
                    </div>
                    <!-- <div class="mb-2">
                        <label class="fw-bold">Clasificación de Riesgo</label>
                        <select name="clasificacion_riesgo_garante_2" class="form-control">
                            <option value="" disabled selected>Seleccione una opción</option>
                            <option value="NOR">NOR</option>
                            <option value="CPP">CPP</option>
                            <option value="DEF">DEF</option>
                            <option value="PER">PER</option>
                        </select>
                    </div> -->

                    <div class="mt-2">
                        <div class="d-flex justify-content-end ">
                            <button type="button" class="btn btn-primarya" onclick="mostrarGarante(3)">Agregar Aval 3</button>
                            <button type="button" class="btn btn-danger" onclick="cancelarGarante(2)">Cancelar Aval</button>
                        </div>
                    </div>
                </div>

                <!-- Garante 3 (oculto inicialmente) -->
                <div id="garante3" class="col-md-12 mt-1" style="display: none;">
                    <div class="titulo-seccion">
                        <h5>Información del Aval 3</h5>
                    </div>
                    <!-- Mismos campos, nombres con _garante_3 -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="fw-bold">Nombre</label>
                            <input type="text" name="nombre_garante_3" class="form-control" placeholder="Nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold">Apellidos</label>
                            <input type="text" name="apellidos_garante_3" class="form-control" placeholder="Apellidos">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">DNI</label>
                            <input type="text" name="dni_garante_3" class="form-control" placeholder="DNI">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Teléfono</label>
                            <input type="number" name="telefono_garante_3" class="form-control" placeholder="Teléfono">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento_garante_3" class="form-control">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Ocupación</label>
                        <input type="text" name="ocupacion_garante_3" class="form-control" placeholder="Ocupación">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 1</label>
                        <input type="text" name="domicilio1_garante_3" class="form-control" placeholder="Domicilio 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 1</label>
                        <input type="text" name="referencia1_garante_3" class="form-control" placeholder="Referencia 1">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Domicilio 2</label>
                        <input type="text" name="domicilio2_garante_3" class="form-control" placeholder="Domicilio 2">
                    </div>
                    <div class="mb-2">
                        <label class="fw-bold">Referencia 2</label>
                        <input type="text" name="referencia2_garante_3" class="form-control" placeholder="Referencia 2">
                    </div>
                    <!-- <div class="mb-2">
                        <label class="fw-bold">Clasificación de Riesgo</label>
                        <select name="clasificacion_riesgo_garante_3" class="form-control">
                            <option value="" disabled selected>Seleccione una opción</option>
                            <option value="NOR">NOR</option>
                            <option value="CPP">CPP</option>
                            <option value="DEF">DEF</option>
                            <option value="PER">PER</option>
                        </select>
                    </div> -->
                    <div class="mt-2">
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-danger" onclick="cancelarGarante(3)">Cancelar Aval</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-1">
                    <div class="titulo-seccion">
                        <h5>Programación de Acciones y Responsabilidades</h5>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">Fecha clave</label>
                            <input type="date" name="fecha_clave" required class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="fw-bold">Actividad programada</label>
                            <input type="text" name="accion_fecha_clave" required class="form-control" placeholder="Actividad programada">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="fw-bold">Gestor</label>
                            <input type="text" name="gestor" required class="form-control"
                                value="<?php echo ($cargo == 'Gestor de Créditos' && isset($_SESSION['usuario'])) ? $_SESSION['usuario'] : ''; ?>"
                                <?php echo ($cargo == 'Gestor de Créditos') ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Supervisor</label>
                            <input type="text" name="supervisor" required class="form-control"
                                value="<?php echo ($cargo == 'Supervisor' && isset($_SESSION['usuario'])) ? $_SESSION['usuario'] : ''; ?>"
                                <?php echo ($cargo == 'Supervisor') ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold">Administrador</label>
                            <input type="text" name="administrador" required class="form-control"
                                value="<?php echo ($cargo == 'Administrador' && isset($_SESSION['usuario'])) ? $_SESSION['usuario'] : ''; ?>"
                                <?php echo ($cargo == 'Administrador') ? 'readonly' : ''; ?>>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed-buttonss">
                <button type="submit" class="btn btn-primary mt-3">Registrar</button>
                <button type="reset" class="btn btn-secondary mt-3">Limpiar</button>
            </div>
        </form>
    </div>
    <script src="../../inactividad.js"></script>
</body>

</html>