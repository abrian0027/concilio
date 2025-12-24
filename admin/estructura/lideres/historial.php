<?php
declare(strict_types=1);

$page_title = "Historial de Líderes";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Determinar iglesia
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

// Obtener períodos de la iglesia
$periodos = [];
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM periodos_iglesia WHERE iglesia_id = ? ORDER BY fecha_inicio DESC");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $periodos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Período seleccionado
$periodo_id = (int)($_GET['periodo_id'] ?? 0);

// Obtener líderes del período seleccionado
$lideres = [];
if ($periodo_id > 0) {
    $stmt = $conexion->prepare("SELECT al.*, 
                                       a.nombre AS area_nombre,
                                       m.nombre, m.apellido, m.foto,
                                       p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin
                                FROM area_lideres al
                                INNER JOIN areas_ministeriales a ON a.id = al.area_id
                                INNER JOIN miembros m ON m.id = al.miembro_id
                                INNER JOIN periodos_iglesia p ON p.id = al.periodo_id
                                WHERE al.periodo_id = ?
                                ORDER BY a.nombre, al.tipo DESC");
    $stmt->bind_param("i", $periodo_id);
    $stmt->execute();
    $lideres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="content-header">
    <h1><i class="fas fa-history"></i> Historial de Líderes</h1>
</div>

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="get">
                <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label"><i class="fas fa-church"></i> Iglesia</label>
                        <select name="iglesia_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Seleccione --</option>
                            <?php while ($igl = $iglesias->fetch_assoc()): ?>
                                <option value="<?php echo $igl['id']; ?>" <?php echo $iglesia_id == $igl['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($igl['conferencia_nombre'] . ' → ' . $igl['distrito_nombre'] . ' → ' . $igl['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($iglesia_id > 0 && !empty($periodos)): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="get">
                <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label"><i class="fas fa-calendar-alt"></i> Período</label>
                        <select name="periodo_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Seleccione un período --</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $periodo_id == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                    (<?php echo date('d/m/Y', strtotime($p['fecha_inicio'])); ?> - 
                                    <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?>)
                                    <?php if ($p['activo']): ?><strong>[ACTIVO]</strong><?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($periodo_id > 0): ?>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-user-tie"></i> Líderes del Período</span>
        </div>
        <div class="card-body">
            <?php if (empty($lideres)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay líderes registrados para este período.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Nombre</th>
                                <th>Área</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lideres as $l): ?>
                                <tr>
                                    <td>
                                        <?php if ($l['foto']): ?>
                                            <img src="../../../uploads/miembros/<?php echo htmlspecialchars($l['foto']); ?>" 
                                                 alt="Foto" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 2px solid #0dcaf0;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%); display: flex; align-items: center; justify-content: center; border: 2px solid #0dcaf0;">
                                                <i class="fas fa-user" style="color: #adb5bd;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($l['nombre'] . ' ' . $l['apellido']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($l['area_nombre']); ?></td>
                                    <td>
                                        <?php if ($l['tipo'] === 'lider'): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                <i class="fas fa-star"></i> Líder
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                Co-líder
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($iglesia_id === 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Seleccione una iglesia para ver el historial.
    </div>
<?php endif; ?>

<div style="margin-top: 1rem;">
    <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
