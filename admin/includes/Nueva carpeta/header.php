<?php
/**
 * Header - Sistema Concilio
 * Bootstrap 5 Puro - Mobile First
 * Sin AdminLTE para evitar conflictos de versiones
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
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo htmlspecialchars($titulo_pagina); ?> - Sistema Concilio</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_path; ?>../assets/img/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_path; ?>../assets/img/apple-touch-icon.png">
    
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

<!-- ========== NAVBAR SUPERIOR ========== -->
<nav class="navbar navbar-expand navbar-light bg-white fixed-top shadow-sm">
    <div class="container-fluid">
        
        <!-- Botón menú + Logo (móvil) -->
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-dark me-2 d-lg-none p-2" type="button" id="sidebarToggle">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $base_path; ?>dashboard.php">
                <img src="<?php echo $base_path; ?>../assets/img/logo-concilio.png" alt="Logo" height="35" class="me-2">
                <span class="fw-semibold d-none d-sm-inline text-primary-dark">Sistema Concilio</span>
            </a>
        </div>
        
        <!-- Título página (solo desktop) -->
        <div class="d-none d-lg-block">
            <span class="navbar-text fw-medium"><?php echo htmlspecialchars($titulo_pagina); ?></span>
        </div>
        
        <!-- Menú derecho -->
        <ul class="navbar-nav ms-auto align-items-center">
            
            <!-- Notificaciones -->
            <li class="nav-item dropdown me-2">
                <a class="nav-link position-relative p-2" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                        3
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow" style="min-width: 280px;">
                    <h6 class="dropdown-header">Notificaciones</h6>
                    <a class="dropdown-item py-2" href="#">
                        <i class="fas fa-user-plus text-info me-2"></i>
                        <small>Nuevo miembro registrado</small>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center small" href="#">Ver todas</a>
                </div>
            </li>
            
            <!-- Usuario -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center p-1" href="#" data-bs-toggle="dropdown">
                    <span class="d-none d-md-inline me-2 small"><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></span>
                    <span class="avatar-circle"><?php echo $iniciales; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
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
            </li>
        </ul>
    </div>
</nav>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
    
    <!-- Header del sidebar -->
    <div class="sidebar-header">
        <img src="<?php echo $base_path; ?>../assets/img/logo-concilio.png" alt="Logo" height="40">
        <span class="sidebar-title">Sistema Concilio</span>
        <button class="btn-close-sidebar d-lg-none" id="sidebarClose">
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

<!-- ========== CONTENIDO PRINCIPAL ========== -->
<main class="main-content">
    <div class="container-fluid py-3">
