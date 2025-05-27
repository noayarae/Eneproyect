$(document).ready(function () {
    console.log("JS cargado correctamente.");

    // Expresiones regulares sincronizadas con PHP
    const REGEX_USUARIO = /^(?=.*[@$!%*?&._-])[a-zA-Z0-9@$!%*?&._-]{3,}$/;
    const REGEX_NOMBRE_APELLIDO = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
    const REGEX_DNI = /^\d{8}$/;
    const REGEX_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const REGEX_PASSWORD = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#$])[A-Za-z\d@$!%*?&#$]{10,}$/;

    // Capturar errores desde la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');

    if (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error,
            confirmButtonText: 'Entendido'
        });
    }

    if (success) {
        Swal.fire({
            icon: 'success',
            title: '¡Registro exitoso!',
            text: success,
            confirmButtonText: 'Aceptar'
        }).then(() => {
            window.location.href = 'https://eneproyect.com/';
        });
    }

    // Alternar visibilidad de contraseña
    $(".toggle-password").click(function() {
        const input = $(this).prev("input");
        const icon = $(this);
        const isPassword = input.attr("type") === "password";
        input.attr("type", isPassword ? "text" : "password");
        icon.toggleClass("fa-eye-slash fa-eye");
    });

    // Limpiar formulario con alerta
    $('#btn_eliminar').click(function(){
        Swal.fire({
            title: '¿Está seguro de limpiar el formulario?',
            text: "Se perderán todos los datos ingresados.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, limpiar'
        }).then((result) => {
            if (result.isConfirmed) {
                $("#registroForm")[0].reset();
                Swal.fire('Limpio', 'El formulario ha sido limpiado.', 'success');
            }
        });
    });

    // Validar nombre y apellido en tiempo real
    $("input[name='nombre'], input[name='apellido']").on("input", function(event) {
        let valor = $(this).val();
        let keyCode = event.originalEvent.inputType;

        if (keyCode === "deleteContentBackward" || keyCode === "deleteContentForward") {
            return;
        }

        if (!REGEX_NOMBRE_APELLIDO.test(valor)) {
            Swal.fire({
                icon: 'warning',
                title: 'Carácter no permitido',
                text: 'Solo puedes ingresar letras y espacios.',
                timer: 1500,
                showConfirmButton: false
            });

            $(this).val(valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, ""));
        }
    });

    // Validación y envío del formulario
    $("#registroForm").submit(async function(event) {
        event.preventDefault();

        // Obtener valores
        const nombre = $("input[name='nombre']").val().trim();
        const apellido = $("input[name='apellido']").val().trim();
        const dni = $("input[name='dni']").val().trim();
        const correo = $("input[name='correo']").val().trim();
        const usuario = $("input[name='usuario']").val().trim();
        const password = $("#password").val();
        const confirmPassword = $("#confirmPassword").val();

        // Validaciones
        let errorMessage = "";
        let errorField = null;

        if (!REGEX_NOMBRE_APELLIDO.test(nombre)) {
            errorMessage = "El nombre contiene un carácter no válido. Por favor, verifique";
            errorField = $("input[name='nombre']");
        } 
        else if (!REGEX_NOMBRE_APELLIDO.test(apellido)) {
            errorMessage = "El apellido contiene un carácter no válido. Por favor, verifique";
            errorField = $("input[name='apellido']");
        }
        else if (!REGEX_DNI.test(dni)) {
            errorMessage = "DNI no válido. Por favor verifique e intente de nuevo";
            errorField = $("input[name='dni']");
        }
        else if (!REGEX_CORREO.test(correo)) {
            errorMessage = "Correo no válido. Verifica el formato e intente de nuevo";
            errorField = $("input[name='correo']");
        }
        else if (!REGEX_USUARIO.test(usuario)) {
            errorMessage = "El usuario debe tener al menos 3 caracteres e incluir un carácter especial (@$!*?&._-)";
            errorField = $("input[name='usuario']");
        }
        else if (!REGEX_PASSWORD.test(password)) {
            errorMessage = "La contraseña debe tener al menos 10 caracteres, una mayúscula, una minúscula, un número y un carácter especial (@$!*?&#$)";
            errorField = $("#password");
        }
        else if (password !== confirmPassword) {
            errorMessage = "Las contraseñas no coinciden. Verifique e intente nuevamente";
            errorField = $("#confirmPassword");
        }

        // Mostrar error si hay alguno
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                text: errorMessage,
                confirmButtonText: 'Entendido'
            }).then(() => {
                if (errorField) {
                    errorField.focus();
                }
            });
            return;
        }

        // Mostrar carga
        const swalInstance = Swal.fire({
            title: 'Procesando registro',
            html: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            // Enviar datos
            const formData = new FormData(this);
            const response = await fetch('registro.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            await swalInstance.close();

            if (result.success) {
                await Swal.fire({
                    icon: 'success',
                    title: '¡Registro exitoso!',
                    text: result.message,
                    confirmButtonText: 'Aceptar'
                });
                
                window.location.href = 'https://eneproyect.com/';
            } else {
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.error || 'Ocurrió un error desconocido',
                        confirmButtonText: 'Entendido'
                    });
                    
                    // Enfocar el campo con error si existe
                    if (result.field) {
                        const fieldSelector = result.field === 'pass' ? '#password' : 
                                             result.field === 'repass' ? '#confirmPassword' : 
                                             `input[name='${result.field}']`;
                        $(fieldSelector).focus();
                    }
                }
            }
        } catch (error) {
            await swalInstance.close();
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo completar la solicitud. Por favor intente nuevamente.',
                confirmButtonText: 'Entendido'
            });
        }
    });
});