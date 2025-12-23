<?php
declare(strict_types=1);

$page_title = "Editar Distrito";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$distrito_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$distrito_id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener distrito
$stmt = $conexion->prepare("SELECT * FROM distritos WHERE id = ?");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$dist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dist) {
    header("Location: index.php?error=" . urlencode("Distrito no encontrado"));
    exit;
}

// Obtener conferencias
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Editar Distrito</h1>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($dist['codigo']); ?></span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php">
            <input type="hidden" name="id" value="<?php echo $distrito_id; ?>">
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-globe-americas"></i> Conferencia <span style="color:red;">*</span>
                </label>
                <select name="conferencia_id" class="form-control" required>
                    <option value="">Seleccione una conferencia</option>
                    <?php while ($conf = $conferencias->fetch_assoc()): ?>
                        <option value="<?php echo $conf['id']; ?>"
                            <?php echo ($dist['conferencia_id'] == $conf['id']) ? 'selected' : ''; ?>>
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
                           required maxlength="20"
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($dist['codigo']); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           required maxlength="150"
                           value="<?php echo htmlspecialchars($dist['nombre']); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="(809) 000-0000" maxlength="14"
                           value="<?php echo htmlspecialchars($dist['telefono'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo Electrónico
                    </label>
                    <input type="email" name="correo" class="form-control" maxlength="150"
                           value="<?php echo htmlspecialchars($dist['correo'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control" style="max-width: 200px;">
                    <option value="1" <?php echo $dist['activo'] ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo !$dist['activo'] ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="asignar_supervisor.php?id=<?php echo $distrito_id; ?>" class="btn btn-info">
                    <i class="fas fa-user-tie"></i> Supervisor
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
