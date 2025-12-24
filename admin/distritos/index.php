<?php
declare(strict_types=1);

$page_title = "Gestión de Distritos";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin puede ver esto
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Filtro por conferencia
$filtro_conferencia = filter_input(INPUT_GET, 'conferencia_id', FILTER_VALIDATE_INT);

// Si es superintendente de conferencia, forzar su conferencia
$es_super_conferencia = ($ROL_NOMBRE === 'super_conferencia');
if ($es_super_conferencia && isset($_SESSION['conferencia_id'])) {
    $filtro_conferencia = (int)$_SESSION['conferencia_id'];
}

// Obtener conferencias para el filtro
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");

// Obtener todos los distritos con su conferencia y supervisor
try {
    $sql = "SELECT d.*, 
                   c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo,
                   p.nombre AS sup_nombre, p.apellido AS sup_apellido,
                   p.cedula AS sup_cedula, p.orden_ministerial,
                   (SELECT COUNT(*) FROM iglesias i WHERE i.distrito_id = d.id AND i.activo = 1) AS total_iglesias
            FROM distritos d
            INNER JOIN conferencias c ON d.conferencia_id = c.id
            LEFT JOIN pastores p ON d.supervisor_id = p.id
            WHERE 1=1";
    
    if ($filtro_conferencia) {
        $sql .= " AND d.conferencia_id = " . (int)$filtro_conferencia;
    }
    
    $sql .= " ORDER BY c.nombre, d.codigo";
    $resultado = $conexion->query($sql);
} catch (Exception $e) {
    error_log("Error al obtener distritos: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar los distritos.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$total_distritos = $resultado ? $resultado->num_rows : 0;
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-map-marked-alt"></i> Gestión de Distritos</h1>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Distrito
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body" style="padding: 1rem;">
        <form method="get" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div>
                <label class="form-label" style="margin-bottom: 0.25rem;">Filtrar por Conferencia:</label>
                <select name="conferencia_id" class="form-control" onchange="this.form.submit()" style="min-width: 250px;">
                    <option value="">-- Todas las Conferencias --</option>
                    <?php 
                    $conferencias->data_seek(0);
                    while ($conf = $conferencias->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $conf['id']; ?>" <?php echo ($filtro_conferencia == $conf['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php if ($filtro_conferencia): ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar Filtro
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Estadísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1.5rem;">
            <h2 style="margin: 0; font-size: 2.5rem;"><?php echo $total_distritos; ?></h2>
            <small>Distritos <?php echo $filtro_conferencia ? 'en esta Conferencia' : 'Totales'; ?></small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Distritos</span>
    </div>
    <div class="card-body">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Conferencia</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Supervisor</th>
                            <th>Iglesias</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($dist = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong style="color: #667eea;"><?php echo htmlspecialchars($dist['conferencia_codigo']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($dist['conferencia_nombre']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($dist['codigo']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($dist['nombre']); ?>
                                </td>
                                <td>
                                    <?php if ($dist['supervisor_id']): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($dist['sup_nombre'] . ' ' . $dist['sup_apellido']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($dist['sup_cedula']); ?>
                                            </small>
                                            <br>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($dist['orden_ministerial']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-user-slash"></i> Sin asignar
                                        </span>
                                        <br>
                                        <a href="asignar_supervisor.php?id=<?php echo $dist['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" style="margin-top: 0.25rem;">
                                            <i class="fas fa-user-plus"></i> Asignar
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-info" style="font-size: 1rem;">
                                        <?php echo $dist['total_iglesias']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($dist['activo'] == 1): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <a href="ver.php?id=<?php echo $dist['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?php echo $dist['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($dist['supervisor_id']): ?>
                                            <a href="asignar_supervisor.php?id=<?php echo $dist['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Cambiar Supervisor">
                                                <i class="fas fa-user-tie"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="eliminar.php?id=<?php echo $dist['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar este distrito?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No hay distritos registrados. 
                <a href="crear.php">Crear el primer distrito</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
