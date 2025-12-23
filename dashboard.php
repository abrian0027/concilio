<?php
/**
 * Dashboard - Sistema Concilio
 * Bootstrap 5 Puro - Mobile First
 * CORREGIDO: Usa el mismo header/footer que las demás páginas
 */

$page_title = "Dashboard";

// Incluir header (esto carga TODO: navbar, sidebar, overlay, etc.)
require_once __DIR__ . '/includes/header.php';

// Cargar configuración de base de datos si no está cargada
if (!isset($conexion)) {
    require_once __DIR__ . '/../config/config.php';
}

// Obtener estadísticas reales según el rol
$stats = [];

// Determinar qué estadísticas mostrar según el rol
if ($ROL_NOMBRE === 'super_admin') {
    // Super Admin ve todo
    $result = $conexion->query("SELECT COUNT(*) as total FROM miembros WHERE estado = 'activo'");
    $miembros = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM iglesias WHERE activo = 1");
    $iglesias = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM distritos WHERE activo = 1");
    $distritos = $result ? $result->fetch_assoc()['total'] : 0;
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM pastores WHERE activo = 1");
    $pastores = $result ? $result->fetch_assoc()['total'] : 0;
    
    $stats = [
        ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-gradient-primary', 'link' => 'miembros/index.php'],
        ['titulo' => 'Iglesias', 'valor' => $iglesias, 'icono' => 'fa-church', 'color' => 'bg-success', 'link' => 'iglesias/index.php'],
        ['titulo' => 'Distritos', 'valor' => $distritos, 'icono' => 'fa-map-marked-alt', 'color' => 'bg-warning', 'link' => 'distritos/index.php'],
        ['titulo' => 'Pastores', 'valor' => $pastores, 'icono' => 'fa-user-tie', 'color' => 'bg-info', 'link' => 'pastores/index.php'],
    ];
    
} elseif (in_array($ROL_NOMBRE, ['pastor', 'secretaria', 'tesorero'])) {
    // Roles de iglesia ven solo su iglesia
    $iglesia_id = isset($IGLESIA_ID) ? (int)$IGLESIA_ID : 0;
    
    if ($iglesia_id > 0) {
        $result = $conexion->query("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = $iglesia_id AND estado = 'activo'");
        $miembros = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conexion->query("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = $iglesia_id AND estado = 'activo' AND es_bautizado = 1");
        $bautizados = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conexion->query("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = $iglesia_id AND estado = 'activo' AND es_lider = 1");
        $lideres = $result ? $result->fetch_assoc()['total'] : 0;
        
        $result = $conexion->query("SELECT COUNT(DISTINCT ministerio_id) as total FROM miembros WHERE iglesia_id = $iglesia_id AND estado = 'activo' AND ministerio_id IS NOT NULL");
        $ministerios = $result ? $result->fetch_assoc()['total'] : 0;
        
        $stats = [
            ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-gradient-primary', 'link' => 'miembros/index.php'],
            ['titulo' => 'Bautizados', 'valor' => $bautizados, 'icono' => 'fa-water', 'color' => 'bg-info', 'link' => 'miembros/index.php?bautizado=1'],
            ['titulo' => 'Líderes', 'valor' => $lideres, 'icono' => 'fa-star', 'color' => 'bg-warning', 'link' => 'miembros/index.php?lider=1'],
            ['titulo' => 'Ministerios', 'valor' => $ministerios, 'icono' => 'fa-hands-praying', 'color' => 'bg-success', 'link' => 'estructura/lideres/index.php'],
        ];
    } else {
        $stats = [
            ['titulo' => 'Miembros', 'valor' => 0, 'icono' => 'fa-users', 'color' => 'bg-gradient-primary', 'link' => '#'],
        ];
    }
    
} else {
    // Otros roles - estadísticas básicas
    $stats = [
        ['titulo' => 'Bienvenido', 'valor' => '👋', 'icono' => 'fa-hand-wave', 'color' => 'bg-gradient-primary', 'link' => '#'],
    ];
}

// Mensaje de bienvenida según rol
$welcome_messages = [
    'super_admin' => 'Administrador del Sistema',
    'pastor' => 'Gestión de la Iglesia',
    'secretaria' => 'Gestión de la Iglesia',
    'tesorero' => 'Gestión Financiera',
    'super_distrito' => 'Supervisor de Distrito',
    'superintendente' => 'Superintendente de Conferencia',
    'lider_ministerio' => 'Líder de Ministerio',
];
$welcome_msg = $welcome_messages[$ROL_NOMBRE] ?? 'Panel de Usuario';
?>

<!-- Bienvenida -->
<div class="card bg-gradient-primary text-white mb-4">
    <div class="card-body py-4">
        <div class="row align-items-center">
            <div class="col-9 col-md-8">
                <h4 class="mb-2">
                    <i class="fas fa-hand-sparkles me-2"></i>
                    ¡Bienvenido, <?php echo htmlspecialchars($USUARIO_NOMBRE); ?>!
                </h4>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-user-tag me-1"></i>
                    <?php echo $welcome_msg; ?>
                </p>
            </div>
            <div class="col-3 col-md-4 text-end">
                <i class="fas fa-church d-none d-sm-inline" style="font-size: 3.5rem; opacity: 0.2;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<?php if (!empty($stats)): ?>
<div class="row mb-4">
    <?php foreach ($stats as $stat): ?>
    <div class="col-6 col-lg-3 mb-3">
        <a href="<?php echo $stat['link']; ?>" class="text-decoration-none">
            <div class="stat-box <?php echo $stat['color']; ?>">
                <h3><?php echo is_numeric($stat['valor']) ? number_format($stat['valor']) : $stat['valor']; ?></h3>
                <p><?php echo $stat['titulo']; ?></p>
                <i class="fas <?php echo $stat['icono']; ?> icon"></i>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Accesos Rápidos -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt text-warning me-2"></i>Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php if (in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria'])): ?>
                    <div class="col-6">
                        <a href="miembros/crear.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-user-plus d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Nuevo Miembro</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="miembros/index.php" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-users d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Ver Miembros</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria', 'tesorero'])): ?>
                    <div class="col-6">
                        <a href="finanzas/entradas.php" class="btn btn-outline-info w-100 py-3">
                            <i class="fas fa-plus-circle d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Registrar Ingreso</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="finanzas/index.php" class="btn btn-outline-warning w-100 py-3">
                            <i class="fas fa-chart-pie d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Finanzas</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>Información
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user me-2 text-muted"></i> Usuario</span>
                        <strong><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></strong>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-id-badge me-2 text-muted"></i> Rol</span>
                        <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $ROL_NOMBRE)); ?></span>
                    </div>
                    <?php if (isset($IGLESIA_NOMBRE) && !empty($IGLESIA_NOMBRE)): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-church me-2 text-muted"></i> Iglesia</span>
                        <strong><?php echo htmlspecialchars($IGLESIA_NOMBRE); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar me-2 text-muted"></i> Fecha</span>
                        <span><?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Incluir footer (esto cierra todo y carga el JavaScript)
require_once __DIR__ . '/includes/footer.php'; 
?>
