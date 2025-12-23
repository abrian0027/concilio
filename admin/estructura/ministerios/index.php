<?php
declare(strict_types=1);

$page_title = "Áreas Ministeriales";
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

// Obtener áreas ministeriales generales
$areas_generales = $conexion->query("SELECT * FROM areas_ministeriales WHERE tipo = 'general' AND activo = 1 ORDER BY nombre");

// Obtener áreas personalizadas de la iglesia
$areas_personalizadas = [];
if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM areas_ministeriales WHERE tipo = 'personalizado' AND iglesia_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $areas_personalizadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="content-header">
    <h1><i class="fas fa-hands-helping"></i> Áreas Ministeriales</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        switch($_GET['success']) {
            case 'creado': echo "Área ministerial creada exitosamente."; break;
            case 'editado': echo "Área ministerial actualizada exitosamente."; break;
            case 'eliminado': echo "Área ministerial eliminada exitosamente."; break;
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

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="get">
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
        </div>
    </div>
<?php endif; ?>

<!-- Áreas Generales -->
<div class="card mb-4">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-globe"></i> Áreas Ministeriales Generales</span>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Estas áreas están disponibles para todas las iglesias del concilio.</p>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $num = 1; while ($area = $areas_generales->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $num++; ?></td>
                            <td><strong><?php echo htmlspecialchars($area['nombre']); ?></strong></td>
                            <td><?php echo $area['descripcion'] ? htmlspecialchars($area['descripcion']) : '<span class="text-muted">-</span>'; ?></td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                    <i class="fas fa-check"></i> Activo
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Áreas Personalizadas -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-church"></i> Áreas Personalizadas de la Iglesia</span>
        <?php if ($puede_crear && $iglesia_id > 0): ?>
            <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Área
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($iglesia_id === 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Seleccione una iglesia para ver sus áreas personalizadas.
            </div>
        <?php elseif (empty($areas_personalizadas)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Esta iglesia no tiene áreas ministeriales personalizadas.
                <?php if ($puede_crear): ?>
                    <a href="crear.php?iglesia_id=<?php echo $iglesia_id; ?>">Crear la primera</a>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $num = 1; foreach ($areas_personalizadas as $area): ?>
                            <tr>
                                <td><?php echo $num++; ?></td>
                                <td><strong><?php echo htmlspecialchars($area['nombre']); ?></strong></td>
                                <td><?php echo $area['descripcion'] ? htmlspecialchars($area['descripcion']) : '<span class="text-muted">-</span>'; ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <i class="fas fa-check"></i> Activo
                                    </span>
                                </td>
                                <td>
                                    <a href="editar.php?id=<?php echo $area['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $area['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta área ministerial?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
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