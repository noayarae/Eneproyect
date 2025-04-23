function mostrarAlerta(tipo, mensaje, redireccion = null) {
    Swal.fire({
        icon: tipo, // "success", "error" o "warning"
        title: tipo === "success" ? "¡Éxito!" : "Atención",
        text: mensaje,
    }).then(() => {
        if (redireccion) {
            window.location.href = redireccion;
        }
    });
}
