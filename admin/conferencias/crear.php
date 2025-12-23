<?php
declare(strict_types=1);

$page_title = "Nueva Conferencia";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Nueva Conferencia</h1>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos de la Conferencia</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Código <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control" 
                           placeholder="Ej: N101, C102" 
                           required maxlength="20"
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($_GET['codigo'] ?? ''); ?>">
                    <small class="text-muted">Código único de la conferencia</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           placeholder="Ej: Conferencia Norte" 
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
                           placeholder="(809) 000-0000" 
                           maxlength="14">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" name="correo" class="form-control" 
                           placeholder="conferencia@concilio.org" 
                           maxlength="150">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control" style="max-width: 200px;">
                    <option value="1" selected>Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> El superintendente se asigna después de crear la conferencia.
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Conferencia
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Formatear teléfono dominicano
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
    div[style*="grid-template-columns: 1fr 2fr"],
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
