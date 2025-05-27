document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("btnCompletar").addEventListener("click", completarDatos);
    document.getElementById("btnLimpiar").addEventListener("click", limpiarFormulario);
    document.getElementById("btnSalir").addEventListener("click", salir);
});

function completarDatos() {
    let form = document.getElementById("formDatos");
    let formData = new FormData(form);

    let fechaNacimiento = document.getElementById("fecha_nacimiento").value;
    let telefono = document.getElementById("telefono").value;
    let direccion = document.getElementById("direccion").value;

    if (fechaNacimiento === "" || telefono === "" || direccion === "") {
        Swal.fire({
            icon: "error",
            title: "Campos incompletos",
            text: "Por favor, completa todos los campos antes de continuar."
        });
        return;
    }

    Swal.fire({
        title: "¿Guardar datos?",
        text: "¿Estás seguro de que quieres completar el registro?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, completar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("llenar_datos.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.text()) // Ver respuesta como texto plano
            .then(text => {
                console.log("Respuesta del servidor:", text);
                return JSON.parse(text); // Convertir a JSON después de verificar
            })
            .then(data => {
                if (data.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Datos guardados",
                        text: data.message,
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "https://eneproyect.com/intranet-eneproyect/";
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al guardar los datos. Inténtalo nuevamente."
                });
            });
        }
    });
}

function limpiarFormulario(event) {
    event.preventDefault(); // Evita que se borre sin confirmar
    Swal.fire({
        title: "¿Limpiar formulario?",
        text: "Se borrarán todos los datos ingresados.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, limpiar",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById("formDatos").reset();
            Swal.fire({
                icon: "info",
                title: "Formulario limpio",
                text: "Los datos han sido eliminados."
            });
        }
    });
}

function salir() {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Si sales, perderás los datos ingresados.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, salir",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "../home.html";
        }
    });
}
