<?php
/**
 * Crear Nueva Zona/Grupo - Sistema Concilio
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Nueva Zona";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Solo pastor, secretaria o super_admin pueden crear
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_crear) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener iglesia_id
if ($ROL_NOMBRE === 'super_admin') {
    // Para super_admin: lista de iglesias
    $result_ig = $conexion->query("SELECT id, nombre FROM iglesias WHERE activo = 1 ORDER BY nombre");
    $iglesias = $result_ig ? $result_ig->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $iglesia_id = (int)$IGLESIA_ID;
}

// Obtener siguiente código disponible
function getSiguienteCodigo($conexion, $iglesia_id) {
    $stmt = $conexion->prepare("SELECT codigo FROM zonas WHERE iglesia_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Extraer número del código (ZONA-001 -> 1)
        preg_match('/(\d+)$/', $row['codigo'], $matches);
        $num = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
    } else {
        $num = 1;
    }
    
    return 'ZONA-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

$codigo_sugerido = '';
if ($ROL_NOMBRE !== 'super_admin') {
    $codigo_sugerido = getSiguienteCodigo($conexion, $iglesia_id);
}
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0"><i class="fas fa-plus-circle text-primary"></i> Nueva Zona / Grupo</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-map-marker-alt"></i> Datos de la Zona</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="post" action="guardar.php" id="formZona">
            <?php if ($ROL_NOMBRE === 'super_admin'): ?>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Iglesia <span class="text-danger">*</span>
                    </label>
                    <select name="iglesia_id" id="iglesia_id" class="form-select" required>
                        <option value="">-- Seleccione iglesia --</option>
                        <?php foreach ($iglesias as $ig): ?>
                        <option value="<?php echo $ig['id']; ?>"><?php echo htmlspecialchars($ig['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-hashtag"></i> Código <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="codigo" id="codigo" class="form-control" 
                           required maxlength="20" 
                           value="<?php echo htmlspecialchars($codigo_sugerido); ?>"
                           placeholder="Ej: ZONA-001, GRUPO-A"
                           style="text-transform: uppercase;">
                    <small class="text-muted">Identificador único de la zona</small>
                </div>

                <div class="col-12 col-md-8">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" class="form-control" 
                           required maxlength="100" 
                           placeholder="Ej: Zona Norte, Grupo Centro, Casa de Paz Villa María">
                    <small class="text-muted">Nombre descriptivo de la zona o grupo</small>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-align-left"></i> Descripción
                    </label>
                    <textarea name="descripcion" class="form-control" rows="3" 
                              maxlength="500" 
                              placeholder="Descripción opcional: ubicación geográfica, sectores que abarca, etc."></textarea>
                </div>
            </div>

            <!-- Ejemplos de nombres -->
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-lightbulb"></i> Ejemplos de nombres:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Por ubicación:</strong>
                        <ul class="mb-0 small">
                            <li>Zona Norte</li>
                            <li>Sector Centro</li>
                            <li>Barrio Las Flores</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong>Por grupo:</strong>
                        <ul class="mb-0 small">
                            <li>Grupo 1</li>
                            <li>Célula Alpha</li>
                            <li>Equipo Rojo</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong>Casa de Paz:</strong>
                        <ul class="mb-0 small">
                            <li>Casa de Paz - Familia Pérez</li>
                            <li>CDP Villa María</li>
                            <li>Reunión Hogar Norte</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Zona
                </button>
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
    
    <?php if ($ROL_NOMBRE === 'super_admin'): ?>
    // Para super_admin: actualizar código sugerido al cambiar iglesia
    document.getElementById('iglesia_id').addEventListener('change', function() {
        const iglesiaId = this.value;
        if (iglesiaId) {
            fetch('ajax_codigo.php?iglesia_id=' + iglesiaId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('codigo').value = data;
                });
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
