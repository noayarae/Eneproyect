<?php
require_once '../tcpdf/tcpdf.php';
require '../../../InterfazLogin/conexion.php';
require(__DIR__ . "/..//../../InterfazLogin/FuncionLogin/Auth/auth.php");


function formatDate($date)
{
    return date('d-m-Y', strtotime($date));
}

// Obtener parámetros del formulario
$mes = isset($_POST['mes']) ? $_POST['mes'] : date('m');
$anio = isset($_POST['anio']) ? $_POST['anio'] : date('Y');
$filtro = isset($_POST['filtro']) ? $_POST['filtro'] : 'todos';
$encabezados_prejudicial = isset($_POST['encabezados_prejudicial']) ? explode(',', $_POST['encabezados_prejudicial']) : [];
$encabezados_judicial = isset($_POST['encabezados_judicial']) ? explode(',', $_POST['encabezados_judicial']) : [];
$encabezados_sin_historial = isset($_POST['encabezados_sin_historial']) ? explode(',', $_POST['encabezados_sin_historial']) : [];

// Inicializar variables para fecha inicio y fecha fin
$fecha_inicio = isset($_POST['fecha_inicio']) && !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
$fecha_fin = isset($_POST['fecha_fin']) && !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

// Determinar rango de fechas a usar
if ($fecha_inicio && $fecha_fin) {
    // Usar rango de fechas personalizado
    $rango_fecha_inicio = $fecha_inicio;
    $rango_fecha_fin = $fecha_fin;
} else {
    // Usar mes y año
    $rango_fecha_inicio = $anio . '-' . $mes . '-01';
    $rango_fecha_fin = date('Y-m-t', strtotime($rango_fecha_inicio));
}

// Consultas para obtener los datos necesarios
$sql_prejudicial = "
    SELECT c.*, p.*
    FROM etapa_prejudicial p
    JOIN clientes c ON p.id_cliente = c.id_cliente
    WHERE p.fecha_acto BETWEEN '$rango_fecha_inicio' AND '$rango_fecha_fin'
    ORDER BY c.nombre, c.apellidos, p.fecha_acto
";
$result_prejudicial = $conn->query($sql_prejudicial);

$sql_judicial = "
    SELECT c.*, j.*
    FROM etapa_judicial j
    JOIN clientes c ON j.id_cliente = c.id_cliente
    WHERE j.fecha_judicial BETWEEN '$rango_fecha_inicio' AND '$rango_fecha_fin'
    ORDER BY c.nombre, c.apellidos, j.fecha_judicial
";
$result_judicial = $conn->query($sql_judicial);

$sql_clientes = "SELECT * FROM clientes ORDER BY nombre, apellidos";
$result_clientes = $conn->query($sql_clientes);

// Obtener la lista de clientes con historial
$clientes_con_historial = [];
while ($row = $result_prejudicial->fetch_assoc()) {
    $clientes_con_historial[$row['id_cliente']] = true;
}
while ($row = $result_judicial->fetch_assoc()) {
    $clientes_con_historial[$row['id_cliente']] = true;
}

// Obtener la lista de clientes sin historial
$clientes_sin_historial = [];
while ($row = $result_clientes->fetch_assoc()) {
    if (!isset($clientes_con_historial[$row['id_cliente']])) {
        $clientes_sin_historial[] = $row;
    }
}

$conn->close();

// Crear una instancia de TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Tu Nombre');
$pdf->SetTitle('Reporte de Historiales');
$pdf->SetSubject('Reporte de Historiales');
$pdf->SetKeywords('Reporte, Historiales, PDF');

$pdf->SetFillColor(230, 230, 230); // Color gris claro

// Establecer márgenes y formato de página
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 3, 5);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

// Configurar fuente por defecto de TCPDF (helvetica)
$pdf->SetFont('helvetica', 'B', 10);

// Construir el texto dinámico según el caso
if ($fecha_inicio && $fecha_fin) {
    $texto_fecha = 'Del ' . formatDate($fecha_inicio) . ' al ' . formatDate($fecha_fin);
} else {
    $meses = [
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre'
    ];
    $texto_fecha = 'Mes: ' . $meses[$mes] . ' - Año: ' . $anio;
}
// Imprimir ambos textos en la misma línea
$pdf->Cell(100, 8, 'REPORTE DE HISTORIALES', 0, 0, 'R');
$pdf->Cell(0, 8, $texto_fecha, 0, 1, 'L');

// Cargar datos para mostrar en el PDF
$clientes_prejudicial = [];
$result_prejudicial->data_seek(0);
while ($row = $result_prejudicial->fetch_assoc()) {
    $clientes_prejudicial[$row['id_cliente']][] = $row;
}

$clientes_judicial = [];
$result_judicial->data_seek(0);
while ($row = $result_judicial->fetch_assoc()) {
    $clientes_judicial[$row['id_cliente']][] = $row;
}

// Agregar los datos al PDF según el filtro
if ($filtro === 'con_historial') {
    foreach ($clientes_prejudicial as $id_cliente => $prejudiciales) {
        $cliente = $prejudiciales[0];
        $judiciales = $clientes_judicial[$id_cliente] ?? [];
        agregarClienteAlPDF($pdf, $cliente, $prejudiciales, $judiciales, $encabezados_prejudicial, $encabezados_judicial);
    }
} elseif ($filtro === 'sin_historial') {
    agregarClientesSinHistorialAlPDF($pdf, $clientes_sin_historial, $encabezados_sin_historial);
} else {
    foreach ($clientes_prejudicial as $id_cliente => $prejudiciales) {
        $cliente = $prejudiciales[0];
        $judiciales = $clientes_judicial[$id_cliente] ?? [];
        agregarClienteAlPDF($pdf, $cliente, $prejudiciales, $judiciales, $encabezados_prejudicial, $encabezados_judicial);
    }
    agregarClientesSinHistorialAlPDF($pdf, $clientes_sin_historial, $encabezados_sin_historial);
}

