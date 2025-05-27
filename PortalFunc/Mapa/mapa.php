<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");
require(__DIR__ . "/../../InterfazLogin/conexion.php");

$usuario = $_SESSION['usuario'];
$correo = $_SESSION['correo'];
$cargo = $_SESSION['cargo'];
$telefono = $_SESSION['telefono'];

// Obtener clientes de la base de datos
$clientes_db = [];
$sql = "SELECT id_cliente, nombre, apellidos, domicilio1, tipo_credito, estado, agencia FROM clientes";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clientes_db[] = $row;
    }
}

// Obtener departamentos para filtros
$departamentos_db = [];
$sql_deptos = "SELECT DISTINCT departamento FROM ubicaciones_peru";
$result_deptos = $conn->query($sql_deptos);

if ($result_deptos->num_rows > 0) {
    while($row = $result_deptos->fetch_assoc()) {
        $departamentos_db[] = $row['departamento'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Clientes | Eneproyect</title>
    <link rel="stylesheet" href="mapa.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-bars toggle-sidebar"></i> <span class="menu-text">Eneproyect</span></h2>
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($usuario); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($correo); ?></div>
                <?php if(isset($cargo) && !empty($cargo)): ?>
                <div class="user-position"><?php echo htmlspecialchars($cargo); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <ul>
        <li><a href="../Mis_Clientes/registro_cliente/index.php"><i class="fas fa-users"></i> Mis Clientes</a></li>
        <li><a href="../Calendario/calendario.php"><i class="fas fa-calendar-alt"></i> Calendario</a></li>
        <li><a href="../Mapa/mapa.php" class="active"><i class="fas fa-map-marked-alt"></i> Mapa</a></li>
        <li><a href="../Ajustes/ajustes.php"><i class="fas fa-cog"></i> Ajustes</a></li>
        <li class="logout-item"><a href="/PortalFunc/Logout/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
    </ul>
</aside>

<!-- Contenido principal -->
<main class="content">
    <header class="mapa-header">
        <h1><i class="fas fa-map-marked-alt"></i> Mapa de Clientes</h1>
        <div class="header-actions">
            <button id="locate-me"><i class="fas fa-location-arrow"></i> Mi ubicación</button>
            <button id="add-client"><i class="fas fa-plus"></i> Agregar Cliente</button>
            <button id="geocode-all" class="btn-warning"><i class="fas fa-map-marked-alt"></i> Geocodificar</button>
        </div>
    </header>

<!-- Reemplaza la sección de controles y estadísticas con esto: -->

    <section class="map-controls">
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="search" placeholder="Buscar cliente...">
            <div id="search-results" class="search-results"></div>
        </div>
        
        <select id="filtroDepartamento" class="custom-select select2" data-placeholder="Departamento">
            <option value=""></option>
            <?php foreach($departamentos_db as $depto): ?>
                <option value="<?= htmlspecialchars($depto) ?>"><?= htmlspecialchars($depto) ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="filtroProvincia" class="custom-select select2" disabled data-placeholder="Provincia">
            <option value=""></option>
        </select>
        
        <select id="filtroDistrito" class="custom-select select2" disabled data-placeholder="Distrito">
            <option value=""></option>
        </select>
        
        <select id="filtroTipo" class="custom-select select2" data-placeholder="Tipo">
            <option value=""></option>
            <option value="Diario">Diario</option>
            <option value="Semanal">Semanal</option>
            <option value="Mensual">Mensual</option>
            <option value="Anual">Anual</option>
        </select>
        
        <select id="filtroEstado" class="custom-select select2" data-placeholder="Estado">
            <option value=""></option>
            <option value="Vigente">Vigente</option>
            <option value="No vigente">No vigente</option>
        </select>
        
        <button id="resetMap" class="btn-primary"><i class="fas fa-sync-alt"></i></button>
        <div class="stats-toggle" title="Mostrar estadísticas">
            <i class="fas fa-chart-bar"></i>
        </div>
    </section>

    <div id="map"></div>

    <div class="map-stats">
        <div class="stat-card">
            <span class="stat-number" id="total-clientes">0</span>
            <span class="stat-label">Clientes totales</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="clientes-vigentes">0</span>
            <span class="stat-label">Clientes vigentes</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="clientes-geocodificados">0</span>
            <span class="stat-label">Geocodificados</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="clientes-vista">0</span>
            <span class="stat-label">En vista</span>
        </div>
    </div>

    <div class="map-legend">
        <h3><i class="fas fa-key"></i> Leyenda</h3>
        <ul>
            <li><span class="legend-icon daily"></span> Crédito Diario</li>
            <li><span class="legend-icon weekly"></span> Crédito Semanal</li>
            <li><span class="legend-icon monthly"></span> Crédito Mensual</li>
            <li><span class="legend-icon yearly"></span> Crédito Anual</li>
            <li><span class="legend-icon inactive"></span> No Vigente</li>
            <li><span class="legend-icon ungeocoded"></span> Sin geocodificar</li>
        </ul>
    </div>
</main>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Datos de clientes desde PHP
const clientesDesdeDB = <?php echo json_encode($clientes_db); ?>;

// Procesar clientes inicialmente sin coordenadas
const clientesProcesados = clientesDesdeDB.map(cliente => {
    return {
        ...cliente,
        id: cliente.id_cliente,
        geocodificado: false,
        lat: null,
        lon: null,
        direccion_completa: `${cliente.domicilio1}, Perú`.trim()
    };
});

// Función para geocodificar un cliente
async function geocodeCliente(cliente) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cliente.direccion_completa)}&countrycodes=pe&limit=1&addressdetails=1`);
        
        if (!response.ok) throw new Error("Error en la respuesta");
        
        const data = await response.json();
        
        if (data && data.length > 0) {
            const result = data[0];
            return {
                ...cliente,
                lat: parseFloat(result.lat),
                lon: parseFloat(result.lon),
                geocodificado: true,
                departamento: result.address.state || '',
                provincia: result.address.county || '',
                distrito: result.address.city_district || result.address.city || result.address.town || '',
                direccion_formateada: result.display_name
            };
        }
        
        return cliente;
    } catch (error) {
        console.error("Error en geocodificación:", error);
        return cliente;
    }
}

// Función para geocodificar en lote
async function geocodeClientes(clientes) {
    const resultados = [];
    
    // Limitar a 1 solicitud por segundo para no saturar el servidor Nominatim
    for (let i = 0; i < clientes.length; i++) {
        const resultado = await geocodeCliente(clientes[i]);
        resultados.push(resultado);
        
        // Actualizar progreso
        updateGeocodeProgress(i + 1, clientes.length);
        
        // Pequeña pausa entre solicitudes
        if (i < clientes.length - 1) {
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
    }
    
    return resultados;
}

function updateGeocodeProgress(current, total) {
    const porcentaje = Math.round((current / total) * 100);
    $('#geocode-all').html(`<i class="fas fa-map-marked-alt"></i> Geocodificando (${porcentaje}%)`);
    
    if (current === total) {
        $('#geocode-all').html('<i class="fas fa-map-marked-alt"></i> Geocodificar');
        $('#geocode-all').removeClass('btn-warning').addClass('btn-success');
        setTimeout(() => $('#geocode-all').removeClass('btn-success').addClass('btn-warning'), 3000);
    }
}
</script>
<script src="mapa.js?v=1.2"></script>
<script src="../inactividad.js"></script>
</body>
</html>