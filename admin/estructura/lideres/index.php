<?php
declare(strict_types=1);

$page_title = "Líderes de Áreas Ministeriales";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$puede_gestionar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

// Determinar iglesia según rol
if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

// Obtener iglesias para super_admin
$iglesias = null;
if ($ROL_NOMBRE === 'super_admin') {
    $iglesias = $conexion->query("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
                                   FROM iglesias i 
                                   INNER JOIN distritos d ON d.id = i.distrito_id 
                                   INNER JOIN conferencias c ON c.id = d.conferencia_id 
                                   WHERE i.activo = 1 
                                   ORDER BY c.nombre, d.nombre, i.nombre");
}

// Verificar período activo
$periodo_activo = null;
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM periodos_iglesia WHERE iglesia_id = ? AND activo = 1 LIMIT 1");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $periodo_activo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Obtener líderes actuales agrupados por área
$lideres_por_area = [];
if ($iglesia_id > 0 && $periodo_activo) {
    $stmt = $conexion->prepare("SELECT al.*, 
                                       a.nombre AS area_nombre, a.tipo AS area_tipo,
                                       m.nombre, m.apellido, m.foto,
                                       p.nombre AS periodo_nombre
                                FROM area_lideres al
                                INNER JOIN areas_ministeriales a ON a.id = al.area_id
                                INNER JOIN miembros m ON m.id = al.miembro_id
                                INNER JOIN periodos_iglesia p ON p.id = al.periodo_id
                                WHERE al.iglesia_id = ? AND al.periodo_id = ? AND al.activo = 1
                                ORDER BY a.nombre, al.tipo DESC");
    $stmt->bind_param("ii", $iglesia_id, $periodo_activo['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $lideres_por_area[$row['area_nombre']][] = $row;
    }
    $stmt->close();
}

// Obtener todas las áreas disponibles (generales + personalizadas de la iglesia)
$areas_disponibles = [];
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM areas_ministeriales 
                                WHERE activo = 1 AND (tipo = 'general' OR iglesia_id = ?)
                                ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $areas_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-1">
            <i class="fas fa-user-tie text-info me-2"></i>Líderes de Áreas Ministeriales
        </h4>
        <p class="text-muted small mb-0">Asignación de líderes por área de ministerio</p>
    </div>
    <a href="../index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        switch($_GET['success']) {
            case 'asignado': echo "Líder asignado exitosamente."; break;
            case 'eliminado': echo "Líder removido exitosamente."; break;
            default: echo "Operación realizada exitosamente.";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get">
                <label class="form-label fw-semibold small mb-2">
                    <i class="fas fa-church text-primary me-1"></i>Seleccionar Iglesia
                </label>
                <select name="iglesia_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleccione una iglesia --</option>
                    <?php 
                    $iglesias->data_seek(0);
                    while ($igl = $iglesias->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $igl['id']; ?>" <?php echo $iglesia_id == $igl['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($igl['conferencia_nombre'] . ' → ' . $igl['distrito_nombre'] . ' → ' . $igl['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>Seleccione una iglesia para ver sus líderes.</div>
    </div>
<?php elseif (!$periodo_activo): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            Esta iglesia no tiene un período activo. 
            <a href="../periodos/crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Crear período</a> antes de asignar líderes.
        </div>
    </div>
<?php else: ?>

    <!-- Info del Período -->
    <div class="alert mb-4" style="background: rgba(13, 202, 240, 0.1); border: 1px solid #0dcaf0; color: #087990;">
        <i class="fas fa-calendar-alt me-2"></i>
        <strong>Período Activo:</strong> <?php echo htmlspecialchars($periodo_activo['nombre']); ?>
        <span style="opacity: 0.8;">
            (<?php echo date('d/m/Y', strtotime($periodo_activo['fecha_inicio'])); ?> - 
            <?php echo date('d/m/Y', strtotime($periodo_activo['fecha_fin'])); ?>)
        </span>
    </div>

    <!-- Botón Asignar -->
    <?php if ($puede_gestionar): ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="asignar.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Asignar Líder
            </a>
            <a href="historial.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-history me-1"></i> Ver Historial
            </a>
        </div>
    <?php endif; ?>

    <!-- Listado de Líderes por Área -->
    <?php if (empty($lideres_por_area)): ?>
        <div class="alert alert-light border shadow-sm d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x me-3 text-muted"></i>
            <div>
                No hay líderes asignados para el período actual.
                <?php if ($puede_gestionar): ?>
                    <a href="asignar.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Asignar líderes</a>.
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($lideres_por_area as $area_nombre => $lideres): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header text-white border-0 py-3" style="background: linear-gradient(135deg, #0891b2 0%, #0dcaf0 100%);">
                            <strong><i class="fas fa-hands-helping me-2"></i><?php echo htmlspecialchars($area_nombre); ?></strong>
                        </div>
                        <div class="card-body p-3">
                            <?php foreach ($lideres as $lider): ?>
                                <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                                    <?php if ($lider['foto']): ?>
                                        <img src="../../../uploads/miembros/<?php echo htmlspecialchars($lider['foto']); ?>" 
                                             alt="Foto" class="rounded-circle" style="width: 42px; height: 42px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-white d-flex align-items-center justify-content-center border" style="width: 42px; height: 42px;">
                                            <i class="fas fa-user text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1 min-width-0">
                                        <strong class="d-block small text-truncate"><?php echo htmlspecialchars($lider['nombre'] . ' ' . $lider['apellido']); ?></strong>
                                        <?php if ($lider['tipo'] === 'lider'): ?>
                                            <span class="badge" style="background: rgba(13, 202, 240, 0.1); color: #087990; border: 1px solid #0dcaf0;">
                                                <i class="fas fa-star me-1"></i>Líder
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                <i class="fas fa-user me-1"></i>Co-líder
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($puede_gestionar): ?>
                                        <a href="quitar.php?id=<?php echo $lider['id']; ?>&iglesia_id=<?php echo $iglesia_id; ?>" 
                                           class="btn btn-sm btn-outline-danger flex-shrink-0" 
                                           onclick="return confirm('¿Está seguro de remover este líder?');"
                                           title="Remover">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Áreas sin líder -->
    <?php
    $areas_con_lider = array_keys($lideres_por_area);
    $areas_sin_lider = array_filter($areas_disponibles, function($a) use ($areas_con_lider) {
        return !in_array($a['nombre'], $areas_con_lider);
    });
    ?>
    
    <?php if (!empty($areas_sin_lider)): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-circle me-2" style="color: #0dcaf0;"></i>Áreas sin Líder Asignado
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($areas_sin_lider as $area): ?>
                        <span class="badge fs-6 px-3 py-2" style="background: rgba(13, 202, 240, 0.1); color: #087990; border: 1px solid #0dcaf0;">
                            <?php echo htmlspecialchars($area['nombre']); ?>
                            <?php if ($puede_gestionar): ?>
                                <a href="asignar.php?iglesia_id=<?php echo $iglesia_id; ?>&area_id=<?php echo $area['id']; ?>" 
                                   class="ms-2" style="color: #087990;" title="Asignar líder">
                                    <i class="fas fa-plus-circle"></i>
                                </a>
                            <?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
