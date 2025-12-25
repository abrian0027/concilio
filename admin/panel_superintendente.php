<?php
/**
 * Panel del Superintendente de Conferencia
 * Ve toda la información de su conferencia (sin finanzas locales)
 * Sistema Concilio - Mobile First
 */

$page_title = "Panel Superintendente";
require_once __DIR__ . '/includes/header.php';

// Solo superintendente de conferencia
if (!in_array($ROL_NOMBRE, ['super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

require_once __DIR__ . '/../config/config.php';

// Obtener conferencia_id de la sesión
$conferencia_id = $_SESSION['conferencia_id'] ?? 0;

if (!$conferencia_id) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> No tienes una conferencia asignada.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Obtener información de la conferencia
$stmt = $conexion->prepare("
    SELECT c.*, p.nombre as super_nombre, p.apellido as super_apellido, p.foto as super_foto
    FROM conferencias c
    LEFT JOIN pastores p ON c.superintendente_id = p.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Conferencia no encontrada.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ============================================
// ESTADÍSTICAS GENERALES
// ============================================

// Total distritos
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM distritos WHERE conferencia_id = ? AND activo = 1");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_distritos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total iglesias
$stmt = $conexion->prepare("
    SELECT COUNT(*) as total FROM iglesias i
    INNER JOIN distritos d ON i.distrito_id = d.id
    WHERE d.conferencia_id = ? AND i.activo = 1
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_iglesias = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total miembros activos
$stmt = $conexion->prepare("
    SELECT COUNT(*) as total FROM miembros m
    INNER JOIN iglesias i ON m.iglesia_id = i.id
    INNER JOIN distritos d ON i.distrito_id = d.id
    WHERE d.conferencia_id = ? AND m.estado = 'activo'
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_miembros = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total pastores
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pastores WHERE conferencia_id = ? AND activo = 1");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_pastores = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total bautizados
$stmt = $conexion->prepare("
    SELECT COUNT(*) as total FROM miembros m
    INNER JOIN iglesias i ON m.iglesia_id = i.id
    INNER JOIN distritos d ON i.distrito_id = d.id
    WHERE d.conferencia_id = ? AND m.estado = 'activo' AND m.es_bautizado = 1
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_bautizados = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total líderes
$stmt = $conexion->prepare("
    SELECT COUNT(*) as total FROM miembros m
    INNER JOIN iglesias i ON m.iglesia_id = i.id
    INNER JOIN distritos d ON i.distrito_id = d.id
    WHERE d.conferencia_id = ? AND m.estado = 'activo' AND m.es_lider = 1
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$total_lideres = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// ============================================
// MIEMBROS POR DISTRITO
// ============================================
$stmt = $conexion->prepare("
    SELECT d.id, d.codigo, d.nombre,
           p.nombre as supervisor_nombre, p.apellido as supervisor_apellido,
           (SELECT COUNT(*) FROM iglesias WHERE distrito_id = d.id AND activo = 1) as total_iglesias,
           (SELECT COUNT(*) FROM miembros m 
            INNER JOIN iglesias i ON m.iglesia_id = i.id 
            WHERE i.distrito_id = d.id AND m.estado = 'activo') as total_miembros
    FROM distritos d
    LEFT JOIN pastores p ON d.supervisor_id = p.id
    WHERE d.conferencia_id = ? AND d.activo = 1
    ORDER BY d.nombre
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$distritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// MIEMBROS POR MINISTERIO
// ============================================
$stmt = $conexion->prepare("
    SELECT mi.nombre as ministerio,
           COUNT(m.id) as total
    FROM ministerios mi
    LEFT JOIN miembros m ON m.ministerio_id = mi.id AND m.estado = 'activo'
    LEFT JOIN iglesias i ON m.iglesia_id = i.id
    LEFT JOIN distritos d ON i.distrito_id = d.id
    WHERE mi.activo = 1 AND (d.conferencia_id = ? OR m.id IS NULL)
    GROUP BY mi.id, mi.nombre
    ORDER BY total DESC
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$ministerios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// TOP 5 IGLESIAS CON MÁS MIEMBROS
// ============================================
$stmt = $conexion->prepare("
    SELECT i.codigo, i.nombre, d.nombre as distrito,
           COUNT(m.id) as total_miembros
    FROM iglesias i
    INNER JOIN distritos d ON i.distrito_id = d.id
    LEFT JOIN miembros m ON m.iglesia_id = i.id AND m.estado = 'activo'
    WHERE d.conferencia_id = ? AND i.activo = 1
    GROUP BY i.id, i.codigo, i.nombre, d.nombre
    ORDER BY total_miembros DESC
    LIMIT 5
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$top_iglesias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ============================================
// LÍDERES DE MINISTERIOS CONFERENCIALES
// ============================================
$stmt = $conexion->prepare("
    SELECT m.nombre as ministerio,
           mb.nombre as lider_nombre, mb.apellido as lider_apellido,
           mlc.cargo
    FROM ministerio_lideres_conferencia mlc
    INNER JOIN ministerios m ON mlc.ministerio_id = m.id
    INNER JOIN miembros mb ON mlc.miembro_id = mb.id
    WHERE mlc.conferencia_id = ? AND mlc.activo = 1
    ORDER BY m.nombre, mlc.cargo
");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$lideres_conf = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<style>
/* ===== PANEL SUPERINTENDENTE - 100% RESPONSIVE ===== */
.super-header {
    background: linear-gradient(135deg, #0891b2 0%, #0dcaf0 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.super-header-photo {
    width: 80px;
    height: 80px;
    border-radius: 14px;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
}

.super-header-photo-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 14px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    border: 3px solid rgba(255,255,255,0.3);
}

.super-header-info h1 {
    font-size: 1.5rem;
    margin: 0 0 0.25rem 0;
}

.super-header-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    margin: 0 auto 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.stat-card .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-card .stat-label {
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

/* Cards */
.panel-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.panel-card-header {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.panel-card-header i {
    color: #0891b2;
}

.panel-card-body {
    padding: 1rem 1.25rem;
}

/* Distrito Item */
.distrito-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.distrito-item:last-child {
    border-bottom: none;
}

.distrito-info h4 {
    font-size: 0.95rem;
    margin: 0;
    color: #1f2937;
}

.distrito-info small {
    color: #6b7280;
    font-size: 0.8rem;
}

.distrito-stats {
    text-align: right;
}

.distrito-stats .stat {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.distrito-stats .stat i {
    font-size: 0.7rem;
}

/* Ministerio Item */
.ministerio-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.ministerio-item:last-child {
    border-bottom: none;
}

.ministerio-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(8, 145, 178, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0891b2;
    font-size: 0.9rem;
}

.ministerio-info {
    flex: 1;
}

.ministerio-info h4 {
    font-size: 0.9rem;
    margin: 0;
    color: #374151;
}

.ministerio-count {
    font-weight: 600;
    color: #0891b2;
    font-size: 0.95rem;
}

/* Iglesia Item */
.iglesia-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.iglesia-item:last-child {
    border-bottom: none;
}

.iglesia-info strong {
    font-size: 0.9rem;
    color: #1f2937;
}

.iglesia-info small {
    display: block;
    color: #6b7280;
    font-size: 0.8rem;
}

.iglesia-count {
    background: #0891b2;
    color: white;
    padding: 0.25rem 0.6rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Lider Item */
.lider-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.6rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.lider-item:last-child {
    border-bottom: none;
}

.lider-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(16, 185, 129, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #10b981;
    font-size: 0.8rem;
}

.lider-info {
    flex: 1;
}

.lider-info h4 {
    font-size: 0.85rem;
    margin: 0;
    color: #374151;
}

.lider-info small {
    color: #6b7280;
    font-size: 0.75rem;
}

.lider-cargo {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 0.5rem;
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    font-weight: 500;
}

/* Actions */
.quick-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.quick-actions .btn {
    flex: 1;
    min-width: 140px;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 991.98px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .super-header {
        flex-direction: column;
        text-align: center;
        padding: 1.25rem;
    }
    
    .super-header-info h1 {
        font-size: 1.25rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .quick-actions .btn {
        min-width: 100%;
    }
}

@media (max-width: 479.98px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .stat-card .stat-value {
        font-size: 1.25rem;
    }
    
    .super-header-photo,
    .super-header-photo-placeholder {
        width: 60px;
        height: 60px;
    }
}
</style>

<!-- Header con información del superintendente -->
<div class="super-header">
    <?php if (!empty($conferencia['super_foto'])): ?>
        <img src="../uploads/pastores/<?php echo htmlspecialchars($conferencia['super_foto']); ?>" 
             alt="Foto" class="super-header-photo">
    <?php else: ?>
        <div class="super-header-photo-placeholder">
            <i class="fas fa-user"></i>
        </div>
    <?php endif; ?>
    
    <div class="super-header-info">
        <h1><?php echo htmlspecialchars($conferencia['nombre']); ?></h1>
        <p>
            <i class="fas fa-user-tie"></i> 
            Superintendente: <?php echo htmlspecialchars($conferencia['super_nombre'] . ' ' . $conferencia['super_apellido']); ?>
        </p>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="quick-actions">
    <a href="distritos/index.php" class="btn btn-primary">
        <i class="fas fa-map-marked-alt"></i> Distritos
    </a>
    <a href="iglesias/index.php" class="btn btn-success">
        <i class="fas fa-church"></i> Iglesias
    </a>
    <a href="pastores/index.php" class="btn btn-info">
        <i class="fas fa-user-tie"></i> Pastores
    </a>
    <a href="ministerios_conf/index.php" class="btn btn-warning">
        <i class="fas fa-hands-praying"></i> Ministerios
    </a>
</div>

<!-- Estadísticas Generales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
            <i class="fas fa-map-marked-alt"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_distritos); ?></div>
        <div class="stat-label">Distritos</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <i class="fas fa-church"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_iglesias); ?></div>
        <div class="stat-label">Iglesias</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(8, 145, 178, 0.1); color: #0891b2;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_miembros); ?></div>
        <div class="stat-label">Miembros</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_pastores); ?></div>
        <div class="stat-label">Pastores</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <i class="fas fa-water"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_bautizados); ?></div>
        <div class="stat-label">Bautizados</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value"><?php echo number_format($total_lideres); ?></div>
        <div class="stat-label">Líderes</div>
    </div>
</div>

<!-- Grid de Contenido -->
<div class="content-grid">
    
    <!-- Miembros por Distrito -->
    <div class="panel-card">
        <div class="panel-card-header">
            <i class="fas fa-map-marked-alt"></i> Miembros por Distrito
        </div>
        <div class="panel-card-body">
            <?php if (!empty($distritos)): ?>
                <?php foreach ($distritos as $dist): ?>
                    <div class="distrito-item">
                        <div class="distrito-info">
                            <h4><?php echo htmlspecialchars($dist['codigo'] . ' - ' . $dist['nombre']); ?></h4>
                            <small>
                                <i class="fas fa-user-tie"></i> 
                                <?php echo $dist['supervisor_nombre'] ? htmlspecialchars($dist['supervisor_nombre'] . ' ' . $dist['supervisor_apellido']) : 'Sin supervisor'; ?>
                            </small>
                        </div>
                        <div class="distrito-stats">
                            <div class="stat"><i class="fas fa-church"></i> <?php echo $dist['total_iglesias']; ?> iglesias</div>
                            <div class="stat"><i class="fas fa-users"></i> <?php echo $dist['total_miembros']; ?> miembros</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">No hay distritos registrados</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top 5 Iglesias -->
    <div class="panel-card">
        <div class="panel-card-header">
            <i class="fas fa-trophy"></i> Top 5 Iglesias por Membresía
        </div>
        <div class="panel-card-body">
            <?php if (!empty($top_iglesias)): ?>
                <?php foreach ($top_iglesias as $igl): ?>
                    <div class="iglesia-item">
                        <div class="iglesia-info">
                            <strong><?php echo htmlspecialchars($igl['codigo'] . ' - ' . $igl['nombre']); ?></strong>
                            <small><?php echo htmlspecialchars($igl['distrito']); ?></small>
                        </div>
                        <span class="iglesia-count"><?php echo $igl['total_miembros']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">No hay datos</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Miembros por Ministerio -->
    <div class="panel-card">
        <div class="panel-card-header">
            <i class="fas fa-hands-praying"></i> Miembros por Ministerio
        </div>
        <div class="panel-card-body">
            <?php if (!empty($ministerios)): ?>
                <?php foreach ($ministerios as $min): ?>
                    <div class="ministerio-item">
                        <div class="ministerio-icon">
                            <i class="fas fa-hands-praying"></i>
                        </div>
                        <div class="ministerio-info">
                            <h4><?php echo htmlspecialchars($min['ministerio']); ?></h4>
                        </div>
                        <span class="ministerio-count"><?php echo $min['total']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">No hay ministerios</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Líderes de Ministerios Conferenciales -->
    <div class="panel-card">
        <div class="panel-card-header">
            <i class="fas fa-crown"></i> Directiva Ministerios Conferencia
        </div>
        <div class="panel-card-body">
            <?php if (!empty($lideres_conf)): ?>
                <?php foreach ($lideres_conf as $lider): ?>
                    <div class="lider-item">
                        <div class="lider-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="lider-info">
                            <h4><?php echo htmlspecialchars($lider['lider_nombre'] . ' ' . $lider['lider_apellido']); ?></h4>
                            <small><?php echo htmlspecialchars($lider['ministerio']); ?></small>
                        </div>
                        <span class="lider-cargo"><?php echo ucfirst($lider['cargo']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-3">No hay líderes asignados</p>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
