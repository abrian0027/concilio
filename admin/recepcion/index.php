<?php
/**
 * Recepción de Miembros - Lista de Solicitudes
 * Sistema Concilio - Panel del Pastor
 */

$page_title = "Recepción de Miembros";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden ver esto
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Filtros
$filtro_estado = $_GET['estado'] ?? 'pendiente';
$estados_validos = ['pendiente', 'aprobado', 'rechazado', 'todos'];
if (!in_array($filtro_estado, $estados_validos)) {
    $filtro_estado = 'pendiente';
}

// Construir consulta según rol
$where = "s.iglesia_id = ?";
$params = [$IGLESIA_ID];
$types = "i";

if ($ROL_NOMBRE === 'super_admin') {
    $where = "1=1";
    $params = [];
    $types = "";
}

if ($filtro_estado !== 'todos') {
    $where .= " AND s.estado = ?";
    $params[] = $filtro_estado;
    $types .= "s";
}

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
    WHERE $where
    ORDER BY s.fecha_solicitud DESC
";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result();
$stmt->close();

// Contar por estado
$conteo_sql = "SELECT estado, COUNT(*) as total FROM solicitudes_membresia WHERE iglesia_id = ? GROUP BY estado";
if ($ROL_NOMBRE === 'super_admin') {
    $conteo_sql = "SELECT estado, COUNT(*) as total FROM solicitudes_membresia GROUP BY estado";
}
$stmt_conteo = $conexion->prepare($conteo_sql);
if ($ROL_NOMBRE !== 'super_admin') {
    $stmt_conteo->bind_param("i", $IGLESIA_ID);
}
$stmt_conteo->execute();
$conteos = $stmt_conteo->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_conteo->close();

$totales = ['pendiente' => 0, 'aprobado' => 0, 'rechazado' => 0];
foreach ($conteos as $c) {
    $totales[$c['estado']] = $c['total'];
}

// Obtener enlace de la iglesia para compartir
$enlace_solicitud = '';
if ($IGLESIA_ID > 0) {
    $stmt = $conexion->prepare("SELECT codigo FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $IGLESIA_ID);
    $stmt->execute();
    $iglesia_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($iglesia_data) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $enlace_solicitud = $base_url . "/concilio/solicitud/" . $iglesia_data['codigo'];
    }
}
?>

<style>
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.stat-card.active {
    border-color: var(--primary-color);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 5px;
}

.stat-card.pendiente .stat-number { color: #f59e0b; }
.stat-card.aprobado .stat-number { color: #10b981; }
.stat-card.rechazado .stat-number { color: #ef4444; }

.share-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.share-box h4 {
    margin-bottom: 10px;
}

.share-box .link-container {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.share-box input {
    flex: 1;
    min-width: 200px;
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
}

.share-box .btn {
    white-space: nowrap;
}

.badge-estado {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-pendiente { background: #fef3c7; color: #92400e; }
.badge-aprobado { background: #d1fae5; color: #065f46; }
.badge-rechazado { background: #fee2e2; color: #991b1b; }

.table-responsive {
    overflow-x: auto;
}

.acciones-btn {
    display: inline-flex;
    gap: 5px;
}

.acciones-btn .btn {
    padding: 6px 12px;
    font-size: 0.85rem;
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1><i class="fas fa-inbox"></i> Recepción de Miembros</h1>
            <p class="text-muted mb-0">Solicitudes de membresía enviadas por los miembros</p>
        </div>
        <?php if (in_array($ROL_NOMBRE, ['super_admin', 'pastor'])): ?>
        <a href="configuracion.php" class="btn btn-outline-primary">
            <i class="fas fa-cog"></i> Configuración
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($enlace_solicitud): ?>
<div class="share-box">
    <h4><i class="fas fa-share-alt"></i> Comparte este enlace con tus miembros</h4>
    <p style="opacity: 0.9; margin-bottom: 15px;">Los miembros pueden llenar su solicitud de membresía desde este enlace:</p>
    <div class="link-container">
        <input type="text" id="enlaceSolicitud" value="<?php echo htmlspecialchars($enlace_solicitud); ?>" readonly>
        <button type="button" class="btn btn-light" onclick="copiarEnlace()">
            <i class="fas fa-copy"></i> Copiar
        </button>
        <a href="https://wa.me/?text=<?php echo urlencode("Solicitud de Membresía: " . $enlace_solicitud); ?>" 
           target="_blank" class="btn btn-success">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
    </div>
</div>
<?php endif; ?>

<div class="stats-row">
    <a href="?estado=pendiente" class="stat-card pendiente <?php echo $filtro_estado === 'pendiente' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo $totales['pendiente']; ?></div>
        <div class="stat-label"><i class="fas fa-clock"></i> Pendientes</div>
    </a>
    <a href="?estado=aprobado" class="stat-card aprobado <?php echo $filtro_estado === 'aprobado' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo $totales['aprobado']; ?></div>
        <div class="stat-label"><i class="fas fa-check"></i> Aprobados</div>
    </a>
    <a href="?estado=rechazado" class="stat-card rechazado <?php echo $filtro_estado === 'rechazado' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo $totales['rechazado']; ?></div>
        <div class="stat-label"><i class="fas fa-times"></i> Rechazados</div>
    </a>
    <a href="?estado=todos" class="stat-card <?php echo $filtro_estado === 'todos' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo array_sum($totales); ?></div>
        <div class="stat-label"><i class="fas fa-list"></i> Todos</div>
    </a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-list"></i> 
            Solicitudes <?php echo $filtro_estado !== 'todos' ? ucfirst($filtro_estado) . 's' : ''; ?>
        </span>
    </div>
    <div class="card-body">
        <?php if ($solicitudes->num_rows === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #d1d5db; margin-bottom: 15px;"></i>
                <p class="text-muted">No hay solicitudes <?php echo $filtro_estado !== 'todos' ? $filtro_estado . 's' : ''; ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <?php if ($ROL_NOMBRE === 'super_admin'): ?>
                                <th>Iglesia</th>
                            <?php endif; ?>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sol = $solicitudes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sol['nombre'] . ' ' . $sol['apellido']); ?></strong>
                                    <?php if ($sol['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($sol['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($sol['numero_documento']); ?></td>
                                <td><?php echo htmlspecialchars($sol['telefono'] ?: '-'); ?></td>
                                <?php if ($ROL_NOMBRE === 'super_admin'): ?>
                                    <td><?php echo htmlspecialchars($sol['iglesia_codigo']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge-estado badge-<?php echo $sol['estado']; ?>">
                                        <?php echo ucfirst($sol['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-btn">
                                        <a href="ver.php?id=<?php echo $sol['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($sol['estado'] === 'pendiente'): ?>
                                            <a href="editar.php?id=<?php echo $sol['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-success" title="Aprobar"
                                               onclick="confirmarAprobar(<?php echo $sol['id']; ?>, '<?php echo htmlspecialchars($sol['nombre'] . ' ' . $sol['apellido'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" title="Rechazar"
                                               onclick="confirmarRechazar(<?php echo $sol['id']; ?>, '<?php echo htmlspecialchars($sol['nombre'] . ' ' . $sol['apellido'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copiarEnlace() {
    const input = document.getElementById('enlaceSolicitud');
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Feedback visual
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-light');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-light');
    }, 2000);
}

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
    
    document.getElementById('modalAprobar').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
    
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
    
    document.getElementById('modalRechazar').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
    
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
