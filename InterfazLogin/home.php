<?php
require(__DIR__ . "/FuncionLogin/Auth/auth.php");

if (
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['correo']) ||
    !isset($_SESSION['cargo']) ||
    !isset($_SESSION['telefono']) ||
    !isset($_SESSION['nombre_completo'])
) {
    header("Location: https://eneproyect.com");
    exit();
}
//llamar a las variables de sesion
$nombre_completo = $_SESSION['nombre_completo'];

?>

<!DOCTYPE html>
<html lang="es">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Trabajadores</title>

    <!-- Enlace a Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Enlace al CSS -->
    <link rel="stylesheet" href="home.css">
</head>
<body>
    
    <!-- Botón de menú para móviles -->
    <button class="toggle-btn" id="menu-toggle"><i class="fas fa-bars"></i></button>
   
    <!-- Barra lateral -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-bars"></i> Eneproyect</h2>
        </div>
        <ul>
            <li><a href="../PortalFunc/Mis_Clientes/registro_cliente/index.php"><i class="fas fa-users"></i> Mis Clientes</a></li>
            <li><a href="../PortalFunc/Calendario/calendario.php"><i class="fas fa-calendar-alt"></i> Calendario</a></li>
            <li><a href="../PortalFunc/Mapa/mapa.php"><i class="fas fa-map-marked-alt"></i> Mapa</a></li>
            <li><a href="../PortalFunc/Ajustes/ajustes.php"><i class="fas fa-cog"></i> Ajustes</a></li>
            <li><a href="../PortalFunc/Logout/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
        </ul>
    </aside>

    <!-- Contenido principal -->
    <main class="content">
        <header>
            <h1>Bienvenido al Portal</h1>
            <h2>Bienvenido, <?php echo htmlspecialchars(string: $nombre_completo); ?></h2>
            <p>Gestiona clientes, citas y más desde aquí.</p>
        </header>

        <!-- Secciones de contenido -->
        <section class="dashboard">
            <div class="card">
                <h3><i class="fas fa-folder"></i> Clientes atendidos</h3>
                <p>5 hoy</p>
            </div>
            <div class="card">
                <h3><i class="fas fa-calendar-check"></i> Citas pendientes</h3>
                <p>2 programadas</p>
            </div>
            <div class="card">
                <h3><i class="fas fa-map-marker-alt"></i> Clientes en espera</h3>
                <p>3 por visitar</p>
            </div>
        </section>

        <!-- Vista previa del mapa -->
        <section class="map-preview">
            <h2><i class="fas fa-map"></i> Ubicación de Clientes</h2>
            <img src="mapa.jpg" alt="Mapa de clientes">
        </section>
    </main>

    <!-- Enlace al archivo JS -->
    <script src="../PortalFunc/inactividad.js"></script>
    <script src="home.js"></script>
</body>
</html>
