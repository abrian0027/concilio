<?php
/**
 * Menú Lateral - Sistema Concilio
 * Bootstrap 5 Puro - Sin AdminLTE
 * Detecta TODOS los roles del usuario consultando las tablas
 */

// ============================================
// CONEXIÓN A BASE DE DATOS
// ============================================
if (!isset($conexion) || $conexion === null) {
    $config_paths = array(
        __DIR__ . '/../../config/config.php',
        __DIR__ . '/../config/config.php',
        dirname(__DIR__) . '/config/config.php'
    );
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// ============================================
// OBTENER ROL DEL USUARIO
// ============================================
$rol = isset($ROL_NOMBRE) ? $ROL_NOMBRE : (isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : 'sin_rol');
$usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
$usuario_cedula = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';

// Variables para roles adicionales
$es_supervisor_distrito = false;
$distrito_supervisa = null;

$es_superintendente = false;
$conferencia_superintende = null;

$es_lider_ministerio_local = false;
$ministerio_local_info = null;

$es_lider_ministerio_conf = false;
$ministerio_conf_info = null;

// ============================================
// DETECTAR ROLES ADICIONALES
// ============================================
if (isset($conexion) && $conexion !== null) {
    
    // Obtener cédula desde tabla usuarios si está vacía
    if (empty($usuario_cedula) && $usuario_id > 0) {
        $result = $conexion->query("SELECT usuario FROM usuarios WHERE id = $usuario_id LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $usuario_cedula = $row['usuario'];
        }
    }
    
    // Solo buscar roles adicionales si hay cédula
    if (!empty($usuario_cedula)) {
        
        // 1. BUSCAR SI ES SUPERVISOR DE DISTRITO
        $cedula_escaped = $conexion->real_escape_string($usuario_cedula);
        $sql = "SELECT d.id, d.codigo, d.nombre, d.conferencia_id
                FROM distritos d 
                INNER JOIN pastores p ON d.supervisor_id = p.id 
                WHERE p.cedula = '$cedula_escaped' AND d.activo = 1
                LIMIT 1";
        $result = $conexion->query($sql);
        if ($result && $result->num_rows > 0) {
            $es_supervisor_distrito = true;
            $distrito_supervisa = $result->fetch_assoc();
        }
        
        // 2. BUSCAR SI ES SUPERINTENDENTE DE CONFERENCIA
        $sql = "SELECT c.id, c.codigo, c.nombre
                FROM conferencias c 
                INNER JOIN pastores p ON c.superintendente_id = p.id 
                WHERE p.cedula = '$cedula_escaped' AND c.activo = 1
                LIMIT 1";
        $result = $conexion->query($sql);
        if ($result && $result->num_rows > 0) {
            $es_superintendente = true;
            $conferencia_superintende = $result->fetch_assoc();
        }
        
        // 3. BUSCAR SI ES PRESIDENTE/DIRECTIVA DE MINISTERIO DE CONFERENCIA
        if ($usuario_id > 0) {
            $sql = "SELECT mlc.id, mlc.ministerio_id, mlc.cargo, mlc.conferencia_id,
                           m.nombre as ministerio_nombre,
                           c.nombre as conferencia_nombre
                    FROM ministerio_lideres_conferencia mlc
                    INNER JOIN ministerios m ON mlc.ministerio_id = m.id
                    INNER JOIN conferencias c ON mlc.conferencia_id = c.id
                    INNER JOIN usuarios u ON u.miembro_id = mlc.miembro_id
                    WHERE u.id = $usuario_id AND mlc.activo = 1
                    LIMIT 1";
            $result = $conexion->query($sql);
            if ($result && $result->num_rows > 0) {
                $es_lider_ministerio_conf = true;
                $ministerio_conf_info = $result->fetch_assoc();
            }
        }
        
        // 4. BUSCAR SI ES LÍDER DE MINISTERIO LOCAL (Iglesia) - Solo para rol lider_ministerio
        if ($rol === 'lider_ministerio' && isset($_SESSION['iglesia_id'])) {
            $iglesia_id = intval($_SESSION['iglesia_id']);
            $sql = "SELECT al.*, am.nombre AS area_nombre
                    FROM area_lideres al
                    INNER JOIN areas_ministeriales am ON al.area_id = am.id
                    INNER JOIN miembros m ON al.miembro_id = m.id
                    INNER JOIN usuarios u ON u.iglesia_id = m.iglesia_id 
                        AND UPPER(m.nombre) = UPPER(u.nombre)
                        AND UPPER(m.apellido) = UPPER(u.apellido)
                    WHERE u.id = $usuario_id AND al.iglesia_id = $iglesia_id AND al.activo = 1 AND al.tipo = 'lider'
                    LIMIT 1";
            $result = $conexion->query($sql);
            if ($result && $result->num_rows > 0) {
                $es_lider_ministerio_local = true;
                $ministerio_local_info = $result->fetch_assoc();
            }
        }
    }
}

// ============================================
// CALCULAR RUTA BASE
// ============================================
$script_path = $_SERVER['SCRIPT_NAME'];
$admin_pos = strpos($script_path, '/admin/');
if ($admin_pos !== false) {
    $after_admin = substr($script_path, $admin_pos + 7);
    $depth = substr_count($after_admin, '/');
} else {
    $depth = 0;
}
$base = str_repeat('../', $depth);
if ($base === '') $base = './';

// Página actual para marcar activo
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<!-- ========== MENÚ BOOTSTRAP 5 ========== -->
<ul class="nav-menu">
    
    <!-- ===== PRINCIPAL ===== -->
    <li class="nav-section">PRINCIPAL</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

<?php if ($rol === 'super_admin'): ?>
    <!-- ==================== SUPER ADMIN ==================== -->
    
    <!-- Administración -->
    <li class="nav-section">ADMINISTRACIÓN</li>
    
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-cogs"></i>
            <span>Configuración</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>conferencias/index.php" class="<?php echo $current_dir === 'conferencias' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Conferencias</a></li>
            <li><a href="<?php echo $base; ?>distritos/index.php" class="<?php echo $current_dir === 'distritos' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Distritos</a></li>
            <li><a href="<?php echo $base; ?>iglesias/index.php" class="<?php echo $current_dir === 'iglesias' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Iglesias</a></li>
        </ul>
    </li>
    
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-users-cog"></i>
            <span>Usuarios</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>usuarios/index.php" class="<?php echo $current_dir === 'usuarios' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Administrar</a></li>
            <li><a href="<?php echo $base; ?>pastores/index.php" class="<?php echo $current_dir === 'pastores' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Pastores</a></li>
            <li><a href="<?php echo $base; ?>roles/index.php" class="<?php echo $current_dir === 'roles' ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Roles</a></li>
        </ul>
    </li>
    
    <!-- Gestión -->
    <li class="nav-section">GESTIÓN</li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>miembros/index.php" class="nav-link <?php echo $current_dir === 'miembros' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Miembros</span>
        </a>
    </li>
    
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-sitemap"></i>
            <span>Estructura</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>estructura/index.php"><i class="fas fa-circle"></i> General</a></li>
            <li><a href="<?php echo $base; ?>estructura/periodos/index.php"><i class="fas fa-circle"></i> Períodos</a></li>
            <li><a href="<?php echo $base; ?>estructura/junta/index.php"><i class="fas fa-circle"></i> Junta Admin.</a></li>
            <li><a href="<?php echo $base; ?>estructura/lideres/index.php"><i class="fas fa-circle"></i> Líderes</a></li>
            <li><a href="<?php echo $base; ?>estructura/zonas/index.php"><i class="fas fa-circle"></i> Zonas/Grupos</a></li>
        </ul>
    </li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>ministerios_conf/index.php" class="nav-link <?php echo $current_dir === 'ministerios_conf' ? 'active' : ''; ?>">
            <i class="fas fa-hands-praying"></i>
            <span>Ministerios Conf.</span>
        </a>
    </li>
    
    <!-- Auditoría - Solo Super Admin -->
    <li class="nav-section">SEGURIDAD</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>auditoria/index.php" class="nav-link <?php echo $current_dir === 'auditoria' ? 'active' : ''; ?>">
            <i class="fas fa-shield-alt"></i>
            <span>Auditoría</span>
        </a>
    </li>
    
    <!-- Finanzas -->
    <li class="nav-section">FINANZAS</li>
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-coins"></i>
            <span>Contabilidad</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>finanzas/index.php"><i class="fas fa-circle"></i> Dashboard</a></li>
            <li><a href="<?php echo $base; ?>finanzas/entradas.php"><i class="fas fa-circle"></i> Ingresos</a></li>
            <li><a href="<?php echo $base; ?>finanzas/salidas.php"><i class="fas fa-circle"></i> Egresos</a></li>
            <li><a href="<?php echo $base; ?>finanzas/cuentas.php"><i class="fas fa-circle"></i> Cuentas</a></li>
            <li><a href="<?php echo $base; ?>finanzas/reportes.php"><i class="fas fa-circle"></i> Reportes</a></li>
        </ul>
    </li>

<?php elseif ($rol === 'super_conferencia'): ?>
    <!-- ==================== SUPERINTENDENTE DE CONFERENCIA ==================== -->
    
    <li class="nav-section">MI CONFERENCIA</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>panel_superintendente.php" class="nav-link <?php echo $current_page === 'panel_superintendente.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Panel General</span>
        </a>
    </li>
    
    <!-- Administración Conferencia -->
    <li class="nav-section">ADMINISTRACIÓN</li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/index.php" class="nav-link <?php echo $current_dir === 'distritos' ? 'active' : ''; ?>">
            <i class="fas fa-map-marked-alt"></i>
            <span>Distritos</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>iglesias/index.php" class="nav-link <?php echo $current_dir === 'iglesias' ? 'active' : ''; ?>">
            <i class="fas fa-church"></i>
            <span>Iglesias</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>pastores/index.php" class="nav-link <?php echo $current_dir === 'pastores' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>Pastores</span>
        </a>
    </li>
    
    <!-- Gestión -->
    <li class="nav-section">GESTIÓN</li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>miembros/index.php" class="nav-link <?php echo $current_dir === 'miembros' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Miembros</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>ministerios_conf/index.php" class="nav-link <?php echo $current_dir === 'ministerios_conf' ? 'active' : ''; ?>">
            <i class="fas fa-hands-praying"></i>
            <span>Ministerios Conf.</span>
        </a>
    </li>
    
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>reportes/miembros_conferencia.php"><i class="fas fa-circle"></i> Miembros</a></li>
            <li><a href="<?php echo $base; ?>reportes/distritos_conferencia.php"><i class="fas fa-circle"></i> Por Distrito</a></li>
            <li><a href="<?php echo $base; ?>reportes/ministerios_conferencia.php"><i class="fas fa-circle"></i> Por Ministerio</a></li>
        </ul>
    </li>

<?php elseif ($rol === 'super_distrito'): ?>
    <!-- ==================== SUPERVISOR DISTRITO ==================== -->
    
    <li class="nav-section">MI DISTRITO</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/panel.php" class="nav-link">
            <i class="fas fa-map-marked-alt"></i>
            <span>Panel</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/mis_iglesias.php" class="nav-link">
            <i class="fas fa-church"></i>
            <span>Mis Iglesias</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>miembros/index.php" class="nav-link">
            <i class="fas fa-users"></i>
            <span>Miembros</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/reportes.php" class="nav-link">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes</span>
        </a>
    </li>

<?php elseif ($rol === 'lider_ministerio'): ?>
    <!-- ==================== LÍDER MINISTERIO ==================== -->
    
    <?php if ($es_lider_ministerio_local && $ministerio_local_info): ?>
    <li class="nav-section">MI MINISTERIO (LOCAL)</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>mi_ministerio/index.php" class="nav-link">
            <i class="fas fa-users"></i>
            <span>Miembros del Ministerio</span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($es_lider_ministerio_conf && $ministerio_conf_info): ?>
    <li class="nav-section">
        <i class="fas fa-crown text-info me-1"></i> MINISTERIO CONFERENCIA
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>ministerio_conferencia/index.php" class="nav-link">
            <i class="fas fa-users-cog"></i>
            <span><?php echo htmlspecialchars($ministerio_conf_info['ministerio_nombre']); ?></span>
            <span class="badge bg-info ms-1"><?php echo ucfirst($ministerio_conf_info['cargo']); ?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if (!$es_lider_ministerio_local && !$es_lider_ministerio_conf): ?>
    <li class="nav-section">MI MINISTERIO</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>mi_ministerio/index.php" class="nav-link">
            <i class="fas fa-users-cog"></i>
            <span>Miembros del Ministerio</span>
        </a>
    </li>
    <?php endif; ?>

<?php elseif (in_array($rol, array('pastor', 'secretaria', 'tesorero'))): ?>
    <!-- ==================== ROLES DE IGLESIA ==================== -->
    
    <?php // ===== SI TAMBIÉN ES SUPERINTENDENTE ===== ?>
    <?php if ($es_superintendente): ?>
    <li class="nav-section">
        <i class="fas fa-crown text-warning me-1"></i> SUPERINTENDENTE <?php echo htmlspecialchars($conferencia_superintende['codigo']); ?>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/index.php" class="nav-link">
            <i class="fas fa-map-marked-alt"></i>
            <span>Distritos</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>ministerios_conf/index.php" class="nav-link">
            <i class="fas fa-hands-praying"></i>
            <span>Ministerios Conf.</span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php // ===== SI TAMBIÉN ES SUPERVISOR DE DISTRITO ===== ?>
    <?php if ($es_supervisor_distrito): ?>
    <li class="nav-section">
        <i class="fas fa-star text-warning me-1"></i> SUPERVISOR <?php echo htmlspecialchars($distrito_supervisa['codigo']); ?>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/mis_iglesias.php" class="nav-link">
            <i class="fas fa-church"></i>
            <span>Iglesias del Distrito</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>distritos/reportes.php" class="nav-link">
            <i class="fas fa-chart-bar"></i>
            <span>Reportes Distrito</span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php // ===== SI TAMBIÉN ES PRESIDENTE/DIRECTIVA DE MINISTERIO CONFERENCIA ===== ?>
    <?php if ($es_lider_ministerio_conf && $ministerio_conf_info): ?>
    <li class="nav-section">
        <i class="fas fa-crown text-purple me-1"></i> MINISTERIO CONFERENCIA
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>ministerio_conferencia/index.php" class="nav-link">
            <i class="fas fa-users-cog"></i>
            <span><?php echo htmlspecialchars($ministerio_conf_info['ministerio_nombre']); ?></span>
            <span class="badge bg-info ms-auto"><?php echo ucfirst($ministerio_conf_info['cargo']); ?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <!-- ===== IGLESIA LOCAL ===== -->
    <li class="nav-section">MI IGLESIA</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>miembros/index.php" class="nav-link <?php echo $current_dir === 'miembros' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Miembros</span>
        </a>
    </li>
    
    <li class="nav-item">
        <a href="<?php echo $base; ?>visitas/index.php" class="nav-link <?php echo $current_dir === 'visitas' ? 'active' : ''; ?>">
            <i class="fas fa-user-plus"></i>
            <span>Visitas</span>
        </a>
    </li>
    
    <?php if ($rol === 'pastor'): ?>
    <li class="nav-item">
        <a href="<?php echo $base; ?>usuarios_iglesia/index.php" class="nav-link">
            <i class="fas fa-user-cog"></i>
            <span>Usuarios</span>
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-sitemap"></i>
            <span>Estructura</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>estructura/index.php"><i class="fas fa-circle"></i> General</a></li>
            <li><a href="<?php echo $base; ?>estructura/periodos/index.php"><i class="fas fa-circle"></i> Períodos</a></li>
            <li><a href="<?php echo $base; ?>estructura/junta/index.php"><i class="fas fa-circle"></i> Junta Admin.</a></li>
            <li><a href="<?php echo $base; ?>estructura/lideres/index.php"><i class="fas fa-circle"></i> Líderes</a></li>
            <li><a href="<?php echo $base; ?>estructura/zonas/index.php"><i class="fas fa-circle"></i> Zonas/Grupos</a></li>
        </ul>
    </li>
    
    <!-- Finanzas -->
    <li class="nav-section">FINANZAS</li>
    <li class="nav-item has-submenu">
        <a href="#" class="nav-link">
            <i class="fas fa-coins"></i>
            <span>Contabilidad</span>
            <i class="fas fa-chevron-down arrow"></i>
        </a>
        <ul class="submenu">
            <li><a href="<?php echo $base; ?>finanzas/index.php"><i class="fas fa-circle"></i> Dashboard</a></li>
            <li><a href="<?php echo $base; ?>finanzas/entradas.php"><i class="fas fa-circle"></i> Ingresos</a></li>
            <li><a href="<?php echo $base; ?>finanzas/salidas.php"><i class="fas fa-circle"></i> Egresos</a></li>
            <li><a href="<?php echo $base; ?>finanzas/cuentas.php"><i class="fas fa-circle"></i> Cuentas</a></li>
            <li><a href="<?php echo $base; ?>finanzas/reportes.php"><i class="fas fa-circle"></i> Reportes</a></li>
        </ul>
    </li>

<?php endif; ?>

    <!-- ===== CUENTA (siempre visible) ===== -->
    <li class="nav-section">CUENTA</li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>perfil/index.php" class="nav-link">
            <i class="fas fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>
    </li>
    <li class="nav-item">
        <a href="<?php echo $base; ?>../auth/logout.php" class="nav-link text-danger-subtle">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </li>
    
</ul>
