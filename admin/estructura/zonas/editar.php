<?php
/**
 * Editar Zona/Grupo - Sistema Concilio
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Editar Zona";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Solo pastor, secretaria o super_admin pueden editar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener zona
$sql = "SELECT z.*, i.nombre AS iglesia_nombre FROM zonas z 
        LEFT JOIN iglesias i ON i.id = z.iglesia_id 
        WHERE z.id = ?";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND z.iglesia_id = " . (int)$IGLESIA_ID;
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$zona = $result->fetch_assoc();

if (!$zona) {
    header("Location: index.php?error=Zona no encontrada");
    exit;
}
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0"><i class="fas fa-edit text-primary"></i> Editar Zona</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-map-marker-alt"></i> Datos de la Zona</span>
        <?php if ($ROL_NOMBRE === 'super_admin'): ?>
        <span class="badge bg-info float-end"><?php echo htmlspecialchars($zona['iglesia_nombre']); ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php" id="formZona">
            <input type="hidden" name="id" value="<?php echo $zona['id']; ?>">

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-hashtag"></i> Código <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="codigo" id="codigo" class="form-control" 
                           required maxlength="20" 
                           value="<?php echo htmlspecialchars($zona['codigo']); ?>"
                           style="text-transform: uppercase;">
                    <small class="text-muted">Identificador único de la zona</small>
                </div>

                <div class="col-12 col-md-8">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" class="form-control" 
                           required maxlength="100" 
                           value="<?php echo htmlspecialchars($zona['nombre']); ?>">
                    <small class="text-muted">Nombre descriptivo de la zona o grupo</small>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Descripción
                    </label>
                    <textarea name="descripcion" class="form-control" rows="3" 
                              maxlength="500"><?php echo htmlspecialchars($zona['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-toggle-on"></i> Estado
                    </label>
                    <select name="activo" class="form-select">
                        <option value="1" <?php echo $zona['activo'] ? 'selected' : ''; ?>>Activo</option>
                        <option value="0" <?php echo !$zona['activo'] ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="ver.php?id=<?php echo $zona['id']; ?>" class="btn btn-outline-info">
                    <i class="fas fa-eye"></i> Ver Zona
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const codigo = document.getElementById('codigo');
    
    // Convertir código a mayúsculas
    codigo.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
