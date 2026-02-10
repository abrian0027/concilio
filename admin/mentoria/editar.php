<?php
/**
 * Editar Mentoría - Sistema Concilio
 * Bootstrap 5 - 100% Responsivo
 */

$page_title = "Editar Mentoría";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden editar
$puede_editar = in_array($ROL_NOMBRE, ['pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para realizar esta acción.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$iglesia_id = (int)$IGLESIA_ID;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener datos de la mentoría
$stmt = $conexion->prepare("
    SELECT m.*, 
           mentor.nombre AS mentor_nombre, mentor.apellido AS mentor_apellido,
           mentoreado.nombre AS mentoreado_nombre, mentoreado.apellido AS mentoreado_apellido
    FROM mentorias m
    INNER JOIN miembros mentor ON m.mentor_id = mentor.id
    INNER JOIN miembros mentoreado ON m.mentoreado_id = mentoreado.id
    WHERE m.id = ? AND m.iglesia_id = ?
");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$mentoria = $stmt->get_result()->fetch_assoc();

if (!$mentoria) {
    header("Location: index.php?error=Mentoría no encontrada");
    exit;
}

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
    $estado = trim($_POST['estado'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    
    if (empty($fecha_inicio) || empty($estado)) {
        $mensaje = "La fecha de inicio y el estado son obligatorios";
        $tipo_mensaje = "danger";
    } else {
        // Si el estado es finalizada y no hay fecha fin, poner hoy
        if ($estado === 'finalizada' && empty($fecha_fin)) {
            $fecha_fin = date('Y-m-d');
        }
        
        $stmt = $conexion->prepare("
            UPDATE mentorias SET 
                fecha_inicio = ?, 
                fecha_fin = ?, 
                estado = ?, 
                notas = ?
            WHERE id = ? AND iglesia_id = ?
        ");
        $stmt->bind_param("ssssii", $fecha_inicio, $fecha_fin, $estado, $notas, $id, $iglesia_id);
        
        if ($stmt->execute()) {
            header("Location: ver.php?id=$id&exito=" . urlencode("Mentoría actualizada correctamente"));
            exit;
        } else {
            $mensaje = "Error al actualizar: " . $conexion->error;
            $tipo_mensaje = "danger";
        }
    }
}
?>

<style>
    .edit-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .edit-card .card-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem;
    }
    .participants-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
    }
    .participant-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
    }
    .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    @media (max-width: 576px) {
        .participant-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        .action-buttons .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>

<!-- Botón regresar -->
<div class="mb-3">
    <a href="ver.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Regresar
    </a>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
    <?= htmlspecialchars($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Participantes (solo lectura) -->
<div class="card edit-card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Participantes</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="participants-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="participant-avatar me-3">
                            <?= strtoupper(substr($mentoria['mentor_nombre'], 0, 1) . substr($mentoria['mentor_apellido'], 0, 1)) ?>
                        </div>
                        <div>
                            <small class="text-muted d-block">Mentor</small>
                            <strong><?= htmlspecialchars($mentoria['mentor_nombre'] . ' ' . $mentoria['mentor_apellido']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="participants-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="participant-avatar me-3" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                            <?= strtoupper(substr($mentoria['mentoreado_nombre'], 0, 1) . substr($mentoria['mentoreado_apellido'], 0, 1)) ?>
                        </div>
                        <div>
                            <small class="text-muted d-block">Mentoreado</small>
                            <strong><?= htmlspecialchars($mentoria['mentoreado_nombre'] . ' ' . $mentoria['mentoreado_apellido']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="fas fa-info-circle me-1"></i>
            Los participantes no pueden modificarse. Si necesitas cambiarlos, finaliza esta mentoría y crea una nueva.
        </small>
    </div>
</div>

<!-- Formulario de edición -->
<div class="card edit-card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Configuración</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <label for="fecha_inicio" class="form-label">
                        <i class="fas fa-calendar me-1"></i>Fecha de Inicio *
                    </label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?= htmlspecialchars($mentoria['fecha_inicio']) ?>" required>
                </div>
                
                <div class="col-12 col-md-4 mb-3">
                    <label for="fecha_fin" class="form-label">
                        <i class="fas fa-calendar-check me-1"></i>Fecha de Fin
                    </label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?= htmlspecialchars($mentoria['fecha_fin'] ?? '') ?>">
                    <small class="text-muted">Solo si está finalizada</small>
                </div>
                
                <div class="col-12 col-md-4 mb-3">
                    <label for="estado" class="form-label">
                        <i class="fas fa-toggle-on me-1"></i>Estado *
                    </label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="activa" <?= $mentoria['estado'] === 'activa' ? 'selected' : '' ?>>
                            🟢 Activa
                        </option>
                        <option value="pausada" <?= $mentoria['estado'] === 'pausada' ? 'selected' : '' ?>>
                            🟡 Pausada
                        </option>
                        <option value="finalizada" <?= $mentoria['estado'] === 'finalizada' ? 'selected' : '' ?>>
                            🔴 Finalizada
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notas" class="form-label">
                    <i class="fas fa-sticky-note me-1"></i>Notas Generales
                </label>
                <textarea class="form-control" id="notas" name="notas" rows="4" 
                          placeholder="Objetivos de la mentoría, áreas de enfoque, observaciones..."><?= htmlspecialchars($mentoria['notas'] ?? '') ?></textarea>
                <small class="text-muted">Opcional: describe el propósito o enfoque de esta mentoría</small>
            </div>
            
            <hr>
            
            <div class="action-buttons d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Guardar Cambios
                </button>
                <a href="ver.php?id=<?= $id ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Cuando se selecciona "finalizada", poner fecha de hoy si está vacía
    document.getElementById('estado').addEventListener('change', function() {
        const fechaFin = document.getElementById('fecha_fin');
        if (this.value === 'finalizada' && !fechaFin.value) {
            fechaFin.value = new Date().toISOString().split('T')[0];
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
