<?php
require '../../../InterfazLogin/conexion.php';
require(__DIR__ . "/..//../../InterfazLogin/FuncionLogin/Auth/auth.php");

$usuario_id = $_SESSION['usuario'] ?? '';

$id_cliente = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';
$cliente = [];
$monto_abonado = 0; // Inicializar con un valor predeterminado
$plazo_credito = 0; // Inicializar con un valor predeterminado

if ($id_cliente) {
    $sql = "SELECT nombre, apellidos, dni, monto, saldo, fecha_desembolso, fecha_vencimiento, agencia, tipo_credito FROM clientes WHERE id_cliente='$id_cliente'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();

        // Calcular Monto Abonado
        $monto_abonado = $cliente['monto'] - $cliente['saldo'];
        // Formatear los montos para mostrarlos con separador de miles y dos decimales
        function formatMoney($amount)
        {
            return number_format($amount, 2, '.', ',');
        }
        $monto_formateado       = formatMoney($cliente['monto']);
        $monto_abonado_formateado = formatMoney($monto_abonado);
        $saldo_formateado       = formatMoney($cliente['saldo']);

        $fecha_desembolso = new DateTime($cliente['fecha_desembolso']);
        $fecha_vencimiento = new DateTime($cliente['fecha_vencimiento']);
        $plazo_credito = $fecha_vencimiento->diff($fecha_desembolso)->days;

        // Verificar si el cliente ya pasó a la etapa judicial
        $sql_check_judicial = "SELECT COUNT(*) as count FROM etapa_prejudicial WHERE id_cliente = $id_cliente AND acto = 'Pasa a Judicial'";
        $result_check_judicial = $conn->query($sql_check_judicial);
        if ($result_check_judicial->num_rows > 0) {
            $row_check_judicial = $result_check_judicial->fetch_assoc();
            if ($row_check_judicial['count'] > 0) {
                $etapa_judicial = true;
            }
        }
        // Formatear la fecha de desembolso
        $fecha_desembolso = isset($cliente['fecha_desembolso']) ? new DateTime($cliente['fecha_desembolso']) : null;
        $fecha_desembolso_formateada = $fecha_desembolso ? $fecha_desembolso->format('d/m/Y') : '';

        // Formatear la fecha de vencimiento
        $fecha_vencimiento = isset($cliente['fecha_vencimiento']) ? new DateTime($cliente['fecha_vencimiento']) : null;
        $fecha_vencimiento_formateada = $fecha_vencimiento ? $fecha_vencimiento->format('d/m/Y') : '';
    } else {
        die("Error: El ID del cliente no existe en la base de datos.");
    }
} else {
    die("Error: ID del cliente no proporcionado.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["tipo_formulario"]) && $_POST["tipo_formulario"] === "prejudicial") {
    /* $fecha_acto = date('Y-m-d H:i:s'); */
    $fecha_acto = $_POST["fecha_acto"]; /* asta mientras esta esto */

    // Obtener la fecha de inicio del caso (fecha_acto del primer registro)
    $sql_select_inicio = "SELECT fecha_acto, saldo_int FROM etapa_prejudicial WHERE id_cliente = $id_cliente ORDER BY id ASC LIMIT 1";
    $result_inicio = $conn->query($sql_select_inicio);

    if ($result_inicio->num_rows > 0) {
        $row_inicio = $result_inicio->fetch_assoc();
        $fecha_inicio_caso = $row_inicio['fecha_acto'];
        $saldo_int_anterior = $row_inicio['saldo_int'];
    } else {
        // Si no hay registros, fecha_inicio_caso es la misma que fecha_acto
        $fecha_inicio_caso = $fecha_acto;
        $saldo_int_anterior = null;
    }

    // Datos de la etapa pre-judicial
    $etapa = "Pre Judicial"; // Establecer automáticamente como "Judicial"
    $acto = $_POST["acto"];
    $n_de_notif_voucher = $_POST["n_de_notif_voucher"] ? $_POST["n_de_notif_voucher"] : '';
    $descripcion = $_POST["descripcion"];
    $notif_compromiso_pago_evidencia = $_FILES["notif_compromiso_pago_evidencia"]["name"];
    $fecha_clave = $_POST["fecha_clave"];
    $accion_fecha_clave = $_POST["accion_fecha_clave"];
    $actor = $_POST["actor"];
    $evidencia1_localizacion = $_FILES["evidencia1_localizacion"]["name"];
    $evidencia2_foto_fecha = $_FILES["evidencia2_foto_fecha"]["name"];
    $monto_amortizado = $_POST["monto_amortizado"] ? $_POST["monto_amortizado"] : '0';

    // Calcular dias_mora_PJ
    $dias_mora_PJ = calcularDiasMoraPJ($fecha_acto, $fecha_inicio_caso);
    // Calcular dias_de_mora usando la fecha de vencimiento del cliente
    $dias_de_mora = calcularDiasDeMora($fecha_acto, $cliente['fecha_vencimiento']);
    // Asignar el valor de dias_mora_PJ a interes
    $interes = $dias_mora_PJ;
    // Calcular saldo_int
    if ($saldo_int_anterior === null) {
        $saldo_int = $cliente['saldo'];
    } else {
        $sql_select_anterior = "SELECT saldo_fecha FROM etapa_prejudicial WHERE id_cliente = $id_cliente ORDER BY id DESC LIMIT 1";
        $result_anterior = $conn->query($sql_select_anterior);
        if ($result_anterior->num_rows > 0) {
            $row_anterior = $result_anterior->fetch_assoc();
            $saldo_int = $row_anterior['saldo_fecha'] + $interes;
        } else {
            $saldo_int = $cliente['saldo'];
        }
    }
    // Calcular saldo_fecha
    $saldo_fecha = $saldo_int - $monto_amortizado;

    // Directorio de destino para los archivos subidos
    $target_dir = "uploads/";
    // Crear el directorio si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    move_uploaded_file($_FILES["notif_compromiso_pago_evidencia"]["tmp_name"], $target_dir . $notif_compromiso_pago_evidencia);
    move_uploaded_file($_FILES["evidencia1_localizacion"]["tmp_name"], $target_dir . $evidencia1_localizacion);
    move_uploaded_file($_FILES["evidencia2_foto_fecha"]["tmp_name"], $target_dir . $evidencia2_foto_fecha);

    // Insertar el nuevo registro
    $sql_insert = "INSERT INTO etapa_prejudicial (id_cliente, etapa, fecha_acto, acto, n_de_notif_voucher, descripcion, notif_compromiso_pago_evidencia, fecha_clave, accion_fecha_clave, actor, evidencia1_localizacion, evidencia2_foto_fecha, dias_de_mora, dias_mora_PJ, interes, saldo_int, monto_amortizado, saldo_fecha)
    VALUES ('$id_cliente', '$etapa', '$fecha_acto', '$acto', '$n_de_notif_voucher', '$descripcion', '$target_dir$notif_compromiso_pago_evidencia', '$fecha_clave', '$accion_fecha_clave', '$actor', '$target_dir$evidencia1_localizacion', '$target_dir$evidencia2_foto_fecha', '$dias_de_mora', '$dias_mora_PJ', '$interes', '$saldo_int', '$monto_amortizado', '$saldo_fecha')";

    if ($conn->query($sql_insert) === TRUE) {
        $last_id = $conn->insert_id;
        actualizarFilaAnterior($conn, $last_id, $fecha_acto, $id_cliente);
        // Registrar el evento en el calendario
        $fecha_clave = $_POST["fecha_clave"];
        $accion_fecha_clave = $_POST["accion_fecha_clave"];
        $actor = $_POST["actor"];

        // Definir las notas del evento
        $notas_evento = "Acción requerida: " . $accion_fecha_clave .
            "\nDescripción: " . $descripcion .
            "\nCliente: " . $cliente['nombre'] . " " . $cliente['apellidos'] .
            "\nActor: " . $actor;

        // Mapear el tipo de evento según el tipo de crédito
        $tipoEventoMapeado = $acto;

        // Insertar evento en el calendario
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
        $stmt->bind_param(
            "sssss",
            $_SESSION['usuario'],
            $cliente['dni'],
            $tipoEventoMapeado,   // Tipo de evento 
            $fecha_clave,         // Fecha clave del formulario
            $notas_evento         // Notas con la acción y detalles
        );

        if ($stmt->execute()) {
            $message = "Registro exitoso y evento agregado al calendario";
        } else {
            $message = "Registro exitoso, pero error al agregar evento: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error: " . $sql_insert . "<br>" . $conn->error;
    }
}

// Función para calcular dias_mora_PJ
function calcularDiasMoraPJ($fecha_acto, $fecha_inicio_caso)
{
    $fecha_acto = new DateTime($fecha_acto);
    $fecha_inicio_caso = new DateTime($fecha_inicio_caso);
    $interval = $fecha_acto->diff($fecha_inicio_caso);
    return $interval->days;
}

//correcto
function calcularDiasDeMora($fecha_acto, $fecha_vencimiento)
{
    $fecha_acto = new DateTime($fecha_acto);
    $fecha_vencimiento = new DateTime($fecha_vencimiento);
    // Si la fecha_acto es menor o igual a fecha_vencimiento, devolver 0
    if ($fecha_acto <= $fecha_vencimiento) {
        return 0;
    }
    // Calcular la diferencia de días
    $interval = $fecha_acto->diff($fecha_vencimiento);
    return $interval->days;
}

//correcto
function actualizarFilaAnterior($conn, $last_id, $fecha_acto, $id_cliente)
{
    $sql_select = "SELECT id, fecha_clave, actor FROM etapa_prejudicial WHERE id_cliente = $id_cliente AND id < $last_id ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql_select);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id_anterior = $row['id'];
        $fecha_clave_anterior = $row['fecha_clave'];
        $actor = $row['actor'];

        $dias_desde_fecha_clave = calcularDiasDesdeFechaClave($fecha_acto, $fecha_clave_anterior);

        if ($actor == "Gestor") {
            $objetivo_logrado = $dias_desde_fecha_clave <= 0 ? "SI" : "NO";
        } else {
            $objetivo_logrado = "";
        }

        $sql_update = "UPDATE etapa_prejudicial SET dias_desde_fecha_clave = $dias_desde_fecha_clave, objetivo_logrado = '$objetivo_logrado' WHERE id = $id_anterior";
        $conn->query($sql_update);
    }
}


function calcularDiasDesdeFechaClave($fecha_acto_siguiente, $fecha_clave)
{
    $fecha_acto_siguiente = new DateTime($fecha_acto_siguiente);
    $fecha_clave = new DateTime($fecha_clave);
    $interval = $fecha_acto_siguiente->diff($fecha_clave);
    $dias = $interval->days;

    if ($fecha_clave > $fecha_acto_siguiente) {
        $dias = -$dias - 1;
    }

    return $dias;
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Registro de Etapas Pre-Judicial y Judicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="validacion_etapas.js" defer></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>

<body class="d-flex flex-column h-100">
    <div class="container-fluid flex-grow-1">
        <div class="titulo-historial">
            <h5 class="titulo-principal-info">Agregar Historial del Clinete</h5>
        </div>
        <div class="client-info border p-2 mb-3">
            <div class="row">
                <div>
                    <h5 class="titulo-informacion-cliente">Informacion Del cliente</h5>
                </div>
                <!-- Primera columna -->
                <div class="col">
                    <p><strong>Nombres:</strong> <?php echo htmlspecialchars(isset($cliente['nombre']) ? $cliente['nombre'] . ' ' . $cliente['apellidos'] : ''); ?></p>
                    <p><strong>DNI:</strong> <?php echo htmlspecialchars($cliente['dni'] ?? ''); ?></p>
                </div>

                <!-- Segunda columna -->
                <div class="col">
                    <p><strong>Agencia:</strong> <?php echo htmlspecialchars($cliente['agencia'] ?? ''); ?></p>
                    <p><strong>Tipo de Crédito:</strong> <?php echo htmlspecialchars($cliente['tipo_credito'] ?? ''); ?></p>
                </div>

                <!-- Tercera columna -->
                <div class="col">
                    <p><strong>Fecha Desembolso:</strong> <?php echo htmlspecialchars($fecha_desembolso_formateada); ?></p>
                    <p><strong>Fecha Vencimiento:</strong> <?php echo htmlspecialchars($fecha_vencimiento_formateada); ?></p>
                </div>

                <!-- Cuarta columna -->
                <div class="col">
                    <p><strong>Monto:</strong> <?php echo htmlspecialchars($monto_formateado); ?></p>
                    <p><strong>Plazo de Crédito (días):</strong> <?php echo htmlspecialchars($plazo_credito); ?></p>
                </div>

                <!-- Quinta columna -->
                <div class="col">
                    <p><strong>Monto Abonado:</strong> <?php echo htmlspecialchars($monto_abonado_formateado); ?></p>
                    <p><strong>Saldo:</strong> <?php echo htmlspecialchars($saldo_formateado); ?></p>
                </div>
            </div>
        </div>

        <div class="row form-etapas">
            <div class="col-md-12 border p-2">
                <?php if ($message): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Etapa Pre-Judicial -->
                <form id="preJudicialForm" method="post" enctype="multipart/form-data" class="form-container <?php echo !$etapa_judicial ? 'active' : ''; ?>" onsubmit="return enviarFormulario()">
                    <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($id_cliente); ?>">
                    <input type="hidden" name="tipo_formulario" value="prejudicial"> <!-- 👈 Este es el nuevo campo -->
                    <h5 class="titulo-etapas">Etapa Pre-judicialL</h5>
                    <!-- Fecha Acto solamente para prueba -->
                    <div class="mb-2">
                        <label class="fw-bold">Fecha Acto:</label>
                        <input type="date" name="fecha_acto" required class="form-control">
                    </div>
                    <!-- asta aqui -->
                    <!-- Nueva disposición en columnas -->
                    <div class="row">
                        <!-- Columna izquierda: Acto, Monto Amortizado y Número de Notificación/Voucher -->
                        <div class="col-md-5">
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-3 fw-bold">
                                    Acto:
                                </div>
                                <div class="col-md-9">
                                    <select name="acto" required class="form-control">
                                        <option value="" disabled selected>Seleccione una opción</option>
                                        <option value="Inicio caso prejudicial">Inicio caso prejudicial</option>
                                        <option value="Notificacion">Notificación</option>
                                        <option value="Amortizacion">Amortización</option>
                                        <option value="Cambio Gestor">Cambio Gestor</option>
                                        <option value="Postergacion">Postergación</option>
                                        <option value="Fin de caso">Fin de caso</option>
                                        <option value="Pasa a Judicial">Pasa a Judicial</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-3 fw-bold">
                                    Monto Amortizado:
                                </div>
                                <div class="col-md-9">
                                    <input type="number" name="monto_amortizado" class="form-control">
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-3 fw-bold">
                                    Núm. de Notificación:
                                </div>
                                <div class="col-md-9">
                                    <input type="text" name="n_de_notif_voucher" class="form-control">
                                </div>
                            </div>
                        </div>
                        <!-- Columna derecha: Descripción -->
                        <div class="col-md-7">
                            <!-- <div class="mb-2">
                                <label class="fw-bold">Descripción:</label>
                                <textarea name="descripcion" required class="form-control" rows="4"></textarea>
                            </div> -->
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-1 fw-bold">
                                    Descripción:
                                </div>
                                <div class="col-md-11">
                                    <textarea name="descripcion" required class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-1 fw-bold">
                                Subir Notificación:
                            </div>
                            <div class="col-md-8">
                                <input type="file" id="notif_compromiso_pago_evidencia"
                                    name="notif_compromiso_pago_evidencia" accept=".docx, .pdf, .jpg, .png"
                                    class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-danger btn-cancelar"
                                    onclick="limpiarArchivo('notif_compromiso_pago_evidencia')">Cancelar</button>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-1 fw-bold">
                                Evidencia 1:
                            </div>
                            <div class="col-md-4">
                                <input type="file" id="evidencia1_localizacion" name="evidencia1_localizacion"
                                    accept="image/*" class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-danger btn-cancelar"
                                    onclick="limpiarArchivo('evidencia1_localizacion')">Remover</button>
                            </div>

                            <div class="col-md-1 fw-bold">
                                Evidencia 2:
                            </div>
                            <div class="col-md-4">
                                <input type="file" id="evidencia2_foto_fecha" name="evidencia2_foto_fecha"
                                    accept="image/*" class="form-control">
                            </div>
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-danger btn-cancelar"
                                    onclick="limpiarArchivo('evidencia2_foto_fecha')">Remover</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <h6 class="titulo-etapas">Próxima Fecha</h6>
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-1 fw-bold">
                                Fecha Clave:
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_clave" required class="form-control">
                            </div>

                            <div class="col-md-1 fw-bold">
                                Acción en Fecha Clave:
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="accion_fecha_clave" required class="form-control">
                            </div>
                            <div class="col-md-1 fw-bold">
                                Actor Involucrado:
                            </div>
                            <div class="col-md-3">
                                <select name="actor" required class="form-control">
                                    <option value="" disabled selected>Seleccione una opción</option>
                                    <option value="Gestor">Gestor</option>
                                    <option value="Cliente">Cliente</option>
                                    <option value="Supervisor">Supervisor</option>
                                    <option value="Administrador">Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="fixed-buttons">
                        <button type="submit" class="btn btn-primary mt-3">Registrar</button>
                        <button type="reset" class="btn btn-secondary mt-3">Limpiar</button>
                        <button type="button" class="btn btn-info mt-3" onclick="verHistorial(<?php echo htmlspecialchars($id_cliente); ?>)">Ver Historial</button>
                        <!-- <button type="button" class="btn btn-success mt-3" onclick="history.back()">Regresar</button> -->
                        <button type="button" class="btn btn-danger mt-3" onclick="window.location.href='../registro_cliente/index.php'">Salir</button>
                    </div>
                </form>

                <!-- Formulario de Etapa Judicial -->
                <form id="judicialForm" method="post" enctype="multipart/form-data" class="form-container <?php echo $etapa_judicial ? 'active' : ''; ?>">
                    <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($id_cliente); ?>">
                    <input type="hidden" name="tipo_formulario" value="judicial"> <!-- 👈 Este es el nuevo campo -->
                    <h5 class="titulo-etapas">Etapa Judicial</h5>
                    <div id="message"></div>
                    <!-- borrar de aqui, solo esta por mientras -->
                    <div class="mb-2">
                        <label class="fw-bold">Fecha Acto:</label>
                        <input type="date" name="fecha_judicial" required class="form-control">
                    </div>
                    <!-- asta aqui -->
                    <div class="row mb-2">
                        <div class="row mb-1 align-items-center">
                            <div class="col-md-1 fw-bold">
                                Acto:
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="acto_judicial" required class="form-control">
                            </div>

                            <div class="col-md-1 fw-bold">
                                Juzgado:
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="juzgado" required class="form-control">
                            </div>
                        </div>
                    </div>
                    <!-- Nueva disposición en columnas -->
                    <div class="row">
                        <div class="col-md-5">
                            <div class="md-2">
                                <div class="row mb-2 align-items-center">
                                    <div class="col-md-3 fw-bold">
                                        N° Exped./Juzgado:
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" name="n_exp_juzgado" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="md-2 ">
                                <div class="row mb-2 align-items-center">
                                    <div class="col-md-3 fw-bold">
                                        Número de Cédula:
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" name="n_cedula" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Columna derecha: Descripción -->
                        <div class="col-md-7">
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-1 fw-bold">
                                    Descripción:
                                </div>
                                <div class="col-md-11">
                                    <textarea name="descripcion_judicial" required class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- asta aqui -->
                    <div class="row mb-2 align-items-center">
                        <div class="col-md-1 fw-bold">
                            Doc. Evidencia:
                        </div>
                        <div class="col-md-8">
                            <input type="file" id="doc_evidencia" name="doc_evidencia" accept=".docx, .pdf, .jpg, .jpeg, .png" required class="form-control">
                        </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <button type="button" class="btn btn-danger btn-cancelar" onclick="limpiarArchivo('doc_evidencia')">Remover</button>
                        </div>
                    </div>

                    <div class="col-md-12 ">
                        <h5 class="titulo-etapas">Próxima Fecha</h5>
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-1 fw-bold">
                                Fecha Clave:
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_clave_judicial" required class="form-control">
                            </div>

                            <div class="col-md-1 fw-bold text-end">
                                Acción en Fecha Clave:
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="accion_en_fecha_clave" required class="form-control">
                            </div>
                            <div class="col-md-1 fw-bold">
                                Actor Involucrado:
                            </div>

                            <div class="col-md-3">
                                <select name="actor_judicial" required class="form-control">
                                    <option value="" disabled selected>Seleccione una opción</option>
                                    <option value="Gestor">Gestor</option>
                                    <option value="Juez">Juez</option>
                                    <option value="Abogado">Abogado</option>
                                    <option value="Supervisor">Supervisor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="fixed-buttons">
                        <button type="submit" class="btn btn-primary mt-3">Registrar</button>
                        <button type="reset" class="btn btn-secondary mt-3">Limpiar</button>
                        <button type="button" class="btn btn-info mt-3" onclick="verHistorial(<?php echo htmlspecialchars($id_cliente); ?>)">Ver Historial</button>
                        <!-- <button type="button" class="btn btn-success mt-3" onclick="history.back()">Regresar</button> -->
                        <button type="button" class="btn btn-danger mt-3" onclick="window.location.href='../registro_cliente/index.php'">Salir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        /* document.querySelector('input[type="date"]').addEventListener('change', function(e) {
            let fecha = new Date(e.target.value);
            let fechaFormateada = fecha.toLocaleDateString('es-ES');
            e.target.value = fechaFormateada;
        }); */

        function limpiarArchivo(inputId) {
            document.getElementById(inputId).value = '';
        }

        function verHistorial(idCliente) {
            if (idCliente) {
                window.location.href = '../historial/record.php?id_cliente=' + encodeURIComponent(idCliente);
            } else {
                alert("Error: ID del cliente no disponible.");
            }
        }

        function enviarFormulario(event) {
            // Validar el formulario antes de evitar el envío tradicional
            if (!validarFormularioPreJudicial()) {
                return false; // Si la validación falla, no se envía el formulario
            }
            event.preventDefault(); // Evita el envío tradicional del formulario

            var formData = new FormData(document.getElementById('preJudicialForm'));

            fetch('registro_prejudicial.php', { // Asegúrate de que la URL sea correcta
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Maneja la respuesta del servidor
                    alert('Registro exitoso');
                    // Recargar la página para mostrar el formulario judicial si es necesario
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Hubo un error al registrar los datos.');
                });

            return false; // Evita el comportamiento predeterminado del formulario
        }

        document.getElementById('judicialForm').addEventListener('submit', function(event) {
            // Validar el formulario judicial antes de enviar
            if (!validarFormularioJudicial()) {
                event.preventDefault(); // Evita el envío del formulario si la validación falla
                return false; // Si la validación falla, no se envía el formulario
            }
            event.preventDefault(); // Evita el envío del formulario si la validación falla
            var formData = new FormData(this);

            fetch('../judicial/registro_judicial.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Muestra el mensaje de éxito o error
                    document.getElementById('message').innerHTML = data;
                    // Limpia el formulario después de registrar
                    document.getElementById('judicialForm').reset();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    </script>
</body>

</html>