<?php
/**
 * Dashboard - Sistema Concilio
 * Bootstrap 5 Puro - Mobile First
 * VERSIÓN FINAL - Compatible con seguridad.php
 */

// Activar visualización de errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Dashboard";

// Incluir header (esto carga seguridad.php automáticamente)
require_once __DIR__ . '/includes/header.php';

// Las variables ya están cargadas por seguridad.php:
// $USUARIO_ID, $USUARIO_NOMBRE, $ROL_NOMBRE, $IGLESIA_ID, $IGLESIA_NOMBRE

// Obtener estadísticas reales según el rol
$stats = [];

try {
    // ==================== SUPER ADMIN ====================
    if ($ROL_NOMBRE === 'super_admin') {
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
    }
    
    // ==================== SUPERVISOR DISTRITO ====================
    elseif ($ROL_NOMBRE === 'super_distrito') {
        // Obtener usuario y cédula
        $usuario_cedula = $_SESSION['usuario'] ?? '';
        
        // Si no hay cédula en sesión, buscarla en BD
        if (empty($usuario_cedula) && $USUARIO_ID > 0) {
            $stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $USUARIO_ID);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $usuario_cedula = $result['usuario'] ?? '';
            $stmt->close();
        }
        
        // Buscar distrito supervisado
        $distrito_id = 0;
        if (!empty($usuario_cedula)) {
            $stmt = $conexion->prepare("SELECT d.id FROM distritos d 
                                       INNER JOIN pastores p ON d.supervisor_id = p.id 
                                       WHERE p.cedula = ? AND d.activo = 1 LIMIT 1");
            $stmt->bind_param("s", $usuario_cedula);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $distrito_id = $result['id'] ?? 0;
            $stmt->close();
        }
        
        if ($distrito_id > 0) {
            // Contar iglesias del distrito
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ? AND activo = 1");
            $stmt->bind_param("i", $distrito_id);
            $stmt->execute();
            $iglesias = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Contar miembros del distrito
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM miembros m
                                       INNER JOIN iglesias i ON m.iglesia_id = i.id
                                       WHERE i.distrito_id = ? AND m.estado = 'activo'");
            $stmt->bind_param("i", $distrito_id);
            $stmt->execute();
            $miembros = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Contar pastores en el distrito
            $stmt = $conexion->prepare("SELECT COUNT(DISTINCT pi.pastor_id) as total 
                                       FROM pastor_iglesias pi
                                       INNER JOIN iglesias i ON pi.iglesia_id = i.id
                                       WHERE i.distrito_id = ? AND pi.activo = 1");
            $stmt->bind_param("i", $distrito_id);
            $stmt->execute();
            $pastores = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stats = [
                ['titulo' => 'Iglesias', 'valor' => $iglesias, 'icono' => 'fa-church', 'color' => 'bg-gradient-primary', 'link' => 'distritos/mis_iglesias.php'],
                ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-success', 'link' => 'miembros/index.php'],
                ['titulo' => 'Pastores', 'valor' => $pastores, 'icono' => 'fa-user-tie', 'color' => 'bg-info', 'link' => '#'],
            ];
        } else {
            $stats = [
                ['titulo' => 'Sin distrito asignado', 'valor' => '⚠️', 'icono' => 'fa-exclamation-triangle', 'color' => 'bg-warning', 'link' => '#'],
            ];
        }
    }
    
    // ==================== ROLES DE IGLESIA ====================
    elseif (in_array($ROL_NOMBRE, ['pastor', 'secretaria', 'tesorero'])) {
        $iglesia_id = (int)$IGLESIA_ID;
        
        // Si no hay iglesia_id en sesión, intentar obtenerla de la BD
        if ($iglesia_id <= 0) {
            $usuario_cedula = $_SESSION['usuario'] ?? '';
            
            // Buscar iglesia del usuario
            if (!empty($usuario_cedula)) {
                // Buscar como pastor
                $stmt = $conexion->prepare("SELECT pi.iglesia_id 
                                           FROM pastor_iglesias pi
                                           INNER JOIN pastores p ON pi.pastor_id = p.id
                                           WHERE p.cedula = ? AND pi.activo = 1
                                           LIMIT 1");
                $stmt->bind_param("s", $usuario_cedula);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $iglesia_id = $result['iglesia_id'] ?? 0;
                $stmt->close();
            }
            
            // Si aún no hay iglesia, buscar por usuario directamente
            if ($iglesia_id <= 0 && $USUARIO_ID > 0) {
                $stmt = $conexion->prepare("SELECT iglesia_id FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $USUARIO_ID);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $iglesia_id = $result['iglesia_id'] ?? 0;
                $stmt->close();
            }
        }
        
        if ($iglesia_id > 0) {
            // Estadísticas de la iglesia
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo'");
            $stmt->bind_param("i", $iglesia_id);
            $stmt->execute();
            $miembros = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo' AND es_bautizado = 1");
            $stmt->bind_param("i", $iglesia_id);
            $stmt->execute();
            $bautizados = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo' AND es_lider = 1");
            $stmt->bind_param("i", $iglesia_id);
            $stmt->execute();
            $lideres = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stats = [
                ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-gradient-primary', 'link' => 'miembros/index.php'],
                ['titulo' => 'Bautizados', 'valor' => $bautizados, 'icono' => 'fa-water', 'color' => 'bg-info', 'link' => 'miembros/index.php?bautizado=1'],
                ['titulo' => 'Líderes', 'valor' => $lideres, 'icono' => 'fa-star', 'color' => 'bg-warning', 'link' => 'miembros/index.php?lider=1'],
            ];
        } else {
            $stats = [
                ['titulo' => 'Sin iglesia asignada', 'valor' => '⚠️', 'icono' => 'fa-exclamation-triangle', 'color' => 'bg-warning', 'link' => '#'],
            ];
        }
    }
    
    // ==================== OTROS ROLES ====================
    else {
        $stats = [
            ['titulo' => 'Bienvenido', 'valor' => '👋', 'icono' => 'fa-hand-wave', 'color' => 'bg-gradient-primary', 'link' => '#'],
        ];
    }
    
} catch (Exception $e) {
    $stats = [
        ['titulo' => 'Error', 'valor' => '❌', 'icono' => 'fa-exclamation-circle', 'color' => 'bg-danger', 'link' => '#'],
    ];
    echo "<div class='alert alert-danger m-3'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
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
            <div class="card h-100 <?php echo $stat['color']; ?> text-white">
                <div class="card-body text-center py-4">
                    <i class="fas <?php echo $stat['icono']; ?> fa-3x mb-3" style="opacity: 0.8;"></i>
                    <h2 class="mb-1"><?php echo is_numeric($stat['valor']) ? number_format($stat['valor']) : $stat['valor']; ?></h2>
                    <p class="mb-0"><?php echo $stat['titulo']; ?></p>
                </div>
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
                    
                    <?php if ($ROL_NOMBRE === 'super_distrito'): ?>
                    <div class="col-6">
                        <a href="distritos/mis_iglesias.php" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-church d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Mis Iglesias</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="distritos/reportes.php" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-chart-bar d-block mb-1" style="font-size: 1.25rem;"></i>
                            <small>Reportes</small>
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
                    <?php if (!empty($IGLESIA_NOMBRE)): ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-church me-2 text-muted"></i> Iglesia</span>
                        <strong><?php echo htmlspecialchars($IGLESIA_NOMBRE); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar me-2 text-muted"></i> Fecha</span>
                        <span><?php echo date('d/m/Y'); ?></span>
                    </div>
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock me-2 text-muted"></i> Hora</span>
                        <span><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
