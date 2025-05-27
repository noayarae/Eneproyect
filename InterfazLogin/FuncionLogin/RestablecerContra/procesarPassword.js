document.addEventListener("DOMContentLoaded", function () {
    const toggleIcons = document.querySelectorAll(".toggle-password");

    toggleIcons.forEach(icon => {
        icon.addEventListener("click", function () {
            togglePasswordVisibility(this);
        });
    });

    function togglePasswordVisibility(icon) {
        const fieldId = icon.getAttribute("data-target");
        const field = document.getElementById(fieldId);

        if (field) {
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            }
        } else {
            console.warn(`No se encontró el campo con id '${fieldId}'`);
        }
    }

    // Obtener el token de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token') || '';

    // Verificar si el input del token existe antes de asignarle el valor
    const tokenInput = document.getElementById("tokenInput");
    if (tokenInput) {
        tokenInput.value = token;
        console.log("Token asignado al input:", token);
    } else {
        console.warn("El input tokenInput no se encontró en el DOM.");
    }

    if (token) {
        // Obtener el nombre del usuario con AJAX
        fetch(`obtener_nombre.php?token=${token}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Respuesta del servidor:", JSON.stringify(data, null, 2)); // Depuración

                const nombreUsuarioElem = document.getElementById("nombreUsuario");
                if (nombreUsuarioElem) {
                    nombreUsuarioElem.textContent = data.Usuario || "Usuario Desconocido";
                } else {
                    console.warn("El elemento #nombreUsuario no se encontró en el DOM.");
                }
            })
            .catch(error => {
                console.error("Error al obtener el nombre del usuario:", error);
                const nombreUsuarioElem = document.getElementById("nombreUsuario");
                if (nombreUsuarioElem) {
                    nombreUsuarioElem.textContent = "Error al cargar usuario";
                }
            });
    } else {
        console.error("No se encontró un token en la URL");
    }
});
