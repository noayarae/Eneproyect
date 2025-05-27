document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // Funcionalidad para el sidebar retráctil
    // =============================================
    const sidebar = document.querySelector('.sidebar');
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    
    // Toggle sidebar en desktop
    toggleSidebar.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });
    
    // Toggle sidebar en móvil
    mobileMenuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });
    
    // Cerrar sidebar en móvil al hacer clic en un enlace
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('show');
            }
        });
    });

    // =============================================
    // Funcionalidad del tema
    // =============================================
    const themeSelect = document.getElementById('modo');
    
    themeSelect.addEventListener('change', () => {
        localStorage.setItem('tema', themeSelect.value);
        document.body.className = themeSelect.value;
    });

    // Aplicar tema guardado al cargar
    const savedTheme = localStorage.getItem('tema') || 'claro';
    themeSelect.value = savedTheme;
    document.body.className = savedTheme;

    // =============================================
    // Validación de formularios
    // =============================================
    const passwordForm = document.querySelector('form[action="cambiar_password.php"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 8 caracteres');
                return;
            }
        });
    }

    // =============================================
    // Mostrar/ocultar contraseña
    // =============================================
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'password-visible';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    // =============================================
    // Ajustar contenido al cambiar tamaño de sidebar
    // =============================================
    function adjustContent() {
        const settingsContainer = document.querySelector('.settings-container');
        if (sidebar.classList.contains('collapsed')) {
            settingsContainer.style.maxWidth = '950px';
        } else {
            settingsContainer.style.maxWidth = '900px';
        }
    }

    // Observar cambios en el sidebar
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                adjustContent();
            }
        });
    });

    observer.observe(sidebar, { attributes: true });
    
    // Ajustar inicialmente
    adjustContent();
});