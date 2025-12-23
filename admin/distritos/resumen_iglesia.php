<?php
/**
 * Resumen Ejecutivo de Iglesia - Vista Supervisor
 * Sistema Concilio
 * Muestra estadísticas, ministerios y junta administrativa
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$iglesia_id = isset($_GET['iglesia']) ? (int)$_GET['iglesia'] : 0;

if ($iglesia_id <= 0) {
    header('Location: mis_iglesias.php');
    exit;
}

// Obtener información de la iglesia
$iglesia = null;
$sql = "SELECT i.*, d.nombre AS distrito_nombre, d.codigo AS distrito_codigo
        FROM iglesias i
        INNER JOIN distritos d ON i.distrito_id = d.id
        WHERE i.id = ? AND i.activo = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$iglesia) {
    header('Location: mis_iglesias.php?error=' . urlencode('Iglesia no encontrada'));
    exit;
}

// Obtener pastor actual
$pastor = null;
$stmt = $conexion->prepare("SELECT CONCAT(p.nombre, ' ', p.apellido) AS nombre, p.telefono
                            FROM pastor_iglesias pi
                            INNER JOIN pastores p ON p.id = pi.pastor_id
                            WHERE pi.iglesia_id = ? AND pi.activo = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ==================== ESTADÍSTICAS GENERALES ====================

// Total de miembros
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros WHERE iglesia_id = ? AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$total_miembros = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Miembros por tipo de membresía
$membresia = [
    'preparacion' => 0,
    'plena_comunion' => 0,
    'menor' => 0
];

$sql = "SELECT 
    SUM(CASE WHEN estado_miembro = 'en_preparacion' THEN 1 ELSE 0 END) AS preparacion,
    SUM(CASE WHEN estado_miembro = 'en_plena' THEN 1 ELSE 0 END) AS plena_comunion,
    SUM(CASE WHEN estado_miembro = 'miembro_menor' THEN 1 ELSE 0 END) AS menor
    FROM miembros 
    WHERE iglesia_id = ? AND estado = 'activo'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$membresia['preparacion'] = (int)$result['preparacion'];
$membresia['plena_comunion'] = (int)$result['plena_comunion'];
$membresia['menor'] = (int)$result['menor'];
$stmt->close();

// ==================== ESTADÍSTICAS POR MINISTERIO ====================

$ministerios = [
    'Damas' => 0,
    'Caballeros' => 0,
    'Jóvenes' => 0,
    'Adolescentes' => 0,
    'Niños' => 0
];

// Contar por ministerio_id asignado (más flexible - respeta asignación manual)
// Damas (ministerio_id = 1)
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros 
                            WHERE iglesia_id = ? AND ministerio_id = 1 AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$ministerios['Damas'] = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Caballeros (ministerio_id = 2)
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros 
                            WHERE iglesia_id = ? AND ministerio_id = 2 AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$ministerios['Caballeros'] = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Jóvenes (ministerio_id = 3)
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros 
                            WHERE iglesia_id = ? AND ministerio_id = 3 AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$ministerios['Jóvenes'] = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Adolescentes (ministerio_id = 5)
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros 
                            WHERE iglesia_id = ? AND ministerio_id = 5 AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$ministerios['Adolescentes'] = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Niños (ministerio_id = 4)
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros 
                            WHERE iglesia_id = ? AND ministerio_id = 4 AND estado = 'activo'");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$ministerios['Niños'] = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calcular el máximo para las barras proporcionales
$max_ministerio = max($ministerios) ?: 1;

// ==================== JUNTA ADMINISTRATIVA ====================

$junta_administrativa = [];

// Obtener el período actual (periodos_iglesia en lugar de periodos_conferencia)
$stmt = $conexion->prepare("SELECT id FROM periodos_iglesia 
                            WHERE iglesia_id = ? AND activo = 1 
                            ORDER BY fecha_fin DESC LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$periodo_result = $stmt->get_result()->fetch_assoc();
$periodo_id = $periodo_result['id'] ?? 0;
$stmt->close();

if ($periodo_id > 0) {
    // Obtener miembros de la junta (tabla juntas y junta_miembros)
    $sql = "SELECT 
            m.nombre,
            m.apellido,
            jm.cargo_id,
            c.nombre AS cargo_nombre
        FROM junta_miembros jm
        INNER JOIN miembros m ON jm.miembro_id = m.id
        INNER JOIN cargos_junta c ON jm.cargo_id = c.id
        WHERE jm.junta_id IN (
            SELECT id FROM juntas 
            WHERE periodo_id = ? AND activa = 1
        )
        ORDER BY c.orden, m.nombre, m.apellido";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $periodo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $junta_administrativa[] = $row;
    }
    $stmt->close();
}

$page_title = "Resumen - " . $iglesia['nombre'];
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Estilos específicos para gráficos de barra */
.stat-bar {
    height: 30px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 0.375rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(13, 202, 240, 0.2);
}

