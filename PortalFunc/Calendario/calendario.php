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
    <title>Calendario Bancario | Eneproyect</title>
    <link rel="stylesheet" href="calendario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
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
                <a href="../Calendario/calendario.php" class="active">
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
                <a href="../Ajustes/ajustes.php">
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
        
        <h1><i class="fas fa-calendar-alt"></i> Calendario</h1>

        <!-- Selector de vista -->
        <div class="view-toggle">
            <button id="calendar-view-btn" class="active"><i class="fas fa-calendar"></i> <span class="btn-text">Vista Calendario</span></button>
            <button id="agenda-view-btn"><i class="fas fa-list"></i> <span class="btn-text">Vista Agenda</span></button>
            <button id="new-event-btn" style="display: none;"><i class="fas fa-plus"></i> <span class="btn-text">Nueva Cita</span></button>
        </div>
        
        <!-- Contenedor del calendario -->
        <div class="calendar-container" id="calendar-container">
            <div id="calendar"></div>
        </div>
        
        <!-- Vista de agenda (oculta inicialmente) -->
        <div class="agenda-view" id="agenda-view" style="display: none;">
            <div class="agenda-header">
                <h2><i class="fas fa-list"></i> Agenda de Citas</h2>
                <div class="agenda-filters">
                    <select id="agenda-filter">
                        <option value="all">Todas las citas</option>
                        <option value="today">Hoy</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                    </select>
                </div>
            </div>
            
            <div class="agenda-list" id="agenda-list">
                <!-- Las citas se cargarán dinámicamente aquí -->
            </div>
        </div>
        
        <!-- Modal para eventos (con correcciones en las etiquetas) -->
        <div class="modal-overlay" id="event-modal">
            <div class="modal">
                <h2 id="modal-title"><i class="fas fa-calendar-plus"></i> Nueva Cita</h2>
                <form id="event-form">
                    <input type="hidden" id="event-id">
                    
                    <!-- Grupo Cliente -->
                    <div class="form-group">
                        <label for="event-client"><i class="fas fa-user"></i> Cliente:</label>
                        <select id="event-client" required>
                            <option value="" disabled selected>Seleccionar cliente...</option>
                            <!-- Opciones se llenarán dinámicamente -->
                        </select>
                        <small id="client-error" class="error-message" style="color: red; display: none;">Debes seleccionar un cliente</small>
                    </div>
                    
                    <!-- Grupo Tipo de Evento -->
                    <div class="form-group">
                        <label for="event-type"><i class="fas fa-briefcase"></i> Tipo:</label>
                        <select id="event-type" required>
                        <option value="" disabled selected>Seleccionar tipo...</option>
                        <option value="consulta">Consulta</option>
                        <option value="reunion">Reunión</option>
                        <option value="volver_informar">Visita a informar</option>                                
                        <option value="Inicio caso prejudicial">Inicio caso prejudicial</option>
                        <option value="Notificacion">Notificación</option>
                        <option value="Amortizacion">Amortización</option>
                        <option value="Cambio Gestor">Cambio Gestor</option>
                        <option value="Postergacion">Postergación</option>
                        <option value="Fin de caso">Fin de caso</option>
                        <option value="Pasa a Judicial">Pasa a Judicial</option>
                        </select>
                    </div>
                                        
                    <!-- Grupo Fecha y Hora -->
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="event-start-date"><i class="fas fa-calendar-day"></i> Fecha:</label>
                            <input type="date" id="event-start-date" required>
                        </div>
                        <div class="form-group half-width">
                            <label for="event-start-time"><i class="fas fa-clock"></i> Hora:</label>
                            <input type="time" id="event-start-time" required>
                        </div>
                    </div>
                    
                    <!-- Grupo Duración -->
                    <div class="form-group">
                        <label for="event-duration"><i class="fas fa-stopwatch"></i> Duración (minutos):</label>
                        <input type="number" id="event-duration" value="30" min="15" step="15" required>
                    </div>
                    
                    <!-- Grupo Notas -->
                    <div class="form-group">
                        <label for="event-notes"><i class="fas fa-comment"></i> Notas:</label>
                        <textarea id="event-notes" rows="3"></textarea>
                    </div>
                    
                    <!-- Acciones del Formulario -->
                    <div class="form-actions">
                        <button type="button" id="delete-event-btn" class="cancel-btn" style="display: none;">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                        <button type="button" class="cancel-btn" id="cancel-event-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script src="calendario.js"></script>
    <script src="../inactividad.js"></script>

    <!-- Script para inicializar el calendario -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar clientes al abrir el modal
            document.getElementById('new-event-btn').addEventListener('click', function() {
                fetchClients();
            });
        });

        async function fetchClients() {
            try {
                const response = await fetch('obtener_clientes.php');
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('event-client');
                    select.innerHTML = '<option value="" disabled selected>Seleccionar cliente...</option>';
                    
                    result.data.forEach(client => {
                        const option = document.createElement('option');
                        option.value = client.dni;
                        option.textContent = client.name;
                        select.appendChild(option);
                    });
                } else {
                    throw new Error(result.error || 'Error al cargar clientes');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'No se pudieron cargar los clientes', 'error');
            }
        }
    </script>
</body>
</html>