<?php
declare(strict_types=1);

$page_title = "Períodos de la Iglesia";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

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

// Obtener períodos de la iglesia
$periodos = [];
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT p.*, i.nombre AS iglesia_nombre 
                                FROM periodos_iglesia p 
                                INNER JOIN iglesias i ON i.id = p.iglesia_id 
                                WHERE p.iglesia_id = ? 
                                ORDER BY p.fecha_inicio DESC");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $periodos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="content-header">
    <h1><i class="fas fa-calendar-alt"></i> Períodos de la Iglesia</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        switch($_GET['success']) {
            case 'creado': echo "Período creado exitosamente."; break;
            case 'editado': echo "Período actualizado exitosamente."; break;
            case 'eliminado': echo "Período eliminado exitosamente."; break;
            default: echo "Operación realizada exitosamente.";
        }
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Períodos</span>
        <?php if ($puede_crear && $iglesia_id > 0): ?>
            <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Período
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($ROL_NOMBRE === 'super_admin'): ?>
            <form method="get" class="mb-4">
                <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label class="form-label"><i class="fas fa-church"></i> Seleccionar Iglesia</label>
                        <select name="iglesia_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Seleccione una iglesia --</option>
                            <?php while ($igl = $iglesias->fetch_assoc()): ?>
                                <option value="<?php echo $igl['id']; ?>" <?php echo $iglesia_id == $igl['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($igl['conferencia_nombre'] . ' → ' . $igl['distrito_nombre'] . ' → ' . $igl['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($iglesia_id === 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Seleccione una iglesia para ver sus períodos.
            </div>
        <?php elseif (empty($periodos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay períodos registrados para esta iglesia.
                <?php if ($puede_crear): ?>
                    <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>">Crear el primer período</a>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periodos as $p): ?>
                            <tr>
                                <td data-label="Nombre">
                                    <strong><?php echo htmlspecialchars($p['nombre']); ?></strong>
                                </td>
                                <td data-label="Fecha Inicio">
                                    <?php echo date('d/m/Y', strtotime($p['fecha_inicio'])); ?>
                                </td>
                                <td data-label="Fecha Fin">
                                    <?php echo date('d/m/Y', strtotime($p['fecha_fin'])); ?>
                                </td>
                                <td data-label="Estado">
                                    <?php if ($p['activo']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                            <i class="fas fa-history"></i> Histórico
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <a href="editar.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (!$p['activo']): ?>
                                        <a href="eliminar.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este período?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
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

<div style="margin-top: 1rem;">
    <a href="../index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver a Estructura
    </a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>