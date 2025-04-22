<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");

$usuario = $_SESSION['usuario'];
$correo = $_SESSION['correo'];
$cargo = $_SESSION['cargo'];
$telefono = $_SESSION['telefono'];
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
        <li><a href="/PortalFunc/Mis_Clientes/registro_cliente/index.php"><i class="fas fa-users"></i> Mis Clientes</a></li>
        <li><a href="/PortalFunc/Calendario/calendario.php"><i class="fas fa-calendar-alt"></i> Calendario</a></li>
        <li><a href="/PortalFunc/Mapa/mapa.php" class="active"><i class="fas fa-map-marked-alt"></i> Mapa</a></li>
        <li><a href="/PortalFunc/Ajustes/ajustes.php"><i class="fas fa-cog"></i> Ajustes</a></li>
        <li class="logout-item"><a href="/PortalFunc/Logout/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
    </ul>
</aside>

<!-- Contenido principal -->
<main class="content">
    <header class="mapa-header">
        <h1><i class="fas fa-map-marked-alt"></i> Mapa de Clientes</h1>
        <div class="header-actions">
            <button id="locate-me"><i class="fas fa-location-arrow"></i> Mi ubicación</button>
        </div>
    </header>

    <section class="map-controls">
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="search" placeholder="Buscar cliente por dirección, nombre...">
        </div>
        <select id="filtroDistrito" class="custom-select">
            <option value="">Todos los distritos</option>
            <option value="San Miguel">San Miguel</option>
            <option value="Miraflores">Miraflores</option>
            <option value="Lince">Lince</option>
        </select>
        <select id="filtroTipo" class="custom-select">
            <option value="">Todos los tipos</option>
            <option value="Residencial">Residencial</option>
            <option value="Comercial">Comercial</option>
            <option value="Industrial">Industrial</option>
        </select>
        <button id="resetMap" class="btn-primary"><i class="fas fa-sync-alt"></i> Reiniciar</button>
    </section>

    <div id="map"></div>
    
    <div class="map-legend">
        <h3><i class="fas fa-key"></i> Leyenda</h3>
        <ul>
            <li><span class="legend-icon residential"></span> Residencial</li>
            <li><span class="legend-icon commercial"></span> Comercial</li>
            <li><span class="legend-icon industrial"></span> Industrial</li>
        </ul>
    </div>
</main>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js" charset="utf-8"></script>
<script src="mapa.js"></script>
<script src="../inactividad.js"></script>
</body>
</html>