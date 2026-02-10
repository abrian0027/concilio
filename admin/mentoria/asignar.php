<?php
/**
 * Asignar Mentoría - Sistema Concilio
 * Formulario para crear nueva relación mentor-mentoreado
 * Bootstrap 5 - 100% Responsivo
 */

$page_title = "Nueva Mentoría";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden crear
$puede_crear = in_array($ROL_NOMBRE, ['pastor', 'secretaria']);

if (!$puede_crear) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para realizar esta acción.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$iglesia_id = (int)$IGLESIA_ID;

// Obtener todos los miembros activos (posibles mentores)
$sql_mentores = "SELECT id, nombre, apellido, telefono 
                 FROM miembros 
                 WHERE iglesia_id = ? AND estado = 'activo'
                 ORDER BY nombre, apellido";
$stmt = $conexion->prepare($sql_mentores);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$todos_miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener miembros que NO tienen mentor activo (posibles mentoreados)
$sql_disponibles = "SELECT id, nombre, apellido, telefono 
                    FROM miembros 
                    WHERE iglesia_id = ? AND estado = 'activo'
                    AND id NOT IN (
                        SELECT mentoreado_id FROM mentorias WHERE estado = 'activa'
                    )
                    ORDER BY nombre, apellido";
$stmt = $conexion->prepare($sql_disponibles);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$miembros_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$errores = [];
$exito = false;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mentor_id = isset($_POST['mentor_id']) ? (int)$_POST['mentor_id'] : 0;
    $mentoreado_id = isset($_POST['mentoreado_id']) ? (int)$_POST['mentoreado_id'] : 0;
    $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
    $notas = isset($_POST['notas']) ? trim($_POST['notas']) : '';
    
    // Validaciones
    if ($mentor_id === 0) {
        $errores[] = "Selecciona un mentor";
    }
    if ($mentoreado_id === 0) {
        $errores[] = "Selecciona un mentoreado";
    }
    if ($mentor_id === $mentoreado_id) {
        $errores[] = "El mentor y mentoreado no pueden ser la misma persona";
    }
    if (empty($fecha_inicio)) {
        $errores[] = "La fecha de inicio es requerida";
    }
    
    // Verificar que el mentoreado no tenga ya una mentoría activa
    if ($mentoreado_id > 0) {
        $stmt = $conexion->prepare("SELECT id FROM mentorias WHERE mentoreado_id = ? AND estado = 'activa'");
        $stmt->bind_param("i", $mentoreado_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errores[] = "Este miembro ya tiene un mentor activo asignado";
        }
    }
    
    if (empty($errores)) {
        $stmt = $conexion->prepare("INSERT INTO mentorias (mentor_id, mentoreado_id, fecha_inicio, notas, iglesia_id, creado_por) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissii", $mentor_id, $mentoreado_id, $fecha_inicio, $notas, $iglesia_id, $_SESSION['usuario_id']);
        
        if ($stmt->execute()) {
            header("Location: index.php?exito=" . urlencode("Mentoría creada exitosamente"));
            exit;
        } else {
            $errores[] = "Error al guardar: " . $conexion->error;
        }
    }
}
?>

<style>
    .form-header {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }
    @media (min-width: 768px) {
        .form-header { padding: 20px; }
    }
    .select-member {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .select-member:hover {
        border-color: #6f42c1;
        background-color: #f8f9fa;
    }
    .select-member.selected {
        border-color: #6f42c1;
        background-color: #f3e8ff;
    }
    .avatar-md {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #6c757d;
    }
</style>

<div class="form-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1"><i class="fas fa-hands-helping me-2"></i>Nueva Mentoría</h5>
            <small class="opacity-75">Asigna un mentor a un miembro</small>
        </div>
        <a href="index.php" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<?php if (!empty($errores)): ?>
<div class="alert alert-danger py-2">
    <i class="fas fa-exclamation-circle"></i>
    <?php echo implode('<br>', $errores); ?>
</div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row g-3">
        <!-- Mentor -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white py-2">
                    <i class="fas fa-user-tie me-1"></i> Mentor (quien guía)
                </div>
                <div class="card-body">
                    <select name="mentor_id" id="mentor_id" class="form-select form-select-lg" required>
                        <option value="">-- Seleccionar mentor --</option>
                        <?php foreach ($todos_miembros as $m): ?>
                        <option value="<?php echo $m['id']; ?>" 
                                <?php echo (isset($_POST['mentor_id']) && $_POST['mentor_id'] == $m['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">
                        <i class="fas fa-info-circle"></i> Cualquier miembro puede ser mentor
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mentoreado -->
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white py-2">
                    <i class="fas fa-user-graduate me-1"></i> Mentoreado (quien recibe guía)
                </div>
                <div class="card-body">
                    <select name="mentoreado_id" id="mentoreado_id" class="form-select form-select-lg" required>
                        <option value="">-- Seleccionar mentoreado --</option>
                        <?php foreach ($miembros_disponibles as $m): ?>
                        <option value="<?php echo $m['id']; ?>"
                                <?php echo (isset($_POST['mentoreado_id']) && $_POST['mentoreado_id'] == $m['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">
                        <i class="fas fa-info-circle"></i> Solo miembros sin mentor activo (<?php echo count($miembros_disponibles); ?> disponibles)
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fecha y Notas -->
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <label for="fecha_inicio" class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Inicio
                    </label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-d'); ?>" required>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <label for="notas" class="form-label">
                        <i class="fas fa-sticky-note"></i> Notas Iniciales (opcional)
                    </label>
                    <textarea class="form-control" id="notas" name="notas" rows="2" 
                              placeholder="Ej: Motivo de la mentoría, objetivos..."><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : ''; ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Botones -->
        <div class="col-12">
            <div class="d-flex gap-2 justify-content-end">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar Mentoría
                </button>
            </div>
        </div>
    </div>
</form>

<script>
// Evitar que mentor y mentoreado sean iguales
document.getElementById('mentor_id').addEventListener('change', function() {
    const mentorId = this.value;
    const mentoreado = document.getElementById('mentoreado_id');
    
    // Habilitar todas las opciones primero
    Array.from(mentoreado.options).forEach(opt => opt.disabled = false);
    
    // Deshabilitar la opción que coincide con el mentor
    if (mentorId) {
        const optToDisable = mentoreado.querySelector('option[value="' + mentorId + '"]');
        if (optToDisable) {
            optToDisable.disabled = true;
            if (mentoreado.value === mentorId) {
                mentoreado.value = '';
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
