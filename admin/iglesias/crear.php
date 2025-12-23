<?php
declare(strict_types=1);

$page_title = "Nueva Iglesia";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener conferencias
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");

// Obtener provincias
$provincias = $conexion->query("SELECT id, nombre FROM provincias ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Nueva Iglesia</h1>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos de la Iglesia</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Conferencia -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Conferencia <span style="color:red;">*</span>
                    </label>
                    <select name="conferencia_id" id="conferencia_id" class="form-control" required>
                        <option value="">Seleccione una conferencia</option>
                        <?php if ($conferencias && $conferencias->num_rows > 0): ?>
                            <?php while ($conf = $conferencias->fetch_assoc()): ?>
                                <option value="<?php echo $conf['id']; ?>">
                                    <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Distrito -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt"></i> Distrito <span style="color:red;">*</span>
                    </label>
                    <select name="distrito_id" id="distrito_id" class="form-control" required>
                        <option value="">Primero seleccione una conferencia</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
                <!-- Código -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Código <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="codigo" class="form-control" 
                           placeholder="Ej: IMLC-201" 
                           required maxlength="20"
                           style="text-transform: uppercase;"
                           value="<?php echo htmlspecialchars($_GET['codigo'] ?? ''); ?>">
                </div>

                <!-- Nombre -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           placeholder="Ej: IML San Francisco Central" 
                           required maxlength="150"
                           value="<?php echo htmlspecialchars($_GET['nombre'] ?? ''); ?>">
                </div>

                <!-- Categoría -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-layer-group"></i> Categoría <span style="color:red;">*</span>
                    </label>
                    <select name="categoria" class="form-control" required>
                        <option value="Circuito" selected>Circuito</option>
                        <option value="Capilla">Capilla</option>
                        <option value="Proyecto Evangelístico">Proyecto Evangelístico</option>
                    </select>
                </div>
            </div>

            <hr style="margin: 1.5rem 0;">
            <h6><i class="fas fa-map-marker-alt"></i> Ubicación (Opcional)</h6>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Provincia -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-globe-americas"></i> Provincia
                    </label>
                    <select name="provincia_id" id="provincia_id" class="form-control">
                        <option value="">Seleccione provincia</option>
                        <?php if ($provincias && $provincias->num_rows > 0): ?>
                            <?php while ($prov = $provincias->fetch_assoc()): ?>
                                <option value="<?php echo $prov['id']; ?>">
                                    <?php echo htmlspecialchars($prov['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Municipio -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-city"></i> Municipio
                    </label>
                    <select name="municipio_id" id="municipio_id" class="form-control">
                        <option value="">Primero seleccione provincia</option>
                    </select>
                </div>
            </div>

            <!-- Dirección -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-road"></i> Dirección / Sector
                </label>
                <input type="text" name="direccion" class="form-control" 
                       placeholder="Calle, número, sector..." 
                       maxlength="255">
            </div>

            <!-- Estado -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control" style="max-width: 200px;">
                    <option value="1" selected>Activa</option>
                    <option value="0">Inactiva</option>
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Iglesia
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <a href="carga_masiva.php" class="btn btn-info" style="float: right;">
                    <i class="fas fa-upload"></i> Carga Masiva
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Cargar distritos al cambiar conferencia
document.getElementById('conferencia_id').addEventListener('change', function() {
    const conferenciaId = this.value;
    const distritoSelect = document.getElementById('distrito_id');
    
    distritoSelect.innerHTML = '<option value="">Cargando...</option>';
    distritoSelect.disabled = true;
    
    if (!conferenciaId) {
        distritoSelect.innerHTML = '<option value="">Primero seleccione una conferencia</option>';
        return;
    }
    
    fetch('../distritos_ajax.php?conferencia_id=' + conferenciaId)
        .then(response => response.json())
        .then(distritos => {
            distritoSelect.innerHTML = '<option value="">Seleccione un distrito</option>';
            
            if (distritos.length === 0) {
                distritoSelect.innerHTML = '<option value="">No hay distritos en esta conferencia</option>';
            } else {
                distritos.forEach(distrito => {
                    const option = document.createElement('option');
                    option.value = distrito.id;
                    option.textContent = distrito.codigo + ' - ' + distrito.nombre;
                    distritoSelect.appendChild(option);
                });
                distritoSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            distritoSelect.innerHTML = '<option value="">Error al cargar distritos</option>';
        });
});

// Cargar municipios al cambiar provincia
document.getElementById('provincia_id').addEventListener('change', function() {
    const provinciaId = this.value;
    const municipioSelect = document.getElementById('municipio_id');
    
    municipioSelect.innerHTML = '<option value="">Cargando...</option>';
    municipioSelect.disabled = true;
    
    if (!provinciaId) {
        municipioSelect.innerHTML = '<option value="">Primero seleccione provincia</option>';
        return;
    }
    
    fetch('../municipios_ajax.php?provincia_id=' + provinciaId)
        .then(response => response.json())
        .then(municipios => {
            municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';
            
            if (municipios.length === 0) {
                municipioSelect.innerHTML = '<option value="">No hay municipios</option>';
            } else {
                municipios.forEach(mun => {
                    const option = document.createElement('option');
                    option.value = mun.id;
                    option.textContent = mun.nombre;
                    municipioSelect.appendChild(option);
                });
                municipioSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            municipioSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
        });
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"],
    div[style*="grid-template-columns: 1fr 2fr"],
    div[style*="grid-template-columns: 1fr 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>