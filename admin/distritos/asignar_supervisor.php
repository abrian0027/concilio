<?php
declare(strict_types=1);

$page_title = "Asignar Supervisor de Distrito";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener ID del distrito
$distrito_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$distrito_id) {
    header("Location: index.php?error=" . urlencode("ID de distrito no válido"));
    exit;
}

// Obtener datos del distrito con su conferencia y supervisor actual
$stmt = $conexion->prepare("
    SELECT d.*, 
           c.id AS conf_id, c.codigo AS conf_codigo, c.nombre AS conf_nombre,
           p.id AS sup_id, p.nombre AS sup_nombre, p.apellido AS sup_apellido, 
           p.cedula AS sup_cedula, p.orden_ministerial
    FROM distritos d
    INNER JOIN conferencias c ON d.conferencia_id = c.id
    LEFT JOIN pastores p ON d.supervisor_id = p.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$distrito) {
    header("Location: index.php?error=" . urlencode("Distrito no encontrado"));
    exit;
}

// Obtener pastores PRESBÍTEROS de la MISMA CONFERENCIA que no sean supervisores de otro distrito
$stmt = $conexion->prepare("
    SELECT p.*, 
           CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo,
           (SELECT GROUP_CONCAT(i.nombre SEPARATOR ', ') 
            FROM pastor_iglesias pi 
            INNER JOIN iglesias i ON pi.iglesia_id = i.id 
            WHERE pi.pastor_id = p.id AND pi.activo = 1) AS iglesias_asignadas
    FROM pastores p
    WHERE p.orden_ministerial = 'Presbítero'
    AND p.conferencia_id = ?
    AND (
        p.id NOT IN (SELECT supervisor_id FROM distritos WHERE supervisor_id IS NOT NULL AND id != ?)
        OR p.id = ?
    )
    ORDER BY p.apellido, p.nombre
");
$stmt->bind_param("iii", $distrito['conf_id'], $distrito_id, $distrito['supervisor_id']);
$stmt->execute();
$pastores = $stmt->get_result();
?>

<div class="content-header">
    <h1><i class="fas fa-user-tie"></i> Asignar Supervisor de Distrito</h1>
</div>

<!-- Info del Distrito -->
<div class="card" style="margin-bottom: 1.5rem; max-width: 800px;">
    <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
        <span class="card-title"><i class="fas fa-map-marked-alt"></i> Distrito</span>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <h3 style="margin: 0;">
                    <strong><?php echo htmlspecialchars($distrito['codigo']); ?></strong> - 
                    <?php echo htmlspecialchars($distrito['nombre']); ?>
                </h3>
                <p style="margin: 0.5rem 0; color: #666;">
                    <i class="fas fa-globe-americas"></i> 
                    Conferencia: <strong><?php echo htmlspecialchars($distrito['conf_codigo'] . ' - ' . $distrito['conf_nombre']); ?></strong>
                </p>
            </div>
        </div>
        
        <?php if ($distrito['supervisor_id']): ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                <p style="margin: 0;"><strong>Supervisor Actual:</strong></p>
                <p style="margin: 0.5rem 0;">
                    <i class="fas fa-user-tie"></i> 
                    <?php echo htmlspecialchars($distrito['sup_nombre'] . ' ' . $distrito['sup_apellido']); ?>
                </p>
                <p style="margin: 0;">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($distrito['sup_cedula']); ?>
                    &nbsp;|&nbsp;
                    <span class="badge bg-primary"><?php echo htmlspecialchars($distrito['orden_ministerial']); ?></span>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 1rem; margin-bottom: 0;">
                <i class="fas fa-exclamation-triangle"></i> Este distrito no tiene supervisor asignado.
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
            <?php echo $distrito['supervisor_id'] ? 'Cambiar Supervisor' : 'Asignar Supervisor'; ?>
        </span>
    </div>
    <div class="card-body">
        <?php if ($pastores->num_rows === 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>No hay pastores disponibles.</strong><br>
                Solo los pastores con orden ministerial <strong>Presbítero</strong> de la conferencia 
                <strong><?php echo htmlspecialchars($distrito['conf_nombre']); ?></strong> pueden ser supervisores.
                <br><br>
                <a href="../pastores/crear.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Registrar Nuevo Pastor
                </a>
            </div>
        <?php else: ?>
        
        <form method="post" action="guardar_supervisor.php" id="formSupervisor">
            <input type="hidden" name="distrito_id" value="<?php echo $distrito_id; ?>">
            <input type="hidden" name="conferencia_id" value="<?php echo $distrito['conf_id']; ?>">
            <input type="hidden" name="supervisor_anterior" value="<?php echo $distrito['supervisor_id'] ?? ''; ?>">
            
            <!-- Seleccionar Pastor -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tie"></i> Seleccionar Pastor Presbítero <span style="color:red;">*</span>
                </label>
                <select name="pastor_id" id="pastor_id" class="form-control" required>
                    <option value="">-- Seleccione un pastor de <?php echo htmlspecialchars($distrito['conf_nombre']); ?> --</option>
                    <?php while ($p = $pastores->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                                data-apellido="<?php echo htmlspecialchars($p['apellido']); ?>"
                                data-cedula="<?php echo htmlspecialchars($p['cedula']); ?>"
                                data-telefono="<?php echo htmlspecialchars($p['telefono'] ?? ''); ?>"
                                data-iglesias="<?php echo htmlspecialchars($p['iglesias_asignadas'] ?? 'Ninguna'); ?>"
                                <?php echo ($distrito['supervisor_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['apellido'] . ', ' . $p['nombre'] . ' - ' . $p['cedula']); ?>
                            <?php if ($distrito['supervisor_id'] == $p['id']): ?> (Actual)<?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small class="text-muted">Solo se muestran Presbíteros de la conferencia <?php echo htmlspecialchars($distrito['conf_codigo']); ?></small>
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
                        <label class="form-label text-muted">Iglesias que Pastorea</label>
                        <input type="text" id="show_iglesias" class="form-control" readonly style="background: white;">
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

            <?php if ($distrito['supervisor_id']): ?>
            <!-- Motivo del cambio -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-comment"></i> Motivo del Cambio
                </label>
                <textarea name="motivo_cambio" class="form-control" rows="2" 
                          placeholder="Opcional: Indique el motivo del cambio de supervisor"></textarea>
            </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Al asignar el supervisor:</strong>
                <ul style="margin-bottom: 0;">
                    <li>Se creará automáticamente un <strong>usuario</strong> con rol de Supervisor de Distrito</li>
                    <li>El usuario para login será la <strong>cédula</strong> del pastor</li>
                    <li>La contraseña inicial será la <strong>cédula sin guiones</strong></li>
                    <li>Podrá ver todas las iglesias y miembros de su distrito</li>
                </ul>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check"></i> 
                    <?php echo $distrito['supervisor_id'] ? 'Cambiar Supervisor' : 'Asignar Supervisor'; ?>
                </button>
                
                <?php if ($distrito['supervisor_id']): ?>
                <a href="quitar_supervisor.php?id=<?php echo $distrito_id; ?>" 
                   class="btn btn-danger btn-lg"
                   onclick="return confirm('¿Está seguro de quitar al supervisor actual?');">
                    <i class="fas fa-user-minus"></i> Quitar Supervisor
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

<!-- Historial de Supervisores -->
<?php
$stmt = $conexion->prepare("
    SELECT h.*, p.nombre, p.apellido, p.cedula
    FROM distrito_supervisores_historial h
    INNER JOIN pastores p ON h.pastor_id = p.id
    WHERE h.distrito_id = ?
    ORDER BY h.fecha_inicio DESC
    LIMIT 10
");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$historial = $stmt->get_result();

if ($historial->num_rows > 0):
?>
<div class="card" style="max-width: 800px; margin-top: 1.5rem;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-history"></i> Historial de Supervisores</span>
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
            document.getElementById('show_iglesias').value = option.dataset.iglesias || 'Ninguna';
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
