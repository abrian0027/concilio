<?php
declare(strict_types=1);

$page_title = "Gestión de Conferencias";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin puede ver esto
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener todas las conferencias con datos del superintendente
try {
    $sql = "SELECT c.*, 
                   p.nombre AS super_nombre, 
                   p.apellido AS super_apellido,
                   p.cedula AS super_cedula,
                   p.telefono AS super_telefono,
                   p.orden_ministerial,
                   (SELECT COUNT(*) FROM distritos d WHERE d.conferencia_id = c.id AND d.activo = 1) AS total_distritos,
                   (SELECT COUNT(*) FROM iglesias i 
                    INNER JOIN distritos d ON i.distrito_id = d.id 
                    WHERE d.conferencia_id = c.id AND i.activo = 1) AS total_iglesias
            FROM conferencias c
            LEFT JOIN pastores p ON c.superintendente_id = p.id
            ORDER BY c.codigo";
    $resultado = $conexion->query($sql);
} catch (Exception $e) {
    error_log("Error al obtener conferencias: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar las conferencias.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Contar totales
$total_conferencias = $resultado ? $resultado->num_rows : 0;
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-globe-americas"></i> Gestión de Conferencias</h1>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Conferencia
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

<!-- Estadísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1.5rem;">
            <h2 style="margin: 0; font-size: 2.5rem;"><?php echo $total_conferencias; ?></h2>
            <small>Conferencias</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Conferencias</span>
    </div>
    <div class="card-body">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Superintendente</th>
                            <th>Distritos</th>
                            <th>Iglesias</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($conf = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong style="color: #667eea;"><?php echo htmlspecialchars($conf['codigo']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($conf['nombre']); ?>
                                </td>
                                <td>
                                    <?php if ($conf['superintendente_id']): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($conf['super_nombre'] . ' ' . $conf['super_apellido']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($conf['super_cedula']); ?>
                                            </small>
                                            <br>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($conf['orden_ministerial']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fas fa-user-slash"></i> Sin asignar
                                        </span>
                                        <br>
                                        <a href="asignar_superintendente.php?id=<?php echo $conf['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" style="margin-top: 0.25rem;">
                                            <i class="fas fa-user-plus"></i> Asignar
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-info" style="font-size: 1rem;">
                                        <?php echo $conf['total_distritos']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge bg-success" style="font-size: 1rem;">
                                        <?php echo $conf['total_iglesias']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($conf['activo'] == 1): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle"></i> Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <a href="ver.php?id=<?php echo $conf['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar.php?id=<?php echo $conf['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($conf['superintendente_id']): ?>
                                            <a href="asignar_superintendente.php?id=<?php echo $conf['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Cambiar Superintendente">
                                                <i class="fas fa-user-tie"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="eliminar.php?id=<?php echo $conf['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar esta conferencia?');">
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
                No hay conferencias registradas. 
                <a href="crear.php">Crear la primera conferencia</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
