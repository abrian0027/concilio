<?php
declare(strict_types=1);

$page_title = "Editar Área Ministerial";
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

// Obtener área
$stmt = $conexion->prepare("SELECT a.*, i.nombre AS iglesia_nombre 
                            FROM areas_ministeriales a 
                            LEFT JOIN iglesias i ON i.id = a.iglesia_id 
                            WHERE a.id = ? AND a.tipo = 'personalizado'");
$stmt->bind_param("i", $id);
$stmt->execute();
$area = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$area) {
    header("Location: index.php?error=Área no encontrada o no es editable");
    exit;
}

// Verificar acceso
if ($ROL_NOMBRE !== 'super_admin' && $area['iglesia_id'] != $IGLESIA_ID) {
    header("Location: index.php?error=No tienes permiso para editar esta área");
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Editar Área Ministerial</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-hands-helping"></i> <?php echo htmlspecialchars($area['nombre']); ?> - <?php echo htmlspecialchars($area['iglesia_nombre']); ?>
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
            <input type="hidden" name="id" value="<?php echo $area['id']; ?>">
            <input type="hidden" name="iglesia_id" value="<?php echo $area['iglesia_id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-tag"></i> Nombre del Área <span style="color:red;">*</span>
                </label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" 
                       value="<?php echo htmlspecialchars($area['nombre']); ?>" style="text-transform: uppercase;">
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-align-left"></i> Descripción
                </label>
                <textarea name="descripcion" class="form-control" rows="3" maxlength="500"><?php echo htmlspecialchars($area['descripcion'] ?? ''); ?></textarea>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Área
                </button>
                <a href="index.php?iglesia_id=<?php echo $area['iglesia_id']; ?>" class="btn btn-secondary">
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