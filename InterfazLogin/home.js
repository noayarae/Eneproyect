document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const sidebar = document.getElementById("sidebar");

    // Manejo de la barra lateral (sidebar)
    menuToggle.addEventListener("click", function (event) {
        event.stopPropagation(); // Evita que el clic cierre el menú inmediatamente
        sidebar.classList.toggle("open");
    });

    // Cierra el menú si se hace clic fuera de él
    document.addEventListener("click", function (event) {
        if (sidebar.classList.contains("open") && !sidebar.contains(event.target) && event.target !== menuToggle) {
            sidebar.classList.remove("open");
        }
    });

    // Evita que el clic dentro del sidebar lo cierre
    sidebar.addEventListener("click", function (event) {
        event.stopPropagation();
    });
});
