<?php
declare(strict_types=1);

$page_title = "Asignar Superintendente";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener ID de la conferencia
$conferencia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conferencia_id) {
    header("Location: index.php?error=" . urlencode("ID de conferencia no válido"));
    exit;
}

// Obtener datos de la conferencia
$stmt = $conexion->prepare("
    SELECT c.*, 
           p.id AS super_id, p.nombre AS super_nombre, p.apellido AS super_apellido, 
           p.cedula AS super_cedula, p.orden_ministerial
    FROM conferencias c
    LEFT JOIN pastores p ON c.superintendente_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header("Location: index.php?error=" . urlencode("Conferencia no encontrada"));
    exit;
}

// Obtener pastores PRESBÍTEROS disponibles (que no sean superintendentes de otra conferencia)
$stmt = $conexion->prepare("
    SELECT p.*, 
           CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo,
           c.nombre AS conferencia_actual
    FROM pastores p
    LEFT JOIN conferencias c ON p.conferencia_id = c.id
    WHERE p.orden_ministerial = 'Presbítero'
    AND (
        p.id NOT IN (SELECT superintendente_id FROM conferencias WHERE superintendente_id IS NOT NULL AND id != ?)
        OR p.id = ?
    )
    ORDER BY p.apellido, p.nombre
");
$stmt->bind_param("ii", $conferencia_id, $conferencia['superintendente_id']);
$stmt->execute();
$pastores = $stmt->get_result();
?>

<div class="content-header">
    <h1><i class="fas fa-user-tie"></i> Asignar Superintendente</h1>
</div>

<!-- Info de la Conferencia -->
<div class="card" style="margin-bottom: 1.5rem; max-width: 800px;">
    <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <span class="card-title"><i class="fas fa-globe-americas"></i> Conferencia</span>
    </div>
    <div class="card-body">
        <h3 style="margin: 0;">
            <strong><?php echo htmlspecialchars($conferencia['codigo']); ?></strong> - 
            <?php echo htmlspecialchars($conferencia['nombre']); ?>
        </h3>
        
        <?php if ($conferencia['superintendente_id']): ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                <p style="margin: 0;"><strong>Superintendente Actual:</strong></p>
                <p style="margin: 0.5rem 0;">
                    <i class="fas fa-user-tie"></i> 
                    <?php echo htmlspecialchars($conferencia['super_nombre'] . ' ' . $conferencia['super_apellido']); ?>
                </p>
                <p style="margin: 0;">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($conferencia['super_cedula']); ?>
                    &nbsp;|&nbsp;
                    <span class="badge bg-primary"><?php echo htmlspecialchars($conferencia['orden_ministerial']); ?></span>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 1rem; margin-bottom: 0;">
                <i class="fas fa-exclamation-triangle"></i> Esta conferencia no tiene superintendente asignado.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" style="max-width: 800px;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Formulario de Asignación -->
<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-user-plus"></i> 
            <?php echo $conferencia['superintendente_id'] ? 'Cambiar Superintendente' : 'Asignar Superintendente'; ?>
        </span>
    </div>
    <div class="card-body">
        <?php if ($pastores->num_rows === 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>No hay pastores disponibles.</strong><br>
                Solo los pastores con orden ministerial <strong>Presbítero</strong> pueden ser superintendentes.
                <br><br>
                <a href="../pastores/crear.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Registrar Nuevo Pastor
                </a>
            </div>
        <?php else: ?>
        
        <form method="post" action="guardar_superintendente.php" id="formSuperintendente">
            <input type="hidden" name="conferencia_id" value="<?php echo $conferencia_id; ?>">
            <input type="hidden" name="superintendente_anterior" value="<?php echo $conferencia['superintendente_id'] ?? ''; ?>">
            
            <!-- Seleccionar Pastor -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tie"></i> Seleccionar Pastor Presbítero <span style="color:red;">*</span>
                </label>
                <select name="pastor_id" id="pastor_id" class="form-control" required>
                    <option value="">-- Seleccione un pastor --</option>
                    <?php while ($p = $pastores->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                                data-apellido="<?php echo htmlspecialchars($p['apellido']); ?>"
                                data-cedula="<?php echo htmlspecialchars($p['cedula']); ?>"
                                data-telefono="<?php echo htmlspecialchars($p['telefono'] ?? ''); ?>"
                                data-conferencia="<?php echo htmlspecialchars($p['conferencia_actual'] ?? 'Sin conferencia'); ?>"
                                <?php echo ($conferencia['superintendente_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['apellido'] . ', ' . $p['nombre'] . ' - ' . $p['cedula']); ?>
                            <?php if ($conferencia['superintendente_id'] == $p['id']): ?> (Actual)<?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Datos del pastor seleccionado -->
            <div id="datos_pastor" style="display: none; background: #e8f4fd; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <h5 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-info-circle"></i> Datos del Pastor Seleccionado
                </h5>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div>
                        <label class="form-label text-muted">Nombre Completo</label>
                        <input type="text" id="show_nombre" class="form-control" readonly style="background: white;">
                    </div>
                    <div>
                        <label class="form-label text-muted">Cédula (será el usuario)</label>
                        <input type="text" id="show_cedula" class="form-control" readonly style="background: white; font-weight: bold;">
                    </div>
                    <div>
                        <label class="form-label text-muted">Teléfono</label>
                        <input type="text" id="show_telefono" class="form-control" readonly style="background: white;">
                    </div>
                    <div>
                        <label class="form-label text-muted">Conferencia Actual</label>
                        <input type="text" id="show_conferencia" class="form-control" readonly style="background: white;">
                    </div>
                </div>
            </div>

            <!-- Fecha de asignación -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-calendar"></i> Fecha de Asignación <span style="color:red;">*</span>
                </label>
                <input type="date" name="fecha_asignacion" class="form-control" 
                       required value="<?php echo date('Y-m-d'); ?>" style="max-width: 250px;">
            </div>

            <?php if ($conferencia['superintendente_id']): ?>
            <!-- Motivo del cambio -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-comment"></i> Motivo del Cambio
                </label>
                <textarea name="motivo_cambio" class="form-control" rows="2" 
                          placeholder="Opcional: Indique el motivo del cambio de superintendente"></textarea>
            </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Al asignar el superintendente:</strong>
                <ul style="margin-bottom: 0;">
                    <li>Se creará automáticamente un <strong>usuario</strong> con rol de Superintendente de Conferencia</li>
                    <li>El usuario para login será la <strong>cédula</strong> del pastor</li>
                    <li>La contraseña inicial será la <strong>cédula sin guiones</strong></li>
                    <li>El pastor se asociará a esta conferencia</li>
                </ul>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check"></i> 
                    <?php echo $conferencia['superintendente_id'] ? 'Cambiar Superintendente' : 'Asignar Superintendente'; ?>
                </button>
                
                <?php if ($conferencia['superintendente_id']): ?>
                <a href="quitar_superintendente.php?id=<?php echo $conferencia_id; ?>" 
                   class="btn btn-danger btn-lg"
                   onclick="return confirm('¿Está seguro de quitar al superintendente actual?');">
                    <i class="fas fa-user-minus"></i> Quitar Superintendente
                </a>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </form>
        
        <?php endif; ?>
    </div>
</div>

<!-- Historial de Superintendentes -->
<?php
$stmt = $conexion->prepare("
    SELECT h.*, p.nombre, p.apellido, p.cedula
    FROM conferencia_superintendentes_historial h
    INNER JOIN pastores p ON h.pastor_id = p.id
    WHERE h.conferencia_id = ?
    ORDER BY h.fecha_inicio DESC
    LIMIT 10
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$historial = $stmt->get_result();

if ($historial->num_rows > 0):
?>
<div class="card" style="max-width: 800px; margin-top: 1.5rem;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-history"></i> Historial de Superintendentes</span>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Pastor</th>
                    <th>Período</th>
                    <th>Motivo Fin</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $historial->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($h['nombre'] . ' ' . $h['apellido']); ?></strong>
                            <br><small class="text-muted"><?php echo htmlspecialchars($h['cedula']); ?></small>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($h['fecha_inicio'])); ?>
                            <?php if ($h['fecha_fin']): ?>
                                - <?php echo date('d/m/Y', strtotime($h['fecha_fin'])); ?>
                            <?php else: ?>
                                - <span class="badge bg-success">Actual</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($h['motivo_fin'] ?? '-'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pastorSelect = document.getElementById('pastor_id');
    const datosDiv = document.getElementById('datos_pastor');

    pastorSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        
        if (this.value) {
            document.getElementById('show_nombre').value = option.dataset.nombre + ' ' + option.dataset.apellido;
            document.getElementById('show_cedula').value = option.dataset.cedula;
            document.getElementById('show_telefono').value = option.dataset.telefono || 'No registrado';
            document.getElementById('show_conferencia').value = option.dataset.conferencia;
            datosDiv.style.display = 'block';
        } else {
            datosDiv.style.display = 'none';
        }
    });

    // Mostrar datos si ya hay uno seleccionado
    if (pastorSelect.value) {
        pastorSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(2"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
