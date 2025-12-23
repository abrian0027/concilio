<?php
/**
 * Menú dinámico del Sistema Concilio
 * Detecta TODOS los roles del usuario consultando las tablas correspondientes
 * Un usuario puede tener: pastor + supervisor + líder ministerio, etc.
 */

// Asegurar que existe la conexión a la base de datos
if (!isset($conexion) || $conexion === null) {
    // Buscar el archivo de configuración
    $config_paths = [
        __DIR__ . '/../../config/config.php',
        __DIR__ . '/../config/config.php',
        dirname(__DIR__) . '/config/config.php'
    ];
    
    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

$rol = isset($ROL_NOMBRE) ? $ROL_NOMBRE : ($_SESSION['rol_nombre'] ?? 'sin_rol');
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// ============================================
// OBTENER LA CÉDULA DEL USUARIO DESDE LA BD
// (porque $_SESSION['usuario'] puede estar vacío)
// ============================================
$usuario_cedula = $_SESSION['usuario'] ?? '';

// Variables para roles adicionales
$es_supervisor_distrito = false;
$distrito_supervisa = null;

$es_superintendente = false;
$conferencia_superintende = null;

$es_lider_ministerio_conf = false;
$ministerios_lidera = [];

// Solo ejecutar consultas si existe la conexión
if (isset($conexion) && $conexion !== null) {
    
    // Obtener cédula desde tabla usuarios si está vacía
    if (empty($usuario_cedula) && $usuario_id > 0) {
        $stmt_ced = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
        if ($stmt_ced) {
            $stmt_ced->bind_param("i", $usuario_id);
            $stmt_ced->execute();
            $result_ced = $stmt_ced->get_result();
            if ($row_ced = $result_ced->fetch_assoc()) {
                $usuario_cedula = $row_ced['usuario'];
            }
            $stmt_ced->close();
        }
    }

    // Solo buscar roles adicionales si hay cédula
    if (!empty($usuario_cedula)) {
        
        // 1. BUSCAR SI ES SUPERVISOR DE DISTRITO
        $sql_supervisor = "SELECT d.id, d.codigo, d.nombre, d.conferencia_id
                           FROM distritos d 
                           INNER JOIN pastores p ON d.supervisor_id = p.id 
                           WHERE p.cedula = ? AND d.activo = 1
                           LIMIT 1";
        $stmt_sup = $conexion->prepare($sql_supervisor);
        if ($stmt_sup) {
            $stmt_sup->bind_param("s", $usuario_cedula);
            $stmt_sup->execute();
            $result_sup = $stmt_sup->get_result();
            if ($result_sup->num_rows > 0) {
                $es_supervisor_distrito = true;
                $distrito_supervisa = $result_sup->fetch_assoc();
            }
            $stmt_sup->close();
        }
        
        // 2. BUSCAR SI ES SUPERINTENDENTE DE CONFERENCIA
        $sql_super_conf = "SELECT c.id, c.codigo, c.nombre
                           FROM conferencias c 
                           INNER JOIN pastores p ON c.superintendente_id = p.id 
                           WHERE p.cedula = ? AND c.activo = 1
                           LIMIT 1";
        $stmt_sc = $conexion->prepare($sql_super_conf);
        if ($stmt_sc) {
            $stmt_sc->bind_param("s", $usuario_cedula);
            $stmt_sc->execute();
            $result_sc = $stmt_sc->get_result();
            if ($result_sc->num_rows > 0) {
                $es_superintendente = true;
                $conferencia_superintende = $result_sc->fetch_assoc();
            }
            $stmt_sc->close();
        }
        
        // 3. BUSCAR SI ES LÍDER DE MINISTERIO A NIVEL CONFERENCIA
        $sql_lider_min = "SELECT mlc.id, mlc.conferencia_id, mlc.ministerio_id, mlc.cargo,
                                 m.nombre AS ministerio_nombre,
                                 c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
                          FROM ministerio_lideres_conferencia mlc
                          INNER JOIN ministerios m ON mlc.ministerio_id = m.id
                          INNER JOIN conferencias c ON mlc.conferencia_id = c.id
                          INNER JOIN miembros mb ON mlc.miembro_id = mb.id
                          WHERE mb.numero_documento = ? AND mlc.activo = 1";
        $stmt_lm = $conexion->prepare($sql_lider_min);
        if ($stmt_lm) {
            $stmt_lm->bind_param("s", $usuario_cedula);
            $stmt_lm->execute();
            $result_lm = $stmt_lm->get_result();
            while ($row = $result_lm->fetch_assoc()) {
                $es_lider_ministerio_conf = true;
                $ministerios_lidera[] = $row;
            }
            $stmt_lm->close();
        }
    }
}

// Obtener la ruta base correcta
$script_path = $_SERVER['SCRIPT_NAME'];
$admin_pos = strpos($script_path, '/admin/');
if ($admin_pos !== false) {
    $after_admin = substr($script_path, $admin_pos + 7);
    $depth = substr_count($after_admin, '/');
} else {
    $depth = 0;
}
$base = str_repeat('../', $depth);
if ($base === '') {
    $base = './';
}
?>
<ul class="menu-list">
    <li class="menu-section">
        <span class="menu-section-title">General</span>
    </li>
    <li>
        <a href="<?php echo $base; ?>panel_generico.php">
            <i class="fas fa-home"></i> Panel principal
        </a>
    </li>

    <?php if ($rol === 'super_admin'): ?>
        <!-- ========== SUPER ADMIN ========== -->
        <li class="menu-section">
            <span class="menu-section-title">Administración General</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_superadmin.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard Super Admin
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>conferencias/index.php">
                <i class="fas fa-globe-americas"></i> Conferencias
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>distritos/index.php">
                <i class="fas fa-map-marked-alt"></i> Distritos
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>iglesias/index.php">
                <i class="fas fa-church"></i> Iglesias
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>pastores/index.php">
                <i class="fas fa-user-tie"></i> Pastores
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>usuarios/index.php">
                <i class="fas fa-user-shield"></i> Usuarios
            </a>
        </li>
        
        <li class="menu-section">
            <span class="menu-section-title">Ministerios Conferencia</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>ministerios_conf/index.php">
                <i class="fas fa-hands-praying"></i> Líderes Ministerios
            </a>
        </li>
        
        <li class="menu-section">
            <span class="menu-section-title">Gestión Eclesiástica</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>miembros/index.php">
                <i class="fas fa-users"></i> Miembros
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/index.php">
                <i class="fas fa-sitemap"></i> Estructura Eclesiástica
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/index.php">
                <i class="fas fa-coins"></i> Finanzas
            </a>
        </li>

    <?php elseif ($rol === 'obispo'): ?>
        <!-- ========== OBISPO ========== -->
        <li class="menu-section">
            <span class="menu-section-title">Obispo</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_obispo.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard Obispo
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>conferencias/index.php">
                <i class="fas fa-globe-americas"></i> Conferencias
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>distritos/index.php">
                <i class="fas fa-map-marked-alt"></i> Distritos
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>pastores/index.php">
                <i class="fas fa-user-tie"></i> Pastores
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>ministerios_conf/index.php">
                <i class="fas fa-hands-praying"></i> Líderes Ministerios
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>miembros/index.php">
                <i class="fas fa-users"></i> Miembros
            </a>
        </li>

    <?php elseif ($rol === 'super_conferencia'): ?>
        <!-- ========== SUPERINTENDENTE DE CONFERENCIA ========== -->
        <li class="menu-section">
            <span class="menu-section-title">Conferencia</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_conferencia.php">
                <i class="fas fa-tachometer-alt"></i> Panel Conferencia
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>distritos/index.php">
                <i class="fas fa-map-marked-alt"></i> Distritos
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>pastores/index.php">
                <i class="fas fa-user-tie"></i> Pastores
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>ministerios_conf/index.php">
                <i class="fas fa-hands-praying"></i> Líderes Ministerios
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>miembros/index.php">
                <i class="fas fa-users"></i> Miembros
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/index.php">
                <i class="fas fa-sitemap"></i> Estructura Eclesiástica
            </a>
        </li>

    <?php elseif ($rol === 'super_distrito'): ?>
        <!-- ========== SUPERVISOR DE DISTRITO (rol puro) ========== -->
        <li class="menu-section">
            <span class="menu-section-title">Distrito</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_distrito.php">
                <i class="fas fa-tachometer-alt"></i> Panel Distrito
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>iglesias/index.php">
                <i class="fas fa-church"></i> Iglesias
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>miembros/index.php">
                <i class="fas fa-users"></i> Miembros
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/index.php">
                <i class="fas fa-sitemap"></i> Estructura Eclesiástica
            </a>
        </li>

    <?php elseif ($rol === 'lider_ministerio'): ?>
        <!-- ========== LÍDER DE MINISTERIO (rol puro) ========== -->
        <li class="menu-section">
            <span class="menu-section-title">Mi Ministerio</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_ministerio.php">
                <i class="fas fa-tachometer-alt"></i> Panel Ministerio
            </a>
        </li>
        <?php foreach ($ministerios_lidera as $ml): ?>
        <li>
            <a href="<?php echo $base; ?>ministerios_conf/ver.php?conferencia=<?php echo $ml['conferencia_id']; ?>&ministerio=<?php echo $ml['ministerio_id']; ?>">
                <i class="fas fa-users"></i> <?php echo htmlspecialchars($ml['ministerio_nombre']); ?>
            </a>
        </li>
        <?php endforeach; ?>
        <li>
            <a href="<?php echo $base; ?>ministerio/reportes.php">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
        </li>

    <?php elseif (in_array($rol, ['pastor', 'secretaria', 'tesorero'])): ?>
        <!-- ========== ROLES DE IGLESIA CON POSIBLES ROLES ADICIONALES ========== -->
        
        <?php // ===== SI TAMBIÉN ES SUPERINTENDENTE DE CONFERENCIA ===== ?>
        <?php if ($es_superintendente): ?>
            <li class="menu-section">
                <span class="menu-section-title">
                    <i class="fas fa-crown" style="color: gold;"></i> Superintendente <?php echo htmlspecialchars($conferencia_superintende['codigo']); ?>
                </span>
            </li>
            <li>
                <a href="<?php echo $base; ?>panel_conferencia.php">
                    <i class="fas fa-tachometer-alt"></i> Panel Conferencia
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>distritos/index.php">
                    <i class="fas fa-map-marked-alt"></i> Distritos
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>ministerios_conf/index.php">
                    <i class="fas fa-hands-praying"></i> Líderes Ministerios
                </a>
            </li>
        <?php endif; ?>
        
        <?php // ===== SI TAMBIÉN ES SUPERVISOR DE DISTRITO ===== ?>
        <?php if ($es_supervisor_distrito): ?>
            <li class="menu-section">
                <span class="menu-section-title">
                    <i class="fas fa-star" style="color: #ffc107;"></i> Supervisor <?php echo htmlspecialchars($distrito_supervisa['codigo']); ?>
                </span>
            </li>
            <li>
                <a href="<?php echo $base; ?>panel_distrito.php">
                    <i class="fas fa-tachometer-alt"></i> Panel Distrito
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>distritos/iglesias.php">
                    <i class="fas fa-church"></i> Iglesias del Distrito
                </a>
            </li>
            <li>
                <a href="<?php echo $base; ?>distritos/reportes.php">
                    <i class="fas fa-chart-bar"></i> Reportes Distrito
                </a>
            </li>
        <?php endif; ?>
        
        <?php // ===== SI TAMBIÉN ES LÍDER DE MINISTERIO CONFERENCIA ===== ?>
        <?php if ($es_lider_ministerio_conf): ?>
            <li class="menu-section">
                <span class="menu-section-title">
                    <i class="fas fa-hands-praying" style="color: #17a2b8;"></i> Líder Ministerio
                </span>
            </li>
            <?php foreach ($ministerios_lidera as $ml): ?>
            <li>
                <a href="<?php echo $base; ?>ministerios_conf/ver.php?conferencia=<?php echo $ml['conferencia_id']; ?>&ministerio=<?php echo $ml['ministerio_id']; ?>">
                    <i class="fas fa-users"></i> <?php echo htmlspecialchars($ml['ministerio_nombre']); ?>
                    <span class="badge bg-info ms-1"><?php echo ucfirst($ml['cargo']); ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- ===== MENÚ DE IGLESIA LOCAL (siempre visible para pastor/secretaria/tesorero) ===== -->
        <li class="menu-section">
            <span class="menu-section-title">Iglesia Local</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>panel_iglesia.php">
                <i class="fas fa-tachometer-alt"></i> Panel Iglesia
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>miembros/index.php">
                <i class="fas fa-users"></i> Miembros
            </a>
        </li>
        <?php if ($rol === 'pastor'): ?>
        <li>
            <a href="<?php echo $base; ?>usuarios_iglesia/index.php">
                <i class="fas fa-users-cog"></i> Usuarios
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo $base; ?>estructura/index.php">
                <i class="fas fa-sitemap"></i> Estructura Eclesiástica
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/periodos/index.php">
                <i class="fas fa-calendar-alt"></i> Períodos
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/junta/index.php">
                <i class="fas fa-users-cog"></i> Junta Administrativa
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>estructura/lideres/index.php">
                <i class="fas fa-user-tie"></i> Líderes Ministerios
            </a>
        </li>
        
        <li class="menu-section">
            <span class="menu-section-title">Finanzas</span>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/index.php">
                <i class="fas fa-coins"></i> Dashboard Finanzas
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/entradas.php">
                <i class="fas fa-arrow-circle-down"></i> Entradas
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/salidas.php">
                <i class="fas fa-arrow-circle-up"></i> Salidas
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/cuentas.php">
                <i class="fas fa-wallet"></i> Cuentas
            </a>
        </li>
        <li>
            <a href="<?php echo $base; ?>finanzas/reportes.php">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
        </li>

    <?php endif; ?>

    <li class="menu-section">
        <span class="menu-section-title">Cuenta</span>
    </li>
    <li>
        <a href="<?php echo $base; ?>../auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </li>
</ul>
