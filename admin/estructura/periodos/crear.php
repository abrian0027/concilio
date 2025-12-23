<?php
declare(strict_types=1);

$page_title = "Nuevo Período";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_crear) {
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

if ($iglesia_id === 0) {
    header("Location: index.php?error=Debe seleccionar una iglesia");
    exit;
}

// Obtener nombre de la iglesia
$stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$iglesia) {
    header("Location: index.php?error=Iglesia no encontrada");
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Nuevo Período</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-calendar-plus"></i> Crear Período para: <?php echo htmlspecialchars($iglesia['nombre']); ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php">
            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-tag"></i> Nombre del Período <span style="color:red;">*</span>
                </label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" 
                       placeholder="Ej: Período 2025-2028" style="text-transform: uppercase;">
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Inicio <span style="color:red;">*</span>
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Fin <span style="color:red;">*</span>
                    </label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control">
                    <option value="1">Activo</option>
                    <option value="0">Histórico</option>
                </select>
                <small class="text-muted">Si marca como activo, los demás períodos pasarán a histórico automáticamente.</small>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Período
                </button>
                <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary">
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