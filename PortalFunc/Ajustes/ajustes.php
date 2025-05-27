<?php
require(__DIR__ . "/../../InterfazLogin/FuncionLogin/Auth/auth.php");

$usuario = $_SESSION['usuario'];
$correo = $_SESSION['correo'];
$telefono = $_SESSION['telefono'] ?? ''; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes</title>
    <link rel="stylesheet" href="ajustes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- Barra lateral retráctil -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-bars toggle-sidebar"></i> <span class="menu-text">Eneproyect</span></h2>
        </div>
        <ul>
            <li>
                <a href="../../InterfazLogin/home.php">
                    <i class="fas fa-house-user"></i>
                    <span class="link-text">Inicio</span>
                </a>
            </li>
            <li>
                <a href="../Mis_Clientes/registro_cliente/index.php">
                    <i class="fas fa-users"></i>
                    <span class="link-text">Mis Clientes</span>
                </a>
            </li>
            <li>
                <a href="../Calendario/calendario.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="link-text">Calendario</span>
                </a>
            </li>
            <li>
                <a href="../Mapa/mapa.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span class="link-text">Mapa</span>
                </a>
            </li>
            <li>
                <a href="../Ajustes/ajustes.php" class="active">
                    <i class="fas fa-cog"></i>
                    <span class="link-text">Ajustes</span>
                </a>
            </li>
            <li class="logout-item">
                <a href="/PortalFunc/Logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="link-text">Cerrar Sesión</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <!-- Botón para móviles -->
        <div class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        
        <h1><i class="fas fa-cog"></i> Ajustes</h1>
        <p>Personaliza tu cuenta y preferencias aquí.</p>

        <div class="settings-container">
            <!-- Información Personal -->
            <section class="settings">
                <h2><i class="fas fa-user"></i> Información Personal</h2>
                <form action="actualizar_telefono.php" method="POST">
                    <div class="input-container">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario); ?>" readonly>
                    </div>
                    
                    <div class="input-container">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($correo); ?>" readonly>
                    </div>
                    
                    <div class="input-container">
                        <label for="telefono">Teléfono:</label>
                        <input type="tel" id="telefono" name="tel" value="<?php echo htmlspecialchars($telefono); ?>" required>
                    </div>

                    <button type="submit">Actualizar Teléfono</button>
                </form>
            </section>
            
            <!-- Seguridad -->
            <section class="settings">
                <h2><i class="fas fa-lock"></i> Seguridad</h2>
                <form action="cambiar_password.php" method="POST">
                    <div class="input-container">
                        <label for="password">Nueva Contraseña:</label>
                        <input type="password" id="password" name="password" placeholder="********" required>
                        <i class="fas fa-eye-slash password-toggle"></i>
                    </div>
                    
                    <div class="input-container">
                        <label for="confirm-password">Confirmar Contraseña:</label>
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="********" required>
                        <i class="fas fa-eye-slash password-toggle"></i> 
                    </div>
                    
                    <button type="submit">Actualizar Contraseña</button>
                </form>
            </section>

            <!-- Preferencias -->
            <section class="settings preferences">
                <h2><i class="fas fa-palette"></i> Preferencias</h2>
                <div class="input-container">
                    <label for="modo">Modo de Tema:</label>
                    <select id="modo">
                        <option value="claro">🌞 Claro</option>
                        <option value="oscuro">🌙 Oscuro</option>
                    </select>
                </div>
            </section>
        </div>
    </div>

    <script src="../inactividad.js"></script>
    <script src="ajustes.js"></script>
</body>
</html>