<?php
/**
 * Listado de Zonas/Grupos - Sistema Concilio
 * Módulo dentro de Estructura
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Zonas / Grupos";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Solo pastor, secretaria o super_admin pueden ver
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Permisos
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);
$puede_eliminar = in_array($ROL_NOMBRE, ['super_admin', 'pastor']);

// Filtrar por iglesia
if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_filtro = isset($_GET['iglesia_id']) ? (int)$_GET['iglesia_id'] : 0;
} else {
    $iglesia_filtro = (int)$IGLESIA_ID;
}

// Obtener zonas con conteo de miembros
$sql = "SELECT z.*, 
               COUNT(m.id) AS total_miembros,
               i.nombre AS iglesia_nombre
        FROM zonas z
        LEFT JOIN miembros m ON m.zona_id = z.id AND m.estado = 'activo'
        LEFT JOIN iglesias i ON i.id = z.iglesia_id
        WHERE z.activo = 1";

if ($iglesia_filtro > 0) {
    $sql .= " AND z.iglesia_id = " . $iglesia_filtro;
}

$sql .= " GROUP BY z.id ORDER BY z.codigo ASC";

$result = $conexion->query($sql);
$zonas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total_zonas = count($zonas);

// Estadísticas
$total_miembros_en_zonas = 0;
foreach ($zonas as $z) {
    $total_miembros_en_zonas += $z['total_miembros'];
}

// Mensajes
$mensaje_exito = isset($_GET['exito']) ? $_GET['exito'] : '';
$mensaje_error = isset($_GET['error']) ? $_GET['error'] : '';

// Para super_admin: lista de iglesias
$iglesias = [];
if ($ROL_NOMBRE === 'super_admin') {
    $result_ig = $conexion->query("SELECT id, nombre FROM iglesias WHERE activo = 1 ORDER BY nombre");
    if ($result_ig) {
        $iglesias = $result_ig->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<style>
    .card-zona {
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        border-radius: 12px;
    }
    .card-zona:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .zona-codigo {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
    }
    .zona-nombre {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }
    .zona-stats {
        font-size: 2rem;
        font-weight: 700;
        color: #0d6efd;
    }
    .btn-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
</style>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="mb-0"><i class="fas fa-map-marker-alt text-primary"></i> Zonas / Grupos</h1>
            <small class="text-muted">Organiza tu iglesia por zonas geográficas o grupos</small>
        </div>
        <?php if ($puede_crear): ?>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Nueva Zona</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($mensaje_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
<!-- Filtro por iglesia para super_admin -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">Filtrar por iglesia:</label>
            </div>
            <div class="col-md-4">
                <select name="iglesia_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- Todas las iglesias --</option>
                    <?php foreach ($iglesias as $ig): ?>
                    <option value="<?php echo $ig['id']; ?>" <?php echo $iglesia_filtro == $ig['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ig['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h3 mb-0"><?php echo $total_zonas; ?></div>
                        <small class="opacity-75">Total Zonas</small>
                    </div>
                    <i class="fas fa-map-marker-alt fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h3 mb-0"><?php echo $total_miembros_en_zonas; ?></div>
                        <small class="opacity-75">Miembros en Zonas</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h3 mb-0"><?php echo $total_zonas > 0 ? round($total_miembros_en_zonas / $total_zonas, 1) : 0; ?></div>
                        <small class="opacity-75">Promedio por Zona</small>
                    </div>
                    <i class="fas fa-chart-pie fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Zonas -->
<?php if ($total_zonas === 0): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">No hay zonas registradas</h5>
        <p class="text-muted">Crea zonas para organizar a los miembros de tu iglesia por ubicación geográfica o grupos.</p>
        <?php if ($puede_crear): ?>
        <a href="crear.php" class="btn btn-primary mt-2">
            <i class="fas fa-plus"></i> Crear Primera Zona
        </a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($zonas as $zona): ?>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-zona h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="zona-codigo"><?php echo htmlspecialchars($zona['codigo']); ?></span>
                        <h5 class="zona-nombre mb-1"><?php echo htmlspecialchars($zona['nombre']); ?></h5>
                        <?php if ($ROL_NOMBRE === 'super_admin' && $iglesia_filtro === 0): ?>
                        <small class="text-muted"><i class="fas fa-church"></i> <?php echo htmlspecialchars($zona['iglesia_nombre']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="zona-stats">
                        <?php echo $zona['total_miembros']; ?>
                    </div>
                </div>
                
                <?php if ($zona['descripcion']): ?>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($zona['descripcion'], 0, 80)); ?><?php echo strlen($zona['descripcion']) > 80 ? '...' : ''; ?></p>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-users"></i> <?php echo $zona['total_miembros']; ?> miembros
                    </span>
                    <div class="btn-group">
                        <a href="ver.php?id=<?php echo $zona['id']; ?>" class="btn btn-sm btn-outline-primary btn-action" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($puede_editar): ?>
                        <a href="editar.php?id=<?php echo $zona['id']; ?>" class="btn btn-sm btn-outline-secondary btn-action" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
