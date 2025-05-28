<?php
require '../../../InterfazLogin/conexion.php';
require(__DIR__ . "/..//../../InterfazLogin/FuncionLogin/Auth/auth.php");

$id_cliente = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';
// solo es para iniciacilar los valores, pero se puede quitar este codigo con normalidad
$cliente = [];
$monto_abonado = 0; // Inicializar con un valor predeterminado
$plazo_credito = 0; // Inicializar con un valor predeterminado

if ($id_cliente) {
    // Obtener información del cliente
    $sql_cliente = "SELECT nombre, apellidos, dni, agencia, tipo_credito, monto, saldo, fecha_desembolso, fecha_vencimiento FROM clientes WHERE id_cliente = '$id_cliente'";
    $result_cliente = $conn->query($sql_cliente);

    if ($result_cliente->num_rows > 0) {
        $cliente = $result_cliente->fetch_assoc();

        // Calcular el plazo de crédito
        $fecha_desembolso = new DateTime($cliente['fecha_desembolso']);
        $fecha_vencimiento = new DateTime($cliente['fecha_vencimiento']);
        $plazo_credito = $fecha_vencimiento->diff($fecha_desembolso)->days;

        // Calcular el monto abonado
        // Función para formatear dinero con separador de miles y 2 decimales
        function formatMoney($amount)
        {
            return number_format($amount, 2, '.', ',');
        }

        // Calcular el monto abonado
        $monto_abonado = $cliente['monto'] - $cliente['saldo'];

        // Formatear montos para mostrarlos
        $monto_formateado       = formatMoney($cliente['monto']);
        $monto_abonado_formateado = formatMoney($monto_abonado);
        $saldo_formateado       = formatMoney($cliente['saldo']);
    } else {
        die("Error: El ID del cliente no existe en la base de datos.");
    }

    // Obtener historial pre-judicial
    $sql_prejudicial = "SELECT * FROM etapa_prejudicial WHERE id_cliente = '$id_cliente'";
    $result_prejudicial = $conn->query($sql_prejudicial);

    // Obtener historial judicial
    $sql_judicial = "SELECT * FROM etapa_judicial WHERE id_cliente = '$id_cliente'";
    $result_judicial = $conn->query($sql_judicial);
} else {
    die("Error: ID del cliente no proporcionado.");
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Historial del Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <link href="bordes.css" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">
    <div class="titulo-principal">
        <h5>HISTORIAL DEL CLIENTE</h5>
    </div>
    <div class="container-fluid flex-grow-1">
        <div class="titulo-cliente border p-1">
            <h5>Informcaion del Cliente</h5>
        </div>
        <div class="client-info border p-3 mb-3">
            <div class="row">
                <!-- Primera columna -->
                <div class="col">
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellidos']); ?></p>
                    <p><strong>DNI:</strong> <?php echo htmlspecialchars($cliente['dni']); ?></p>
                </div>
                <!-- Segunda columna -->
                <div class="col">
                    <p><strong>Agencia:</strong> <?php echo htmlspecialchars($cliente['agencia']); ?></p>
                    <p><strong>Tipo de Crédito:</strong> <?php echo htmlspecialchars($cliente['tipo_credito']); ?></p>
                </div>
                <!-- tercera columna -->
                <div class="col">
                    <p><strong>Fecha de Desembolso:</strong> <?php echo DateTime::createFromFormat('Y-m-d', $cliente['fecha_desembolso'])->format('d-m-Y'); ?></p>
                    <p><strong>Fecha de Vencimiento:</strong> <?php echo DateTime::createFromFormat('Y-m-d', $cliente['fecha_vencimiento'])->format('d-m-Y'); ?></p>
                </div>
                <!-- cuarta columna -->
                <div class="col">
                    <p><strong>Monto:</strong> <?php echo htmlspecialchars($monto_formateado); ?></p>
                    <p><strong>Plazo de Crédito (días):</strong> <?php echo htmlspecialchars($plazo_credito); ?></p>
                </div>
                <!-- quinta columna -->
                <div class="col">
                    <p><strong>Monto Abonado:</strong> <?php echo htmlspecialchars($monto_abonado_formateado); ?></p>
                    <p><strong>Saldo:</strong> <?php echo htmlspecialchars($saldo_formateado); ?></p>
                </div>
            </div>
        </div>
        <div class="historial border p-0">
            <div class="mb-4">
                <?php if ($result_prejudicial->num_rows > 0): ?>
                    <div class="table-responsive">
                        <h5>Historial Pre-Judicial</h5>
                    </div>
                    <div class="scrollable-table-container">
                        <table class="table table-bordered table-striped table-fixed">
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Fecha Acto</th>
                                    <th>Acto</th>
                                    <th>N° de Notif. Voucher</th>
                                    <th>Descripción</th>
                                    <th>Notificación</th>
                                    <th>Evidencia 1</th>
                                    <th>Evidencia 2</th>
                                    <th>Fecha Clave</th>
                                    <th>Acción en Fecha Clave</th>
                                    <th>Actor</th>
                                    <th>Días desde Fecha Clave</th>
                                    <th>Objetivo Logrado</th>
                                    <th>Días de Mora</th>
                                    <th>Días Mora PJ</th>
                                    <th>Interés</th>
                                    <th>Saldo más Interés</th>
                                    <th>Monto Amortizado</th>
                                    <th>Saldo a la Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = 1;
                                mysqli_data_seek($result_prejudicial, 0); // Reiniciar puntero
                                while ($row = $result_prejudicial->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$count}</td>
                                        <td>" . (!empty($row['fecha_acto']) ? DateTime::createFromFormat('Y-m-d', $row['fecha_acto'])->format('d-m-Y') : '') . "</td>
                                        <td>{$row['acto']}</td>
                                        <td>{$row['n_de_notif_voucher']}</td>
                                        <td>{$row['descripcion']}</td>
                                        <td>" . (isset($row['notif_compromiso_pago_evidencia']) && !empty($row['notif_compromiso_pago_evidencia']) && $row['notif_compromiso_pago_evidencia'] !== 'uploads/' ? "<a href='#' class='file-link' data-url='../pre_judicial/{$row['notif_compromiso_pago_evidencia']}'>{$row['notif_compromiso_pago_evidencia']}</a> <a href='../pre_judicial/{$row['notif_compromiso_pago_evidencia']}' download style='margin-left:10px; color:blue; font-weight:bold;'>📥Descargar</a>" : '') . "</td>
                                        <td>" . (isset($row['evidencia1_localizacion']) && !empty($row['evidencia1_localizacion']) && $row['evidencia1_localizacion'] !== 'uploads/' ? "<a href='#' class='file-link' data-url='../pre_judicial/{$row['evidencia1_localizacion']}'>{$row['evidencia1_localizacion']}</a> <a href='../pre_judicial/{$row['evidencia1_localizacion']}' download style='margin-left:10px; color:blue; font-weight:bold;'>📥Descargar</a>" : '') . "</td>
                                        <td>" . (isset($row['evidencia2_foto_fecha']) && !empty($row['evidencia2_foto_fecha']) && $row['evidencia2_foto_fecha'] !== 'uploads/' ? "<a href='#' class='file-link' data-url='../pre_judicial/{$row['evidencia2_foto_fecha']}'>{$row['evidencia2_foto_fecha']}</a> <a href='../pre_judicial/{$row['evidencia2_foto_fecha']}' download style='margin-left:10px; color:blue; font-weight:bold;'>📥Descargar</a>" : '') . "</td>
                                        <td>" . (!empty($row['fecha_clave']) ? DateTime::createFromFormat('Y-m-d', $row['fecha_clave'])->format('d-m-Y') : '') . "</td>
                                        <td>{$row['accion_fecha_clave']}</td>
                                        <td>{$row['actor']}</td>
                                        <td>{$row['dias_desde_fecha_clave']}</td>
                                        <td>{$row['objetivo_logrado']}</td>
                                        <td>{$row['dias_de_mora']}</td>
                                        <td>{$row['dias_mora_PJ']}</td>
                                        <td>{$row['interes']}</td>
                                        <td>" . htmlspecialchars(formatMoney((float)($row['saldo_int'] ?? 0))) . "</td>
                                        <td>" . htmlspecialchars(formatMoney((float)($row['monto_amortizado'] ?? 0))) . "</td>
                                        <td>" . htmlspecialchars(formatMoney((float)($row['saldo_fecha'] ?? 0))) . "</td>
                                    </tr>";
                                    $count++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-2">No tiene historial registrado.</div>
                <?php endif; ?>
            </div>

            <!-- Historial Judicial -->
            <?php if ($result_judicial->num_rows > 0): ?>
                <div class="mb-4">
                    <div class="table-responsive">
                        <h5>Historial Judicial</h5>
                    </div>
                    <div class="scrollable-table-container">
                        <table class="table table-bordered table-striped table-fixed-judicial">
                            <thead>
                                <tr>
                                    <th>Nº</th>
                                    <th>Etapa</th>
                                    <th>Fecha</th>
                                    <th>Acto</th>
                                    <th>Juzgado</th>
                                    <th>N° Expediente</th>
                                    <th>N° Cédula</th>
                                    <th>Descripción</th>
                                    <th>Doc. Evidencia</th>
                                    <th>Fecha Clave</th>
                                    <th>Acción en Fecha Clave</th>
                                    <th>Actor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($result_judicial, 0); // Reiniciar puntero
                                while ($row = $result_judicial->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$count}</td>
                                        <td>{$row['etapa']}</td>
                                        <td>" . (!empty($row['fecha_judicial']) ? DateTime::createFromFormat('Y-m-d', $row['fecha_judicial'])->format('d-m-Y') : '') . "</td>
                                        <td>{$row['acto_judicial']}</td>
                                        <td>{$row['juzgado']}</td>
                                        <td>{$row['n_exp_juzgado']}</td>
                                        <td>{$row['n_cedula']}</td>
                                        <td>{$row['descripcion_judicial']}</td>
                                        <td>" . (isset($row['doc_evidencia']) && !empty($row['doc_evidencia']) && $row['doc_evidencia'] !== 'uploads/' ? "<a href='#' class='file-link' data-url='../judicial/{$row['doc_evidencia']}'>{$row['doc_evidencia']}</a> <a href='../judicial/{$row['doc_evidencia']}' download style='margin-left:10px; color:blue; font-weight:bold;'>📥Descargar</a>" : '') . "</td>
                                        <td>" . (!empty($row['fecha_clave_judicial']) ? DateTime::createFromFormat('Y-m-d', $row['fecha_clave_judicial'])->format('d-m-Y') : '') . "</td>
                                        <td>{$row['accion_en_fecha_clave']}</td>
                                        <td>{$row['actor_judicial']}</td>
                                    </tr>";
                                    $count++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="fixed-buttons mt-2 p-1">
        <button type="button" class="btn btn-primary" onclick="agregarHistoria()">Agregar Historia</button>
        <!-- <button type="button" class="btn btn-secondary" onclick="history.back()">Regresar</button> -->
        <button type="button" class="btn btn-danger" onclick="window.location.href='../registro_cliente/index.php'">Salir</button>
    </div>

    <!-- Contenedor de vista previa -->
    <div class="preview-container" id="previewContainer">
        <button class="close-btn" onclick="closePreview()">Cerrar</button>
        <iframe id="previewFrame"></iframe>
    </div>

    <script>
        function agregarHistoria() {
            var idCliente = <?php echo json_encode($id_cliente); ?>;
            if (idCliente) {
                window.location.href = '../pre_judicial/registro_prejudicial.php?id_cliente=' + encodeURIComponent(idCliente);
            } else {
                alert("Error: ID del cliente no disponible.");
            }
        }

        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('file-link')) {
                event.preventDefault();
                var fileUrl = event.target.getAttribute('data-url');
                openPreview(fileUrl);
            }
        });
        //Para la vista previa
        function openPreview(url) {
            var previewContainer = document.getElementById('previewContainer');
            var previewFrame = document.getElementById('previewFrame');
            previewFrame.src = url;
            previewFrame.onload = function() {
                previewContainer.style.display = 'flex'; // Usa flex para centrar
            };
            previewFrame.onerror = function() {
                alert('El archivo no se puede mostrar. Verifica la URL o la existencia del archivo.');
            };
        }

        function closePreview() {
            var previewContainer = document.getElementById('previewContainer');
            previewContainer.style.display = 'none';
        }
    </script>
</body>

</html>