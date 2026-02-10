<?php
require_once __DIR__ . '/../includes/header.php';

// Verificar permisos
if (!in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria', 'lider_ministerio'])) {
    header('Location: ../dashboard.php');
    exit;
}

// Obtener ID de mentoría
$mentoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$mentoria_id) {
    header('Location: index.php');
    exit;
}

// Obtener datos de la mentoría
$query = "SELECT m.*, 
          mentor.nombre as mentor_nombre, mentor.apellido as mentor_apellido, mentor.telefono as mentor_telefono,
          mentoreado.nombre as mentoreado_nombre, mentoreado.apellido as mentoreado_apellido, mentoreado.telefono as mentoreado_telefono,
          u.nombre as creador_nombre
          FROM mentorias m
          INNER JOIN miembros mentor ON m.mentor_id = mentor.id
          INNER JOIN miembros mentoreado ON m.mentoreado_id = mentoreado.id
          LEFT JOIN usuarios u ON m.creado_por = u.id
          WHERE m.id = ? AND m.iglesia_id = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $mentoria_id, $IGLESIA_ID);
$stmt->execute();
$result = $stmt->get_result();
$mentoria = $result->fetch_assoc();

if (!$mentoria) {
    header('Location: index.php');
    exit;
}

// Obtener seguimientos
$query_seg = "SELECT s.*, u.nombre as registrador_nombre
              FROM mentoria_seguimientos s
              LEFT JOIN usuarios u ON s.registrado_por = u.id
              WHERE s.mentoria_id = ?
              ORDER BY s.fecha_reunion DESC";
$stmt_seg = $conexion->prepare($query_seg);
$stmt_seg->bind_param("i", $mentoria_id);
$stmt_seg->execute();
$seguimientos = $stmt_seg->get_result();

// Badge de estado
function getBadgeClass($estado) {
    switch($estado) {
        case 'activa': return 'bg-success';
        case 'pausada': return 'bg-warning text-dark';
        case 'finalizada': return 'bg-secondary';
        default: return 'bg-primary';
    }
}

// Generar iniciales
function getIniciales($nombre, $apellido) {
    return strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
}
?>

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.mentoria-header {
    background: var(--primary-gradient);
    color: white;
    padding: 1.5rem;
    border-radius: 1rem;
    margin-bottom: 1.5rem;
}

.person-card {
    background: white;
    border-radius: 1rem;
    padding: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    transition: transform 0.2s;
}

.person-card:hover {
    transform: translateY(-2px);
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: white;
    flex-shrink: 0;
}

.mentor-avatar {
    background: var(--success-gradient);
}

.mentoreado-avatar {
    background: var(--warning-gradient);
}

.person-info {
    flex: 1;
    min-width: 0;
}

.person-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.person-role {
    color: #6c757d;
    font-size: 0.85rem;
}

.whatsapp-btn {
    background: #25D366;
    color: white;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.whatsapp-btn:hover {
    background: #128C7E;
    color: white;
    transform: scale(1.02);
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 0.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-gradient);
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1rem;
}

.timeline-content h6 {
    color: #495057;
    margin-bottom: 0.5rem;
}

.fab-button {
    position: fixed;
    bottom: 1.5rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: #28a745;
    color: white;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    font-weight: 500;
    z-index: 1000;
}

.fab-button:hover {
    background: #218838;
    color: white;
}

