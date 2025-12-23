<?php
/**
 * Lista de Iglesias del Distrito - VERSIÓN CORREGIDA
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener cédula del usuario
$usuario_cedula = '';
$stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $usuario_cedula = $row['usuario'];
}
$stmt->close();

// Buscar el distrito - PRIMERO como supervisor
$distrito = null;
$sql = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
        FROM distritos d
        INNER JOIN conferencias c ON d.conferencia_id = c.id
        WHERE d.supervisor_id IN (SELECT id FROM pastores WHERE cedula = ?)
        AND d.activo = 1
        LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario_cedula);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si no se encontró como supervisor, buscar como pastor asignado
if (!$distrito) {
    // Obtener el distrito a través de la iglesia del pastor
    $sql = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
            FROM distritos d
            INNER JOIN conferencias c ON d.conferencia_id = c.id
            INNER JOIN iglesias i ON d.id = i.distrito_id
            INNER JOIN pastor_iglesias pi ON i.id = pi.iglesia_id
            INNER JOIN pastores p ON pi.pastor_id = p.id
            WHERE p.cedula = ? 
            AND pi.activo = 1
            AND i.activo = 1
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario_cedula);
    $stmt->execute();
    $distrito = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fallback para super_admin / super_distrito usando distrito_id en sesión
if (!$distrito && in_array($ROL_NOMBRE, ['super_admin', 'super_distrito'])) {
    $distrito_id = $_SESSION['distrito_id'] ?? 0;
    if ($distrito_id > 0) {
        $stmt = $conexion->prepare("SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
                                    FROM distritos d
                                    INNER JOIN conferencias c ON d.conferencia_id = c.id
                                    WHERE d.id = ?");
        $stmt->bind_param("i", $distrito_id);
        $stmt->execute();
        $distrito = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$distrito) {
    header('Location: ../panel_generico.php?error=' . urlencode('No tiene distrito asignado'));
    exit;
}

$titulo_pagina = "Iglesias del Distrito " . $distrito['codigo'];
$distrito_id = $distrito['id'];

// Obtener iglesias del distrito con estadísticas detalladas
$iglesias = [];
$total_miembros = 0;
$con_pastor = 0;

$sql = "SELECT 
        i.id,
        i.codigo,
        i.nombre,
        i.direccion,
        i.telefono,
        COALESCE(m.total_miembros, 0) AS total_miembros,
        CONCAT(p.nombre, ' ', p.apellido) AS pastor_nombre,
        p.telefono AS pastor_telefono
    FROM iglesias i
    LEFT JOIN (
        SELECT iglesia_id, COUNT(*) AS total_miembros
        FROM miembros
        WHERE estado = 'activo'
        GROUP BY iglesia_id
    ) m ON m.iglesia_id = i.id
    LEFT JOIN pastor_iglesias pi ON pi.iglesia_id = i.id AND pi.activo = 1
    LEFT JOIN pastores p ON p.id = pi.pastor_id
    WHERE i.distrito_id = ? AND i.activo = 1
    ORDER BY i.codigo";

$stmt = $conexion->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $distrito_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['pastor_nombre'] = $row['pastor_nombre'] ?: 'No asignado';
        $row['pastor_telefono'] = $row['pastor_telefono'] ?: '';
        $row['total_miembros'] = (int)$row['total_miembros'];
        
        // Obtener distribución por ministerios
        $ministerios_query = "SELECT 
            SUM(CASE WHEN ministerio_id = 1 THEN 1 ELSE 0 END) AS damas,
            SUM(CASE WHEN ministerio_id = 2 THEN 1 ELSE 0 END) AS caballeros,
            SUM(CASE WHEN ministerio_id = 3 THEN 1 ELSE 0 END) AS jovenes,
            SUM(CASE WHEN ministerio_id = 5 THEN 1 ELSE 0 END) AS adolescentes,
            SUM(CASE WHEN ministerio_id = 4 THEN 1 ELSE 0 END) AS ninos
            FROM miembros 
            WHERE iglesia_id = ? AND estado = 'activo'";
        $stmt_min = $conexion->prepare($ministerios_query);
        $stmt_min->bind_param("i", $row['id']);
        $stmt_min->execute();
        $ministerios = $stmt_min->get_result()->fetch_assoc();
        $row['ministerios'] = $ministerios;
        $stmt_min->close();
        
        // Obtener líderes de ministerios (áreas ministeriales)
        $lideres_query = "SELECT 
            am.nombre AS area_nombre,
            CONCAT(m.nombre, ' ', m.apellido) AS lider_nombre,
            al.tipo
            FROM area_lideres al
            INNER JOIN areas_ministeriales am ON al.area_id = am.id
            INNER JOIN miembros m ON al.miembro_id = m.id
            WHERE al.iglesia_id = ? AND al.activo = 1 AND al.tipo = 'lider'
            ORDER BY am.id";
        $stmt_lid = $conexion->prepare($lideres_query);
        $stmt_lid->bind_param("i", $row['id']);
        $stmt_lid->execute();
        $result_lid = $stmt_lid->get_result();
        $row['lideres_areas'] = [];
        while ($lider = $result_lid->fetch_assoc()) {
            $row['lideres_areas'][] = $lider;
        }
        $stmt_lid->close();
        
        $total_miembros += $row['total_miembros'];
        if ($row['pastor_nombre'] !== 'No asignado') {
            $con_pastor++;
        }
        
        $iglesias[] = $row;
    }
    $stmt->close();
}

$total_iglesias = count($iglesias);

// Usar el header unificado (incluye el menú lateral)
$page_title = $titulo_pagina ?? 'Iglesias del Distrito';
require_once __DIR__ . '/../includes/header.php';

// El header ya abrió el contenedor principal, continuamos con el contenido
?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-church me-2"></i><?php echo $titulo_pagina; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../panel_distrito.php">Panel Distrito</a></li>
                                <li class="breadcrumb-item active">Iglesias</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="../panel_distrito.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <!-- Info del distrito -->
                <div class="alert alert-info mb-4">
                    <strong><i class="fas fa-map-marked-alt me-2"></i>Distrito:</strong> 
                    <?php echo htmlspecialchars($distrito['codigo'] . ' - ' . $distrito['nombre']); ?>
                    <span class="ms-3">
                        <strong><i class="fas fa-globe-americas me-1"></i>Conferencia:</strong> 
                        <?php echo htmlspecialchars($distrito['conferencia_nombre']); ?>
                    </span>
                </div>

                <!-- Resumen -->
                <div class="row g-2 g-md-3 mb-3 mb-md-4">
                    <div class="col-12 col-sm-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center p-2 p-md-3">
                                <h3 class="mb-0 fs-4 fs-md-3"><?php echo $total_iglesias; ?></h3>
                                <small class="d-block">Total Iglesias</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center p-2 p-md-3">
                                <h3 class="mb-0 fs-4 fs-md-3"><?php echo $total_miembros; ?></h3>
                                <small class="d-block">Total Miembros</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center p-2 p-md-3">
                                <h3 class="mb-0 fs-4 fs-md-3"><?php echo $con_pastor; ?> / <?php echo $total_iglesias; ?></h3>
                                <small class="d-block">Con Pastor Asignado</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de iglesias -->
                <div class="row g-2 g-md-3">
                    <?php if (count($iglesias) > 0): ?>
                        <?php foreach ($iglesias as $igl): ?>
                        <div class="col-12 col-sm-6 col-lg-4 mb-3 mb-md-4">
                            <div class="card card-iglesia h-100 shadow-sm">
                                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-church text-primary me-2"></i>
                                        <?php echo htmlspecialchars($igl['codigo']); ?>
                                    </h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($igl['nombre']); ?></small>
                                </div>
                                <div class="card-body">
                                    <!-- Pastor -->
                                    <div class="mb-3 pb-2 border-bottom">
                                        <p class="mb-1">
                                            <i class="fas fa-user-tie me-2 text-primary"></i>
                                            <strong><?php echo htmlspecialchars($igl['pastor_nombre']); ?></strong>
                                        </p>
                                        <?php if (!empty($igl['pastor_telefono'])): ?>
                                        <p class="mb-0 ms-4">
                                            <i class="fas fa-phone me-2 text-muted"></i>
                                            <small><?php echo htmlspecialchars($igl['pastor_telefono']); ?></small>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Distribución por Ministerios -->
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2"><i class="fas fa-users me-2"></i>Ministerios:</h6>
                                        <div class="small">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span><i class="fas fa-female me-1 text-danger"></i> Damas</span>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><?php echo $igl['ministerios']['damas'] ?? 0; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span><i class="fas fa-male me-1 text-primary"></i> Caballeros</span>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?php echo $igl['ministerios']['caballeros'] ?? 0; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span><i class="fas fa-user-graduate me-1 text-success"></i> Jóvenes</span>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success"><?php echo $igl['ministerios']['jovenes'] ?? 0; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span><i class="fas fa-user-friends me-1 text-info"></i> Adolescentes</span>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info"><?php echo $igl['ministerios']['adolescentes'] ?? 0; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="fas fa-child me-1 text-warning"></i> Niños</span>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning"><?php echo $igl['ministerios']['ninos'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Líderes de Áreas Ministeriales -->
                                    <?php if (!empty($igl['lideres_areas'])): ?>
                                    <div class="mb-2">
                                        <h6 class="text-muted mb-2"><i class="fas fa-user-check me-2"></i>Líderes de Áreas:</h6>
                                        <div class="small">
                                            <?php foreach ($igl['lideres_areas'] as $lider): ?>
                                            <div class="mb-1">
                                                <i class="fas fa-chevron-right me-1 text-secondary"></i>
                                                <strong><?php echo htmlspecialchars($lider['area_nombre']); ?>:</strong>
                                                <span class="text-muted"><?php echo htmlspecialchars($lider['lider_nombre']); ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Total de Miembros -->
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold"><i class="fas fa-users me-1"></i>TOTAL</span>
                                            <span class="badge bg-success fs-6"><?php echo $igl['total_miembros']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="btn-group w-100">
                                        <a href="resumen_iglesia.php?iglesia=<?php echo $igl['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-chart-pie me-1"></i>Resumen
                                        </a>
                                        <a href="../miembros/index.php?iglesia=<?php echo $igl['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-list me-1"></i>Lista
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-info-circle me-2"></i>No hay iglesias registradas en este distrito.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>