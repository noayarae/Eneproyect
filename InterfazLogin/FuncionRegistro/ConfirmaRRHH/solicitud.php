<?php
session_start();
require('../../conexion.php');

// Función para enviar respuesta JSON y terminar ejecución
function enviarRespuestaJSON($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// 5. Procesar formulario si fue enviado (MOVER AL INICIO)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5.1 Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        enviarRespuestaJSON(['error' => 'Token de seguridad inválido']);
    }

    // 5.2 Validar acción
    if (!isset($_POST['accion']) || !in_array($_POST['accion'], ['aprobar', 'rechazar'])) {
        enviarRespuestaJSON(['error' => 'Acción inválida']);
    }

    // Validar DNI en POST
    if (!isset($_POST['dni']) || !preg_match('/^\d{8}$/', $_POST['dni'])) {
        enviarRespuestaJSON(['error' => 'DNI inválido']);
    }

    $dni = $_POST['dni'];
    $accion = $_POST['accion'];
    $comentarios = isset($_POST['comentarios']) ? htmlspecialchars($_POST['comentarios']) : '';

    // Validación adicional para aprobación
    if ($accion === 'aprobar' && (empty($_POST['cargo']) || $_POST['cargo'] === 'default')) {
        enviarRespuestaJSON(['error' => 'Debe seleccionar un cargo válido']);
    }

    // Validación adicional para rechazo
    if ($accion === 'rechazar' && empty(trim($comentarios))) {
        enviarRespuestaJSON(['error' => 'Los comentarios son obligatorios para rechazar una solicitud']);
    }

    $cargo = $accion === 'aprobar' ? htmlspecialchars($_POST['cargo']) : 'Rechazado';

    try {
        // Conexión a BD
        $conn = new mysqli($servidor, $usuario, $contrasena, $baseDatos);
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }

        // Verificar que el registro existe y está pendiente
        $stmt = $conn->prepare("SELECT nombre, apellido, correo, estado FROM registro_prelim_trabajadores WHERE dni = ?");
        if (!$stmt) {
            throw new Exception("Error en preparación: " . $conn->error);
        }

        $stmt->bind_param("s", $dni);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar: " . $stmt->error);
        }

        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            enviarRespuestaJSON(['error' => 'No existe registro con este DNI']);
        }

        $stmt->bind_result($nombre, $apellido, $correo, $estado);
        $stmt->fetch();
        $stmt->close();

        // Verificar si ya fue procesada
        if ($estado !== 'pendiente') {
            enviarRespuestaJSON(['error' => "Esta solicitud ya fue $estado"]);
        }

        // 5.3 Actualización segura
        $stmt = $conn->prepare("UPDATE registro_prelim_trabajadores 
                              SET estado = ?, cargo = ? 
                              WHERE dni = ?");
        $nuevo_estado = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
        $stmt->bind_param("sss", $nuevo_estado, $cargo, $dni);

        if ($stmt->execute()) {
            // Guardar comentarios en tabla separada si existen
            if (!empty($comentarios)) {
                $stmt_comentario = $conn->prepare("INSERT INTO registro_comentarios (dni, comentarios, accion) VALUES (?, ?, ?)");
                $stmt_comentario->bind_param("sss", $dni, $comentarios, $accion);
                $stmt_comentario->execute();
                $stmt_comentario->close();
            }
            
            $stmt->close();
            // 5.4 Enviar correo de notificación
            require_once __DIR__ . '/enviarCorreoResultado.php';
            $resultado_correo = enviarCorreoResultado($nombre, $apellido, $correo, $nuevo_estado, $comentarios);

            // 5.5 Respuesta exitosa
            $mensaje = $accion === 'aprobar' 
                ? "Usuario $nombre $apellido aprobado como $cargo" 
                : "Solicitud de $nombre $apellido rechazada";
            
            enviarRespuestaJSON(['success' => $mensaje]);
        } else {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("Error al procesar: " . $e->getMessage());
        enviarRespuestaJSON(['error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
    }
}

// RESTO DEL CÓDIGO PARA MOSTRAR LA PÁGINA (solo para GET)

// 1. Verificación básica de DNI
if (!isset($_GET['dni']) || !preg_match('/^\d{8}$/', $_GET['dni'])) {
    header("Location: /InterfazLogin/FuncionRegistro/registro.html?error=DNI inválido");
    exit;
}

$dni = $_GET['dni'];

// 2. Conexión segura a BD con manejo de errores
try {
    $conn = new mysqli($servidor, $usuario, $contrasena, $baseDatos);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    // 3. Consulta preparada para evitar SQL injection
    $stmt = $conn->prepare("SELECT nombre, apellido, correo, estado FROM registro_prelim_trabajadores WHERE dni = ?");
    if (!$stmt) {
        throw new Exception("Error en preparación: " . $conn->error);
    }

    $stmt->bind_param("s", $dni);
    if (!$stmt->execute()) {
        throw new Exception("Error al ejecutar: " . $stmt->error);
    }

    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        throw new Exception("No existe registro con este DNI");
    }

    $stmt->bind_result($nombre, $apellido, $correo, $estado);
    $stmt->fetch();
    $stmt->close();

    // 4. Verificar si ya fue procesada
    if ($estado !== 'pendiente') {
        header("Location: /InterfazLogin/FuncionRegistro/registro.html?info=Esta solicitud ya fue $estado");
        exit;
    }

} catch (Exception $e) {
    error_log("Error en solicitud.php: " . $e->getMessage());
    header("Location: /InterfazLogin/FuncionRegistro/registro.html?error=Error al procesar solicitud");
    exit;
}

// 6. Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión de Solicitud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card { max-width: 800px; margin: 2rem auto; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card-header { border-radius: 10px 10px 0 0 !important; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
        .btn-custom { min-width: 120px; font-weight: 500; }
        .btn-approve { background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); }
        .btn-reject { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .is-invalid { border-color: #dc3545 !important; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card border-0 shadow">
            <div class="card-header text-white">
                <h4 class="mb-0"><i class="fas fa-user-check me-2"></i>Revisión de Solicitud</h4>
            </div>
            <div class="card-body">
                <!-- Mostrar mensajes de éxito/error -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <h5 class="border-bottom pb-2"><i class="fas fa-user me-2"></i>Datos del Solicitante</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?= htmlspecialchars($nombre) ?></p>
                            <p><strong>Apellido:</strong> <?= htmlspecialchars($apellido) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>DNI:</strong> <?= htmlspecialchars($dni) ?></p>
                            <p><strong>Correo:</strong> <?= htmlspecialchars($correo) ?></p>
                        </div>
                    </div>
                </div>

                <form method="POST" id="formRevision">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="dni" value="<?= htmlspecialchars($dni) ?>">

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-briefcase me-2"></i>Asignar Cargo:</label>
                        <select name="cargo" class="form-select" id="selectCargo" required>
                            <option value="default" selected disabled>-- Seleccione un cargo --</option>
                            <option value="Gestor">Gestor de Créditos</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Soporte">Soporte Técnico</option>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione un cargo</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-comment me-2"></i>Comentarios:</label>
                        <textarea name="comentarios" class="form-control" id="textComentarios" rows="3" 
                                  placeholder="Obligatorio si rechaza la solicitud"></textarea>
                        <div class="invalid-feedback">Los comentarios son requeridos para rechazos</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" name="accion" value="rechazar" 
                                class="btn btn-danger btn-custom btn-reject">
                            <i class="fas fa-times-circle me-2"></i>Rechazar
                        </button>
                        <button type="submit" name="accion" value="aprobar" 
                                class="btn btn-success btn-custom btn-approve">
                            <i class="fas fa-check-circle me-2"></i>Aprobar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Variables globales
        const form = document.getElementById('formRevision');
        const selectCargo = document.getElementById('selectCargo');
        const textComentarios = document.getElementById('textComentarios');

        // Manejar el envío del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Obtener la acción del botón clickeado
            const accion = document.activeElement.value;
            
            // Validación del formulario
            if (!validarFormulario(accion)) {
                return;
            }

            // Mostrar carga
            Swal.fire({
                title: 'Procesando...',
                html: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                // Enviar datos
                const formData = new FormData(form);
                // Asegurar que la acción se incluya en el FormData
                formData.set('accion', accion);
                
                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                });
                
                // Verificar si la respuesta es válida
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta no JSON:', text);
                    throw new Error('El servidor no devolvió una respuesta JSON válida');
                }
                
                const result = await response.json();
                
                if (result.error) {
                    throw new Error(result.error);
                }
                
                // Mostrar éxito y redirigir
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: result.success,
                    confirmButtonText: 'OK',
                    timer: 3000
                }).then(() => {
                    // Redirigir a eneproyect.com
                    window.location.href = 'https://eneproyect.com';
                });
                
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Error al procesar la solicitud',
                    confirmButtonText: 'OK'
                });
                console.error('Error:', error);
            }
        });

        // Validación del formulario
        function validarFormulario(accion) {
            // Resetear validación
            selectCargo.classList.remove('is-invalid');
            textComentarios.classList.remove('is-invalid');
            
            let esValido = true;
            
            // Validar cargo si es aprobación
            if (accion === 'aprobar' && (selectCargo.value === 'default' || !selectCargo.value)) {
                selectCargo.classList.add('is-invalid');
                Swal.fire({
                    icon: 'error',
                    title: 'Cargo requerido',
                    text: 'Debe seleccionar un cargo para aprobar la solicitud',
                    confirmButtonText: 'Entendido'
                });
                esValido = false;
            }
            
            // Validar comentarios si es rechazo
            if (accion === 'rechazar' && textComentarios.value.trim() === '') {
                textComentarios.classList.add('is-invalid');
                Swal.fire({
                    icon: 'error',
                    title: 'Comentario requerido',
                    text: 'Debe ingresar un comentario para rechazar la solicitud',
                    confirmButtonText: 'Entendido'
                });
                esValido = false;
            }
            
            return esValido;
        }

        // Mostrar mensajes de SweetAlert desde URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('success')) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: urlParams.get('success'),
                    confirmButtonText: 'OK'
                });
            } else if (urlParams.has('error')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: urlParams.get('error'),
                    confirmButtonText: 'OK'
                });
            }
        });
    </script>
</body>
</html>