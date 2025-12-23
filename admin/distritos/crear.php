<?php
declare(strict_types=1);

$page_title = "Nuevo Distrito";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener todas las conferencias para el dropdown
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Nuevo Distrito</h1>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos del Distrito</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-globe-americas"></i> Conferencia <span style="color:red;">*</span>
                </label>
                <select name="conferencia_id" class="form-control" required>
                    <option value="">Seleccione una conferencia</option>
                    <?php while ($conf = $conferencias->fetch_assoc()): ?>
                        <option value="<?php echo $conf['id']; ?>"
                            <?php echo (isset($_GET['conferencia_id']) && $_GET['conferencia_id'] == $conf['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Código <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control" 
                           placeholder="Ej: 101, 102" 
                           required maxlength="20"
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($_GET['codigo'] ?? ''); ?>">
                    <small class="text-muted">Código único dentro de la conferencia</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           placeholder="Ej: Distrito Central" 
                           required maxlength="150"
                           value="<?php echo htmlspecialchars($_GET['nombre'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="(809) 000-0000" maxlength="14">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" name="correo" class="form-control" 
                           placeholder="distrito@concilio.org" maxlength="150">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control" style="max-width: 200px;">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> El supervisor se asigna después de crear el distrito.
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Distrito
                </button>
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
