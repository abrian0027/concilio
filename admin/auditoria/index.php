<?php
/**
 * SISTEMA DE AUDITORÍA - Panel de Consulta
 * Solo accesible para super_admin
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/auditoria.php';

// Solo super_admin puede ver esta página
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: ../dashboard.php');
    exit;
}

// Parámetros de filtro
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$filtro_modulo = isset($_GET['modulo']) ? $_GET['modulo'] : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-7 days'));
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 50;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta con filtros
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($filtro_usuario)) {
    $where .= " AND (usuario_nombre LIKE ? OR CAST(usuario_id AS CHAR) = ?)";
    $params[] = "%$filtro_usuario%";
    $params[] = $filtro_usuario;
    $types .= "ss";
}

if (!empty($filtro_accion)) {
    $where .= " AND accion = ?";
    $params[] = $filtro_accion;
    $types .= "s";
}

if (!empty($filtro_modulo)) {
    $where .= " AND modulo = ?";
    $params[] = $filtro_modulo;
    $types .= "s";
}

if (!empty($filtro_fecha_desde)) {
    $where .= " AND DATE(created_at) >= ?";
    $params[] = $filtro_fecha_desde;
    $types .= "s";
}

if (!empty($filtro_fecha_hasta)) {
    $where .= " AND DATE(created_at) <= ?";
    $params[] = $filtro_fecha_hasta;
    $types .= "s";
}

// Contar total de registros
$sql_count = "SELECT COUNT(*) as total FROM auditoria $where";
$stmt = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_registros = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_paginas = ceil($total_registros / $por_pagina);

// Obtener registros
$sql = "SELECT * FROM auditoria $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener lista de módulos para el filtro
$modulos = $conexion->query("SELECT DISTINCT modulo FROM auditoria ORDER BY modulo")->fetch_all(MYSQLI_ASSOC);

// Obtener estadísticas rápidas
$stats = [];
$stats['total_hoy'] = $conexion->query("SELECT COUNT(*) as c FROM auditoria WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$stats['logins_hoy'] = $conexion->query("SELECT COUNT(*) as c FROM auditoria WHERE DATE(created_at) = CURDATE() AND accion = 'login'")->fetch_assoc()['c'];
$stats['errores_hoy'] = $conexion->query("SELECT COUNT(*) as c FROM auditoria WHERE DATE(created_at) = CURDATE() AND accion = 'login_fallido'")->fetch_assoc()['c'];

$page_title = "Auditoría del Sistema";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1"><i class="fas fa-shield-alt text-primary me-2"></i>Auditoría del Sistema</h2>
        <p class="text-muted mb-0">Registro de actividades y cambios en el sistema</p>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['total_hoy']); ?></h3>
                        <small>Acciones hoy</small>
                    </div>
                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['logins_hoy']); ?></h3>
                        <small>Logins exitosos hoy</small>
                    </div>
                    <i class="fas fa-sign-in-alt fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($stats['errores_hoy']); ?></h3>
                        <small>Intentos fallidos hoy</small>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <i class="fas fa-filter me-2"></i>Filtros de búsqueda
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Nombre o ID" 
                       value="<?php echo htmlspecialchars($filtro_usuario); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Acción</label>
                <select name="accion" class="form-select">
                    <option value="">Todas</option>
                    <option value="login" <?php echo $filtro_accion === 'login' ? 'selected' : ''; ?>>Login</option>
                    <option value="logout" <?php echo $filtro_accion === 'logout' ? 'selected' : ''; ?>>Logout</option>
                    <option value="login_fallido" <?php echo $filtro_accion === 'login_fallido' ? 'selected' : ''; ?>>Login fallido</option>
                    <option value="crear" <?php echo $filtro_accion === 'crear' ? 'selected' : ''; ?>>Crear</option>
                    <option value="editar" <?php echo $filtro_accion === 'editar' ? 'selected' : ''; ?>>Editar</option>
                    <option value="eliminar" <?php echo $filtro_accion === 'eliminar' ? 'selected' : ''; ?>>Eliminar</option>
                    <option value="asignar" <?php echo $filtro_accion === 'asignar' ? 'selected' : ''; ?>>Asignar</option>
                    <option value="quitar" <?php echo $filtro_accion === 'quitar' ? 'selected' : ''; ?>>Quitar</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Módulo</label>
                <select name="modulo" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($modulos as $mod): ?>
                    <option value="<?php echo $mod['modulo']; ?>" <?php echo $filtro_modulo === $mod['modulo'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst($mod['modulo']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?php echo $filtro_fecha_desde; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $filtro_fecha_hasta; ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de resultados -->
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>Registros de auditoría</span>
        <span class="badge bg-secondary"><?php echo number_format($total_registros); ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 150px;">Fecha/Hora</th>
                        <th>Usuario</th>
                        <th style="width: 100px;">Acción</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th style="width: 120px;">IP</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            No se encontraron registros con los filtros seleccionados
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td class="text-nowrap small">
                            <?php echo date('d/m/Y H:i:s', strtotime($reg['created_at'])); ?>
                        </td>
                        <td>
                            <?php if ($reg['usuario_id']): ?>
                            <strong><?php echo htmlspecialchars($reg['usuario_nombre']); ?></strong>
                            <br><small class="text-muted">ID: <?php echo $reg['usuario_id']; ?></small>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badge_class = match($reg['accion']) {
                                'login' => 'bg-success',
                                'logout' => 'bg-secondary',
                                'login_fallido' => 'bg-danger',
                                'crear' => 'bg-primary',
                                'editar' => 'bg-warning text-dark',
                                'eliminar' => 'bg-danger',
                                'asignar' => 'bg-info',
                                'quitar' => 'bg-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $reg['accion'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?php echo ucfirst($reg['modulo']); ?>
                            </span>
                            <?php if ($reg['tabla_afectada']): ?>
                            <br><small class="text-muted"><?php echo $reg['tabla_afectada']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($reg['descripcion'] ?? '-'); ?>
                        </td>
                        <td class="small text-muted">
                            <?php echo $reg['ip_address'] ?? '-'; ?>
                        </td>
                        <td>
                            <?php if ($reg['datos_antes'] || $reg['datos_despues']): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    data-bs-toggle="modal" data-bs-target="#modalDetalle"
                                    data-antes='<?php echo htmlspecialchars($reg['datos_antes'] ?? '{}'); ?>'
                                    data-despues='<?php echo htmlspecialchars($reg['datos_despues'] ?? '{}'); ?>'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($total_paginas > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php 
                $inicio = max(1, $pagina - 2);
                $fin = min($total_paginas, $pagina + 2);
                for ($i = $inicio; $i <= $fin; $i++): 
                ?>
                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para ver detalles -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-code me-2"></i>Detalle del cambio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="fas fa-minus-circle me-1"></i>Datos anteriores</h6>
                        <pre id="datos-antes" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="fas fa-plus-circle me-1"></i>Datos nuevos</h6>
                        <pre id="datos-despues" class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalDetalle');
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const antes = button.getAttribute('data-antes');
        const despues = button.getAttribute('data-despues');
        
        try {
            const antesJson = JSON.parse(antes || '{}');
            const despuesJson = JSON.parse(despues || '{}');
            
            document.getElementById('datos-antes').textContent = 
                Object.keys(antesJson).length ? JSON.stringify(antesJson, null, 2) : '(Sin datos)';
            document.getElementById('datos-despues').textContent = 
                Object.keys(despuesJson).length ? JSON.stringify(despuesJson, null, 2) : '(Sin datos)';
        } catch (e) {
            document.getElementById('datos-antes').textContent = antes || '(Sin datos)';
            document.getElementById('datos-despues').textContent = despues || '(Sin datos)';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
