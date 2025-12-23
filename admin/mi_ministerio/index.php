<?php
/**
 * MI MINISTERIO - Dashboard para Líder de Área Ministerial (Nivel Local/Iglesia)
 * Sistema Concilio - Módulo de Ministerios
 * 
 * Este dashboard es para líderes de ministerio a NIVEL DE IGLESIA LOCAL
 * Ejemplo: Enger Florimon - Líder de Jóvenes en IML-Matancitas
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

// Verificar que el usuario tiene una iglesia asignada
if (!$iglesia_id) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> No tienes una iglesia asignada.</div>";
    exit;
}

// Buscar al miembro asociado a este usuario por nombre y apellido
$stmt = $conexion->prepare("SELECT m.* FROM miembros m 
                            INNER JOIN usuarios u ON u.iglesia_id = m.iglesia_id 
                                AND UPPER(m.nombre) = UPPER(u.nombre)
                                AND UPPER(m.apellido) = UPPER(u.apellido)
                            WHERE u.id = ? AND m.iglesia_id = ?
                            LIMIT 1");
$stmt->bind_param("ii", $usuario_id, $iglesia_id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No se encontró tu registro como miembro.</div>";
    exit;
}

$miembro_id = $miembro['id'];

// Verificar si este miembro es líder de algún área ministerial
$stmt = $conexion->prepare("SELECT al.*, am.nombre AS area_nombre, am.id AS area_id
                            FROM area_lideres al
                            INNER JOIN areas_ministeriales am ON al.area_id = am.id
                            WHERE al.miembro_id = ? AND al.iglesia_id = ? AND al.activo = 1 AND al.tipo = 'lider'
                            LIMIT 1");
$stmt->bind_param("ii", $miembro_id, $iglesia_id);
$stmt->execute();
$liderazgo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si no es líder, mostrar mensaje
if (!$liderazgo) {
    $page_title = "Mi Ministerio";
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="alert alert-info border-0">
        <i class="fas fa-info-circle me-2"></i>
        <strong>No eres líder de ningún ministerio</strong><br>
        <small>Si crees que esto es un error, contacta al pastor de tu iglesia.</small>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$area_id = $liderazgo['area_id'];
$area_nombre = $liderazgo['area_nombre'];

// Obtener información de la iglesia
$stmt = $conexion->prepare("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre
                            FROM iglesias i
                            INNER JOIN distritos d ON i.distrito_id = d.id
                            INNER JOIN conferencias c ON d.conferencia_id = c.id
                            WHERE i.id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

// MAPEO: areas_ministeriales → ministerios (tabla de miembros)
// 1=Damas → 1, 2=Caballeros → 2, 3=Jóvenes → 3, 4=Niños → 4, 5=Adolescentes → 5
$area_to_ministerio = [
    1 => 1,  // Damas
    2 => 2,  // Caballeros
    3 => 3,  // Jóvenes
    4 => 4,  // Niños (CIC)
    5 => 5   // Adolescentes
];

$ministerio_id = $area_to_ministerio[$area_id] ?? 0;

// Obtener estadísticas del ministerio
$stats = [
    'total' => 0,
    'hombres' => 0,
    'mujeres' => 0,
    'bautizados' => 0,
    'edad_min' => 0,
    'edad_max' => 0,
    'edad_promedio' => 0
];

if ($ministerio_id > 0) {
    $sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            SUM(CASE WHEN es_bautizado = 1 THEN 1 ELSE 0 END) AS bautizados,
            MIN(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) AS edad_min,
            MAX(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) AS edad_max,
            AVG(TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())) AS edad_promedio
            FROM miembros
            WHERE iglesia_id = ? AND ministerio_id = ? AND estado = 'activo'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $iglesia_id, $ministerio_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Obtener co-líder si existe
$colider = null;
$stmt = $conexion->prepare("SELECT CONCAT(m.nombre, ' ', m.apellido) AS nombre, m.telefono
                            FROM area_lideres al
                            INNER JOIN miembros m ON al.miembro_id = m.id
                            WHERE al.iglesia_id = ? AND al.area_id = ? AND al.tipo = 'colider' AND al.activo = 1
                            LIMIT 1");
$stmt->bind_param("ii", $iglesia_id, $area_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $colider = $result->fetch_assoc();
}
$stmt->close();

// Obtener lista de miembros del ministerio
$miembros_ministerio = [];
if ($ministerio_id > 0) {
    $stmt = $conexion->prepare("SELECT 
                                m.id, 
                                CONCAT(m.nombre, ' ', m.apellido) AS nombre_completo,
                                m.sexo,
                                TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) AS edad,
                                m.es_bautizado,
                                m.telefono,
                                m.estado_miembro
                                FROM miembros m
                                WHERE m.iglesia_id = ? AND m.ministerio_id = ? AND m.estado = 'activo'
                                ORDER BY m.apellido, m.nombre");
    $stmt->bind_param("ii", $iglesia_id, $ministerio_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $miembros_ministerio[] = $row;
    }
    $stmt->close();
}

// Configurar header
$page_title = "Mi Ministerio - " . $area_nombre;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Compacto -->
<div class="row mb-3 mb-md-4">
    <div class="col-12 col-md-8 mb-3 mb-md-0">
        <h1 class="h4 h-md-3 mb-2 text-dark">
            <i class="fas fa-users-cog text-primary me-2"></i>
            <span class="d-none d-md-inline">Mi Ministerio - </span><?php echo htmlspecialchars($area_nombre); ?>
        </h1>
        <p class="text-muted small mb-0">
            <i class="fas fa-church me-1"></i><?php echo htmlspecialchars($iglesia['codigo'] . ' - ' . $iglesia['nombre']); ?>
        </p>
    </div>
    <div class="col-12 col-md-4 text-start text-md-end">
        <a href="../dashboard.php" class="btn btn-sm btn-outline-secondary w-100 w-md-auto">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <!-- Total Miembros -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center flex-column flex-md-row text-center text-md-start">
                    <div class="bg-primary bg-opacity-10 rounded p-2 p-md-3 mb-2 mb-md-0 me-md-3">
                        <i class="fas fa-users fa-lg fa-md-2x text-primary"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Total Miembros</p>
                        <h3 class="mb-0 fw-bold fs-5 fs-md-3"><?php echo $stats['total'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hombres -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center flex-column flex-md-row text-center text-md-start">
                    <div class="bg-info bg-opacity-10 rounded p-2 p-md-3 mb-2 mb-md-0 me-md-3">
                        <i class="fas fa-male fa-lg fa-md-2x text-info"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Hombres</p>
                        <h3 class="mb-0 fw-bold fs-5 fs-md-3"><?php echo $stats['hombres'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mujeres -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center flex-column flex-md-row text-center text-md-start">
                    <div class="bg-danger bg-opacity-10 rounded p-2 p-md-3 mb-2 mb-md-0 me-md-3">
                        <i class="fas fa-female fa-lg fa-md-2x text-danger"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Mujeres</p>
                        <h3 class="mb-0 fw-bold fs-5 fs-md-3"><?php echo $stats['mujeres'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bautizados -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center flex-column flex-md-row text-center text-md-start">
                    <div class="bg-success bg-opacity-10 rounded p-2 p-md-3 mb-2 mb-md-0 me-md-3">
                        <i class="fas fa-water fa-lg fa-md-2x text-success"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Bautizados</p>
                        <h3 class="mb-0 fw-bold fs-5 fs-md-3">
                            <?php echo $stats['bautizados'] ?? 0; ?>
                            <small class="text-muted d-block d-md-inline fs-6">
                                (<?php echo $stats['total'] > 0 ? round(($stats['bautizados'] / $stats['total']) * 100) : 0; ?>%)
                            </small>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Columna Izquierda -->
    <div class="col-12 col-md-4 mb-3 mb-md-4">
        <!-- Estructura Directiva -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary bg-opacity-10 border-0">
                <h6 class="mb-0 fw-semibold small">
                    <i class="fas fa-sitemap text-primary me-2"></i>Estructura Directiva
                </h6>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="mb-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-2 me-md-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-crown text-warning"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Líder Principal</small>
                            <strong><?php echo htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']); ?></strong>
                            <?php if (!empty($miembro['telefono'])): ?>
                            <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($miembro['telefono']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($colider): ?>
                <div class="mb-2 pt-3 border-top">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-2 me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-friends text-info"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Co-líder</small>
                            <strong><?php echo htmlspecialchars($colider['nombre']); ?></strong>
                            <?php if (!empty($colider['telefono'])): ?>
                            <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($colider['telefono']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-light border mb-0 small">
                    <i class="fas fa-info-circle me-2"></i>No hay co-líder asignado
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rango de Edades -->
        <?php if ($stats['total'] > 0): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info bg-opacity-10 border-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-birthday-cake text-info me-2"></i>Rango de Edades
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Edad Mínima:</span>
                    <span class="badge bg-light text-dark border fs-6"><?php echo $stats['edad_min']; ?> años</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Edad Máxima:</span>
                    <span class="badge bg-light text-dark border fs-6"><?php echo $stats['edad_max']; ?> años</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Promedio:</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-6"><?php echo round($stats['edad_promedio']); ?> años</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Columna Derecha - Lista de Miembros -->
    <div class="col-12 col-md-8 mb-3 mb-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-2 py-md-3">
                <h6 class="mb-0 fw-semibold small">
                    <i class="fas fa-list text-primary me-2"></i>Miembros del Ministerio
                    <span class="badge bg-primary ms-2"><?php echo count($miembros_ministerio); ?></span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($miembros_ministerio) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-2 ps-md-3 text-nowrap">Nombre</th>
                                    <th class="border-0 text-center text-nowrap">Edad</th>
                                    <th class="border-0 text-center text-nowrap d-none d-sm-table-cell">Sexo</th>
                                    <th class="border-0 text-center text-nowrap d-none d-md-table-cell">Bautizado</th>
                                    <th class="border-0 text-nowrap d-none d-lg-table-cell">Membresía</th>
                                    <th class="border-0 pe-2 pe-md-3 text-nowrap d-none d-xl-table-cell">Teléfono</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($miembros_ministerio as $m): ?>
                                <tr>
                                    <td class="ps-2 ps-md-3">
                                        <strong class="d-block text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($m['nombre_completo']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><?php echo $m['edad']; ?></span>
                                    </td>
                                    <td class="text-center d-none d-sm-table-cell">
                                        <?php if ($m['sexo'] == 'M'): ?>
                                            <i class="fas fa-male text-primary"></i>
                                        <?php else: ?>
                                            <i class="fas fa-female text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php if ($m['es_bautizado']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                <i class="fas fa-times"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <?php
                                        $badge_estado = 'secondary';
                                        $estado_texto = 'N/A';
                                        if ($m['estado_miembro'] == 'en_plena') {
                                            $badge_estado = 'success';
                                            $estado_texto = 'Plena';
                                        } elseif ($m['estado_miembro'] == 'en_preparacion') {
                                            $badge_estado = 'warning';
                                            $estado_texto = 'Preparación';
                                        } elseif ($m['estado_miembro'] == 'miembro_menor') {
                                            $badge_estado = 'info';
                                            $estado_texto = 'Menor';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_estado; ?> bg-opacity-10 text-<?php echo $badge_estado; ?> border border-<?php echo $badge_estado; ?>">
                                            <?php echo $estado_texto; ?>
                                        </span>
                                    </td>
                                    <td class="pe-2 pe-md-3 d-none d-xl-table-cell">
                                        <small class="text-muted">
                                            <?php echo !empty($m['telefono']) ? htmlspecialchars($m['telefono']) : '-'; ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                        <p class="text-muted">No hay miembros asignados a este ministerio</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Botones de Acción -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-light border">
            <i class="fas fa-info-circle text-primary me-2"></i>
            <strong>Nota:</strong> Para agregar o remover miembros del ministerio, contacta al pastor o secretaria de la iglesia.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
