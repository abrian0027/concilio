<?php
/**
 * Dashboard - Sistema Concilio
 * Bootstrap 5 Puro - Mobile First
 * VERSIÓN MEJORADA - Manejo de errores y todos los roles
 */

// Activar visualización de errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Dashboard";

// Incluir header
require_once __DIR__ . '/includes/header.php';

// Cargar configuración de base de datos si no está cargada
if (!isset($conexion)) {
    require_once __DIR__ . '/../config/config.php';
}

// Verificar variables esenciales
$ROL_NOMBRE = $ROL_NOMBRE ?? $_SESSION['rol_nombre'] ?? 'sin_rol';
$USUARIO_NOMBRE = $USUARIO_NOMBRE ?? $_SESSION['usuario_nombre'] ?? 'Usuario';
$IGLESIA_ID = $IGLESIA_ID ?? $_SESSION['iglesia_id'] ?? 0;
$IGLESIA_NOMBRE = $IGLESIA_NOMBRE ?? $_SESSION['iglesia_nombre'] ?? '';

// Debug info (comentar en producción)
// echo "<!-- ROL: $ROL_NOMBRE, IGLESIA_ID: $IGLESIA_ID -->";

// Obtener estadísticas reales según el rol
$stats = [];

try {
    // Determinar qué estadísticas mostrar según el rol
    if ($ROL_NOMBRE === 'super_admin') {
        // ==================== SUPER ADMIN ====================
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
    
    } elseif ($ROL_NOMBRE === 'super_conferencia') {
        // ==================== SUPERINTENDENTE DE CONFERENCIA ====================
        $conferencia_id = $_SESSION['conferencia_id'] ?? 0;
        
        if ($conferencia_id > 0) {
            // Contar distritos de la conferencia
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM distritos WHERE conferencia_id = ? AND activo = 1");
            $stmt->bind_param("i", $conferencia_id);
            $stmt->execute();
            $distritos = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Contar iglesias de la conferencia
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total FROM iglesias i
                INNER JOIN distritos d ON i.distrito_id = d.id
                WHERE d.conferencia_id = ? AND i.activo = 1
            ");
            $stmt->bind_param("i", $conferencia_id);
            $stmt->execute();
            $iglesias = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Contar miembros de la conferencia
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total FROM miembros m
                INNER JOIN iglesias i ON m.iglesia_id = i.id
                INNER JOIN distritos d ON i.distrito_id = d.id
                WHERE d.conferencia_id = ? AND m.estado = 'activo'
            ");
            $stmt->bind_param("i", $conferencia_id);
            $stmt->execute();
            $miembros = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Contar pastores de la conferencia
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pastores WHERE conferencia_id = ? AND activo = 1");
            $stmt->bind_param("i", $conferencia_id);
            $stmt->execute();
            $pastores = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stats = [
                ['titulo' => 'Distritos', 'valor' => $distritos, 'icono' => 'fa-map-marked-alt', 'color' => 'bg-gradient-primary', 'link' => 'distritos/index.php'],
                ['titulo' => 'Iglesias', 'valor' => $iglesias, 'icono' => 'fa-church', 'color' => 'bg-success', 'link' => 'iglesias/index.php'],
                ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-info', 'link' => 'miembros/index.php'],
                ['titulo' => 'Pastores', 'valor' => $pastores, 'icono' => 'fa-user-tie', 'color' => 'bg-warning', 'link' => 'pastores/index.php'],
            ];
        } else {
            $stats = [
                ['titulo' => 'Sin conferencia', 'valor' => '⚠️', 'icono' => 'fa-exclamation-triangle', 'color' => 'bg-warning', 'link' => '#'],
            ];
        }
        
    } elseif ($ROL_NOMBRE === 'super_distrito') {
        // ==================== SUPERVISOR DISTRITO ====================
        // Obtener distrito del supervisor
        $usuario_id = $_SESSION['usuario_id'] ?? 0;
        $usuario_cedula = $_SESSION['usuario'] ?? '';
        
        // Obtener cédula si no está en sesión
        if (empty($usuario_cedula) && $usuario_id > 0) {
            $stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
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
            
            $stats = [
                ['titulo' => 'Iglesias', 'valor' => $iglesias, 'icono' => 'fa-church', 'color' => 'bg-gradient-primary', 'link' => 'distritos/mis_iglesias.php'],
                ['titulo' => 'Miembros', 'valor' => $miembros, 'icono' => 'fa-users', 'color' => 'bg-success', 'link' => 'miembros/index.php'],
            ];
        } else {
            $stats = [
                ['titulo' => 'Sin distrito', 'valor' => '⚠️', 'icono' => 'fa-exclamation-triangle', 'color' => 'bg-warning', 'link' => '#'],
            ];
        }
        
    } elseif (in_array($ROL_NOMBRE, ['pastor', 'secretaria', 'tesorero'])) {
        // ==================== ROLES DE IGLESIA ====================
        $iglesia_id = (int)$IGLESIA_ID;
        
        if ($iglesia_id > 0) {
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
        
    } else {
        // ==================== OTROS ROLES ====================
        $stats = [
            ['titulo' => 'Bienvenido', 'valor' => '👋', 'icono' => 'fa-hand-wave', 'color' => 'bg-gradient-primary', 'link' => '#'],
        ];
    }
    
} catch (Exception $e) {
    // En caso de error, mostrar mensaje
    $stats = [
        ['titulo' => 'Error', 'valor' => '❌', 'icono' => 'fa-exclamation-circle', 'color' => 'bg-danger', 'link' => '#'],
    ];
    
    // Mostrar error en desarrollo (comentar en producción)
    echo "<div class='alert alert-danger'>Error al cargar estadísticas: " . htmlspecialchars($e->getMessage()) . "</div>";
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

<!-- Bienvenida Compacta -->
<div class="card bg-gradient-primary text-white mb-3">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h6 class="mb-0 fw-bold">
                    <?php echo htmlspecialchars($USUARIO_NOMBRE); ?>
                </h6>
                <small class="opacity-75">
                    <?php echo ucfirst(str_replace('_', ' ', $ROL_NOMBRE)); ?>
                    <?php if (!empty($IGLESIA_NOMBRE)): ?>
                     • <?php echo htmlspecialchars($IGLESIA_NOMBRE); ?>
                    <?php endif; ?>
                </small>
            </div>
            <i class="fas fa-user-circle fa-2x opacity-50"></i>
        </div>
    </div>
</div>

<!-- Estadísticas Compactas -->
<?php if (!empty($stats)): ?>
<div class="row g-2 mb-3">
    <?php foreach ($stats as $stat): ?>
    <div class="col-4 col-lg-3">
        <a href="<?php echo $stat['link']; ?>" class="text-decoration-none d-block">
            <div class="card <?php echo $stat['color']; ?> text-white shadow-sm">
                <div class="card-body text-center p-2">
                    <i class="fas <?php echo $stat['icono']; ?> mb-1" style="font-size: 1.25rem; opacity: 0.8;"></i>
                    <h4 class="mb-0 fw-bold"><?php echo is_numeric($stat['valor']) ? number_format($stat['valor']) : $stat['valor']; ?></h4>
                    <small class="opacity-90" style="font-size: 0.7rem;"><?php echo $stat['titulo']; ?></small>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Accesos Rápidos e Información -->
<div class="row g-2 g-md-3">
    <div class="col-12 col-lg-6 mb-3 mb-lg-0">
        <div class="card h-100">
            <div class="card-header py-2 py-md-3">
                <h5 class="card-title mb-0 fs-6">
                    <i class="fas fa-bolt text-warning me-2"></i>Accesos Rápidos
                </h5>
            </div>
            <div class="card-body p-2 p-md-3">
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
    
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2 py-md-3">
                <h5 class="card-title mb-0 fs-6">
                    <i class="fas fa-info-circle text-info me-2"></i>Información
                </h5>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center flex-wrap">
                        <span class="small"><i class="fas fa-user me-2 text-muted"></i>Usuario</span>
                        <strong class="small text-truncate" style="max-width: 55%;"><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="small"><i class="fas fa-id-badge me-2 text-muted"></i>Rol</span>
                        <span class="badge bg-primary"><?php echo ucfirst(str_replace('_', ' ', $ROL_NOMBRE)); ?></span>
                    </div>
                    <?php if (isset($IGLESIA_NOMBRE) && !empty($IGLESIA_NOMBRE)): ?>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center flex-wrap">
                        <span class="small"><i class="fas fa-church me-2 text-muted"></i>Iglesia</span>
                        <strong class="small text-truncate" style="max-width: 55%;"><?php echo htmlspecialchars($IGLESIA_NOMBRE); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="small"><i class="fas fa-calendar me-2 text-muted"></i>Fecha</span>
                        <span class="small"><?php echo date('d/m/Y'); ?></span>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="small"><i class="fas fa-clock me-2 text-muted"></i>Hora</span>
                        <span class="small"><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ==================== WIDGET CUMPLEAÑOS DEL MES ====================
// Mostrar según el rol del usuario
require_once __DIR__ . '/widgets/cumpleanos_mes.php';

$session_data = [
    'rol_nombre' => $ROL_NOMBRE,
    'iglesia_id' => $IGLESIA_ID,
    'usuario_id' => $_SESSION['usuario_id'] ?? 0
];

// Solo mostrar para roles específicos
if (in_array($ROL_NOMBRE, ['pastor', 'secretaria', 'super_admin', 'super_conferencia', 'super_distrito', 'lider_ministerio'])):
?>
<div class="row g-2 g-md-3 mt-3">
    <?php 
    renderCumpleanosMes(['col' => 'col-12 col-lg-6', 'max' => 10], $conexion, $session_data); 
    ?>
</div>
<?php endif; ?>

<?php 
// Incluir footer
require_once __DIR__ . '/includes/footer.php'; 
?>