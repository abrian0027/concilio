<?php
declare(strict_types=1);

$page_title = "Ver Distrito";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$distrito_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$distrito_id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener distrito con supervisor y conferencia
$stmt = $conexion->prepare("
    SELECT d.*, 
           c.codigo AS conf_codigo, c.nombre AS conf_nombre,
           p.nombre AS sup_nombre, p.apellido AS sup_apellido,
           p.cedula AS sup_cedula, p.telefono AS sup_telefono,
           p.orden_ministerial, p.foto AS sup_foto
    FROM distritos d
    INNER JOIN conferencias c ON d.conferencia_id = c.id
    LEFT JOIN pastores p ON d.supervisor_id = p.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$dist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dist) {
    header("Location: index.php?error=" . urlencode("Distrito no encontrado"));
    exit;
}

// Obtener estadísticas
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ? AND activo = 1");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$total_iglesias = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener iglesias de este distrito
$iglesias = $conexion->prepare("
    SELECT i.*, 
           (SELECT COUNT(*) FROM miembros m WHERE m.iglesia_id = i.id AND m.estado = 'activo') AS total_miembros
    FROM iglesias i
    WHERE i.distrito_id = ? AND i.activo = 1
    ORDER BY i.codigo
");
$iglesias->bind_param("i", $distrito_id);
$iglesias->execute();
$lista_iglesias = $iglesias->get_result();
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-map-marked-alt"></i> <?php echo htmlspecialchars($dist['nombre']); ?></h1>
        <div>
            <a href="editar.php?id=<?php echo $distrito_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1rem;">
            <h2 style="margin: 0;"><?php echo $total_iglesias; ?></h2>
            <small>Iglesias</small>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Datos del Distrito -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-info-circle"></i> Datos del Distrito</span>
        </div>
        <div class="card-body">
            <table class="table" style="margin: 0;">
                <tr>
                    <td style="width: 40%;"><strong>Código:</strong></td>
                    <td><span style="color: #11998e; font-weight: bold; font-size: 1.2rem;"><?php echo htmlspecialchars($dist['codigo']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Nombre:</strong></td>
                    <td><?php echo htmlspecialchars($dist['nombre']); ?></td>
                </tr>
                <tr>
                    <td><strong>Conferencia:</strong></td>
                    <td>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($dist['conf_codigo']); ?></span>
                        <?php echo htmlspecialchars($dist['conf_nombre']); ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Teléfono:</strong></td>
                    <td><?php echo htmlspecialchars($dist['telefono'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Correo:</strong></td>
                    <td><?php echo htmlspecialchars($dist['correo'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Estado:</strong></td>
                    <td>
                        <?php if ($dist['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Supervisor -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
            <span class="card-title"><i class="fas fa-user-tie"></i> Supervisor de Distrito</span>
        </div>
        <div class="card-body">
            <?php if ($dist['supervisor_id']): ?>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($dist['sup_foto'])): ?>
                        <img src="../../uploads/pastores/<?php echo htmlspecialchars($dist['sup_foto']); ?>" 
                             alt="Foto" style="width: 80px; height: 80px; border-radius: 14px; object-fit: cover; border: 3px solid #0dcaf0;">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; border-radius: 14px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%); display: flex; align-items: center; justify-content: center; border: 3px solid #0dcaf0;">
                            <i class="fas fa-user" style="font-size: 2rem; color: #999;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h4 style="margin: 0;">
                            <?php echo htmlspecialchars($dist['sup_nombre'] . ' ' . $dist['sup_apellido']); ?>
                        </h4>
                        <p style="margin: 0.25rem 0;">
                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($dist['sup_cedula']); ?>
                        </p>
                        <p style="margin: 0;">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($dist['sup_telefono'] ?? '-'); ?>
                        </p>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($dist['orden_ministerial']); ?></span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="asignar_supervisor.php?id=<?php echo $distrito_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-exchange-alt"></i> Cambiar
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning" style="margin: 0;">
                    <i class="fas fa-exclamation-triangle"></i> Sin supervisor asignado
                </div>
                <a href="asignar_supervisor.php?id=<?php echo $distrito_id; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> Asignar Supervisor
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Iglesias del Distrito -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-church"></i> Iglesias (<?php echo $total_iglesias; ?>)</span>
    </div>
    <div class="card-body">
        <?php if ($lista_iglesias->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Miembros</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($i = $lista_iglesias->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($i['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($i['nombre']); ?></td>
                            <td>
                                <?php 
                                $cat_color = 'secondary';
                                if ($i['categoria'] === 'Circuito') $cat_color = 'success';
                                elseif ($i['categoria'] === 'Capilla') $cat_color = 'info';
                                elseif ($i['categoria'] === 'Proyecto Evangelístico') $cat_color = 'warning';
                                ?>
                                <span class="badge bg-<?php echo $cat_color; ?>">
                                    <?php echo htmlspecialchars($i['categoria']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo $i['total_miembros']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $i['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $i['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No hay iglesias registradas en este distrito.</p>
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
