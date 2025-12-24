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
           c.nombre AS conferencia,
           ne.nombre AS nivel_estudio,
           ca.nombre AS carrera
    FROM pastores p
    LEFT JOIN nacionalidades n ON p.nacionalidad_id = n.id
    LEFT JOIN conferencias c ON p.conferencia_id = c.id
    LEFT JOIN niveles_estudio ne ON p.nivel_estudio_id = ne.id
    LEFT JOIN carreras ca ON p.carrera_id = ca.id
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

<style>
/* ===== CSS 100% RESPONSIVE PARA VER PASTOR ===== */

/* Header de página */
.pastor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.pastor-header h1 {
    font-size: 1.5rem;
    margin: 0;
}

.pastor-header-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.pastor-header-buttons .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

/* Grid principal */
.pastor-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    max-width: 1200px;
}

/* Tarjeta de perfil */
.pastor-profile-card {
    text-align: center;
}

.pastor-photo {
    width: 150px;
    height: 150px;
    border-radius: 18px;
    object-fit: cover;
    border: 4px solid var(--primary, #0dcaf0);
    margin-bottom: 1rem;
}

.pastor-photo-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 18px;
    background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    border: 4px solid var(--primary, #0dcaf0);
}

.pastor-photo-placeholder i {
    font-size: 4rem;
    color: #999;
}

.pastor-name {
    font-size: 1.3rem;
    margin: 0 0 0.5rem 0;
    word-break: break-word;
}

.pastor-orden-badge {
    font-size: 0.95rem;
    padding: 0.4rem 0.8rem;
}

.pastor-cedula {
    color: #666;
    margin: 0.5rem 0;
    font-size: 0.95rem;
}

.pastor-info-list {
    text-align: left;
}

.pastor-info-list p {
    margin: 0.5rem 0;
    font-size: 0.95rem;
    word-break: break-word;
}

.pastor-info-list i {
    width: 20px;
    text-align: center;
    margin-right: 0.5rem;
    color: #667eea;
}

.pastor-anos-servicio {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}

.pastor-anos-servicio h4 {
    margin: 0;
    font-size: 1.8rem;
}

/* Tarjeta de iglesia */
.iglesia-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #17a2b8;
}

.iglesia-item.principal {
    border-left-color: #28a745;
}

.iglesia-info {
    flex: 1;
}

.iglesia-info strong {
    word-break: break-word;
}

.iglesia-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

