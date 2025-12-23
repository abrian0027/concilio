<?php
declare(strict_types=1);

$page_title = "Junta Administrativa";
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

// Obtener junta activa de la iglesia
$junta_activa = null;
$miembros_junta = [];
if ($iglesia_id > 0) {
    // Buscar junta activa
    $stmt = $conexion->prepare("SELECT j.*, p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin 
                                FROM juntas j 
                                INNER JOIN periodos_iglesia p ON p.id = j.periodo_id 
                                WHERE j.iglesia_id = ? AND j.activa = 1 
                                LIMIT 1");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $junta_activa = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Si hay junta activa, obtener miembros
    if ($junta_activa) {
        $stmt = $conexion->prepare("SELECT jm.*, m.nombre, m.apellido, m.foto, c.nombre AS cargo_nombre, c.orden
                                    FROM junta_miembros jm 
                                    INNER JOIN miembros m ON m.id = jm.miembro_id 
                                    INNER JOIN cargos_junta c ON c.id = jm.cargo_id 
                                    WHERE jm.junta_id = ? AND jm.activo = 1 
                                    ORDER BY jm.es_pastor DESC, c.orden, m.nombre");
        $stmt->bind_param("i", $junta_activa['id']);
        $stmt->execute();
        $miembros_junta = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Obtener historial de juntas
$juntas_historicas = [];
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT j.*, p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin,
                                       (SELECT COUNT(*) FROM junta_miembros WHERE junta_id = j.id) AS total_miembros
                                FROM juntas j 
                                INNER JOIN periodos_iglesia p ON p.id = j.periodo_id 
                                WHERE j.iglesia_id = ? AND j.activa = 0 
                                ORDER BY p.fecha_inicio DESC");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $juntas_historicas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Verificar si hay período activo
$periodo_activo = null;
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM periodos_iglesia WHERE iglesia_id = ? AND activo = 1 LIMIT 1");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $periodo_activo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!-- Header -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark">
            <i class="fas fa-users-cog text-warning me-2"></i>Junta Administrativa
        </h1>
        <p class="text-muted small mb-0">Gestión de cargos directivos de la iglesia</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="../index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0">
        <i class="fas fa-check-circle me-2"></i>
        <?php 
        switch($_GET['success']) {
            case 'creado': echo "Junta creada exitosamente."; break;
            case 'actualizado': echo "Junta actualizada exitosamente."; break;
            case 'miembro_agregado': echo "Miembro agregado a la junta."; break;
            case 'miembro_eliminado': echo "Miembro removido de la junta."; break;
            default: echo "Operación realizada exitosamente.";
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get">
                <label class="form-label fw-semibold">
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
    <div class="alert alert-info border-0 d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>Seleccione una iglesia para ver su junta administrativa.</div>
    </div>
<?php elseif (!$periodo_activo): ?>
    <div class="alert alert-warning border-0 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            Esta iglesia no tiene un período activo. 
            <a href="../periodos/crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Crear período</a> antes de configurar la junta.
        </div>
    </div>
<?php else: ?>

    <!-- Junta Activa -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-1">
                        <i class="fas fa-users text-warning me-2"></i>Junta Administrativa Actual
                    </h6>
                    <?php if ($junta_activa): ?>
                        <div class="mt-2">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info me-2">
                                <?php echo htmlspecialchars($junta_activa['periodo_nombre']); ?>
                            </span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                <?php echo date('d/m/Y', strtotime($junta_activa['fecha_inicio'])); ?> - 
                                <?php echo date('d/m/Y', strtotime($junta_activa['fecha_fin'])); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($puede_gestionar): ?>
                    <div>
                        <?php if ($junta_activa): ?>
                            <a href="asignar.php?junta_id=<?php echo $junta_activa['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-user-plus"></i> Agregar Miembro
                            </a>
                        <?php else: ?>
                            <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear Junta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$junta_activa): ?>
                <div class="alert alert-light border d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3 text-muted"></i>
                    <div>
                        No hay junta administrativa configurada para el período activo.
                        <?php if ($puede_gestionar): ?>
                            <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Crear junta</a>.
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (empty($miembros_junta)): ?>
                <div class="alert alert-light border d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3 text-muted"></i>
                    <div>
                        La junta no tiene miembros asignados.
                        <?php if ($puede_gestionar): ?>
                            <a href="asignar.php?junta_id=<?php echo $junta_activa['id']; ?>" class="alert-link">Agregar miembros</a>.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Foto</th>
                                <th>Nombre</th>
                                <th>Cargo</th>
                                <th>Tipo</th>
                                <?php if ($puede_gestionar): ?>
                                    <th style="width: 100px;" class="text-center">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($miembros_junta as $mj): ?>
                                <tr>
                                    <td>
                                        <?php if ($mj['foto']): ?>
                                            <img src="../../../uploads/miembros/<?php echo htmlspecialchars($mj['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mj['nombre'] . ' ' . $mj['apellido']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                            <?php echo htmlspecialchars($mj['cargo_nombre']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($mj['es_pastor']): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                <i class="fas fa-star"></i> Presidente (No cuenta en los <?php echo $junta_activa['tipo']; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                Miembro de Junta
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($puede_gestionar): ?>
                                        <td class="text-center">
                                            <a href="quitar_miembro.php?id=<?php echo $mj['id']; ?>&junta_id=<?php echo $junta_activa['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Está seguro de remover este miembro de la junta?');"
                                               title="Remover">
                                                <i class="fas fa-user-minus"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php
                // Contar miembros (sin pastores)
                $total_miembros = 0;
                $total_pastores = 0;
                foreach ($miembros_junta as $mj) {
                    if ($mj['es_pastor']) {
                        $total_pastores++;
                    } else {
                        $total_miembros++;
                    }
                }
                ?>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                        <i class="fas fa-users me-1"></i>Pastores: <?php echo $total_pastores; ?>
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                        <i class="fas fa-user-check me-1"></i>Miembros de Junta: <?php echo $total_miembros; ?> / <?php echo $junta_activa['tipo']; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Juntas -->
    <?php if (!empty($juntas_historicas)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0"><i class="fas fa-history text-secondary me-2"></i>Historial de Juntas</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Período</th>
                                <th>Fechas</th>
                                <th>Tipo</th>
                                <th>Miembros</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($juntas_historicas as $jh): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($jh['periodo_nombre']); ?></strong></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($jh['fecha_inicio'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($jh['fecha_fin'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                            <?php echo $jh['tipo']; ?> miembros
                                        </span>
                                    </td>
                                    <td><?php echo $jh['total_miembros']; ?></td>
                                    <td class="text-center">
                                        <a href="ver.php?id=<?php echo $jh['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>