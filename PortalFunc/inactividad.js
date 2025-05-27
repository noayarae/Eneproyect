document.addEventListener("DOMContentLoaded", function () {
    const inactivityTime = 30 * 60 * 1000; // 3 minutos en milisegundos
    let timeout;
    let remainingTime = inactivityTime; // Tiempo restante para la inactividad en milisegundos
    let countdownInterval; // Variable global para el intervalo del contador

    // Función para resetear el temporizador cuando el usuario interactúa
    function resetTimer() {
        clearTimeout(timeout);
        remainingTime = inactivityTime; // Resetea el contador a 3 minutos
        timeout = setTimeout(redirectToLogin, inactivityTime);
        startCountdown(); // Inicia el contador de minutos
    }

    // Función que redirige al usuario al login después de 3 minutos
    function redirectToLogin() {
        // Destruir la sesión antes de redirigir
        fetch('/InterfazLogin/FuncionLogin/logout.php')
            .then(() => {
                window.location.href = '/InterfazLogin/FuncionLogin/login.html?inactividad=true';
            });
    }    

    // Función para mostrar el contador en la consola (opcional)
    function startCountdown() {
        // Limpiar cualquier contador previo
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Iniciar el contador de minutos y segundos
        countdownInterval = setInterval(function () {
            remainingTime -= 1000; // Resta 1 segundo (1000 ms)
            const minutes = Math.floor(remainingTime / 60000); // Obtiene los minutos
            const seconds = Math.floor((remainingTime % 60000) / 1000); // Obtiene los segundos

            // Muestra el tiempo restante en la consola (opcional)
            console.log(`Tiempo restante para inactividad: ${minutes}m ${seconds}s`);

            // Si el tiempo llega a 0, redirige al login
            if (remainingTime <= 0) {
                clearInterval(countdownInterval); // Detiene el contador
            }
        }, 1000); // Actualiza cada segundo
    }

    // Detectar la actividad del usuario (clic, movimiento de ratón, teclas, scroll)
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    document.ontouchstart = resetTimer;
    document.ontouchmove = resetTimer;
    window.onscroll = resetTimer; // Detecta el scroll como actividad
});
