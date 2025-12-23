<?php
declare(strict_types=1);

$page_title = "Asignar Líder";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_asignar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_asignar) {
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

// Obtener áreas disponibles (generales + personalizadas)
$stmt = $conexion->prepare("SELECT * FROM areas_ministeriales 
                            WHERE activo = 1 AND (tipo = 'general' OR iglesia_id = ?)
                            ORDER BY nombre");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener miembros de la iglesia
$stmt = $conexion->prepare("SELECT id, nombre, apellido, foto 
                            FROM miembros 
                            WHERE iglesia_id = ? AND estado = 'activo'
                            ORDER BY nombre, apellido");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Área preseleccionada
$area_id_preseleccionada = (int)($_GET['area_id'] ?? 0);
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Asignar Líder</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="fas fa-hands-helping"></i> Asignar Líder para: <?php echo htmlspecialchars($iglesia['nombre']); ?>
        </span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-info mb-4">
            <i class="fas fa-calendar-alt"></i>
            <strong>Período:</strong> <?php echo htmlspecialchars($periodo['nombre']); ?>
            (<?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?> - 
            <?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?>)
        </div>

        <?php if (empty($miembros)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No hay miembros registrados en esta iglesia.
                <a href="../../miembros/crear.php">Registrar miembros</a> primero.
            </div>
        <?php else: ?>
            <form method="post" action="guardar.php">
                <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                <input type="hidden" name="periodo_id" value="<?php echo $periodo['id']; ?>">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-hands-helping"></i> Área Ministerial <span style="color:red;">*</span>
                        </label>
                        <select name="area_id" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?php echo $a['id']; ?>" <?php echo $area_id_preseleccionada == $a['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a['nombre']); ?>
                                    <?php if ($a['tipo'] === 'personalizado'): ?>(Personalizado)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Miembro <span style="color:red;">*</span>
                        </label>
                        <select name="miembro_id" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($miembros as $m): ?>
                                <option value="<?php echo $m['id']; ?>">
                                    <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-tag"></i> Tipo de Liderazgo <span style="color:red;">*</span>
                    </label>
                    <select name="tipo" class="form-control" required>
                        <option value="lider">Líder Principal</option>
                        <option value="colider">Co-líder</option>
                    </select>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Asignar Líder
                    </button>
                    <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>