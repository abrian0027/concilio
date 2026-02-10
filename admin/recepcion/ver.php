<?php
/**
 * Ver Solicitud de Membresía
 * Sistema Concilio - Panel del Pastor
 */

$page_title = "Ver Solicitud";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden ver esto
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID no válido</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener solicitud
$sql = "
    SELECT s.*, 
           i.nombre AS iglesia_nombre, i.codigo AS iglesia_codigo,
           n.nombre AS nacionalidad_nombre,
           ne.nombre AS nivel_estudio_nombre,
           c.nombre AS carrera_nombre,
           CONCAT(u.nombre, ' ', u.apellido) AS revisado_por_nombre
    FROM solicitudes_membresia s
    LEFT JOIN iglesias i ON i.id = s.iglesia_id
    LEFT JOIN nacionalidades n ON n.id = s.nacionalidad_id
    LEFT JOIN niveles_estudio ne ON ne.id = s.nivel_estudio_id
    LEFT JOIN carreras c ON c.id = s.carrera_id
    LEFT JOIN usuarios u ON u.id = s.revisado_por
    WHERE s.id = ?
";

// Verificar permisos (solo su iglesia o super_admin)
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND s.iglesia_id = ?";
}

$stmt = $conexion->prepare($sql);
if ($ROL_NOMBRE !== 'super_admin') {
    $stmt->bind_param("ii", $id, $IGLESIA_ID);
} else {
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    echo "<div class='alert alert-danger'>Solicitud no encontrada</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Calcular edad
$edad = '';
if ($solicitud['fecha_nacimiento']) {
    $nacimiento = new DateTime($solicitud['fecha_nacimiento']);
    $hoy = new DateTime();
    $diff = $nacimiento->diff($hoy);
    $edad = $diff->y . ' años';
}

$estados_civiles = [
    'soltero' => 'Soltero/a',
    'casado' => 'Casado/a',
    'union_libre' => 'Unión Libre',
    'divorciado' => 'Divorciado/a',
    'viudo' => 'Viudo/a'
];
?>

<style>
.solicitud-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.solicitud-info h2 {
    margin-bottom: 5px;
}

.badge-estado {
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
}

.badge-pendiente { background: #fef3c7; color: #92400e; }
.badge-aprobado { background: #d1fae5; color: #065f46; }
.badge-rechazado { background: #fee2e2; color: #991b1b; }

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.info-section {
    background: #f9fafb;
    border-radius: 12px;
    padding: 20px;
}

.info-section h4 {
    color: var(--primary-color);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #6b7280;
    font-size: 0.9rem;
}

.info-value {
    font-weight: 500;
    color: #1f2937;
    text-align: right;
}

.acciones-solicitud {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 25px;
}

.meta-info {
    background: #f3f4f6;
    padding: 15px 20px;
    border-radius: 10px;
    margin-top: 20px;
    font-size: 0.85rem;
    color: #6b7280;
}

.meta-info span {
    margin-right: 20px;
}
</style>

<div class="content-header">
    <a href="index.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="solicitud-header">
            <div class="solicitud-info">
                <h2><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido']); ?></h2>
                <p class="text-muted">
                    <i class="fas fa-church"></i> <?php echo htmlspecialchars($solicitud['iglesia_nombre']); ?>
                    (<?php echo htmlspecialchars($solicitud['iglesia_codigo']); ?>)
                </p>
            </div>
            <span class="badge-estado badge-<?php echo $solicitud['estado']; ?>">
                <i class="fas fa-<?php echo $solicitud['estado'] === 'pendiente' ? 'clock' : ($solicitud['estado'] === 'aprobado' ? 'check' : 'times'); ?>"></i>
                <?php echo ucfirst($solicitud['estado']); ?>
            </span>
        </div>
        
        <div class="info-grid">
            <!-- Datos Personales -->
            <div class="info-section">
                <h4><i class="fas fa-user"></i> Datos Personales</h4>
                <div class="info-row">
                    <span class="info-label">Nombre Completo</span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sexo</span>
                    <span class="info-value"><?php echo $solicitud['sexo'] === 'M' ? 'Masculino' : 'Femenino'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Nacimiento</span>
                    <span class="info-value">
                        <?php echo $solicitud['fecha_nacimiento'] ? date('d/m/Y', strtotime($solicitud['fecha_nacimiento'])) . " ($edad)" : '-'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nacionalidad</span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['nacionalidad_nombre'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php echo $solicitud['tipo_documento'] === 'cedula' ? 'Cédula' : 'Pasaporte'; ?></span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['numero_documento']); ?></span>
                </div>
            </div>
            
            <!-- Contacto -->
            <div class="info-section">
                <h4><i class="fas fa-address-book"></i> Contacto</h4>
                <div class="info-row">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">
                        <?php if ($solicitud['telefono']): ?>
                            <a href="tel:<?php echo $solicitud['telefono']; ?>"><?php echo htmlspecialchars($solicitud['telefono']); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo Electrónico</span>
                    <span class="info-value">
                        <?php if ($solicitud['email']): ?>
                            <a href="mailto:<?php echo $solicitud['email']; ?>"><?php echo htmlspecialchars($solicitud['email']); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dirección</span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['direccion'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado Civil</span>
                    <span class="info-value"><?php echo $estados_civiles[$solicitud['estado_civil']] ?? $solicitud['estado_civil']; ?></span>
                </div>
            </div>
            
            <!-- Estudios -->
            <div class="info-section">
                <h4><i class="fas fa-graduation-cap"></i> Nivel de Estudios</h4>
                <div class="info-row">
                    <span class="info-label">Nivel de Estudio</span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['nivel_estudio_nombre'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Carrera</span>
                    <span class="info-value"><?php echo htmlspecialchars($solicitud['carrera_nombre'] ?: '-'); ?></span>
                </div>
            </div>
            
            <!-- Datos Eclesiásticos -->
            <div class="info-section">
                <h4><i class="fas fa-church"></i> Datos Eclesiásticos</h4>
                <div class="info-row">
                    <span class="info-label">¿Es Bautizado?</span>
                    <span class="info-value"><?php echo $solicitud['es_bautizado'] ? 'Sí' : 'No'; ?></span>
                </div>
                <?php if ($solicitud['es_bautizado']): ?>
                    <div class="info-row">
                        <span class="info-label">Fecha de Bautismo</span>
                        <span class="info-value"><?php echo $solicitud['fecha_bautismo'] ? date('d/m/Y', strtotime($solicitud['fecha_bautismo'])) : '-'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Iglesia donde fue bautizado</span>
                        <span class="info-value"><?php echo htmlspecialchars($solicitud['iglesia_bautismo'] ?: '-'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($solicitud['estado'] === 'rechazado' && $solicitud['motivo_rechazo']): ?>
            <div class="alert alert-danger mt-4">
                <strong><i class="fas fa-times-circle"></i> Motivo del rechazo:</strong><br>
                <?php echo nl2br(htmlspecialchars($solicitud['motivo_rechazo'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($solicitud['observaciones']): ?>
            <div class="alert alert-info mt-4">
                <strong><i class="fas fa-sticky-note"></i> Observaciones:</strong><br>
                <?php echo nl2br(htmlspecialchars($solicitud['observaciones'])); ?>
            </div>
        <?php endif; ?>
        
        <div class="meta-info">
            <span><i class="fas fa-calendar"></i> Fecha solicitud: <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></span>
            <?php if ($solicitud['fecha_revision']): ?>
                <span><i class="fas fa-user-check"></i> Revisado por: <?php echo htmlspecialchars($solicitud['revisado_por_nombre']); ?> el <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_revision'])); ?></span>
            <?php endif; ?>
            <?php if ($solicitud['ip_solicitud']): ?>
                <span><i class="fas fa-globe"></i> IP: <?php echo htmlspecialchars($solicitud['ip_solicitud']); ?></span>
            <?php endif; ?>
        </div>
        
        <?php if ($solicitud['estado'] === 'pendiente'): ?>
            <div class="acciones-solicitud">
                <a href="editar.php?id=<?php echo $solicitud['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar Datos
                </a>
                <button type="button" class="btn btn-success"
                   onclick="confirmarAprobar(<?php echo $solicitud['id']; ?>, '<?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido'], ENT_QUOTES); ?>')">
                    <i class="fas fa-check"></i> Aprobar y Crear Miembro
                </button>
                <button type="button" class="btn btn-danger"
                   onclick="confirmarRechazar(<?php echo $solicitud['id']; ?>, '<?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido'], ENT_QUOTES); ?>')">
                    <i class="fas fa-times"></i> Rechazar
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Modal de confirmación para Aprobar
function confirmarAprobar(id, nombreCompleto) {
    const modalHtml = `
    <div class="modal fade" id="modalAprobar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-user-check" style="font-size: 2rem; color: #059669;"></i>
                    </div>
                    <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">¿Aprobar solicitud?</h4>
                    <p style="color: #6b7280; margin-bottom: 0.5rem; line-height: 1.6;">
                        Estás a punto de aprobar la solicitud de:
                    </p>
                    <p style="color: #1f2937; font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem;">
                        ${nombreCompleto}
                    </p>
                    <div style="background: #f0fdf4; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #bbf7d0;">
                        <p style="margin: 0; color: #166534; font-size: 0.9rem;">
                            <i class="fas fa-info-circle me-2"></i>
                            Se creará un nuevo miembro en el sistema con los datos de la solicitud.
                        </p>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            Cancelar
                        </button>
                        <a href="aprobar.php?id=${id}" class="btn btn-success px-4" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-check me-1"></i> Aprobar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalAprobar');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalAprobar'));
    document.getElementById('modalAprobar').addEventListener('hidden.bs.modal', function() { this.remove(); });
    modal.show();
}

// Modal de confirmación para Rechazar
function confirmarRechazar(id, nombreCompleto) {
    const modalHtml = `
    <div class="modal fade" id="modalRechazar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #fee2e2, #fecaca); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-user-times" style="font-size: 2rem; color: #dc2626;"></i>
                    </div>
                    <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">¿Rechazar solicitud?</h4>
                    <p style="color: #6b7280; margin-bottom: 0.5rem; line-height: 1.6;">
                        Estás a punto de rechazar la solicitud de:
                    </p>
                    <p style="color: #1f2937; font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem;">
                        ${nombreCompleto}
                    </p>
                    <div style="background: #fef2f2; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #fecaca;">
                        <p style="margin: 0; color: #991b1b; font-size: 0.9rem;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Se te pedirá un motivo de rechazo en la siguiente pantalla.
                        </p>
                    </div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            Cancelar
                        </button>
                        <a href="rechazar.php?id=${id}" class="btn btn-danger px-4" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-times me-1"></i> Rechazar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalRechazar');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('modalRechazar'));
    document.getElementById('modalRechazar').addEventListener('hidden.bs.modal', function() { this.remove(); });
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
