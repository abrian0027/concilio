<?php
declare(strict_types=1);

$page_title = "Asignar Miembro a Junta";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_asignar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_asignar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$junta_id = (int)($_GET['junta_id'] ?? 0);

if ($junta_id === 0) {
    header("Location: index.php?error=ID de junta no válido");
    exit;
}

// Obtener junta
$stmt = $conexion->prepare("SELECT j.*, i.nombre AS iglesia_nombre, p.nombre AS periodo_nombre 
                            FROM juntas j 
                            INNER JOIN iglesias i ON i.id = j.iglesia_id 
                            INNER JOIN periodos_iglesia p ON p.id = j.periodo_id 
                            WHERE j.id = ? AND j.activa = 1");
$stmt->bind_param("i", $junta_id);
$stmt->execute();
$junta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$junta) {
    header("Location: index.php?error=Junta no encontrada o no está activa");
    exit;
}

$iglesia_id = $junta['iglesia_id'];

// Verificar acceso
if ($ROL_NOMBRE !== 'super_admin' && $iglesia_id != $IGLESIA_ID) {
    header("Location: index.php?error=No tienes permiso");
    exit;
}

// Obtener cargos
$cargos = $conexion->query("SELECT * FROM cargos_junta WHERE activo = 1 ORDER BY orden");

// Obtener miembros de la iglesia que NO están en la junta actual
$stmt = $conexion->prepare("SELECT m.id, m.nombre, m.apellido, m.foto 
                            FROM miembros m 
                            WHERE m.iglesia_id = ? AND m.estado = 'activo'
                            AND m.id NOT IN (SELECT miembro_id FROM junta_miembros WHERE junta_id = ? AND activo = 1)
                            ORDER BY m.nombre, m.apellido");
$stmt->bind_param("ii", $iglesia_id, $junta_id);
$stmt->execute();
$miembros_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Contar miembros actuales (sin pastores)
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM junta_miembros WHERE junta_id = ? AND es_pastor = 0 AND activo = 1");
$stmt->bind_param("i", $junta_id);
$stmt->execute();
$conteo = $stmt->get_result()->fetch_assoc();
$miembros_actuales = $conteo['total'];
$stmt->close();

$limite_alcanzado = $miembros_actuales >= (int)$junta['tipo'];
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Asignar Miembro a Junta</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">
                <i class="fas fa-users-cog text-warning me-2"></i><?php echo htmlspecialchars($junta['iglesia_nombre']); ?> - <?php echo htmlspecialchars($junta['periodo_nombre']); ?>
            </h6>
            <span class="badge bg-info bg-opacity-10 text-info border border-info">
                Junta de <?php echo $junta['tipo']; ?> miembros
            </span>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0">
                <i class="fas fa-check-circle me-2"></i>Junta creada. Ahora agregue los miembros.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i> 
            <strong>Miembros asignados:</strong> <?php echo $miembros_actuales; ?> / <?php echo $junta['tipo']; ?>
            <br><small>Los pastores no cuentan en este límite.</small>
        </div>

        <?php if (empty($miembros_disponibles)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No hay miembros disponibles para agregar. 
                Primero debe <a href="../../miembros/crear.php">registrar miembros</a> en la iglesia.
            </div>
        <?php else: ?>
            <form method="post" action="guardar_miembro.php">
                <input type="hidden" name="junta_id" value="<?php echo $junta_id; ?>">
                <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Miembro <span style="color:red;">*</span>
                        </label>
                        <select name="miembro_id" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($miembros_disponibles as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-id-badge"></i> Cargo <span style="color:red;">*</span>
                        </label>
                        <select name="cargo_id" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            <?php while ($c = $cargos->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-star"></i> ¿Es Pastor/Presidente?
                    </label>
                    <select name="es_pastor" class="form-control">
                        <option value="0">No - Cuenta como miembro de los <?php echo $junta['tipo']; ?></option>
                        <option value="1">Sí - Es Pastor (No cuenta en los <?php echo $junta['tipo']; ?>)</option>
                    </select>
                    <small class="text-muted">El pastor preside la junta pero no cuenta dentro del límite de miembros.</small>
                </div>

                <?php if ($limite_alcanzado): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Se alcanzó el límite de <?php echo $junta['tipo']; ?> miembros. Solo puede agregar pastores.
                    </div>
                <?php endif; ?>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Agregar a la Junta
                    </button>
                    <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>