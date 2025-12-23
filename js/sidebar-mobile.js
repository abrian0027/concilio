/**
 * Sidebar Mobile - Sistema Concilio
 * Manejo unificado del menú móvil y desplegables
 * Versión optimizada y ligera
 */

(function() {
    'use strict';
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('🚀 Inicializando sidebar móvil...');
        
        // ==================== ELEMENTOS ====================
        const body = document.body;
        const sidebar = document.querySelector('.app-sidebar');
        const toggleButton = document.querySelector('[data-lte-toggle="sidebar"]');
        let overlay = document.querySelector('.sidebar-overlay');
        
        // Validar elementos esenciales
        if (!sidebar) {
            console.error('❌ Sidebar no encontrado');
            return;
        }
        
        if (!toggleButton) {
            console.error('❌ Botón toggle no encontrado');
            return;
        }
        
        // Crear overlay si no existe
        if (!overlay) {
            console.log('📱 Creando overlay...');
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
        }
        
        // ==================== FUNCIONES ====================
        
        function openSidebar() {
            body.classList.add('sidebar-open');
            sidebar.setAttribute('aria-hidden', 'false');
            console.log('✅ Sidebar abierto');
        }
        
        function closeSidebar() {
            body.classList.remove('sidebar-open');
            sidebar.setAttribute('aria-hidden', 'true');
            console.log('✅ Sidebar cerrado');
        }
        
        function toggleSidebar(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            if (body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
        
        // ==================== EVENT LISTENERS ====================
        
        // Botón hamburguesa
        toggleButton.addEventListener('click', toggleSidebar, {passive: false});
        console.log('✅ Event listener agregado al botón');
        
        // Overlay
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebar();
        });
        
        // Cerrar al hacer clic en enlaces del menú (solo móvil)
        const menuLinks = sidebar.querySelectorAll('.nav-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                const href = this.getAttribute('href');
                // Solo cerrar si es un enlace real y estamos en móvil
                if (href && href !== '#' && window.innerWidth < 992) {
                    setTimeout(closeSidebar, 150);
                }
            });
        });
        
        // Cerrar con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        });
        
        // Cerrar al cambiar a desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 992 && body.classList.contains('sidebar-open')) {
                    closeSidebar();
                }
            }, 100);
        });
        
        // ==================== MENÚS DESPLEGABLES ====================
        
        const treeviewToggles = document.querySelectorAll('.has-treeview > .nav-link');
        
        treeviewToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                const parentLi = this.parentElement;
                const isOpen = parentLi.classList.contains('menu-open');
                const submenu = parentLi.querySelector('.nav-treeview');
                
                // Cerrar otros menús (comportamiento acordeón)
                document.querySelectorAll('.has-treeview.menu-open').forEach(item => {
                    if (item !== parentLi) {
                        item.classList.remove('menu-open');
                    }
                });
                
                // Toggle del menú actual
                if (isOpen) {
                    parentLi.classList.remove('menu-open');
                } else {
                    parentLi.classList.add('menu-open');
                }
            });
        });
        
        // ==================== MARCAR ENLACE ACTIVO ====================
        
        const currentPath = window.location.pathname;
        const currentFile = currentPath.split('/').pop();
        
        document.querySelectorAll('.nav-sidebar .nav-link').forEach(link => {
            const href = link.getAttribute('href');
            
            if (href && href !== '#') {
                const linkFile = href.split('/').pop().split('?')[0];
                
                if (currentFile === linkFile || currentPath.includes(linkFile)) {
                    link.classList.add('active');
                    
                    // Expandir el padre si está dentro de un submenú
                    const parentTreeview = link.closest('.has-treeview');
                    if (parentTreeview) {
                        parentTreeview.classList.add('menu-open');
                    }
                }
            }
        });
        
        console.log('✅ Sidebar inicializado correctamente');
    }
    
})();

