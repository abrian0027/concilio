<?php
declare(strict_types=1);

$page_title = "Ver Junta Histórica";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener junta
$stmt = $conexion->prepare("SELECT j.*, i.nombre AS iglesia_nombre, p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin 
                            FROM juntas j 
                            INNER JOIN iglesias i ON i.id = j.iglesia_id 
                            INNER JOIN periodos_iglesia p ON p.id = j.periodo_id 
                            WHERE j.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$junta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$junta) {
    header("Location: index.php?error=Junta no encontrada");
    exit;
}

// Obtener miembros
$stmt = $conexion->prepare("SELECT jm.*, m.nombre, m.apellido, m.foto, c.nombre AS cargo_nombre, c.orden
                            FROM junta_miembros jm 
                            INNER JOIN miembros m ON m.id = jm.miembro_id 
                            INNER JOIN cargos_junta c ON c.id = jm.cargo_id 
                            WHERE jm.junta_id = ?
                            ORDER BY jm.es_pastor DESC, c.orden, m.nombre");
$stmt->bind_param("i", $id);
$stmt->execute();
$miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="content-header">
    <h1><i class="fas fa-history"></i> Junta Histórica</h1>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">
                <i class="fas fa-users-cog text-warning me-2"></i><?php echo htmlspecialchars($junta['iglesia_nombre']); ?>
            </h6>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                <?php echo htmlspecialchars($junta['periodo_nombre']); ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-light border mb-3">
            <strong>Período:</strong> 
            <?php echo date('d/m/Y', strtotime($junta['fecha_inicio'])); ?> - 
            <?php echo date('d/m/Y', strtotime($junta['fecha_fin'])); ?>
            <br>
            <strong>Tipo:</strong> Junta de <?php echo $junta['tipo']; ?> miembros
        </div>

        <?php if (empty($miembros)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay registros de miembros para esta junta.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Cargo</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($miembros as $m): ?>
                            <tr>
                                <td>
                                    <?php if ($m['foto']): ?>
                                        <img src="../../../uploads/miembros/<?php echo htmlspecialchars($m['foto']); ?>" 
                                             alt="Foto" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 2px solid #0dcaf0;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%); display: flex; align-items: center; justify-content: center; border: 2px solid #0dcaf0;">
                                            <i class="fas fa-user" style="color: #adb5bd;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?></strong></td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                        <?php echo htmlspecialchars($m['cargo_nombre']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($m['es_pastor']): ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                            <i class="fas fa-star"></i> Pastor
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                            Miembro
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div style="margin-top: 1rem;">
    <a href="index.php?iglesia_id=<?php echo $junta['iglesia_id']; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>