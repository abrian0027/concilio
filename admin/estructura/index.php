<?php
declare(strict_types=1);

// Debug: Mostrar errores (quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', '1');

$page_title = "Estructura Eclesiástica";
require_once __DIR__ . '/../includes/header.php';

// Verificar que config.php esté cargado
if (!isset($conexion)) {
    require_once __DIR__ . '/../../config/config.php';
}

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE ?? '', ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$puede_gestionar = in_array($ROL_NOMBRE ?? '', ['super_admin', 'pastor', 'secretaria']);

// Determinar iglesia según rol
if (($ROL_NOMBRE ?? '') === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)($IGLESIA_ID ?? 0);
}

// Obtener iglesias para super_admin
$iglesias = [];
if (($ROL_NOMBRE ?? '') === 'super_admin') {
    $result = $conexion->query("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
                                FROM iglesias i 
                                INNER JOIN distritos d ON d.id = i.distrito_id 
                                INNER JOIN conferencias c ON c.id = d.conferencia_id 
                                WHERE i.activo = 1 
                                ORDER BY c.nombre, d.nombre, i.nombre");
    if ($result) {
        $iglesias = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Inicializar variables
$iglesia_info = null;
$periodo_activo = null;
$pastores = [];
$junta_miembros = [];
$lideres_areas = [];
$areas_disponibles = [];
$stats = ['pastores' => 0, 'junta' => 0, 'areas' => 0, 'lideres' => 0];
$tiempo_restante = '';

if ($iglesia_id > 0) {
    // Info de la iglesia
    $stmt = $conexion->prepare("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
                                FROM iglesias i 
                                INNER JOIN distritos d ON d.id = i.distrito_id 
                                INNER JOIN conferencias c ON c.id = d.conferencia_id 
                                WHERE i.id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $iglesia_id);
        $stmt->execute();
        $iglesia_info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Período activo
    $stmt = $conexion->prepare("SELECT * FROM periodos_iglesia WHERE iglesia_id = ? AND activo = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $iglesia_id);
        $stmt->execute();
        $periodo_activo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Calcular tiempo restante del período
    if ($periodo_activo) {
        $fecha_fin = new DateTime($periodo_activo['fecha_fin']);
        $hoy = new DateTime();
        if ($fecha_fin > $hoy) {
            $diff = $hoy->diff($fecha_fin);
            $partes = [];
            if ($diff->y > 0) $partes[] = $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
            if ($diff->m > 0) $partes[] = $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
            if (empty($partes) && $diff->d > 0) $partes[] = $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
            $tiempo_restante = !empty($partes) ? implode(', ', $partes) . ' restantes' : 'Finaliza pronto';
        } else {
            $tiempo_restante = 'Período vencido';
        }
        
        // Obtener junta activa
        $stmt = $conexion->prepare("SELECT * FROM juntas WHERE iglesia_id = ? AND activa = 1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $iglesia_id);
            $stmt->execute();
            $junta = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($junta) {
                // Pastores (presidentes)
                $stmt = $conexion->prepare("SELECT jm.*, m.nombre, m.apellido, m.foto, m.telefono, c.nombre AS cargo_nombre
                                            FROM junta_miembros jm
                                            INNER JOIN miembros m ON m.id = jm.miembro_id
                                            INNER JOIN cargos_junta c ON c.id = jm.cargo_id
                                            WHERE jm.junta_id = ? AND jm.es_pastor = 1 AND jm.activo = 1
                                            ORDER BY m.nombre");
                if ($stmt) {
                    $stmt->bind_param("i", $junta['id']);
                    $stmt->execute();
                    $pastores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    $stats['pastores'] = count($pastores);
                }
                
                // Miembros de junta (sin pastores)
                $stmt = $conexion->prepare("SELECT jm.*, m.nombre, m.apellido, m.foto, c.nombre AS cargo_nombre, c.orden
                                            FROM junta_miembros jm
                                            INNER JOIN miembros m ON m.id = jm.miembro_id
                                            INNER JOIN cargos_junta c ON c.id = jm.cargo_id
                                            WHERE jm.junta_id = ? AND jm.es_pastor = 0 AND jm.activo = 1
                                            ORDER BY c.orden, m.nombre");
                if ($stmt) {
                    $stmt->bind_param("i", $junta['id']);
                    $stmt->execute();
                    $junta_miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    $stats['junta'] = count($junta_miembros);
                }
            }
        }
        
        // Líderes de áreas ministeriales
        $stmt = $conexion->prepare("SELECT al.*, a.nombre AS area_nombre, a.id AS area_id, m.nombre, m.apellido, m.foto
                                    FROM area_lideres al
                                    INNER JOIN areas_ministeriales a ON a.id = al.area_id
                                    INNER JOIN miembros m ON m.id = al.miembro_id
                                    WHERE al.iglesia_id = ? AND al.periodo_id = ? AND al.activo = 1
                                    ORDER BY a.nombre, al.tipo DESC");
        if ($stmt) {
            $stmt->bind_param("ii", $iglesia_id, $periodo_activo['id']);
            $stmt->execute();
            $lideres_areas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $stats['lideres'] = count($lideres_areas);
        }
    }
    
    // Áreas disponibles (siempre cargar si hay iglesia)
    $stmt = $conexion->prepare("SELECT * FROM areas_ministeriales 
                                WHERE activo = 1 AND (tipo = 'general' OR iglesia_id = ?)
                                ORDER BY nombre");
    if ($stmt) {
        $stmt->bind_param("i", $iglesia_id);
        $stmt->execute();
        $areas_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $stats['areas'] = count($areas_disponibles);
    }
}

// Agrupar líderes por área
$lideres_por_area = [];
foreach ($lideres_areas as $la) {
    if (!isset($lideres_por_area[$la['area_id']])) {
        $lideres_por_area[$la['area_id']] = ['nombre' => $la['area_nombre'], 'lideres' => []];
    }
    $lideres_por_area[$la['area_id']]['lideres'][] = $la;
}

// Función para obtener iniciales
function getInicialesEstructura($nombre, $apellido) {
    $n = !empty($nombre) ? mb_strtoupper(mb_substr($nombre, 0, 1)) : '';
    $a = !empty($apellido) ? mb_strtoupper(mb_substr($apellido, 0, 1)) : '';
    return $n . $a;
}
?>

<style>
/* Hero Card */
.hero-card {
    background: linear-gradient(135deg, #0891b2 0%, #0dcaf0 50%, #22d3ee 100%);
    border-radius: 1rem;
    padding: 1.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.hero-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.hero-card .hero-icon {
    position: absolute;
    right: 1.5rem;
    bottom: 1rem;
    font-size: 5rem;
    opacity: 0.1;
}

.hero-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    position: relative;
}

.hero-card .hero-subtitle {
    opacity: 0.85;
    font-size: 0.9rem;
    position: relative;
}

.hero-card .hero-periodo {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.85rem;
    margin-top: 1rem;
    position: relative;
}

/* Stat Cards */
.stat-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #fff;
    border-radius: 0.75rem;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    position: relative;
    overflow: hidden;
    display: block;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    text-decoration: none;
}

.stat-card .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-card .stat-label {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
}

.stat-card .stat-icon {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.stat-card.stat-pastor .stat-number { color: #6366f1; }
.stat-card.stat-pastor .stat-icon { background: rgba(99,102,241,0.1); color: #6366f1; }
.stat-card.stat-junta .stat-number { color: #10b981; }
.stat-card.stat-junta .stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
.stat-card.stat-areas .stat-number { color: #0891b2; }
.stat-card.stat-areas .stat-icon { background: rgba(8,145,178,0.1); color: #0891b2; }
.stat-card.stat-lideres .stat-number { color: #f59e0b; }
.stat-card.stat-lideres .stat-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }

/* Section Cards */
.section-card {
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    height: 100%;
}

.section-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.section-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-header .section-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.6rem;
    border-radius: 1rem;
}

.section-body { padding: 1.25rem; }

/* Pastor Card */
.pastor-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.pastor-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #6366f1;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
    flex-shrink: 0;
}

.pastor-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.75rem;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
    flex-shrink: 0;
}

.pastor-info h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #1f2937;
}

.pastor-info .pastor-cargo { color: #6366f1; font-weight: 500; font-size: 0.9rem; }
.pastor-info .pastor-contacto { font-size: 0.8rem; color: #9ca3af; margin-top: 0.5rem; }

/* Junta Members */
.junta-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.junta-member {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f9fafb;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
}

.junta-member img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }

.junta-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 600;
    flex-shrink: 0;
}

.junta-member-info { line-height: 1.2; }
.junta-member-name { font-weight: 500; color: #374151; }
.junta-member-cargo { font-size: 0.75rem; color: #9ca3af; }

.junta-more {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
    text-decoration: none;
}

.junta-more:hover { background: #d1d5db; color: #374151; }

/* Ministerios Grid */
.ministerios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
}

.ministerio-card {
    background: #f9fafb;
    border-radius: 0.625rem;
    padding: 1rem;
    border-left: 4px solid #0891b2;
    transition: all 0.2s ease;
}

.ministerio-card:hover { background: #f0fdfa; border-left-color: #0dcaf0; }
.ministerio-card.vacante { border-left-color: #f59e0b; background: #fffbeb; }

.ministerio-nombre {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ministerio-nombre i { color: #0891b2; font-size: 0.8rem; }
.ministerio-card.vacante .ministerio-nombre i { color: #f59e0b; }

.ministerio-lideres { font-size: 0.8rem; color: #6b7280; }
.ministerio-lideres .lider-item { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.35rem; }
.ministerio-lideres .lider-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; }

.ministerio-lideres .lider-avatar-mini {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #0891b2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.55rem;
    font-weight: 600;
}

.vacante-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: #fef3c7;
    color: #d97706;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
}

/* Actions Section */
.actions-section {
    background: #f9fafb;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-top: 1.5rem;
}

.actions-section h4 {
    font-size: 0.85rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.actions-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 2px solid #e5e7eb;
    background: #fff;
    color: #374151;
}

.action-btn:hover {
    border-color: #0891b2;
    color: #0891b2;
    background: #f0fdfa;
    text-decoration: none;
}

/* Empty State */
.empty-state { text-align: center; padding: 2rem; color: #9ca3af; }
.empty-state i { font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5; display: block; }
.empty-state p { margin: 0 0 1rem; }

/* Responsive */
@media (max-width: 991.98px) {
    .stat-cards { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 767.98px) {
    .hero-card { padding: 1.25rem; }
    .hero-card h2 { font-size: 1.25rem; }
    .hero-card .hero-icon { font-size: 3rem; right: 1rem; }
    .stat-cards { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
    .stat-card { padding: 1rem; }
    .stat-card .stat-number { font-size: 2rem; }
    .stat-card .stat-icon { width: 30px; height: 30px; font-size: 0.8rem; }
    .pastor-profile { flex-direction: column; text-align: center; }
    .ministerios-grid { grid-template-columns: repeat(2, 1fr); }
    .actions-grid { flex-direction: column; }
    .action-btn { justify-content: center; }
}

@media (max-width: 575.98px) {
    .hero-card .hero-periodo { font-size: 0.75rem; padding: 0.4rem 0.75rem; flex-wrap: wrap; justify-content: center; }
    .ministerios-grid { grid-template-columns: 1fr 1fr; gap: 0.5rem; }
    .ministerio-card { padding: 0.75rem; }
    .junta-member { flex: 1 1 100%; }
}
</style>

<?php if (($ROL_NOMBRE ?? '') === 'super_admin'): ?>
<!-- Selector de Iglesia -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get">
            <label class="form-label fw-semibold small mb-2">
                <i class="fas fa-church text-primary me-1"></i>Seleccionar Iglesia
            </label>
            <select name="iglesia_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Seleccione una iglesia --</option>
                <?php foreach ($iglesias as $igl): ?>
                    <option value="<?php echo $igl['id']; ?>" <?php echo $iglesia_id == $igl['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(($igl['conferencia_nombre'] ?? '') . ' → ' . ($igl['distrito_nombre'] ?? '') . ' → ' . ($igl['nombre'] ?? '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($iglesia_id === 0): ?>
<!-- Sin iglesia seleccionada -->
<div class="empty-state">
    <i class="fas fa-church"></i>
    <p>Seleccione una iglesia para ver su estructura organizacional</p>
</div>

<?php elseif (!$periodo_activo): ?>
<!-- Sin período activo -->
<?php if ($iglesia_info): ?>
<div class="hero-card">
    <i class="fas fa-church hero-icon"></i>
    <h2><?php echo htmlspecialchars($iglesia_info['nombre'] ?? 'Iglesia'); ?></h2>
    <p class="hero-subtitle"><?php echo htmlspecialchars(($iglesia_info['conferencia_nombre'] ?? '') . ' • ' . ($iglesia_info['distrito_nombre'] ?? '')); ?></p>
</div>
<?php endif; ?>

<div class="alert alert-warning d-flex align-items-center gap-3">
    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
    <div>
        <strong>Sin período activo</strong><br>
        <span class="text-muted">Esta iglesia no tiene un período configurado.</span>
        <?php if ($puede_gestionar): ?>
            <a href="periodos/crear.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link ms-2">Crear período →</a>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- HERO CARD -->
<div class="hero-card">
    <i class="fas fa-church hero-icon"></i>
    <h2><i class="fas fa-landmark me-2"></i><?php echo htmlspecialchars($iglesia_info['nombre'] ?? 'Iglesia'); ?></h2>
    <p class="hero-subtitle"><?php echo htmlspecialchars(($iglesia_info['conferencia_nombre'] ?? '') . ' • ' . ($iglesia_info['distrito_nombre'] ?? '')); ?></p>
    <div class="hero-periodo">
        <i class="fas fa-calendar-alt"></i>
        <span><?php echo htmlspecialchars($periodo_activo['nombre'] ?? 'Período'); ?></span>
        <?php if ($tiempo_restante): ?>
        <span class="opacity-75">•</span>
        <span class="opacity-75"><i class="fas fa-hourglass-half me-1"></i><?php echo $tiempo_restante; ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="stat-card stat-pastor">
        <span class="stat-icon"><i class="fas fa-cross"></i></span>
        <div class="stat-number"><?php echo $stats['pastores']; ?></div>
        <div class="stat-label">Pastor<?php echo $stats['pastores'] != 1 ? 'es' : ''; ?></div>
    </a>
    <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="stat-card stat-junta">
        <span class="stat-icon"><i class="fas fa-users"></i></span>
        <div class="stat-number"><?php echo $stats['junta']; ?></div>
        <div class="stat-label">Junta Adm.</div>
    </a>
    <a href="ministerios/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="stat-card stat-areas">
        <span class="stat-icon"><i class="fas fa-hands-helping"></i></span>
        <div class="stat-number"><?php echo $stats['areas']; ?></div>
        <div class="stat-label">Áreas Min.</div>
    </a>
    <a href="lideres/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="stat-card stat-lideres">
        <span class="stat-icon"><i class="fas fa-user-tie"></i></span>
        <div class="stat-number"><?php echo $stats['lideres']; ?></div>
        <div class="stat-label">Líderes</div>
    </a>
</div>

<!-- LIDERAZGO Y JUNTA -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-cross text-primary"></i> Liderazgo Pastoral</h3>
            </div>
            <div class="section-body">
                <?php if (empty($pastores)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>Sin pastor asignado</p>
                        <?php if ($puede_gestionar): ?>
                            <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Configurar Junta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($pastores as $p): ?>
                    <div class="pastor-profile mb-3">
                        <?php if (!empty($p['foto'])): ?>
                            <img src="../../uploads/miembros/<?php echo htmlspecialchars($p['foto']); ?>" alt="Foto" class="pastor-avatar">
                        <?php else: ?>
                            <div class="pastor-avatar-placeholder">
                                <?php echo getInicialesEstructura($p['nombre'] ?? '', $p['apellido'] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                        <div class="pastor-info">
                            <h4><?php echo htmlspecialchars(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')); ?></h4>
                            <div class="pastor-cargo"><?php echo htmlspecialchars($p['cargo_nombre'] ?? 'Pastor'); ?></div>
                            <?php if (!empty($p['telefono'])): ?>
                            <div class="pastor-contacto"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($p['telefono']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-users text-success"></i> Junta Administrativa</h3>
                <span class="section-badge bg-success bg-opacity-10 text-success"><?php echo $stats['junta']; ?> miembros</span>
            </div>
            <div class="section-body">
                <?php if (empty($junta_miembros)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>Sin miembros asignados</p>
                        <?php if ($puede_gestionar): ?>
                            <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-plus me-1"></i> Configurar Junta
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="junta-grid">
                        <?php 
                        $mostrar = min(6, count($junta_miembros));
                        for ($i = 0; $i < $mostrar; $i++): 
                            $jm = $junta_miembros[$i];
                        ?>
                        <div class="junta-member">
                            <?php if (!empty($jm['foto'])): ?>
                                <img src="../../uploads/miembros/<?php echo htmlspecialchars($jm['foto']); ?>" alt="Foto">
                            <?php else: ?>
                                <div class="junta-avatar-sm"><?php echo getInicialesEstructura($jm['nombre'] ?? '', $jm['apellido'] ?? ''); ?></div>
                            <?php endif; ?>
                            <div class="junta-member-info">
                                <div class="junta-member-name"><?php echo htmlspecialchars(($jm['nombre'] ?? '') . ' ' . ($jm['apellido'] ?? '')); ?></div>
                                <div class="junta-member-cargo"><?php echo htmlspecialchars($jm['cargo_nombre'] ?? ''); ?></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                        
                        <?php if (count($junta_miembros) > 6): ?>
                        <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="junta-more">+<?php echo count($junta_miembros) - 6; ?> más</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3 text-end">
                        <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-success">
                            Ver todos <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MINISTERIOS -->
<div class="section-card mb-4">
    <div class="section-header">
        <h3><i class="fas fa-hands-helping text-primary"></i> Áreas Ministeriales</h3>
        <span class="section-badge bg-primary bg-opacity-10 text-primary"><?php echo $stats['areas']; ?> áreas</span>
    </div>
    <div class="section-body">
        <?php if (empty($areas_disponibles)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p>No hay áreas ministeriales configuradas</p>
            </div>
        <?php else: ?>
            <div class="ministerios-grid">
                <?php foreach ($areas_disponibles as $area): 
                    $area_lideres = $lideres_por_area[$area['id']]['lideres'] ?? [];
                    $es_vacante = empty($area_lideres);
                ?>
                <div class="ministerio-card <?php echo $es_vacante ? 'vacante' : ''; ?>">
                    <div class="ministerio-nombre">
                        <i class="fas <?php echo $es_vacante ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <?php echo htmlspecialchars($area['nombre'] ?? ''); ?>
                    </div>
                    <div class="ministerio-lideres">
                        <?php if ($es_vacante): ?>
                            <span class="vacante-badge"><i class="fas fa-user-slash"></i> Sin líder</span>
                            <?php if ($puede_gestionar): ?>
                            <a href="lideres/asignar.php?iglesia_id=<?php echo $iglesia_id; ?>&area_id=<?php echo $area['id']; ?>" class="btn btn-sm btn-outline-warning mt-2 w-100">
                                <i class="fas fa-plus"></i> Asignar
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php foreach ($area_lideres as $lider): ?>
                            <div class="lider-item">
                                <?php if (!empty($lider['foto'])): ?>
                                    <img src="../../uploads/miembros/<?php echo htmlspecialchars($lider['foto']); ?>" alt="" class="lider-avatar">
                                <?php else: ?>
                                    <span class="lider-avatar-mini"><?php echo getInicialesEstructura($lider['nombre'] ?? '', $lider['apellido'] ?? ''); ?></span>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($lider['nombre'] ?? ''); ?></span>
                                <?php if (($lider['tipo'] ?? '') === 'lider'): ?>
                                    <i class="fas fa-star text-warning" style="font-size: 0.6rem;"></i>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ACCIONES RÁPIDAS -->
<?php if ($puede_gestionar): ?>
<div class="actions-section">
    <h4><i class="fas fa-bolt text-warning"></i> Acciones Rápidas</h4>
    <div class="actions-grid">
        <a href="periodos/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="action-btn"><i class="fas fa-calendar-alt"></i> Períodos</a>
        <a href="junta/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="action-btn"><i class="fas fa-user-plus"></i> Junta</a>
        <a href="lideres/asignar.php?iglesia_id=<?php echo $iglesia_id; ?>" class="action-btn"><i class="fas fa-user-tie"></i> Asignar Líder</a>
        <a href="ministerios/index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="action-btn"><i class="fas fa-hands-helping"></i> Ministerios</a>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>