.notas-section {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 1rem;
    border-radius: 0 0.5rem 0.5rem 0;
    margin-bottom: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.action-buttons .btn {
    flex: 1;
    min-width: 120px;
}

@media (max-width: 576px) {
    .mentoria-header {
        padding: 1rem;
    }
    
    .avatar {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .person-name {
        font-size: 1rem;
    }
    
    .timeline {
        padding-left: 1.5rem;
    }
    
    .fab-button {
        bottom: 1.5rem;
        right: 1.5rem;
    }
}
</style>

<div class="container-fluid py-3">
    <?php if (isset($_GET['exito'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php 
        $msg = $_GET['exito'];
        if ($msg == 'seguimiento') echo 'Seguimiento registrado exitosamente.';
        elseif ($msg == 'estado') echo 'Estado actualizado exitosamente.';
        else echo 'Operación realizada exitosamente.';
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Header de Mentoría -->
    <div class="mentoria-header">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <a href="index.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <span class="badge <?php echo getBadgeClass($mentoria['estado']); ?> fs-6">
                <?php echo ucfirst($mentoria['estado']); ?>
            </span>
        </div>
        
        <h4 class="mb-1"><i class="bi bi-people-fill me-2"></i>Relación de Mentoría</h4>
        <small class="opacity-75">
            Iniciada el <?php echo date('d/m/Y', strtotime($mentoria['fecha_inicio'])); ?>
            <?php if ($mentoria['creador_nombre']): ?>
            por <?php echo htmlspecialchars($mentoria['creador_nombre']); ?>
            <?php endif; ?>
        </small>
    </div>

    <!-- Cards de Personas -->
    <div class="row g-3 mb-4">
        <!-- Mentor -->
        <div class="col-12 col-md-6">
            <div class="person-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar mentor-avatar">
                        <?php echo getIniciales($mentoria['mentor_nombre'], $mentoria['mentor_apellido']); ?>
                    </div>
                    <div class="person-info">
                        <div class="person-name"><?php echo htmlspecialchars($mentoria['mentor_nombre'] . ' ' . $mentoria['mentor_apellido']); ?></div>
                        <div class="person-role"><i class="bi bi-star-fill text-warning me-1"></i>Mentor</div>
                    </div>
                </div>
                <?php if ($mentoria['mentor_telefono']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $mentoria['mentor_telefono']); ?>" 
                   target="_blank" class="whatsapp-btn w-100 justify-content-center">
                    <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mentoreado -->
        <div class="col-12 col-md-6">
            <div class="person-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar mentoreado-avatar">
                        <?php echo getIniciales($mentoria['mentoreado_nombre'], $mentoria['mentoreado_apellido']); ?>
                    </div>
                    <div class="person-info">
                        <div class="person-name"><?php echo htmlspecialchars($mentoria['mentoreado_nombre'] . ' ' . $mentoria['mentoreado_apellido']); ?></div>
                        <div class="person-role"><i class="bi bi-person-fill text-info me-1"></i>Mentoreado</div>
                    </div>
                </div>
                <?php if ($mentoria['mentoreado_telefono']): ?>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $mentoria['mentoreado_telefono']); ?>" 
                   target="_blank" class="whatsapp-btn w-100 justify-content-center">
                    <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notas -->
    <?php if (!empty($mentoria['notas'])): ?>
    <div class="notas-section">
        <h6 class="mb-2"><i class="bi bi-sticky me-2"></i>Notas</h6>
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($mentoria['notas'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Seguimientos -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Bitácora de Seguimientos</h5>
                <?php if ($mentoria['estado'] == 'activa'): ?>
                <button class="btn btn-primary btn-sm d-none d-md-inline-flex" data-bs-toggle="modal" data-bs-target="#modalSeguimiento">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if ($seguimientos->num_rows > 0): ?>
            <div class="timeline">
                <?php while ($seg = $seguimientos->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?php echo date('d/m/Y', strtotime($seg['fecha_reunion'])); ?>
                        <?php if ($seg['registrador_nombre']): ?>
                        <span class="text-muted">· <?php echo htmlspecialchars($seg['registrador_nombre']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-content">
                        <h6><i class="bi bi-chat-left-text me-2"></i>Descripción</h6>
                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($seg['descripcion'])); ?></p>
                        
                        <?php if (!empty($seg['proximos_pasos'])): ?>
                        <h6 class="mt-3"><i class="bi bi-arrow-right-circle me-2"></i>Próximos Pasos</h6>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($seg['proximos_pasos'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <h5>Sin seguimientos registrados</h5>
                <p class="mb-0">Aún no hay reuniones de seguimiento registradas para esta mentoría.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="action-buttons mb-5">
        <a href="editar.php?id=<?php echo $mentoria_id; ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <?php if ($mentoria['estado'] == 'activa'): ?>
        <form action="cambiar_estado.php" method="POST" class="d-inline flex-fill" 
              onsubmit="return confirm('¿Está seguro de finalizar esta mentoría?');">
            <input type="hidden" name="mentoria_id" value="<?php echo $mentoria_id; ?>">
            <input type="hidden" name="nuevo_estado" value="finalizada">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="bi bi-check-circle me-1"></i> Finalizar
            </button>
        </form>
        <?php elseif ($mentoria['estado'] == 'finalizada'): ?>
        <form action="cambiar_estado.php" method="POST" class="d-inline flex-fill">
            <input type="hidden" name="mentoria_id" value="<?php echo $mentoria_id; ?>">
            <input type="hidden" name="nuevo_estado" value="activa">
            <button type="submit" class="btn btn-outline-success w-100">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reactivar
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Botón fijo para móvil -->
<?php if ($mentoria['estado'] == 'activa'): ?>
<button class="fab-button d-md-none" data-bs-toggle="modal" data-bs-target="#modalSeguimiento">
    <i class="bi bi-plus-circle-fill"></i> Agregar Seguimiento
</button>
<?php endif; ?>

<!-- Modal para agregar seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="guardar_seguimiento.php" method="POST">
                <input type="hidden" name="mentoria_id" value="<?php echo $mentoria_id; ?>">
                
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nuevo Seguimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fecha de Reunión <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_reunion" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <textarea name="descripcion" class="form-control" rows="4" 
                                  placeholder="¿Qué temas trataron? ¿Cómo estuvo la reunión?" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Próximos Pasos</label>
                        <textarea name="proximos_pasos" class="form-control" rows="3" 
                                  placeholder="¿Qué compromisos o tareas quedaron pendientes?"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
