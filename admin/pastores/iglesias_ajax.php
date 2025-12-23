<?php
declare(strict_types=1);

$page_title = "Detalles del Pastor";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin, Obispo y Super Conferencia
if (!in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener datos del pastor con joins
$stmt = $conexion->prepare("
    SELECT p.*, 
           n.nombre AS nacionalidad,
           c.nombre AS conferencia
    FROM pastores p
    LEFT JOIN nacionalidades n ON p.nacionalidad_id = n.id
    LEFT JOIN conferencias c ON p.conferencia_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header("Location: index.php?error=" . urlencode("Pastor no encontrado"));
    exit;
}

// Obtener iglesias asignadas
$stmt = $conexion->prepare("
    SELECT pi.*, i.codigo, i.nombre AS iglesia_nombre, i.categoria,
           d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre
    FROM pastor_iglesias pi
    INNER JOIN iglesias i ON pi.iglesia_id = i.id
    INNER JOIN distritos d ON i.distrito_id = d.id
    INNER JOIN conferencias c ON d.conferencia_id = c.id
    WHERE pi.pastor_id = ? AND pi.activo = 1
    ORDER BY pi.es_principal DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$iglesias = $stmt->get_result();

// Obtener ministerios conferenciales
$stmt = $conexion->prepare("
    SELECT pmc.*, am.nombre AS ministerio_nombre, c.nombre AS conferencia_nombre
    FROM pastor_ministerios_conferenciales pmc
    INNER JOIN areas_ministeriales am ON pmc.area_ministerial_id = am.id
    INNER JOIN conferencias c ON pmc.conferencia_id = c.id
    WHERE pmc.pastor_id = ? AND pmc.activo = 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$ministerios = $stmt->get_result();

// Obtener historial
$stmt = $conexion->prepare("
    SELECT ph.*, i.codigo, i.nombre AS iglesia_nombre
    FROM pastor_historial_asignaciones ph
    INNER JOIN iglesias i ON ph.iglesia_id = i.id
    WHERE ph.pastor_id = ?
    ORDER BY ph.fecha_inicio DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$historial = $stmt->get_result();
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-user-tie"></i> Detalles del Pastor</h1>
        <div>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="asignar_iglesia.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-church"></i> Asignar Iglesia
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem; max-width: 1200px;">
    
    <!-- Columna izquierda: Foto y datos básicos -->
    <div>
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <?php if (!empty($pastor['foto'])): ?>
                    <img src="../../uploads/pastores/<?php echo htmlspecialchars($pastor['foto']); ?>" 
                         alt="Foto" style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; border: 4px solid #667eea; margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 200px; height: 200px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; border: 4px solid #667eea;">
                        <i class="fas fa-user" style="font-size: 5rem; color: #999;"></i>
                    </div>
                <?php endif; ?>
                
                <h3 style="margin: 0;">
                    <?php echo htmlspecialchars($pastor['nombre'] . ' ' . $pastor['apellido']); ?>
                </h3>
                
                <p style="margin: 0.5rem 0;">
                    <?php
                    $badge_class = 'secondary';
                    if ($pastor['orden_ministerial'] == 'Presbítero') $badge_class = 'success';
                    elseif ($pastor['orden_ministerial'] == 'Diácono') $badge_class = 'primary';
                    ?>
                    <span class="badge bg-<?php echo $badge_class; ?>" style="font-size: 1rem;">
                        <?php echo htmlspecialchars($pastor['orden_ministerial']); ?>
                    </span>
                </p>
                
                <p style="color: #666; margin: 0;">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($pastor['cedula']); ?>
                </p>
                
                <hr>
                
                <div style="text-align: left;">
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($pastor['telefono']); ?></p>
                    <p><i class="fas fa-birthday-cake"></i> <?php echo $pastor['edad'] ?? 'N/A'; ?> años</p>
                    <p><i class="fas fa-ring"></i> <?php echo htmlspecialchars($pastor['estado_civil']); ?></p>
                    <p><i class="fas fa-flag"></i> <?php echo htmlspecialchars($pastor['nacionalidad'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-users"></i> <?php echo htmlspecialchars($pastor['conferencia'] ?? 'N/A'); ?></p>
                </div>
                
                <hr>
                
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 8px;">
                    <h4 style="margin: 0;"><?php echo $pastor['anos_servicio'] ?? 0; ?></h4>
                    <small>Años en el Ministerio</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Columna derecha: Información detallada -->
    <div>
        <!-- Iglesias Asignadas -->
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-church"></i> Iglesias que Pastorea</span>
            </div>
            <div class="card-body">
                <?php if ($iglesias->num_rows > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php while ($igl = $iglesias->fetch_assoc()): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid <?php echo $igl['es_principal'] ? '#28a745' : '#17a2b8'; ?>;">
                                <div>
                                    <strong><?php echo htmlspecialchars($igl['codigo']); ?></strong> - 
                                    <?php echo htmlspecialchars($igl['iglesia_nombre']); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($igl['distrito_nombre']); ?> | 
                                        <?php echo htmlspecialchars($igl['conferencia_nombre']); ?>
                                    </small>
                                </div>
                                <div>
                                    <?php if ($igl['es_principal']): ?>
                                        <span class="badge bg-success">Principal</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Secundaria</span>
                                    <?php endif; ?>
                                    <br>
                                    <small>Desde: <?php echo date('d/m/Y', strtotime($igl['fecha_asignacion'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><em>No tiene iglesias asignadas</em></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Datos Ministeriales -->
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-cross"></i> Datos Ministeriales</span>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <strong>Fecha de Ingreso:</strong><br>
                        <?php echo !empty($pastor['fecha_ingreso_ministerio']) ? date('d/m/Y', strtotime($pastor['fecha_ingreso_ministerio'])) : 'N/A'; ?>
                    </div>
                    <div>
                        <strong>Orden Ministerial:</strong><br>
                        <?php echo htmlspecialchars($pastor['orden_ministerial']); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formación Académica -->
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-graduation-cap"></i> Formación Académica</span>
            </div>
            <div class="card-body">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <strong>Estudios (Formación Base):</strong><br>
                        <?php echo !empty($pastor['estudios_basicos']) ? nl2br(htmlspecialchars($pastor['estudios_basicos'])) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                    <div>
                        <strong>Formación Continuada:</strong><br>
                        <?php echo !empty($pastor['formacion_continuada']) ? nl2br(htmlspecialchars($pastor['formacion_continuada'])) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                    <div>
                        <strong>Profesión / Técnico:</strong><br>
                        <?php echo !empty($pastor['profesion']) ? htmlspecialchars($pastor['profesion']) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ministerios Conferenciales -->
        <?php if ($ministerios->num_rows > 0): ?>
        <div class="card" style="margin-bottom: 1rem;">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-hands-helping"></i> Ministerios Conferenciales que Dirige</span>
            </div>
            <div class="card-body">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php while ($min = $ministerios->fetch_assoc()): ?>
                        <li>
                            <?php echo htmlspecialchars($min['ministerio_nombre']); ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($min['conferencia_nombre']); ?>)</small>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Historial de Asignaciones -->
        <?php if ($historial->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-history"></i> Historial de Asignaciones</span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Iglesia</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($hist = $historial->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($hist['codigo']); ?> - 
                                    <?php echo htmlspecialchars($hist['iglesia_nombre']); ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($hist['fecha_inicio'])); ?></td>
                                <td>
                                    <?php echo $hist['fecha_fin'] ? date('d/m/Y', strtotime($hist['fecha_fin'])) : '<span class="badge bg-success">Actual</span>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($hist['motivo_fin'] ?? '-'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media (max-width: 900px) {
    div[style*="grid-template-columns: 300px 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>