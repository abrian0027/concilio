/**
 * Sistema Concilio - JavaScript
 * Bootstrap 5 Puro - Mobile First
 * Maneja: Sidebar, Submenús, Utilidades
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==================== ELEMENTOS ====================
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    
    // ==================== SIDEBAR TOGGLE ====================
    
    // Abrir sidebar
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            openSidebar();
        });
    }
    
    // Cerrar sidebar (botón X)
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeSidebar();
        });
    }
    
    // Cerrar sidebar (click en overlay)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('open');
            body.classList.add('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.classList.add('active');
        }
    }
    
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('open');
            body.classList.remove('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        }
    }
    
    // ==================== SUBMENÚS ====================
    
    const submenuToggles = document.querySelectorAll('.nav-menu .has-submenu > .nav-link');
    
    submenuToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement;
            const isOpen = parent.classList.contains('open');
            
            // Cerrar otros submenús
            document.querySelectorAll('.nav-menu .has-submenu.open').forEach(function(item) {
                if (item !== parent) {
                    item.classList.remove('open');
                }
            });
            
            // Toggle actual
            parent.classList.toggle('open');
        });
    });
    
    // Abrir submenú activo al cargar
    document.querySelectorAll('.nav-menu .submenu a.active').forEach(function(link) {
        const parentSubmenu = link.closest('.has-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('open');
        }
    });
    
    // ==================== CERRAR SIDEBAR EN MÓVIL ====================
    
    // Al hacer click en links normales
    document.querySelectorAll('.nav-menu .nav-link:not(.has-submenu > .nav-link)').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });
    
    // Al hacer click en links de submenú
    document.querySelectorAll('.nav-menu .submenu a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });
    
    // ==================== RESIZE HANDLER ====================
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        }, 250);
    });
    
    // ==================== CONFIRMACIÓN ELIMINACIÓN ====================
    
    document.querySelectorAll('[data-confirm]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || '¿Está seguro?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // ==================== AUTO-DISMISS ALERTS ====================
    
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(alert) {
        const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 5000;
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            alert.style.transition = 'all 0.3s ease';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, delay);
    });
    
    // ==================== TOOLTIPS ====================
    
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
    
    console.log('✅ Sistema Concilio - App.js inicializado');
});

// ==================== FUNCIONES GLOBALES ====================

/**
 * Mostrar toast
 */
function showToast(message, type, duration) {
    type = type || 'info';
    duration = duration || 3000;
    
    const colors = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#0dcaf0'
    };
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + '"></i><span>' + message + '</span>';
    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:14px 20px;background:' + (colors[type] || colors.info) + ';color:#fff;border-radius:10px;display:flex;align-items:center;gap:10px;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,0.2);animation:slideIn 0.3s ease;';
    
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(function() {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }, duration);
}

/**
 * Confirmar eliminación
 */
function confirmarEliminacion(id, nombre) {
    if (confirm('¿Eliminar "' + nombre + '"?\n\nEsta acción no se puede deshacer.')) {
        window.location.href = 'eliminar.php?id=' + id;
    }
}

/**
 * Formatear número
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Formatear moneda RD
 */
function formatCurrency(amount) {
    return 'RD$ ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
