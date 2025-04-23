document.addEventListener("DOMContentLoaded", function () {
    console.log("JS cargado correctamente.");

    // Elementos del DOM
    const passwordInput = document.getElementById("password");
    const togglePass = document.getElementById("togglePass");
    const loginButton = document.getElementById("loginButton");
    const loginForm = document.getElementById("loginForm");
    const usernameInput = document.getElementById("usuario");
    const resetButton = document.getElementById("resetButton");
    const emailInput = document.getElementById("email");

    // 🔹 Función para validar email
    function validateEmail(email) {
        let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    console.log("¿Existe validateEmail?", typeof validateEmail); // 🔹 Depuración

    // 🔹 Funcionalidad de mostrar/ocultar contraseña
    if (passwordInput && togglePass) {
        console.log("Ícono de ojo encontrado:", togglePass);
        togglePass.addEventListener("click", function () {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePass.classList.remove("fa-eye-slash");
                togglePass.classList.add("fa-eye");
            } else {
                passwordInput.type = "password";
                togglePass.classList.remove("fa-eye");
                togglePass.classList.add("fa-eye-slash");
            }
        });
    } else {
        console.error("No se encontró el input de contraseña o el icono.");
    }

    // 🔹 Validación antes de enviar el formulario de login
    if (loginButton && loginForm) {
        loginButton.addEventListener("click", function (event) {
            if (!usernameInput.value.trim() || !passwordInput.value.trim()) {
                event.preventDefault();
                Swal.fire({
                    icon: "error",
                    title: "Campos vacíos",
                    text: "Por favor, completa todos los campos antes de continuar.",
                });
            } else {
                loginForm.submit();
            }
        });
    } else {
        console.error("No se encontró el botón o formulario de login.");
    }

    // 🔹 Validación para restablecer contraseña
    if (resetButton && emailInput) {
        resetButton.addEventListener("click", function (event) {
            event.preventDefault(); // Evita el envío automático
    
            let emailValue = emailInput.value.trim();
            console.log("Valor de emailValue:", emailValue); // 🔹 Depuración
    
            if (emailValue === "") {
                Swal.fire({
                    icon: "warning",
                    title: "Campo vacío",
                    text: "Por favor, ingresa tu correo electrónico.",
                    confirmButtonColor: "#3085d6"
                });
                return;
            }
    
            if (!validateEmail(emailValue)) {
                Swal.fire({
                    icon: "error",
                    title: "Correo inválido",
                    text: "Por favor, ingresa un correo electrónico válido.",
                    confirmButtonColor: "#d33"
                });
                return;
            }
    
            console.log("Enviando formulario de recuperación..."); // Debug
            document.getElementById("resetForm").submit();
        });
    }
});
