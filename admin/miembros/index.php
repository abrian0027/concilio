<?php
declare(strict_types=1);

$page_title = "Gestión de Miembros";
require_once __DIR__ . '/../includes/header.php';

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

$puede_crear = in_array($ROL_NOMBRE ?? '', ['super_admin', 'pastor', 'secretaria']);
$puede_eliminar = in_array($ROL_NOMBRE ?? '', ['super_admin', 'pastor']);

// Filtros
$filtro_ministerio = isset($_GET['ministerio']) ? (int)$_GET['ministerio'] : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_estado_miembro = isset($_GET['estado_miembro']) ? $_GET['estado_miembro'] : '';
$filtro_bautizado = isset($_GET['bautizado']) ? $_GET['bautizado'] : '';
$filtro_lider = isset($_GET['lider']) ? $_GET['lider'] : '';
$filtro_zona = isset($_GET['zona']) ? (int)$_GET['zona'] : 0;
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Paginación
$por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta según rol
$where = [];
$params = [];
$types = '';

if (($ROL_NOMBRE ?? '') === 'pastor' || ($ROL_NOMBRE ?? '') === 'secretaria') {
    $where[] = "m.iglesia_id = ?";
    $params[] = $IGLESIA_ID ?? 0;
    $types .= 'i';
} elseif (($ROL_NOMBRE ?? '') === 'super_distrito') {
    $where[] = "i.distrito_id = ?";
    $params[] = $DISTRITO_ID ?? 0;
    $types .= 'i';
} elseif (($ROL_NOMBRE ?? '') === 'super_conferencia') {
    $where[] = "d.conferencia_id = ?";
    $params[] = $CONFERENCIA_ID ?? 0;
    $types .= 'i';
}

if ($filtro_ministerio > 0) {
    $where[] = "m.ministerio_id = ?";
    $params[] = $filtro_ministerio;
    $types .= 'i';
}

if ($filtro_zona > 0) {
    $where[] = "m.zona_id = ?";
    $params[] = $filtro_zona;
    $types .= 'i';
}

if ($filtro_estado !== '') {
    $where[] = "m.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if ($filtro_estado_miembro !== '') {
    $where[] = "m.estado_miembro = ?";
    $params[] = $filtro_estado_miembro;
    $types .= 's';
}

if ($filtro_bautizado === '1') {
    $where[] = "m.es_bautizado = 1";
}

if ($filtro_lider === '1') {
    $where[] = "m.es_lider = 1";
}

if ($buscar !== '') {
    $where[] = "(m.nombre LIKE ? OR m.apellido LIKE ? OR m.numero_documento LIKE ? OR m.telefono LIKE ? OR CONCAT(m.nombre, ' ', m.apellido) LIKE ?)";
    $buscar_like = "%$buscar%";
    $params = array_merge($params, [$buscar_like, $buscar_like, $buscar_like, $buscar_like, $buscar_like]);
    $types .= 'sssss';
}

$where_clause = !empty($where) ? " WHERE " . implode(' AND ', $where) : "";

$sql_base = "FROM miembros m
             INNER JOIN iglesias i ON i.id = m.iglesia_id
             INNER JOIN distritos d ON d.id = i.distrito_id
             LEFT JOIN ministerios min ON min.id = m.ministerio_id";

// Stats base (sin filtros de usuario)
$where_base = [];
$params_base = [];
$types_base = '';

if (($ROL_NOMBRE ?? '') === 'pastor' || ($ROL_NOMBRE ?? '') === 'secretaria') {
    $where_base[] = "m.iglesia_id = ?";
    $params_base[] = $IGLESIA_ID ?? 0;
    $types_base .= 'i';
} elseif (($ROL_NOMBRE ?? '') === 'super_distrito') {
    $where_base[] = "i.distrito_id = ?";
    $params_base[] = $DISTRITO_ID ?? 0;
    $types_base .= 'i';
} elseif (($ROL_NOMBRE ?? '') === 'super_conferencia') {
    $where_base[] = "d.conferencia_id = ?";
    $params_base[] = $CONFERENCIA_ID ?? 0;
    $types_base .= 'i';
}