/* Grid de datos ministeriales */
.datos-ministeriales-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* Tabla responsive */
.table-responsive-custom {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table-responsive-custom table {
    min-width: 500px;
}

/* ===== MEDIA QUERIES MOBILE FIRST ===== */

/* Tablets y móviles grandes (< 992px) */
@media (max-width: 991.98px) {
    .pastor-grid {
        grid-template-columns: 1fr;
    }
    
    .pastor-photo,
    .pastor-photo-placeholder {
        width: 120px;
        height: 120px;
    }
    
    .pastor-photo-placeholder i {
        font-size: 3rem;
    }
}

/* Móviles (< 768px) */
@media (max-width: 767.98px) {
    .pastor-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .pastor-header h1 {
        font-size: 1.25rem;
        text-align: center;
    }
    
    .pastor-header-buttons {
        justify-content: center;
    }
    
    .pastor-header-buttons .btn {
        flex: 1;
        min-width: 0;
        padding: 0.6rem 0.5rem;
        font-size: 0.85rem;
    }
    
    .pastor-header-buttons .btn-text {
        display: none;
    }
    
    .pastor-name {
        font-size: 1.15rem;
    }
    
    .datos-ministeriales-grid {
        grid-template-columns: 1fr;
    }
    
    .iglesia-item {
        padding: 0.75rem;
    }
}

/* Móviles pequeños (< 480px) */
@media (max-width: 479.98px) {
    .pastor-header-buttons .btn {
        padding: 0.5rem;
        font-size: 0.8rem;
    }
    
    .pastor-photo,
    .pastor-photo-placeholder {
        width: 100px;
        height: 100px;
    }
    
    .pastor-photo-placeholder i {
        font-size: 2.5rem;
    }
    
    .pastor-name {
        font-size: 1.1rem;
    }
    
    .pastor-orden-badge {
        font-size: 0.85rem;
    }
    
    .pastor-info-list p {
        font-size: 0.9rem;
    }
    
    .pastor-anos-servicio h4 {
        font-size: 1.5rem;
    }
    
    .table-responsive-custom table {
        font-size: 0.85rem;
    }
}
</style>

<div class="content-header">
    <div class="pastor-header">
        <h1><i class="fas fa-user-tie"></i> Detalles del Pastor</h1>
        <div class="pastor-header-buttons">
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> <span class="btn-text">Editar</span>
            </a>
            <a href="asignar_iglesia.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i class="fas fa-church"></i> <span class="btn-text">Asignar</span>
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <span class="btn-text">Volver</span>
            </a>
        </div>
    </div>
</div>

<div class="pastor-grid">
    
    <!-- Columna izquierda: Foto y datos básicos -->
    <div>
        <div class="card">
            <div class="card-body pastor-profile-card">
                <?php if (!empty($pastor['foto'])): ?>
                    <img src="../../uploads/pastores/<?php echo htmlspecialchars($pastor['foto']); ?>" 
                         alt="Foto" class="pastor-photo">
                <?php else: ?>
                    <div class="pastor-photo-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <h3 class="pastor-name">
                    <?php echo htmlspecialchars($pastor['nombre'] . ' ' . $pastor['apellido']); ?>
                </h3>
                
                <p style="margin: 0.5rem 0;">
                    <?php
                    $badge_class = 'secondary';
                    if ($pastor['orden_ministerial'] == 'Presbítero') $badge_class = 'success';
                    elseif ($pastor['orden_ministerial'] == 'Diácono') $badge_class = 'primary';
                    ?>
                    <span class="badge bg-<?php echo $badge_class; ?> pastor-orden-badge">
                        <?php echo htmlspecialchars($pastor['orden_ministerial']); ?>
                    </span>
                </p>
                
                <p class="pastor-cedula">
                    <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($pastor['cedula']); ?>
                </p>
                
                <hr>
                
                <div class="pastor-info-list">
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($pastor['telefono']); ?></p>
                    <p><i class="fas fa-birthday-cake"></i> <?php echo $pastor['edad'] ?? 'N/A'; ?> años</p>
                    <p><i class="fas fa-ring"></i> <?php echo htmlspecialchars($pastor['estado_civil']); ?></p>
                    <p><i class="fas fa-flag"></i> <?php echo htmlspecialchars($pastor['nacionalidad'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-users"></i> <?php echo htmlspecialchars($pastor['conferencia'] ?? 'N/A'); ?></p>
                </div>
                
                <hr>
                
                <div class="pastor-anos-servicio">
                    <h4><?php echo $pastor['anos_servicio'] ?? 0; ?></h4>
                    <small>Años en el Ministerio</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Columna derecha: Información detallada -->
    <div>
        <!-- Iglesias Asignadas -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-church"></i> Iglesias que Pastorea</span>
            </div>
            <div class="card-body">
                <?php if ($iglesias->num_rows > 0): ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php while ($igl = $iglesias->fetch_assoc()): ?>
                            <div class="iglesia-item <?php echo $igl['es_principal'] ? 'principal' : ''; ?>">
                                <div class="iglesia-info">
                                    <strong><?php echo htmlspecialchars($igl['codigo']); ?></strong> - 
                                    <?php echo htmlspecialchars($igl['iglesia_nombre']); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($igl['distrito_nombre']); ?> | 
                                        <?php echo htmlspecialchars($igl['conferencia_nombre']); ?>
                                    </small>
                                </div>
                                <div class="iglesia-meta">
                                    <?php if ($igl['es_principal']): ?>
                                        <span class="badge bg-success">Principal</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Secundaria</span>
                                    <?php endif; ?>
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
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-cross"></i> Datos Ministeriales</span>
            </div>
            <div class="card-body">
                <div class="datos-ministeriales-grid">
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
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-graduation-cap"></i> Formación Académica</span>
            </div>
            <div class="card-body">
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <strong>Nivel de Estudio:</strong><br>
                        <?php echo !empty($pastor['nivel_estudio']) ? htmlspecialchars($pastor['nivel_estudio']) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                    <div>
                        <strong>Carrera/Profesión:</strong><br>
                        <?php echo !empty($pastor['carrera']) ? htmlspecialchars($pastor['carrera']) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                    <div>
                        <strong>Formación Continuada:</strong><br>
                        <?php echo !empty($pastor['formacion_continuada']) ? nl2br(htmlspecialchars($pastor['formacion_continuada'])) : '<em class="text-muted">No registrado</em>'; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ministerios Conferenciales -->
        <?php if ($ministerios->num_rows > 0): ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-hands-helping"></i> Ministerios Conferenciales</span>
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
                <div class="table-responsive-custom">
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
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>