<?php
declare(strict_types=1);

$page_title = "Editar Período";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener período
$stmt = $conexion->prepare("SELECT p.*, i.nombre AS iglesia_nombre 
                            FROM periodos_iglesia p 
                            INNER JOIN iglesias i ON i.id = p.iglesia_id 
                            WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$periodo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$periodo) {
    header("Location: index.php?error=Período no encontrado");
    exit;
}

// Verificar acceso
if ($ROL_NOMBRE !== 'super_admin' && $periodo['iglesia_id'] != $IGLESIA_ID) {
    header("Location: index.php?error=No tienes permiso para editar este período");
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Editar Período</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($periodo['nombre']); ?> - <?php echo htmlspecialchars($periodo['iglesia_nombre']); ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php">
            <input type="hidden" name="id" value="<?php echo $periodo['id']; ?>">
            <input type="hidden" name="iglesia_id" value="<?php echo $periodo['iglesia_id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-tag"></i> Nombre del Período <span style="color:red;">*</span>
                </label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" 
                       value="<?php echo htmlspecialchars($periodo['nombre']); ?>" style="text-transform: uppercase;">
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Inicio <span style="color:red;">*</span>
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control" required 
                           value="<?php echo $periodo['fecha_inicio']; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Fin <span style="color:red;">*</span>
                    </label>
                    <input type="date" name="fecha_fin" class="form-control" required 
                           value="<?php echo $periodo['fecha_fin']; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control">
                    <option value="1" <?php echo $periodo['activo'] ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo !$periodo['activo'] ? 'selected' : ''; ?>>Histórico</option>
                </select>
                <small class="text-muted">Si marca como activo, los demás períodos pasarán a histórico automáticamente.</small>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Período
                </button>
                <a href="index.php?iglesia_id=<?php echo $periodo['iglesia_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nombre = document.querySelector('input[name="nombre"]');
    nombre.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>