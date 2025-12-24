<?php
declare(strict_types=1);

$page_title = "Asignar Iglesia al Pastor";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin y Obispo
if (!in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener ID del pastor
$pastor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$pastor_id) {
    header("Location: index.php?error=" . urlencode("ID de pastor no válido"));
    exit;
}

// Obtener datos del pastor
$stmt = $conexion->prepare("SELECT * FROM pastores WHERE id = ?");
$stmt->bind_param("i", $pastor_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header("Location: index.php?error=" . urlencode("Pastor no encontrado"));
    exit;
}

// Obtener iglesias ya asignadas
$iglesias_asignadas = $conexion->prepare("
    SELECT pi.*, i.codigo, i.nombre AS iglesia_nombre, 
           d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre
    FROM pastor_iglesias pi
    INNER JOIN iglesias i ON pi.iglesia_id = i.id
    INNER JOIN distritos d ON i.distrito_id = d.id
    INNER JOIN conferencias c ON d.conferencia_id = c.id
    WHERE pi.pastor_id = ? AND pi.activo = 1
    ORDER BY pi.es_principal DESC, pi.fecha_asignacion
");
$iglesias_asignadas->bind_param("i", $pastor_id);
$iglesias_asignadas->execute();
$asignaciones = $iglesias_asignadas->get_result();
$num_asignaciones = $asignaciones->num_rows;

// Obtener conferencias para el select
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-church"></i> Asignar Iglesia al Pastor</h1>
</div>

<!-- Info del Pastor -->
<div class="card" style="margin-bottom: 1.5rem; max-width: 800px;">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <span class="card-title"><i class="fas fa-user-tie"></i> Datos del Pastor</span>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <?php if (!empty($pastor['foto'])): ?>
                <img src="../../uploads/pastores/<?php echo htmlspecialchars($pastor['foto']); ?>" 
                     alt="Foto" style="width: 80px; height: 80px; border-radius: 14px; object-fit: cover; border: 3px solid #0dcaf0;">
            <?php else: ?>
                <div style="width: 80px; height: 80px; border-radius: 14px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%); display: flex; align-items: center; justify-content: center; border: 3px solid #0dcaf0;">
                    <i class="fas fa-user" style="font-size: 2rem; color: #999;"></i>
                </div>
            <?php endif; ?>
            
            <div>
                <h3 style="margin: 0;">
                    <?php echo htmlspecialchars($pastor['nombre'] . ' ' . $pastor['apellido']); ?>
                </h3>
                <p style="margin: 0.25rem 0; color: #666;">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($pastor['cedula']); ?>
                    &nbsp;|&nbsp;
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($pastor['telefono']); ?>
                </p>
                <p style="margin: 0;">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($pastor['orden_ministerial']); ?></span>
                    <span class="badge bg-secondary"><?php echo $pastor['anos_servicio'] ?? 0; ?> años de servicio</span>
                </p>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width: 800px;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" style="max-width: 800px;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Iglesias Asignadas Actualmente -->
<?php if ($num_asignaciones > 0): ?>
<div class="card" style="margin-bottom: 1.5rem; max-width: 800px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Iglesias Asignadas Actualmente (<?php echo $num_asignaciones; ?>)</span>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Iglesia</th>
                    <th>Distrito</th>
                    <th>Conferencia</th>
                    <th>Desde</th>
                    <th>Tipo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($asig = $asignaciones->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($asig['codigo']); ?></strong><br>
                            <small><?php echo htmlspecialchars($asig['iglesia_nombre']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($asig['distrito_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($asig['conferencia_nombre']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($asig['fecha_asignacion'])); ?></td>
                        <td>
                            <?php if ($asig['es_principal']): ?>
                                <span class="badge bg-success">Principal</span>
                            <?php else: ?>
                                <span class="badge bg-info">Secundaria</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="quitar_iglesia.php?pastor_id=<?php echo $pastor_id; ?>&asignacion_id=<?php echo $asig['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Está seguro de quitar esta asignación?');">
                                <i class="fas fa-times"></i> Quitar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Formulario para nueva asignación -->
<?php if ($num_asignaciones < 2): ?>
<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-plus-circle"></i> 
            <?php echo ($num_asignaciones == 0) ? 'Asignar Primera Iglesia' : 'Asignar Segunda Iglesia'; ?>
        </span>
    </div>
    <div class="card-body">
        <form method="post" action="guardar_asignacion.php">
            <input type="hidden" name="pastor_id" value="<?php echo $pastor_id; ?>">
            <input type="hidden" name="es_principal" value="<?php echo ($num_asignaciones == 0) ? '1' : '0'; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <!-- Conferencia -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Conferencia <span style="color:red;">*</span>
                    </label>
                    <select name="conferencia_id" id="conferencia_id" class="form-control" required>
                        <option value="">Seleccione conferencia...</option>
                        <?php while ($conf = $conferencias->fetch_assoc()): ?>
                            <option value="<?php echo $conf['id']; ?>"
                                <?php echo ($pastor['conferencia_id'] == $conf['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Distrito -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt"></i> Distrito <span style="color:red;">*</span>
                    </label>
                    <select name="distrito_id" id="distrito_id" class="form-control" required>
                        <option value="">Primero seleccione conferencia</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Iglesia -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Iglesia <span style="color:red;">*</span>
                    </label>
                    <select name="iglesia_id" id="iglesia_id" class="form-control" required>
                        <option value="">Primero seleccione distrito</option>
                    </select>
                </div>
                
                <!-- Fecha de asignación -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Asignación <span style="color:red;">*</span>
                    </label>
                    <input type="date" name="fecha_asignacion" class="form-control" 
                           required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Al asignar la iglesia, automáticamente se creará:
                <ul style="margin-bottom: 0;">
                    <li>Un registro del pastor como <strong>miembro</strong> de esa iglesia</li>
                    <li>Un <strong>usuario</strong> con rol de Pastor para acceso al sistema</li>
                    <li>La contraseña inicial será la <strong>cédula</strong> del pastor</li>
                </ul>
            </div>
            
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check"></i> Asignar Iglesia
                </button>
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning" style="max-width: 800px;">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Límite alcanzado:</strong> Este pastor ya tiene 2 iglesias asignadas. 
    Para asignar otra, primero debe quitar una de las actuales.
</div>
<a href="index.php" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> Volver al listado
</a>
<?php endif; ?>

<script>
// Cargar distritos al cambiar conferencia
document.getElementById('conferencia_id').addEventListener('change', function() {
    const conferenciaId = this.value;
    const distritoSelect = document.getElementById('distrito_id');
    const iglesiaSelect = document.getElementById('iglesia_id');
    
    distritoSelect.innerHTML = '<option value="">Cargando...</option>';
    distritoSelect.disabled = true;
    iglesiaSelect.innerHTML = '<option value="">Primero seleccione distrito</option>';
    iglesiaSelect.disabled = true;
    
    if (!conferenciaId) {
        distritoSelect.innerHTML = '<option value="">Primero seleccione conferencia</option>';
        return;
    }
    
    fetch('../distritos_ajax.php?conferencia_id=' + conferenciaId)
        .then(response => response.json())
        .then(distritos => {
            distritoSelect.innerHTML = '<option value="">Seleccione distrito...</option>';
            
            if (distritos.length === 0) {
                distritoSelect.innerHTML = '<option value="">No hay distritos</option>';
            } else {
                distritos.forEach(d => {
                    const option = document.createElement('option');
                    option.value = d.id;
                    option.textContent = d.codigo + ' - ' + d.nombre;
                    distritoSelect.appendChild(option);
                });
                distritoSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            distritoSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
});

// Cargar iglesias al cambiar distrito
document.getElementById('distrito_id').addEventListener('change', function() {
    const distritoId = this.value;
    const iglesiaSelect = document.getElementById('iglesia_id');
    
    iglesiaSelect.innerHTML = '<option value="">Cargando...</option>';
    iglesiaSelect.disabled = true;
    
    if (!distritoId) {
        iglesiaSelect.innerHTML = '<option value="">Primero seleccione distrito</option>';
        return;
    }
    
    fetch('../iglesias_ajax.php?distrito_id=' + distritoId)
        .then(response => response.json())
        .then(iglesias => {
            iglesiaSelect.innerHTML = '<option value="">Seleccione iglesia...</option>';
            
            if (iglesias.length === 0) {
                iglesiaSelect.innerHTML = '<option value="">No hay iglesias en este distrito</option>';
            } else {
                iglesias.forEach(i => {
                    const option = document.createElement('option');
                    option.value = i.id;
                    option.textContent = i.codigo + ' - ' + i.nombre;
                    iglesiaSelect.appendChild(option);
                });
                iglesiaSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            iglesiaSelect.innerHTML = '<option value="">Error al cargar</option>';
        });
});

// Cargar distritos si ya hay conferencia seleccionada
window.addEventListener('load', function() {
    const conferenciaId = document.getElementById('conferencia_id').value;
    if (conferenciaId) {
        document.getElementById('conferencia_id').dispatchEvent(new Event('change'));
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"],
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>