.stat-bar:hover {
    transform: scaleX(1.02);
    box-shadow: 0 4px 8px rgba(13, 202, 240, 0.3);
}

.ministerio-card {
    transition: all 0.3s ease;
}

.ministerio-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.kpi-card {
    border-left: 4px solid var(--primary);
    transition: all 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.kpi-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2><i class="fas fa-chart-pie me-2 text-primary"></i>Resumen - <?php echo htmlspecialchars($iglesia['nombre']); ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="panel_distrito.php">Panel Distrito</a></li>
                <li class="breadcrumb-item"><a href="mis_iglesias.php">Iglesias</a></li>
                <li class="breadcrumb-item active">Resumen</li>
            </ol>
        </nav>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-outline-secondary me-2">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
        <a href="mis_iglesias.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Información de la iglesia -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title mb-3">
                    <i class="fas fa-church text-primary me-2"></i>
                    <?php echo htmlspecialchars($iglesia['codigo'] . ' - ' . $iglesia['nombre']); ?>
                </h5>
                <p class="mb-2">
                    <strong><i class="fas fa-map-marked-alt me-2 text-muted"></i>Distrito:</strong>
                    <?php echo htmlspecialchars($iglesia['distrito_codigo'] . ' - ' . $iglesia['distrito_nombre']); ?>
                </p>
                <?php if (!empty($iglesia['direccion'])): ?>
                <p class="mb-2">
                    <strong><i class="fas fa-map-marker-alt me-2 text-muted"></i>Dirección:</strong>
                    <?php echo htmlspecialchars($iglesia['direccion']); ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php if ($pastor): ?>
                <div class="alert alert-info mb-0">
                    <h6 class="alert-heading"><i class="fas fa-user-tie me-2"></i>Pastor</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($pastor['nombre']); ?></strong></p>
                    <?php if (!empty($pastor['telefono'])): ?>
                    <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($pastor['telefono']); ?></p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Sin pastor asignado
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- KPIs Principales -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-6 col-sm-6 col-md-3 mb-2 mb-md-3">
        <div class="card kpi-card h-100">
            <div class="card-body d-flex align-items-center p-2 p-md-3">
                <div class="kpi-icon bg-primary bg-opacity-10 text-primary me-2 me-md-3">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 class="mb-0 fs-5 fs-md-3"><?php echo $total_miembros; ?></h3>
                    <small class="text-muted d-block">Total Miembros</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-sm-6 col-md-3 mb-2 mb-md-3">
        <div class="card kpi-card h-100" style="border-left-color: var(--warning);">
            <div class="card-body d-flex align-items-center p-2 p-md-3">
                <div class="kpi-icon bg-warning bg-opacity-10 text-warning me-2 me-md-3">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div>
                    <h3 class="mb-0 fs-5 fs-md-3"><?php echo $membresia['preparacion']; ?></h3>
                    <small class="text-muted d-block">En Preparación</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-sm-6 col-md-3 mb-2 mb-md-3">
        <div class="card kpi-card h-100" style="border-left-color: var(--success);">
            <div class="card-body d-flex align-items-center p-2 p-md-3">
                <div class="kpi-icon bg-success bg-opacity-10 text-success me-2 me-md-3">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <h3 class="mb-0 fs-5 fs-md-3"><?php echo $membresia['plena_comunion']; ?></h3>
                    <small class="text-muted d-block">Plena Comunión</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-sm-6 col-md-3 mb-2 mb-md-3">
        <div class="card kpi-card h-100" style="border-left-color: var(--info);">
            <div class="card-body d-flex align-items-center p-2 p-md-3">
                <div class="kpi-icon bg-info bg-opacity-10 text-info me-2 me-md-3">
                    <i class="fas fa-child"></i>
                </div>
                <div>
                    <h3 class="mb-0 fs-5 fs-md-3"><?php echo $membresia['menor']; ?></h3>
                    <small class="text-muted d-block">Menores</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas por Ministerio -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-hands-helping me-2 text-primary"></i>Distribución por Ministerio
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php 
            $colores = [
                'Damas' => ['bg' => 'rgba(236, 72, 153, 0.1)', 'border' => '#ec4899', 'icon' => 'fa-female'],
                'Caballeros' => ['bg' => 'rgba(59, 130, 246, 0.1)', 'border' => '#3b82f6', 'icon' => 'fa-male'],
                'Jóvenes' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'border' => '#10b981', 'icon' => 'fa-user-friends'],
                'Adolescentes' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'border' => '#f59e0b', 'icon' => 'fa-user-graduate'],
                'Niños' => ['bg' => 'rgba(139, 92, 246, 0.1)', 'border' => '#8b5cf6', 'icon' => 'fa-child']
            ];
            
            foreach ($ministerios as $nombre => $cantidad): 
                $porcentaje = $max_ministerio > 0 ? ($cantidad / $max_ministerio) * 100 : 0;
                $color = $colores[$nombre];
            ?>
            <div class="col-md-6 mb-3">
                <div class="ministerio-card p-3 border rounded" style="background: <?php echo $color['bg']; ?>; border-color: <?php echo $color['border']; ?> !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <i class="fas <?php echo $color['icon']; ?> fa-2x me-3" style="color: <?php echo $color['border']; ?>;"></i>
                            <div>
                                <h6 class="mb-0"><?php echo $nombre; ?></h6>
                                <small class="text-muted"><?php echo number_format($porcentaje, 1); ?>% del total</small>
                            </div>
                        </div>
                        <h4 class="mb-0" style="color: <?php echo $color['border']; ?>;"><?php echo $cantidad; ?></h4>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="stat-bar" style="width: <?php echo $porcentaje; ?>%; background: linear-gradient(90deg, <?php echo $color['border']; ?> 0%, <?php echo $color['border']; ?>dd 100%);"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Junta Administrativa -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-users-cog me-2 text-primary"></i>Junta Administrativa
        </h5>
    </div>
    <div class="card-body">
        <?php if ($pastor): ?>
        <div class="d-flex align-items-center p-3 mb-3 bg-primary bg-opacity-10 rounded">
            <div class="me-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                     style="width: 50px; height: 50px;">
                    <i class="fas fa-user-tie fa-lg"></i>
                </div>
            </div>
            <div>
                <h6 class="mb-0"><?php echo htmlspecialchars($pastor['nombre']); ?></h6>
                <small class="text-muted">Pastor (Presidente de Junta)</small>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (count($junta_administrativa) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50"><i class="fas fa-user"></i></th>
                        <th>Nombre</th>
                        <th>Cargo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($junta_administrativa as $miembro): ?>
                    <tr>
                        <td>
                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" 
                                 style="width: 35px; height: 35px;">
                                <i class="fas fa-user"></i>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                <?php echo htmlspecialchars(ucfirst($miembro['cargo_nombre'])); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-warning mb-0">
            <i class="fas fa-info-circle me-2"></i>No hay junta administrativa registrada para el período actual.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Botón para ver lista completa -->
<div class="text-center mb-4 no-print">
    <a href="../miembros/index.php?iglesia=<?php echo $iglesia_id; ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-list me-2"></i>Ver Lista Completa de Miembros
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
