$(document).ready(function () {
    console.log("JS cargado correctamente.");
    //nuevo

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
    const nameRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;

    $("input[name='nombre'], input[name='apellido']").on("input", function(event) {
        let valor = $(this).val();
        let keyCode = event.originalEvent.inputType; // Tipo de entrada

        // Evitar activar la alerta con teclas de edición
        if (keyCode === "deleteContentBackward" || keyCode === "deleteContentForward") {
            return;
        }

        if (!nameRegex.test(valor)) {
            Swal.fire({
                icon: 'warning',
                title: 'Carácter no permitido',
                text: 'Solo puedes ingresar letras y espacios.',
                timer: 1500,
                showConfirmButton: false
            });

            // Remueve el último carácter inválido ingresado
            $(this).val(valor.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, ""));
        }
    });

    // Validación antes de enviar el formulario
    $("#registroForm").submit(function(event) {
        event.preventDefault(); // Evitar envío inmediato

        const nombre = $("input[name='nombre']").val().trim();
        const apellido = $("input[name='apellido']").val().trim();
        const password = $("#password").val();
        const confirmPassword = $("#confirmPassword").val();

        let errorMessage = "";

        // Validación de nombre y apellido
        if (!nameRegex.test(nombre)) {
            errorMessage = "El nombre solo puede contener letras y espacios.";
        } else if (!nameRegex.test(apellido)) {
            errorMessage = "El apellido solo puede contener letras y espacios.";
        }

        // Validación de contraseña
        if (!errorMessage) {
            if (password.length < 10) {
                errorMessage = "La contraseña debe tener al menos 10 caracteres.";
            } else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password)) {
                errorMessage = "Debe incluir al menos una mayúscula y una minúscula.";
            } else if (!/\d/.test(password)) {
                errorMessage = "Debe incluir al menos un número.";
            } else if (!/[@$!%*?&]/.test(password)) {
                errorMessage = "Debe incluir al menos un carácter especial (@$!%*?&).";
            }
        }

        // Verificar coincidencia de contraseñas
        if (!errorMessage && password !== confirmPassword) {
            errorMessage = "Las contraseñas no coinciden.";
        }

        // Si hay errores, mostrar alerta
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                text: errorMessage,
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Si todo está bien, enviar formulario
        $("#registroForm")[0].submit();
    });
});
