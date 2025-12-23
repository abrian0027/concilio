// header-actions.js - Funcionalidades del header
document.addEventListener('DOMContentLoaded', function() {
    // ==================== VARIABLES ====================
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebarMenu');
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    const skipLink = document.querySelector('.skip-link');
    const mainContent = document.getElementById('main-content');
    
    // ==================== MENÚ MÓVIL ====================
    if (menuToggle && sidebar) {
        // Crear overlay para móviles
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        document.body.appendChild(overlay);
        
        // Función para alternar menú
        function toggleMenu() {
            const isOpen = sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', isOpen);
            menuToggle.innerHTML = isOpen 
                ? '<i class="fas fa-times"></i><span class="sr-only">Cerrar menú</span>' 
                : '<i class="fas fa-bars"></i><span class="sr-only">Abrir menú</span>';
            
            // Bloquear scroll del body cuando el menú está abierto
            document.body.style.overflow = isOpen ? 'hidden' : '';
        }
        
        // Eventos
        menuToggle.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
        
        // Cerrar menú con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                toggleMenu();
            }
        });
        
        // Cerrar menú al hacer clic en enlace (móviles)
        sidebar.addEventListener('click', function(e) {
            if (window.innerWidth <= 992 && e.target.closest('a')) {
                toggleMenu();
            }
        });
    }
    
    // ==================== TOGGLE TEMA ====================
    if (themeToggle) {
        // Verificar preferencia del sistema
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const savedTheme = localStorage.getItem('tema_oscuro');
        
        // Aplicar tema inicial
        if (savedTheme === 'true' || (!savedTheme && prefersDark)) {
            htmlElement.classList.add('theme-dark');
            updateThemeIcon(true);
        }
        
        // Función para cambiar tema
        function toggleTheme() {
            const isDark = !htmlElement.classList.contains('theme-dark');
            htmlElement.classList.toggle('theme-dark');
            
            // Guardar preferencia
            localStorage.setItem('tema_oscuro', isDark);
            document.cookie = `tema_oscuro=${isDark}; path=/; max-age=31536000; SameSite=Lax`;
            
            // Actualizar icono
            updateThemeIcon(isDark);
            
            // Feedback para usuarios de lectores de pantalla
            const message = isDark ? 'Modo oscuro activado' : 'Modo claro activado';
            announceToScreenReader(message);
        }
        
        // Actualizar icono del botón
        function updateThemeIcon(isDark) {
            const icon = themeToggle.querySelector('i');
            const text = themeToggle.querySelector('span');
            
            if (isDark) {
                icon.className = 'fas fa-sun';
                text.textContent = 'Modo Claro';
            } else {
                icon.className = 'fas fa-moon';
                text.textContent = 'Modo Oscuro';
            }
        }
        
        themeToggle.addEventListener('click', toggleTheme);
        
        // Escuchar cambios en preferencia del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('tema_oscuro')) {
                const isDark = e.matches;
                htmlElement.classList.toggle('theme-dark', isDark);
                updateThemeIcon(isDark);
            }
        });
    }
    
    // ==================== ACCESIBILIDAD ====================
    // Manejar skip link
    if (skipLink && mainContent) {
        skipLink.addEventListener('click', function(e) {
            e.preventDefault();
            mainContent.setAttribute('tabindex', '-1');
            mainContent.focus();
        });
        
        // Restaurar tabindex después del foco
        mainContent.addEventListener('blur', function() {
            this.removeAttribute('tabindex');
        });
    }
    
    // Mejorar focus para teclado
    document.addEventListener('keyup', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('focus-visible');
        }
    });
    
    document.addEventListener('click', function() {
        document.body.classList.remove('focus-visible');
    });
    
    // ==================== UTILIDADES ====================
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
    
    // ==================== PERFORMANCE ====================
    // Cargar CSS diferido
    function loadDeferredStyles() {
        const links = document.querySelectorAll('link[rel="preload"][as="style"]');
        links.forEach(link => {
            if (link.onload) return;
            link.rel = 'stylesheet';
        });
    }
    
    // Cargar cuando la página esté lista
    if (document.readyState === 'complete') {
        loadDeferredStyles();
    } else {
        window.addEventListener('load', loadDeferredStyles);
    }
    
    // ==================== RESIZE HANDLER ====================
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Cerrar menú si se cambia a desktop
            if (window.innerWidth > 992 && sidebar && sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
                document.querySelector('.overlay').classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.innerHTML = '<i class="fas fa-bars"></i><span class="sr-only">Abrir menú</span>';
                document.body.style.overflow = '';
            }
        }, 250);
    });
});