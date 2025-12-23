<?php
declare(strict_types=1);

$page_title = "Ver Conferencia";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$conferencia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conferencia_id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener conferencia con superintendente
$stmt = $conexion->prepare("
    SELECT c.*, 
           p.nombre AS super_nombre, p.apellido AS super_apellido,
           p.cedula AS super_cedula, p.telefono AS super_telefono,
           p.orden_ministerial, p.foto AS super_foto
    FROM conferencias c
    LEFT JOIN pastores p ON c.superintendente_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conf = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conf) {
    header("Location: index.php?error=" . urlencode("Conferencia no encontrada"));
    exit;
}

// Obtener estadísticas
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM distritos WHERE conferencia_id = ? AND activo = 1");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_distritos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexion->prepare("
    SELECT COUNT(*) as total FROM iglesias i 
    INNER JOIN distritos d ON i.distrito_id = d.id 
    WHERE d.conferencia_id = ? AND i.activo = 1
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_iglesias = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pastores WHERE conferencia_id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_pastores = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener distritos de esta conferencia
$distritos = $conexion->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM iglesias WHERE distrito_id = d.id AND activo = 1) AS total_iglesias
    FROM distritos d
    WHERE d.conferencia_id = ? AND d.activo = 1
    ORDER BY d.codigo
");
$distritos->bind_param("i", $conferencia_id);
$distritos->execute();
$lista_distritos = $distritos->get_result();
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-globe-americas"></i> <?php echo htmlspecialchars($conf['nombre']); ?></h1>
        <div>
            <a href="editar.php?id=<?php echo $conferencia_id; ?>" class="btn btn-warning">
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
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1rem;">
            <h2 style="margin: 0;"><?php echo $total_distritos; ?></h2>
            <small>Distritos</small>
        </div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1rem;">
            <h2 style="margin: 0;"><?php echo $total_iglesias; ?></h2>
            <small>Iglesias</small>
        </div>
    </div>
    <div class="card" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1rem;">
            <h2 style="margin: 0;"><?php echo $total_pastores; ?></h2>
            <small>Pastores</small>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Datos de la Conferencia -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fas fa-info-circle"></i> Datos de la Conferencia</span>
        </div>
        <div class="card-body">
            <table class="table" style="margin: 0;">
                <tr>
                    <td style="width: 40%;"><strong>Código:</strong></td>
                    <td><span style="color: #667eea; font-weight: bold; font-size: 1.2rem;"><?php echo htmlspecialchars($conf['codigo']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Nombre:</strong></td>
                    <td><?php echo htmlspecialchars($conf['nombre']); ?></td>
                </tr>
                <tr>
                    <td><strong>Teléfono:</strong></td>
                    <td><?php echo htmlspecialchars($conf['telefono'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Correo:</strong></td>
                    <td><?php echo htmlspecialchars($conf['correo'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td><strong>Estado:</strong></td>
                    <td>
                        <?php if ($conf['activo']): ?>
                            <span class="badge bg-success">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactiva</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Superintendente -->
    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <span class="card-title"><i class="fas fa-user-tie"></i> Superintendente General</span>
        </div>
        <div class="card-body">
            <?php if ($conf['superintendente_id']): ?>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <?php if (!empty($conf['super_foto'])): ?>
                        <img src="../../uploads/pastores/<?php echo htmlspecialchars($conf['super_foto']); ?>" 
                             alt="Foto" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea;">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="font-size: 2rem; color: #999;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h4 style="margin: 0;">
                            <?php echo htmlspecialchars($conf['super_nombre'] . ' ' . $conf['super_apellido']); ?>
                        </h4>
                        <p style="margin: 0.25rem 0;">
                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($conf['super_cedula']); ?>
                        </p>
                        <p style="margin: 0;">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($conf['super_telefono'] ?? '-'); ?>
                        </p>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($conf['orden_ministerial']); ?></span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <a href="asignar_superintendente.php?id=<?php echo $conferencia_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-exchange-alt"></i> Cambiar
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-warning" style="margin: 0;">
                    <i class="fas fa-exclamation-triangle"></i> Sin superintendente asignado
                </div>
                <a href="asignar_superintendente.php?id=<?php echo $conferencia_id; ?>" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> Asignar Superintendente
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Distritos de la Conferencia -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-map-marked-alt"></i> Distritos (<?php echo $total_distritos; ?>)</span>
    </div>
    <div class="card-body">
        <?php if ($lista_distritos->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Iglesias</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = $lista_distritos->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($d['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $d['total_iglesias']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $d['activo'] ? 'success' : 'danger'; ?>">
                                    <?php echo $d['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No hay distritos registrados en esta conferencia.</p>
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
