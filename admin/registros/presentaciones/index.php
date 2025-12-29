<?php
/**
 * Listado de Presentaciones de Niños
 * Sistema Concilio - Módulo Registros
 */

$page_title = "Presentaciones de Niños";
require_once __DIR__ . '/../../includes/header.php';

// Verificar permisos (pastor y secretaria)
$roles_permitidos = array('super_admin', 'obispo', 'pastor', 'secretaria');
if (!in_array($ROL_NOMBRE, $roles_permitidos)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
if (!in_array($ROL_NOMBRE, $roles_permitidos)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección. Solo Pastor o Secretaria.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($iglesia_id <= 0) {
    echo '<div class="alert alert-warning">No tiene una iglesia asignada.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$anio_filtro = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'activo';

// Paginación
$por_pagina = 15;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta - Solo registros de la iglesia local
$where = "WHERE p.iglesia_id = ? AND p.estado = ?";
$params = array($iglesia_id, $estado_filtro);
$types = "is";

if (!empty($busqueda)) {
    $where .= " AND (p.nombres LIKE ? OR p.apellidos LIKE ? OR CONCAT(p.nombres, ' ', p.apellidos) LIKE ?)";
    $busqueda_like = "%{$busqueda}%";
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $params[] = $busqueda_like;
    $types .= "sss";
}

if ($anio_filtro > 0) {
    $where .= " AND YEAR(p.fecha_presentacion) = ?";
    $params[] = $anio_filtro;
    $types .= "i";
}

// Contar total
$sql_count = "SELECT COUNT(*) as total FROM presentacion_ninos p $where";
$stmt = $conexion->prepare($sql_count);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_registros = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros
$sql = "SELECT p.*, 
        CONCAT(p.nombres, ' ', COALESCE(p.apellidos, '')) AS nombre_completo,
        TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad_actual
        FROM presentacion_ninos p 
        $where 
        ORDER BY p.fecha_presentacion DESC, p.id DESC 
        LIMIT ? OFFSET ?";

$params[] = $por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener años disponibles para filtro
$sql_anios = "SELECT DISTINCT YEAR(fecha_presentacion) as anio 
              FROM presentacion_ninos 
              WHERE iglesia_id = ? 
              ORDER BY anio DESC";
$stmt = $conexion->prepare($sql_anios);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$anios_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Si no hay años, agregar el actual
if (empty($anios_disponibles)) {
    $anios_disponibles = array(array('anio' => date('Y')));
}

// Estadísticas rápidas
$sql_stats = "SELECT 
              COUNT(*) as total_anio,
              SUM(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) as ninos,
              SUM(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) as ninas
              FROM presentacion_ninos 
              WHERE iglesia_id = ? AND estado = 'activo' AND YEAR(fecha_presentacion) = ?";
$stmt = $conexion->prepare($sql_stats);
$stmt->bind_param("ii", $iglesia_id, $anio_filtro);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Encabezado y Botón Nuevo -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="mb-1"><i class="fas fa-baby text-primary me-2"></i>Presentaciones de Niños</h4>
        <p class="text-muted mb-0 small">Registro de actas de presentación al Señor</p>
    </div>
    <a href="crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Nueva Presentación
    </a>
</div>

<!-- Estadísticas del año -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary"><?php echo $stats['total_anio'] ?? 0; ?></h3>
                <small class="text-muted">Total <?php echo $anio_filtro; ?></small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm bg-info bg-opacity-10">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info"><?php echo $stats['ninos'] ?? 0; ?></h3>
                <small class="text-muted">Niños</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-danger"><?php echo $stats['ninas'] ?? 0; ?></h3>
                <small class="text-muted">Niñas</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small">Buscar por nombre</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Nombre del niño..." value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small">Año</label>
                <select name="anio" class="form-select">
                    <?php foreach ($anios_disponibles as $a): ?>
                    <option value="<?php echo $a['anio']; ?>" <?php echo $anio_filtro == $a['anio'] ? 'selected' : ''; ?>>
                        <?php echo $a['anio']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Estado</label>
                <select name="estado" class="form-select">
                    <option value="activo" <?php echo $estado_filtro == 'activo' ? 'selected' : ''; ?>>Activos</option>
                    <option value="anulado" <?php echo $estado_filtro == 'anulado' ? 'selected' : ''; ?>>Anulados</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Registros -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (count($registros) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Acta No.</th>
                        <th>Nombre del Niño/a</th>
                        <th class="d-none d-md-table-cell">Fecha Presentación</th>
                        <th class="d-none d-md-table-cell">Padres</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($reg['numero_acta']); ?></span>
                        </td>
                        <td>
                            <div class="fw-bold">
                                <?php echo htmlspecialchars($reg['nombre_completo']); ?>
                                <?php if ($reg['sexo'] == 'M'): ?>
                                    <i class="fas fa-mars text-info ms-1" title="Niño"></i>
                                <?php else: ?>
                                    <i class="fas fa-venus text-danger ms-1" title="Niña"></i>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted d-md-none">
                                <?php echo date('d/m/Y', strtotime($reg['fecha_presentacion'])); ?>
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <?php echo date('d/m/Y', strtotime($reg['fecha_presentacion'])); ?>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <?php 
                                $padres = array();
                                if (!empty($reg['nombre_padre'])) $padres[] = $reg['nombre_padre'];
                                if (!empty($reg['nombre_madre'])) $padres[] = $reg['nombre_madre'];
                                echo !empty($padres) ? htmlspecialchars(implode(' / ', $padres)) : '<span class="text-muted">No registrados</span>';
                                ?>
                            </small>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="ver.php?id=<?php echo $reg['id']; ?>" class="btn btn-outline-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="imprimir.php?id=<?php echo $reg['id']; ?>" class="btn btn-outline-success" title="Imprimir" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="editar.php?id=<?php echo $reg['id']; ?>" class="btn btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&anio=<?php echo $anio_filtro; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo $estado_filtro; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&anio=<?php echo $anio_filtro; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo $estado_filtro; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&anio=<?php echo $anio_filtro; ?>&busqueda=<?php echo urlencode($busqueda); ?>&estado=<?php echo $estado_filtro; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <p class="text-center text-muted small mt-2 mb-0">
                Mostrando <?php echo count($registros); ?> de <?php echo $total_registros; ?> registros
            </p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-baby fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-3">No hay presentaciones registradas<?php echo $anio_filtro ? " en {$anio_filtro}" : ''; ?></p>
            <a href="crear.php" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Registrar Primera Presentación
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