$where_base_clause = !empty($where_base) ? " WHERE " . implode(' AND ', $where_base) : "";

$stats = ['total' => 0, 'activos' => 0, 'bautizados' => 0, 'lideres' => 0, 'inactivos' => 0];

try {
    // Total
    $sql = "SELECT COUNT(*) as total $sql_base $where_base_clause";
    $stmt = $conexion->prepare($sql);
    if (!empty($params_base)) $stmt->bind_param($types_base, ...$params_base);
    $stmt->execute();
    $stats['total'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Activos
    $sql = "SELECT COUNT(*) as total $sql_base " . ($where_base_clause ? $where_base_clause . " AND " : " WHERE ") . "m.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    if (!empty($params_base)) $stmt->bind_param($types_base, ...$params_base);
    $stmt->execute();
    $stats['activos'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Bautizados
    $sql = "SELECT COUNT(*) as total $sql_base " . ($where_base_clause ? $where_base_clause . " AND " : " WHERE ") . "m.es_bautizado = 1 AND m.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    if (!empty($params_base)) $stmt->bind_param($types_base, ...$params_base);
    $stmt->execute();
    $stats['bautizados'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Líderes
    $sql = "SELECT COUNT(*) as total $sql_base " . ($where_base_clause ? $where_base_clause . " AND " : " WHERE ") . "m.es_lider = 1 AND m.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    if (!empty($params_base)) $stmt->bind_param($types_base, ...$params_base);
    $stmt->execute();
    $stats['lideres'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    
    // Inactivos
    $sql = "SELECT COUNT(*) as total $sql_base " . ($where_base_clause ? $where_base_clause . " AND " : " WHERE ") . "m.estado != 'activo'";
    $stmt = $conexion->prepare($sql);
    if (!empty($params_base)) $stmt->bind_param($types_base, ...$params_base);
    $stmt->execute();
    $stats['inactivos'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    // Total con filtros
    $sql = "SELECT COUNT(*) as total $sql_base $where_clause";
    $stmt = $conexion->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_registros = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $total_paginas = (int)ceil($total_registros / $por_pagina);
    $stmt->close();

    // Datos - Ordenados por los últimos registrados primero
    $sql = "SELECT m.*, i.nombre AS iglesia_nombre, min.nombre AS ministerio_nombre 
            $sql_base $where_clause ORDER BY m.id DESC LIMIT ? OFFSET ?";
    $params_pag = array_merge($params, [$por_pagina, $offset]);
    $types_pag = $types . 'ii';
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types_pag, ...$params_pag);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 ORDER BY nombre");

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar los miembros: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

function calcularEdadMiembro($fecha): int {
    if (empty($fecha)) return 0;
    return (int)(new DateTime($fecha))->diff(new DateTime())->y;
}

function getInicialesMiembro($nombre, $apellido): string {
    $n = !empty($nombre) ? mb_strtoupper(mb_substr($nombre, 0, 1)) : '';
    $a = !empty($apellido) ? mb_strtoupper(mb_substr($apellido, 0, 1)) : '';
    return $n . $a;
}

function buildUrl($params_override = []): string {
    global $filtro_ministerio, $filtro_estado, $filtro_estado_miembro, $filtro_bautizado, $filtro_lider, $buscar;
    
    $params = [];
    if ($filtro_ministerio > 0) $params['ministerio'] = $filtro_ministerio;
    if ($filtro_estado !== '') $params['estado'] = $filtro_estado;
    if ($filtro_estado_miembro !== '') $params['estado_miembro'] = $filtro_estado_miembro;
    if ($filtro_bautizado !== '') $params['bautizado'] = $filtro_bautizado;
    if ($filtro_lider !== '') $params['lider'] = $filtro_lider;
    if ($buscar !== '') $params['buscar'] = $buscar;
    
    $params = array_merge($params, $params_override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== '0');
    
    return 'index.php' . (!empty($params) ? '?' . http_build_query($params) : '');
}

$hay_filtros = $filtro_ministerio > 0 || $filtro_zona > 0 || $filtro_estado !== '' || $filtro_estado_miembro !== '' || 
               $filtro_bautizado !== '' || $filtro_lider !== '' || $buscar !== '';

// Obtener zonas para el filtro
$zonas_filtro = [];
$iglesia_para_zonas = ($ROL_NOMBRE === 'pastor' || $ROL_NOMBRE === 'secretaria') ? ($IGLESIA_ID ?? 0) : 0;
if ($iglesia_para_zonas > 0) {
    $result_zonas = $conexion->query("SELECT id, codigo, nombre FROM zonas WHERE iglesia_id = $iglesia_para_zonas AND activo = 1 ORDER BY codigo");
    if ($result_zonas) {
        $zonas_filtro = $result_zonas->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<style>
.members-header{background:linear-gradient(135deg,#0891b2 0%,#0dcaf0 50%,#22d3ee 100%);border-radius:1rem;padding:1.5rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden}
.members-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,transparent 70%);border-radius:50%}
.members-header-content{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;position:relative}
.members-header h1{font-size:1.5rem;font-weight:700;margin:0;display:flex;align-items:center;gap:0.5rem}
.members-header .header-subtitle{opacity:0.85;font-size:0.9rem;margin-top:0.25rem}
.members-header .btn-primary{background:#fff;border-color:#fff;color:#0891b2}
.members-header .btn-primary:hover{background:#f0fdfa;color:#0891b2}

.stat-cards{display:grid;grid-template-columns:repeat(5,1fr);gap:0.75rem;margin-bottom:1.5rem}
.stat-card{background:#fff;border-radius:0.75rem;padding:1rem;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06);transition:all 0.3s ease;text-decoration:none;color:inherit;border:2px solid transparent;display:block}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,0.1);text-decoration:none}
.stat-card.active{border-color:#0891b2;background:#f0fdfa}
.stat-card .stat-number{font-size:1.75rem;font-weight:700;line-height:1;margin-bottom:0.25rem}
.stat-card .stat-label{font-size:0.75rem;color:#6b7280;font-weight:500;text-transform:uppercase}
.stat-card.stat-total .stat-number{color:#0891b2}
.stat-card.stat-activos .stat-number{color:#10b981}
.stat-card.stat-bautizados .stat-number{color:#6366f1}
.stat-card.stat-lideres .stat-number{color:#f59e0b}
.stat-card.stat-inactivos .stat-number{color:#ef4444}

.search-section{background:#fff;border-radius:0.75rem;padding:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-bottom:1rem}
.search-row{display:flex;gap:0.75rem;align-items:stretch;flex-wrap:wrap}
.search-input-wrapper{flex:1;min-width:200px;position:relative}
.search-input-wrapper i{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af}
.search-input-wrapper input{width:100%;padding:0.75rem 1rem 0.75rem 2.75rem;border:2px solid #e5e7eb;border-radius:0.5rem;font-size:0.95rem;transition:all 0.2s ease}
.search-input-wrapper input:focus{outline:none;border-color:#0891b2;box-shadow:0 0 0 3px rgba(8,145,178,0.1)}
.filter-toggle{display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;background:#f9fafb;border:2px solid #e5e7eb;border-radius:0.5rem;color:#374151;cursor:pointer;transition:all 0.2s ease}
.filter-toggle:hover{background:#f3f4f6;border-color:#d1d5db}
.filter-toggle.active{background:#0891b2;border-color:#0891b2;color:#fff}
.filter-badge{background:#ef4444;color:#fff;font-size:0.7rem;padding:0.1rem 0.4rem;border-radius:1rem;font-weight:600}
.filters-panel{display:none;padding-top:1rem;margin-top:1rem;border-top:1px solid #e5e7eb}
.filters-panel.show{display:block}
.filters-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem}
.filter-group label{display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:0.35rem}
.filter-group select{width:100%;padding:0.6rem 0.75rem;border:2px solid #e5e7eb;border-radius:0.5rem;font-size:0.9rem;background:#fff;cursor:pointer}
.filter-group select:focus{outline:none;border-color:#0891b2}
.filter-actions{display:flex;gap:0.5rem;margin-top:1rem;justify-content:flex-end}

.results-info{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;font-size:0.9rem;color:#6b7280}
.results-info strong{color:#0891b2}

/* Estilos de Lista de Miembros */
.members-table-container{background:#fff;border-radius:0.75rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden}
.members-table{width:100%;border-collapse:collapse}
.members-table thead{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)}
.members-table th{padding:1rem;text-align:left;font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #e2e8f0}
.members-table tbody tr{transition:all 0.2s ease;cursor:pointer;border-bottom:1px solid #f1f5f9}
.members-table tbody tr:hover{background:#f0fdfa}
.members-table tbody tr:last-child{border-bottom:none}
.members-table td{padding:0.875rem 1rem;vertical-align:middle}

.member-cell{display:flex;align-items:center;gap:0.75rem}
.member-avatar{width:45px;height:45px;border-radius:12px;object-fit:cover;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.member-avatar-placeholder{width:45px;height:45px;border-radius:12px;background:linear-gradient(135deg,#0891b2 0%,#0dcaf0 100%);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;font-weight:700;box-shadow:0 2px 8px rgba(8,145,178,0.3)}
.member-info{display:flex;flex-direction:column;gap:0.125rem}
.member-name{font-weight:600;color:#1e293b;font-size:0.95rem}
.member-document{font-size:0.8rem;color:#94a3b8}

.member-phone{display:flex;align-items:center;gap:0.5rem;color:#64748b;font-size:0.9rem}
.member-phone i{color:#0891b2;font-size:0.8rem}

.member-ministry{display:inline-flex;align-items:center;gap:0.375rem;background:rgba(8,145,178,0.1);color:#0891b2;padding:0.25rem 0.625rem;border-radius:1rem;font-size:0.8rem;font-weight:500}
.member-ministry i{font-size:0.7rem}
.member-ministry.empty{background:#f1f5f9;color:#94a3b8}

.member-badges{display:flex;gap:0.375rem}
.member-badge{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.7rem}
.member-badge.bautizado{background:rgba(99,102,241,0.1);color:#6366f1}
.member-badge.lider{background:rgba(245,158,11,0.1);color:#f59e0b}

.member-status{font-size:0.7rem;font-weight:600;padding:0.3rem 0.625rem;border-radius:1rem;text-transform:uppercase;letter-spacing:0.3px}
.member-status.activo{background:rgba(16,185,129,0.1);color:#059669}
.member-status.inactivo{background:rgba(107,114,128,0.1);color:#6b7280}
.member-status.fallecido{background:rgba(239,68,68,0.1);color:#dc2626}
.member-status.trasladado{background:rgba(245,158,11,0.1);color:#d97706}

.member-actions{display:flex;gap:0.375rem}
.member-actions .btn{width:32px;height:32px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:0.8rem}

.pagination-wrapper{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-top:1.5rem;padding:1rem;background:#fff;border-radius:0.75rem;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.pagination-info{font-size:0.9rem;color:#6b7280}
.pagination{margin:0;gap:0.25rem}
.pagination .page-link{border-radius:0.375rem;border:none;color:#374151;padding:0.5rem 0.85rem}
.pagination .page-link:hover{background:#f0fdfa;color:#0891b2}
.pagination .page-item.active .page-link{background:#0891b2;color:#fff}
.pagination .page-item.disabled .page-link{color:#d1d5db}

.empty-state{text-align:center;padding:3rem;background:#fff;border-radius:0.75rem;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
.empty-state i{font-size:3rem;color:#d1d5db;margin-bottom:1rem}
.empty-state h3{font-size:1.1rem;color:#6b7280;margin-bottom:0.5rem}
.empty-state p{color:#9ca3af;margin-bottom:1rem}

/* Responsive */
@media(max-width:991.98px){
    .members-table thead{display:none}
    .members-table tbody tr{display:block;padding:1rem;margin-bottom:0.5rem;border:1px solid #e2e8f0;border-radius:0.75rem}
    .members-table td{display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border:none}
    .members-table td::before{content:attr(data-label);font-weight:600;color:#64748b;font-size:0.75rem;text-transform:uppercase}
    .members-table td:first-child::before{content:none}
    .members-table td:first-child{justify-content:flex-start}
    .member-cell{width:100%}
}
@media(max-width:767.98px){
    .members-header{padding:1.25rem}.members-header h1{font-size:1.25rem}
    .members-header-content{flex-direction:column;align-items:stretch}
    .stat-cards{grid-template-columns:repeat(3,1fr)}.stat-card{padding:0.75rem}.stat-card .stat-number{font-size:1.5rem}.stat-card .stat-label{font-size:0.65rem}
    .search-row{flex-direction:column}
    .pagination-wrapper{flex-direction:column;text-align:center}
}
@media(max-width:575.98px){
    .stat-cards{grid-template-columns:repeat(2,1fr)}.stat-card:first-child{grid-column:span 2}
    .results-info{flex-direction:column;gap:0.5rem;text-align:center}
    .member-avatar,.member-avatar-placeholder{width:40px;height:40px;border-radius:10px}
    .member-name{font-size:0.9rem}
}
</style>

<!-- Hero Header -->
<div class="members-header">
    <div class="members-header-content">
        <div>
            <h1><i class="fas fa-users"></i> Gestión de Miembros</h1>
            <p class="header-subtitle"><?php echo isset($IGLESIA_NOMBRE) ? htmlspecialchars($IGLESIA_NOMBRE) : 'Sistema Concilio'; ?> • <?php echo number_format($stats['total']); ?> miembros</p>
        </div>
        <?php if ($puede_crear): ?>
        <a href="crear.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Nuevo Miembro</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php 
    $msg = $_GET['success'];
    echo $msg === 'creado' ? "Miembro registrado exitosamente." : ($msg === 'editado' ? "Miembro actualizado." : ($msg === 'eliminado' ? "Miembro eliminado." : "Operación exitosa."));
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="stat-cards">
    <a href="index.php" class="stat-card stat-total <?php echo !$hay_filtros ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
        <div class="stat-label">Total</div>
    </a>
    <a href="<?php echo buildUrl(['estado' => 'activo', 'bautizado' => '', 'lider' => '']); ?>" class="stat-card stat-activos <?php echo $filtro_estado === 'activo' && !$filtro_bautizado && !$filtro_lider ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo number_format($stats['activos']); ?></div>
        <div class="stat-label">Activos</div>
    </a>
    <a href="<?php echo buildUrl(['bautizado' => '1', 'estado' => 'activo', 'lider' => '']); ?>" class="stat-card stat-bautizados <?php echo $filtro_bautizado === '1' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo number_format($stats['bautizados']); ?></div>
        <div class="stat-label">Bautizados</div>
    </a>
    <a href="<?php echo buildUrl(['lider' => '1', 'estado' => 'activo', 'bautizado' => '']); ?>" class="stat-card stat-lideres <?php echo $filtro_lider === '1' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo number_format($stats['lideres']); ?></div>
        <div class="stat-label">Líderes</div>
    </a>
    <a href="<?php echo buildUrl(['estado' => 'inactivo', 'bautizado' => '', 'lider' => '']); ?>" class="stat-card stat-inactivos <?php echo $filtro_estado === 'inactivo' ? 'active' : ''; ?>">
        <div class="stat-number"><?php echo number_format($stats['inactivos']); ?></div>
        <div class="stat-label">Inactivos</div>
    </a>
</div>

<!-- Search Section -->
<div class="search-section">
    <form method="get" id="searchForm">
        <div class="search-row">
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="buscar" placeholder="Buscar por nombre, cédula, teléfono..." value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            <button type="button" class="filter-toggle <?php echo $hay_filtros ? 'active' : ''; ?>" onclick="toggleFilters()">
                <i class="fas fa-sliders-h"></i> Filtros
                <?php $nf = ($filtro_ministerio > 0 ? 1 : 0) + ($filtro_estado !== '' ? 1 : 0) + ($filtro_estado_miembro !== '' ? 1 : 0); if ($nf > 0): ?>
                <span class="filter-badge"><?php echo $nf; ?></span>
                <?php endif; ?>
            </button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </div>
        
        <div class="filters-panel <?php echo $hay_filtros ? 'show' : ''; ?>" id="filtersPanel">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>Ministerio</label>
                    <select name="ministerio">
                        <option value="0">Todos</option>
                        <?php if ($ministerios): while ($min = $ministerios->fetch_assoc()): ?>
                        <option value="<?php echo $min['id']; ?>" <?php echo $filtro_ministerio == $min['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($min['nombre']); ?></option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <?php if (!empty($zonas_filtro)): ?>
                <div class="filter-group">
                    <label>Zona/Grupo</label>
                    <select name="zona">
                        <option value="0">Todas</option>
                        <?php foreach ($zonas_filtro as $zf): ?>
                        <option value="<?php echo $zf['id']; ?>" <?php echo $filtro_zona == $zf['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($zf['codigo'] . ' - ' . $zf['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="filter-group">
                    <label>Membresía</label>
                    <select name="estado_miembro">
                        <option value="">Todas</option>
                        <option value="en_plena" <?php echo $filtro_estado_miembro === 'en_plena' ? 'selected' : ''; ?>>En Plena Comunión</option>
                        <option value="en_preparacion" <?php echo $filtro_estado_miembro === 'en_preparacion' ? 'selected' : ''; ?>>En Preparación</option>
                        <option value="miembro_menor" <?php echo $filtro_estado_miembro === 'miembro_menor' ? 'selected' : ''; ?>>Miembro Menor</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activos</option>
                        <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                        <option value="fallecido" <?php echo $filtro_estado === 'fallecido' ? 'selected' : ''; ?>>Fallecidos</option>
                        <option value="trasladado" <?php echo $filtro_estado === 'trasladado' ? 'selected' : ''; ?>>Trasladados</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-times me-1"></i> Limpiar</a>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i> Aplicar</button>
            </div>
        </div>
        <?php if ($filtro_bautizado): ?><input type="hidden" name="bautizado" value="<?php echo $filtro_bautizado; ?>"><?php endif; ?>
        <?php if ($filtro_lider): ?><input type="hidden" name="lider" value="<?php echo $filtro_lider; ?>"><?php endif; ?>
    </form>
</div>

<!-- Results Info -->
<div class="results-info">
    <span>Mostrando <strong><?php echo number_format($total_registros); ?></strong> miembros<?php echo $buscar ? ' para "'.htmlspecialchars($buscar).'"' : ''; ?></span>
    <?php if ($total_paginas > 1): ?><span>Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span><?php endif; ?>
</div>

<!-- Members Table -->
<?php if ($resultado && $resultado->num_rows > 0): ?>
<div class="members-table-container">
    <table class="members-table">
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Teléfono</th>
                <th>Ministerio</th>
                <th>Badges</th>
                <th>Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($m = $resultado->fetch_assoc()): ?>
            <tr onclick="window.location.href='ver.php?id=<?php echo $m['id']; ?>'">
                <td>
                    <div class="member-cell">
                        <?php if (!empty($m['foto']) && file_exists(__DIR__.'/../../uploads/miembros/'.$m['foto'])): ?>
                            <img src="../../uploads/miembros/<?php echo htmlspecialchars($m['foto']); ?>" class="member-avatar" alt="">
                        <?php else: ?>
                            <div class="member-avatar-placeholder"><?php echo getInicialesMiembro($m['nombre']??'',$m['apellido']??''); ?></div>
                        <?php endif; ?>
                        <div class="member-info">
                            <span class="member-name"><?php echo htmlspecialchars(($m['nombre']??'').' '.($m['apellido']??'')); ?></span>
                            <?php if (!empty($m['numero_documento'])): ?>
                            <span class="member-document"><?php echo htmlspecialchars($m['numero_documento']); ?></span>
                            <?php elseif (!empty($m['fecha_nacimiento'])): ?>
                            <span class="member-document"><?php echo calcularEdadMiembro($m['fecha_nacimiento']); ?> años</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td data-label="Teléfono">
                    <?php if (!empty($m['telefono'])): ?>
                    <div class="member-phone">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($m['telefono']); ?></span>
                    </div>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td data-label="Ministerio">
                    <?php if (!empty($m['ministerio_nombre'])): ?>
                    <span class="member-ministry">
                        <i class="fas fa-hands-helping"></i>
                        <?php echo htmlspecialchars($m['ministerio_nombre']); ?>
                    </span>
                    <?php else: ?>
                    <span class="member-ministry empty">Sin asignar</span>
                    <?php endif; ?>
                </td>
                <td data-label="Badges">
                    <div class="member-badges">
                        <?php if (!empty($m['es_bautizado'])): ?>
                        <span class="member-badge bautizado" title="Bautizado"><i class="fas fa-water"></i></span>
                        <?php endif; ?>
                        <?php if (!empty($m['es_lider'])): ?>
                        <span class="member-badge lider" title="Líder"><i class="fas fa-star"></i></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td data-label="Estado">
                    <span class="member-status <?php echo $m['estado']??'activo'; ?>"><?php echo ucfirst($m['estado']??'activo'); ?></span>
                </td>
                <td class="text-center" onclick="event.stopPropagation();">
                    <div class="member-actions">
                        <a href="ver.php?id=<?php echo $m['id']; ?>" class="btn btn-outline-info btn-sm" title="Ver"><i class="fas fa-eye"></i></a>
                        <?php if ($puede_crear): ?>
                        <a href="editar.php?id=<?php echo $m['id']; ?>" class="btn btn-outline-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php if ($total_paginas > 1): ?>
<div class="pagination-wrapper">
    <div class="pagination-info">Mostrando <?php echo min($offset+1,$total_registros); ?>-<?php echo min($offset+$por_pagina,$total_registros); ?> de <?php echo $total_registros; ?></div>
    <nav><ul class="pagination">
        <?php if ($pagina_actual > 1): ?><li class="page-item"><a class="page-link" href="<?php echo buildUrl(['pagina'=>$pagina_actual-1]); ?>"><i class="fas fa-chevron-left"></i></a></li><?php endif; ?>
        <?php
        $rango = 2; $inicio = max(1,$pagina_actual-$rango); $fin = min($total_paginas,$pagina_actual+$rango);
        if ($inicio > 1): ?><li class="page-item"><a class="page-link" href="<?php echo buildUrl(['pagina'=>1]); ?>">1</a></li><?php if ($inicio > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
        for ($i = $inicio; $i <= $fin; $i++): ?><li class="page-item <?php echo $i==$pagina_actual?'active':''; ?>"><a class="page-link" href="<?php echo buildUrl(['pagina'=>$i]); ?>"><?php echo $i; ?></a></li><?php endfor;
        if ($fin < $total_paginas): ?><?php if ($fin < $total_paginas-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="<?php echo buildUrl(['pagina'=>$total_paginas]); ?>"><?php echo $total_paginas; ?></a></li><?php endif;
        if ($pagina_actual < $total_paginas): ?><li class="page-item"><a class="page-link" href="<?php echo buildUrl(['pagina'=>$pagina_actual+1]); ?>"><i class="fas fa-chevron-right"></i></a></li><?php endif; ?>
    </ul></nav>
</div>
<?php endif; ?>

<?php else: ?>
<div class="empty-state">
    <i class="fas fa-users-slash"></i>
    <h3>No se encontraron miembros</h3>
    <p><?php echo $hay_filtros ? 'Intenta cambiar los filtros.' : 'Aún no hay miembros registrados.'; ?></p>
    <?php if ($hay_filtros): ?><a href="index.php" class="btn btn-outline-primary"><i class="fas fa-times me-1"></i> Limpiar filtros</a>
    <?php elseif ($puede_crear): ?><a href="crear.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Registrar primer miembro</a><?php endif; ?>
</div>
<?php endif; ?>

<script>
function toggleFilters(){document.getElementById('filtersPanel').classList.toggle('show');document.querySelector('.filter-toggle').classList.toggle('active');}
function confirmarEliminacion(id,nombre){if(confirm('¿Eliminar a "'+nombre+'"?\n\nEsta acción no se puede deshacer.'))window.location.href='eliminar.php?id='+id;}
document.querySelector('.search-input-wrapper input').addEventListener('keypress',function(e){if(e.key==='Enter')document.getElementById('searchForm').submit();});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
