<?php
declare(strict_types=1);

$page_title = "Gestión de Iglesias";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin puede ver esto
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener todas las iglesias con conferencia y distrito
try {
    $sql = "SELECT i.*, 
                   d.nombre AS distrito_nombre, d.codigo AS distrito_codigo,
                   c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
            FROM iglesias i
            INNER JOIN distritos d ON i.distrito_id = d.id
            INNER JOIN conferencias c ON d.conferencia_id = c.id
            ORDER BY c.nombre, d.nombre, i.codigo";
    $resultado = $conexion->query($sql);
} catch (Exception $e) {
    error_log("Error al obtener iglesias: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar las iglesias.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-church"></i> Gestión de Iglesias</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        $success = htmlspecialchars($_GET['success']);
        if ($success === 'creada') echo "Iglesia creada exitosamente.";
        if ($success === 'editada') echo "Iglesia actualizada exitosamente.";
        if ($success === 'eliminada') echo "Iglesia eliminada exitosamente.";
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Iglesias</span>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Iglesia
        </a>
    </div>
    <div class="card-body">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Conferencia</th>
                            <th>Distrito</th>
                            <th>Código Iglesia</th>
                            <th>Nombre Iglesia</th>
                            <th>Pastor</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($igl = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Conferencia">
                                    <strong style="color: #2c5aa0; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($igl['conferencia_codigo']); ?>
                                    </strong>
                                    <br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo htmlspecialchars($igl['conferencia_nombre']); ?>
                                    </small>
                                </td>
                                <td data-label="Distrito">
                                    <strong style="color: #27ae60; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($igl['distrito_codigo']); ?>
                                    </strong>
                                    <br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo htmlspecialchars($igl['distrito_nombre']); ?>
                                    </small>
                                </td>
                                <td data-label="Código Iglesia">
                                    <strong><?php echo htmlspecialchars($igl['codigo']); ?></strong>
                                </td>
                                <td data-label="Nombre">
                                    <?php echo htmlspecialchars($igl['nombre']); ?>
                                </td>
                                <td data-label="Pastor">
                                    <?php echo htmlspecialchars($igl['pastor_nombre'] ?? '-'); ?>
                                </td>
                                <td data-label="Dirección">
                                    <?php echo htmlspecialchars($igl['direccion'] ?? '-'); ?>
                                </td>
                                <td data-label="Teléfono">
                                    <?php echo htmlspecialchars($igl['telefono'] ?? '-'); ?>
                                </td>
                                <td data-label="Estado">
                                    <?php if ($igl['activo'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle"></i> Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-times-circle"></i> Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <a href="editar.php?id=<?php echo $igl['id']; ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="eliminar.php?id=<?php echo $igl['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       title="Eliminar"
                                       onclick="return confirm('¿Está seguro de eliminar esta iglesia?');">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No hay iglesias registradas. 
                <a href="crear.php">Crear la primera iglesia</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>