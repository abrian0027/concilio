<?php
/**
 * Header - Sistema Concilio
 * Bootstrap 5 Puro - Sin AdminLTE
 * Sin títulos duplicados - Diseño limpio
 */

require_once __DIR__ . '/seguridad.php';

// Título de la página
$titulo_pagina = isset($page_title) ? $page_title : 'Sistema Concilio';

// Calcular rutas relativas
$script_path = $_SERVER['SCRIPT_NAME'];
$admin_pos = strpos($script_path, '/admin/');
if ($admin_pos !== false) {
    $after_admin = substr($script_path, $admin_pos + 7);
    $depth = substr_count($after_admin, '/');
} else {
    $depth = 0;
}
$base_path = str_repeat('../', $depth);

// Iniciales del usuario
$iniciales = obtenerIniciales($USUARIO_NOMBRE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#0891b2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?php echo htmlspecialchars($titulo_pagina); ?> - Sistema Concilio</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/concilio/assets/img/favicon.ico">
    <link rel="shortcut icon" href="/concilio/assets/img/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/concilio/assets/img/apple-touch-icon.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>../css/custom.css">
</head>
<body>

<!-- Overlay para cerrar sidebar en móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
    
    <!-- Header del sidebar -->
    <div class="sidebar-header">
        <a href="<?php echo $base_path; ?>dashboard.php" class="d-flex align-items-center text-white text-decoration-none">
            <img src="<?php echo $base_path; ?>../assets/img/logo-concilio.png" alt="Logo" height="38" class="sidebar-logo">
            <span class="sidebar-title ms-2">Sistema Concilio</span>
        </a>
        <button class="btn-close-sidebar d-lg-none" id="sidebarClose" type="button">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <!-- Menú de navegación -->
    <nav class="sidebar-nav">
        <?php include __DIR__ . '/menu.php'; ?>
    </nav>
    
    <!-- Footer del sidebar -->
    <div class="sidebar-footer">
        <small>v2.0 © <?php echo date('Y'); ?></small>
    </div>
    
</aside>

<!-- ========== NAVBAR SUPERIOR ========== -->
<nav class="top-navbar">
    <div class="navbar-container">
        
        <!-- Izquierda: Menú hamburguesa + Título -->
        <div class="navbar-left">
            <button class="btn-menu" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title d-none d-sm-inline"><?php echo htmlspecialchars($titulo_pagina); ?></span>
        </div>
        
        <!-- Derecha: Notificaciones + Usuario -->
        <div class="navbar-right">
            
            <!-- Notificaciones -->
            <div class="dropdown">
                <button class="btn-icon" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 280px;">
                    <h6 class="dropdown-header">Notificaciones</h6>
                    <a class="dropdown-item py-2" href="#">
                        <i class="fas fa-user-plus text-info me-2"></i>
                        <small>Nuevo miembro registrado</small>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center small" href="#">Ver todas</a>
                </div>
            </div>
            
            <!-- Usuario -->
            <div class="dropdown">
                <button class="btn-user" type="button" data-bs-toggle="dropdown">
                    <span class="user-name d-none d-md-inline"><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></span>
                    <span class="user-avatar"><?php echo $iniciales; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li class="dropdown-header">
                        <strong><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></strong><br>
                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $ROL_NOMBRE)); ?></small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?php echo $base_path; ?>perfil/index.php">
                            <i class="fas fa-user me-2 text-muted"></i> Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?php echo $base_path; ?>configuracion/index.php">
                            <i class="fas fa-cog me-2 text-muted"></i> Configuración
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?php echo $base_path; ?>../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- ========== CONTENIDO PRINCIPAL ========== -->
<main class="main-content">
    <div class="content-wrapper">