// Cerrar y generar el PDF
$pdf->Output('reporte_' . ($fecha_inicio ?: $mes . '_' . $anio) . '.pdf', 'I');

// Funciones para agregar contenido al PDF
function agregarClienteAlPDF($pdf, $cliente, $prejudiciales, $judiciales, $encabezados_prejudicial, $encabezados_judicial)
{
    // Nombre del cliente
    $pdf->SetFont('helvetica', 'B', 10);
    $nombreCompleto = 'Cliente: ' . htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellidos']);
    if (!empty($prejudiciales)) {
        $etapa = 'Etapa Pre-Judicial';
    } elseif (!empty($judiciales)) {
        $etapa = 'Etapa Judicial';
    } else {
        $etapa = '';
    }
    $pdf->Cell(100, 8, $nombreCompleto, 0, 0, 'L');
    $pdf->Cell(0, 8, $etapa, 0, 1, 'C');
    $pdf->Ln(-2);

    // Etapa Pre-Judicial
    if (!empty($prejudiciales) && !empty($encabezados_prejudicial)) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFont('helvetica', '', 10);
        agregarTablaAlPDF($pdf, $prejudiciales, $encabezados_prejudicial);
    }

    // Etapa Judicial
    if (!empty($judiciales) && !empty($encabezados_judicial)) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Etapa Judicial', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        agregarTablaAlPDF($pdf, $judiciales, $encabezados_judicial);
    }

    $pdf->Ln(1);
}

function agregarClientesSinHistorialAlPDF($pdf, $clientes_sin_historial, $encabezados_sin_historial)
{
    if (!empty($clientes_sin_historial) && !empty($encabezados_sin_historial)) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'Clientes sin Historial', 0, 1, 'L');
        $pdf->Ln(-1);
        $pdf->SetFont('helvetica', '', 10);
        agregarTablaAlPDF($pdf, $clientes_sin_historial, $encabezados_sin_historial);
    }
}

function agregarTablaAlPDF($pdf, $datos, $encabezados)
{
    // Definición de anchos de columna
    $w = [];
    foreach ($encabezados as $col) {
        switch ($col) {
            case 'Fecha':
                $w[] = 20;
                break;
            case 'Fecha Clave':
                $w[] = 23;
                break;
            case 'Acto':
                $w[] = 32;
                break;
            case 'Acción en Fecha Clave':
                $w[] = 42;
                break;
            case 'Descripción':
                $w[] = 50;
                break;
            case 'Objetivo Logrado':
                $w[] = 28;
                break;
            case 'Nombres':
                $w[] = 90;
                break;
            case 'DNI':
                $w[] = 30;
                break;
        }
    }

    // Verificar si hay espacio suficiente para el título y una fila
    if ($pdf->GetY() + 30 > $pdf->getPageHeight()) {
        $pdf->AddPage();
    }

    // Dibujar encabezado de la tabla
    $pdf->SetFont('helvetica', 'B', 8);
    foreach ($encabezados as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Datos de la tabla
    $pdf->SetFont('helvetica', '', 8);
    foreach ($datos as $row) {
        $rowData = [];
        foreach ($encabezados as $col) {
            $value = '';
            if ($col == 'Fecha') {
                $value = formatDate($row['fecha_acto'] ?? $row['fecha_judicial'] ?? '');
            } elseif ($col == 'Fecha Clave') {
                $value = formatDate($row['fecha_clave'] ?? $row['fecha_clave_judicial'] ?? '');
            } elseif ($col == 'Acto') {
                $value = $row['acto'] ?? $row['acto_judicial'] ?? '';
            } elseif ($col == 'Acción en Fecha Clave') {
                $value = $row['accion_fecha_clave'] ?? $row['accion_en_fecha_clave'] ?? '';
            } elseif ($col == 'Descripción') {
                $value = $row['descripcion'] ?? $row['descripcion_judicial'] ?? '';
            } elseif ($col == 'Objetivo Logrado') {
                $value = $row['objetivo_logrado'] ?? '';
            } elseif ($col == 'Nombres') {
                $value = $row['nombre'] . ' ' . $row['apellidos'];
            } elseif ($col == 'DNI') {
                $value = $row['dni'] ?? '';
            }
            $rowData[] = $value;
        }

        // Calcular altura de la fila basada en el contenido más largo
        $maxHeight = 0;
        foreach ($rowData as $i => $txt) {
            $nb = $pdf->getNumLines($txt, $w[$i]);
            $height = 4 * $nb;
            if ($height > $maxHeight) $maxHeight = $height;
        }

        // Si no hay espacio suficiente, añadir nueva página
        if ($pdf->GetY() + $maxHeight > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
            // Redibujar encabezados si es necesario
            $pdf->SetFont('helvetica', 'B', 8);
            foreach ($encabezados as $i => $col) {
                $pdf->Cell($w[$i], 7, $col, 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 8);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        foreach ($rowData as $i => $txt) {
            $align = in_array($encabezados[$i], ['Objetivo Logrado']) ? 'C' : 'L';
            $pdf->MultiCell($w[$i], $maxHeight, $txt, 1, $align, false, 0, '', '', true, 0, false, true, $maxHeight, 'T');
        }

        // Salto a la siguiente línea de fila
        $pdf->Ln($maxHeight);
    }

    $pdf->Ln(-1); // Espacio después de la tabla
}
