<?php
/**
 * Ver Líderes de Ministerio por Iglesia
 * Muestra el presidente de cada iglesia para un ministerio específico
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
$roles_permitidos = ['super_admin', 'obispo', 'super_conferencia', 'lider_ministerio'];
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $roles_permitidos)) {
    header('Location: ../panel_generico.php?error=' . urlencode('Sin permisos'));
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'];

// Obtener parámetros
$conferencia_id = isset($_GET['conferencia']) ? (int)$_GET['conferencia'] : 0;
$ministerio_id = isset($_GET['ministerio']) ? (int)$_GET['ministerio'] : 0;

// Si es super_conferencia o lider_ministerio, usar su conferencia
if (in_array($ROL_NOMBRE, ['super_conferencia', 'lider_ministerio'])) {
    $conferencia_id = $_SESSION['conferencia_id'] ?? 0;
}

// Validar conferencia
if ($conferencia_id <= 0) {
    header('Location: index.php?error=' . urlencode('Seleccione una conferencia'));
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

// Validar ministerio
if ($ministerio_id <= 0) {
    header('Location: index.php?conferencia=' . $conferencia_id . '&error=' . urlencode('Seleccione un ministerio'));
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM ministerios WHERE id = ?");
$stmt->bind_param("i", $ministerio_id);
$stmt->execute();
$ministerio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ministerio) {
    header('Location: index.php?conferencia=' . $conferencia_id . '&error=' . urlencode('Ministerio no encontrado'));
    exit;
}

// Obtener líder de conferencia para este ministerio
$stmt = $conexion->prepare("SELECT * FROM v_lideres_ministerio_conferencia 
                            WHERE conferencia_id = ? AND ministerio_id = ? AND activo = 1
                            ORDER BY FIELD(cargo, 'presidente', 'vicepresidente', 'secretario', 'tesorero', 'vocal')");
$stmt->bind_param("ii", $conferencia_id, $ministerio_id);
$stmt->execute();
$result = $stmt->get_result();
$lideres_conferencia = [];
while ($row = $result->fetch_assoc()) {
    $lideres_conferencia[] = $row;
}
$stmt->close();

// Obtener distritos de la conferencia
$stmt = $conexion->prepare("SELECT id, codigo, nombre FROM distritos WHERE conferencia_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$result = $stmt->get_result();
$distritos = [];
while ($row = $result->fetch_assoc()) {
    $distritos[] = $row;
}
$stmt->close();

// Buscar el área ministerial correspondiente (por nombre exacto)
$area_id = null;
$stmt = $conexion->prepare("SELECT am.id FROM areas_ministeriales am 
                            INNER JOIN ministerios mn ON am.nombre = mn.nombre 
                            WHERE mn.id = ? AND am.activo = 1 LIMIT 1");
$stmt->bind_param("i", $ministerio_id);
$stmt->execute();
$area = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($area) {
    $area_id = $area['id'];
}

// Obtener líderes por iglesia (de la tabla area_lideres)
$lideres_por_iglesia = [];
$sql_lideres = "SELECT al.*, 
                       CONCAT(m.nombre, ' ', m.apellido) AS lider_nombre,
                       m.numero_documento AS lider_cedula,
                       m.telefono AS lider_telefono,
                       i.id AS iglesia_id,
                       i.codigo AS iglesia_codigo,
                       i.nombre AS iglesia_nombre,
                       d.id AS distrito_id,
                       d.nombre AS distrito_nombre
                FROM area_lideres al
                INNER JOIN miembros m ON al.miembro_id = m.id
                INNER JOIN iglesias i ON al.iglesia_id = i.id
                INNER JOIN distritos d ON i.distrito_id = d.id
                WHERE d.conferencia_id = ?
                  AND al.activo = 1";

if ($area_id) {
    $sql_lideres .= " AND al.area_id = " . (int)$area_id;
}

$sql_lideres .= " ORDER BY d.nombre, i.nombre, al.tipo";

$stmt = $conexion->prepare($sql_lideres);
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $iglesia_id = $row['iglesia_id'];
    if (!isset($lideres_por_iglesia[$iglesia_id])) {
        $lideres_por_iglesia[$iglesia_id] = [
            'iglesia_codigo' => $row['iglesia_codigo'],
            'iglesia_nombre' => $row['iglesia_nombre'],
            'distrito_id' => $row['distrito_id'],
            'distrito_nombre' => $row['distrito_nombre'],
            'lideres' => []
        ];
    }
    $lideres_por_iglesia[$iglesia_id]['lideres'][] = $row;
}
$stmt->close();

// Obtener todas las iglesias de la conferencia para mostrar las que no tienen líder
$stmt = $conexion->prepare("SELECT i.id, i.codigo, i.nombre, d.id AS distrito_id, d.nombre AS distrito_nombre
                            FROM iglesias i
                            INNER JOIN distritos d ON i.distrito_id = d.id
                            WHERE d.conferencia_id = ? AND i.activo = 1
                            ORDER BY d.nombre, i.nombre");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$result = $stmt->get_result();
$todas_iglesias = [];
while ($row = $result->fetch_assoc()) {
    $todas_iglesias[$row['id']] = $row;
}
$stmt->close();

// Contar estadísticas
$total_iglesias = count($todas_iglesias);
$iglesias_con_lider = count($lideres_por_iglesia);
$iglesias_sin_lider = $total_iglesias - $iglesias_con_lider;

$titulo_pagina = $ministerio['nombre'] . " - " . $conferencia['nombre'];
$page_title = $titulo_pagina;
include __DIR__ . '/../includes/header.php';
?>

<style>
        .header-ministerio { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: white; }
        .card-lider-conf { border-left: 4px solid #ffc107; }
        .stat-card { border-radius: 10px; }
        .sin-lider { background-color: #fff3cd; }
        .con-lider { background-color: #d1e7dd; }
    </style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="margin-left: 0; padding: 1.5rem;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-users me-2"></i><?php echo htmlspecialchars($ministerio['nombre']); ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../panel_generico.php">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="index.php?conferencia=<?php echo $conferencia_id; ?>">Líderes Ministerios</a></li>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($ministerio['nombre']); ?></li>
                            </ol>
                        </nav>
                    </div>
                    <a href="index.php?conferencia=<?php echo $conferencia_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <!-- Header con info del ministerio -->
                <div class="card header-ministerio mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1">Ministerio de <?php echo htmlspecialchars($ministerio['nombre']); ?></h4>
                                <p class="mb-0 opacity-75">
                                    <i class="fas fa-globe-americas me-1"></i>
                                    <?php echo htmlspecialchars($conferencia['codigo'] . ' - ' . $conferencia['nombre']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-hands-praying" style="font-size: 4rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Líderes de Conferencia -->
                    <div class="col-lg-4 mb-4">
                        <div class="card card-lider-conf h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-crown me-2"></i>Líderes de Conferencia</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($lideres_conferencia) > 0): ?>
                                    <?php foreach ($lideres_conferencia as $lc): ?>
                                    <div class="mb-3 pb-2 border-bottom">
                                        <span class="badge bg-<?php 
                                            echo $lc['cargo'] == 'presidente' ? 'primary' : 
                                                ($lc['cargo'] == 'vicepresidente' ? 'info' : 'secondary'); 
                                        ?>"><?php echo ucfirst($lc['cargo']); ?></span>
                                        <div class="fw-bold mt-1"><?php echo htmlspecialchars($lc['lider_nombre']); ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-church me-1"></i><?php echo htmlspecialchars($lc['iglesia_origen'] ?? 'N/A'); ?>
                                        </small>
                                        <?php if ($lc['lider_telefono']): ?>
                                        <br><small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($lc['lider_telefono']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-info-circle me-1"></i>No hay líderes de conferencia asignados.
                                    </p>
                                    <a href="asignar.php?conferencia=<?php echo $conferencia_id; ?>&ministerio=<?php echo $ministerio_id; ?>" 
                                       class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-plus me-1"></i>Asignar líder
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Estadísticas</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="stat-card bg-primary text-white p-2 rounded">
                                            <h4 class="mb-0"><?php echo $total_iglesias; ?></h4>
                                            <small>Total</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-card bg-success text-white p-2 rounded">
                                            <h4 class="mb-0"><?php echo $iglesias_con_lider; ?></h4>
                                            <small>Con líder</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-card bg-warning text-dark p-2 rounded">
                                            <h4 class="mb-0"><?php echo $iglesias_sin_lider; ?></h4>
                                            <small>Sin líder</small>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($total_iglesias > 0): ?>
                                <div class="progress mt-3" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($iglesias_con_lider / $total_iglesias) * 100; ?>%">
                                        <?php echo round(($iglesias_con_lider / $total_iglesias) * 100); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">Cobertura de líderes</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Líderes por Iglesia -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-church me-2"></i>Líderes por Iglesia</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-3" id="tabDistritos">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#todos">Todos</a>
                                    </li>
                                    <?php foreach ($distritos as $dist): ?>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#dist_<?php echo $dist['id']; ?>">
                                            <?php echo htmlspecialchars($dist['codigo']); ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="todos">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="tablaLideres">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Distrito</th>
                                                        <th>Iglesia</th>
                                                        <th>Líder</th>
                                                        <th>Teléfono</th>
                                                        <th>Tipo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($todas_iglesias as $igl_id => $igl): ?>
                                                    <tr class="<?php echo isset($lideres_por_iglesia[$igl_id]) ? 'con-lider' : 'sin-lider'; ?>">
                                                        <td><?php echo htmlspecialchars($igl['distrito_nombre']); ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($igl['codigo']); ?></strong>
                                                            <br><small><?php echo htmlspecialchars($igl['nombre']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if (isset($lideres_por_iglesia[$igl_id])): ?>
                                                                <?php foreach ($lideres_por_iglesia[$igl_id]['lideres'] as $l): ?>
                                                                    <?php echo htmlspecialchars($l['lider_nombre']); ?>
                                                                    <?php if ($l['tipo'] == 'colider'): ?>
                                                                        <span class="badge bg-secondary">Co-líder</span>
                                                                    <?php endif; ?>
                                                                    <br>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted"><i class="fas fa-user-slash me-1"></i>Sin asignar</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (isset($lideres_por_iglesia[$igl_id])): ?>
                                                                <?php foreach ($lideres_por_iglesia[$igl_id]['lideres'] as $l): ?>
                                                                    <?php echo htmlspecialchars($l['lider_telefono'] ?? '-'); ?><br>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (isset($lideres_por_iglesia[$igl_id])): ?>
                                                                <span class="badge bg-success">Líder</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

</div><!-- /.content-wrapper -->

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
        $(document).ready(function() {
            $('#tablaLideres').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [[0, 'asc'], [1, 'asc']]
            });
        });
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</html>
