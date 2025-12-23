<?php
/**
 * Lista de Líderes de Ministerios por Conferencia
 * Sistema Concilio - Diseño AdminLTE
 */

$page_title = "Líderes de Ministerios";
require_once __DIR__ . '/../includes/header.php';

// Verificar permisos
$roles_permitidos = array('super_admin', 'obispo', 'super_conferencia');
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $roles_permitidos)) {
    header('Location: ../panel_generico.php?error=' . urlencode('Sin permisos'));
    exit;
}

$es_super_admin = ($ROL_NOMBRE === 'super_admin' || $ROL_NOMBRE === 'obispo');

// Filtro de conferencia
$conferencia_filtro = isset($_GET['conferencia']) ? (int)$_GET['conferencia'] : 0;

// Si es super_conferencia, solo ve su conferencia
if ($ROL_NOMBRE === 'super_conferencia') {
    $conferencia_filtro = $_SESSION['conferencia_id'] ?? 0;
}

// Obtener conferencias para filtro
$conferencias = array();
if ($es_super_admin) {
    $result = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $conferencias[] = $row;
        }
    }
}

// Obtener ministerios
$ministerios = array();
$result = $conexion->query("SELECT id, nombre, descripcion FROM ministerios WHERE activo = 1 ORDER BY nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ministerios[] = $row;
    }
}

// Obtener líderes por ministerio
$lideres_por_ministerio = array();
$sql_lideres = "SELECT * FROM v_lideres_ministerio_conferencia WHERE activo = 1";
if ($conferencia_filtro > 0) {
    $sql_lideres .= " AND conferencia_id = " . (int)$conferencia_filtro;
}

$result = $conexion->query($sql_lideres);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $min_id = $row['ministerio_id'];
        if (!isset($lideres_por_ministerio[$min_id])) {
            $lideres_por_ministerio[$min_id] = array();
        }
        $lideres_por_ministerio[$min_id][] = $row;
    }
}

// Obtener nombre de conferencia filtrada
$conferencia_nombre = '';
if ($conferencia_filtro > 0) {
    $stmt = $conexion->prepare("SELECT nombre FROM conferencias WHERE id = ?");
    $stmt->bind_param("i", $conferencia_filtro);
    $stmt->execute();
    $conf = $stmt->get_result()->fetch_assoc();
    $conferencia_nombre = $conf['nombre'] ?? '';
    $stmt->close();
}
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtro de Conferencia -->
<?php if ($es_super_admin && count($conferencias) > 0): ?>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label"><i class="fas fa-globe-americas me-1"></i>Filtrar por Conferencia</label>
                <select name="conferencia" class="form-select" onchange="this.form.submit()">
                    <option value="0">-- Todas las conferencias --</option>
                    <?php foreach ($conferencias as $conf): ?>
                        <option value="<?php echo $conf['id']; ?>" <?php echo $conferencia_filtro == $conf['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($conferencia_filtro > 0): ?>
            <div class="col-md-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Limpiar filtro
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Alerta si no hay conferencia seleccionada -->
<?php if ($es_super_admin && $conferencia_filtro == 0): ?>
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Seleccione una conferencia para ver y gestionar los líderes de ministerios.
</div>
<?php endif; ?>

<!-- Lista de Ministerios con sus Líderes -->
<?php if ($conferencia_filtro > 0 || !$es_super_admin): ?>

<?php if ($conferencia_nombre): ?>
<div class="alert alert-primary mb-4">
    <i class="fas fa-globe-americas me-2"></i>
    <strong>Conferencia:</strong> <?php echo htmlspecialchars($conferencia_nombre); ?>
</div>
<?php endif; ?>

<div class="row">
    <?php foreach ($ministerios as $min): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users text-info me-2"></i>
                    <?php echo htmlspecialchars($min['nombre']); ?>
                </h5>
                <a href="asignar.php?conferencia=<?php echo $conferencia_filtro; ?>&ministerio=<?php echo $min['id']; ?>" 
                   class="btn btn-sm btn-info" title="Asignar líder">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (isset($lideres_por_ministerio[$min['id']]) && count($lideres_por_ministerio[$min['id']]) > 0): ?>
                    <ul class="list-group list-group-flush">
                    <?php foreach ($lideres_por_ministerio[$min['id']] as $lider): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-<?php 
                                        echo $lider['cargo'] == 'presidente' ? 'primary' : 
                                            ($lider['cargo'] == 'vicepresidente' ? 'info' : 
                                            ($lider['cargo'] == 'secretario' ? 'success' : 
                                            ($lider['cargo'] == 'tesorero' ? 'warning' : 'secondary'))); 
                                    ?> mb-1" style="font-size: 0.7rem;">
                                        <?php echo ucfirst($lider['cargo']); ?>
                                    </span>
                                    <div class="fw-bold"><?php echo htmlspecialchars($lider['lider_nombre']); ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-church me-1"></i><?php echo htmlspecialchars($lider['iglesia_origen'] ?? 'Sin iglesia'); ?>
                                    </small>
                                    <?php if (!empty($lider['lider_telefono'])): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($lider['lider_telefono']); ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item text-danger" 
                                               href="quitar.php?id=<?php echo $lider['id']; ?>"
                                               onclick="return confirm('¿Remover este líder?');">
                                                <i class="fas fa-times me-2"></i>Quitar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="p-3 text-muted text-center">
                        <i class="fas fa-user-slash me-2"></i>Sin líderes asignados
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="ver.php?conferencia=<?php echo $conferencia_filtro; ?>&ministerio=<?php echo $min['id']; ?>" 
                   class="btn btn-sm btn-outline-secondary w-100">
                    <i class="fas fa-eye me-1"></i>Ver líderes por iglesia
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
