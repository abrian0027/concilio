<?php
declare(strict_types=1);

$page_title = "Editar Conferencia";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$conferencia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conferencia_id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener conferencia
$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conf = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conf) {
    header("Location: index.php?error=" . urlencode("Conferencia no encontrada"));
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Editar Conferencia</h1>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-globe-americas"></i> <?php echo htmlspecialchars($conf['codigo']); ?></span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php">
            <input type="hidden" name="id" value="<?php echo $conferencia_id; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Código <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control" 
                           required maxlength="20"
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($conf['codigo']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           required maxlength="150"
                           value="<?php echo htmlspecialchars($conf['nombre']); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="(809) 000-0000" maxlength="14"
                           value="<?php echo htmlspecialchars($conf['telefono'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" name="correo" class="form-control" 
                           maxlength="150"
                           value="<?php echo htmlspecialchars($conf['correo'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control" style="max-width: 200px;">
                    <option value="1" <?php echo $conf['activo'] ? 'selected' : ''; ?>>Activa</option>
                    <option value="0" <?php echo !$conf['activo'] ? 'selected' : ''; ?>>Inactiva</option>
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="asignar_superintendente.php?id=<?php echo $conferencia_id; ?>" class="btn btn-info">
                    <i class="fas fa-user-tie"></i> Superintendente
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('telefono').addEventListener('input', function(e) {
    let valor = e.target.value.replace(/\D/g, '');
    if (valor.length > 10) valor = valor.substring(0, 10);
    
    let formato = '';
    if (valor.length > 0) formato = '(' + valor.substring(0, 3);
    if (valor.length > 3) formato += ') ' + valor.substring(3, 6);
    if (valor.length > 6) formato += '-' + valor.substring(6, 10);
    
    e.target.value = formato;
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
