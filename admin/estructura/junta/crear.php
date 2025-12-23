<?php
declare(strict_types=1);

$page_title = "Crear Junta Administrativa";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_crear) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Determinar iglesia
if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

if ($iglesia_id === 0) {
    header("Location: index.php?error=Debe seleccionar una iglesia");
    exit;
}

// Obtener iglesia
$stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$iglesia) {
    header("Location: index.php?error=Iglesia no encontrada");
    exit;
}

// Verificar período activo
$stmt = $conexion->prepare("SELECT * FROM periodos_iglesia WHERE iglesia_id = ? AND activo = 1 LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$periodo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$periodo) {
    header("Location: index.php?iglesia_id=$iglesia_id&error=Debe crear un período activo primero");
    exit;
}

// Verificar que no exista junta activa
$stmt = $conexion->prepare("SELECT id FROM juntas WHERE iglesia_id = ? AND activa = 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: index.php?iglesia_id=$iglesia_id&error=Ya existe una junta activa para esta iglesia");
    exit;
}
$stmt->close();
?>

<!-- Header -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark">
            <i class="fas fa-plus-circle text-warning me-2"></i>Crear Junta Administrativa
        </h1>
        <p class="text-muted small mb-0">Nueva junta para: <strong><?php echo htmlspecialchars($iglesia['nombre']); ?></strong></p>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-light border mb-4">
            <i class="fas fa-info-circle text-primary me-2"></i> 
            <strong>Período:</strong> <?php echo htmlspecialchars($periodo['nombre']); ?>
            <span class="text-muted">
                (<?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?>)
            </span>
        </div>

        <form method="post" action="guardar.php">
            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
            <input type="hidden" name="periodo_id" value="<?php echo $periodo['id']; ?>">
            
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-users text-warning me-1"></i>Tipo de Junta <span class="text-danger">*</span>
                </label>
                <select name="tipo" class="form-select" required>
                    <option value="5">5 Miembros (+ Pastor/es)</option>
                    <option value="7">7 Miembros (+ Pastor/es)</option>
                </select>
                <small class="text-muted">El pastor preside la junta pero no cuenta dentro de los 5 o 7 miembros.</small>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Junta
                </button>
                <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>