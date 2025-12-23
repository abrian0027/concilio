<?php
/**
 * Panel del Supervisor de Distrito
 * Muestra estadísticas y listado de iglesias del distrito
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener cédula del usuario desde la BD
$usuario_cedula = '';
$stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $usuario_cedula = $row['usuario'];
}
$stmt->close();

// Buscar el distrito que supervisa este usuario
$distrito = null;
$sql_distrito = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo,
                        CONCAT(p.nombre, ' ', p.apellido) AS supervisor_nombre
                 FROM distritos d
                 INNER JOIN conferencias c ON d.conferencia_id = c.id
                 LEFT JOIN pastores p ON d.supervisor_id = p.id
                 WHERE p.cedula = ? AND d.activo = 1
                 LIMIT 1";
$stmt = $conexion->prepare($sql_distrito);
$stmt->bind_param("s", $usuario_cedula);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si es super_admin o el distrito viene por sesión/parámetro
if (!$distrito && ($ROL_NOMBRE === 'super_admin' || $ROL_NOMBRE === 'super_distrito')) {
    $distrito_id = $_SESSION['distrito_id'] ?? ($_GET['distrito'] ?? 0);
    if ($distrito_id > 0) {
        $stmt = $conexion->prepare("SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo,
                                           CONCAT(p.nombre, ' ', p.apellido) AS supervisor_nombre
                                    FROM distritos d
                                    INNER JOIN conferencias c ON d.conferencia_id = c.id
                                    LEFT JOIN pastores p ON d.supervisor_id = p.id
                                    WHERE d.id = ?");
        $stmt->bind_param("i", $distrito_id);
        $stmt->execute();
        $distrito = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$distrito) {
    header('Location: panel_generico.php?error=' . urlencode('No tiene distrito asignado'));
    exit;
}

$distrito_id = $distrito['id'];

// ============================================
// ESTADÍSTICAS DEL DISTRITO
// ============================================

// Total de iglesias
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ? AND activo = 1");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$total_iglesias = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de pastores asignados (iglesias con pastor)
$stmt = $conexion->prepare("SELECT COUNT(DISTINCT pi.pastor_id) as total 
                            FROM pastor_iglesia pi
                            INNER JOIN iglesias i ON pi.iglesia_id = i.id
                            WHERE i.distrito_id = ? AND pi.activo = 1 AND i.activo = 1");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$total_pastores = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de miembros
$stmt = $conexion->prepare("SELECT COUNT(*) as total 
                            FROM miembros m
                            INNER JOIN iglesias i ON m.iglesia_id = i.id
                            WHERE i.distrito_id = ? AND i.activo = 1 
                            AND m.estado = 'activo'");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$total_miembros = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Iglesias con pastor asignado (para calcular cobertura)
$stmt = $conexion->prepare("SELECT COUNT(DISTINCT i.id) as total 
                            FROM iglesias i
                            INNER JOIN pastor_iglesia pi ON i.id = pi.iglesia_id AND pi.activo = 1
                            WHERE i.distrito_id = ? AND i.activo = 1");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$iglesias_con_pastor = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$cobertura_pastores = $total_iglesias > 0 ? round(($iglesias_con_pastor / $total_iglesias) * 100) : 0;

// ============================================
// LISTADO DE IGLESIAS CON DETALLES
// ============================================
$iglesias = [];
$sql_iglesias = "SELECT i.id, i.codigo, i.nombre, i.direccion, i.telefono,
                        (SELECT COUNT(*) FROM miembros m WHERE m.iglesia_id = i.id 
                         AND m.estado = 'activo') AS total_miembros,
                        (SELECT CONCAT(p.nombre, ' ', p.apellido) 
                         FROM pastor_iglesia pi 
                         INNER JOIN pastores p ON pi.pastor_id = p.id 
                         WHERE pi.iglesia_id = i.id AND pi.activo = 1 
                         LIMIT 1) AS pastor_nombre,
                        (SELECT p.telefono 
                         FROM pastor_iglesia pi 
                         INNER JOIN pastores p ON pi.pastor_id = p.id 
                         WHERE pi.iglesia_id = i.id AND pi.activo = 1 
                         LIMIT 1) AS pastor_telefono
                 FROM iglesias i
                 WHERE i.distrito_id = ? AND i.activo = 1
                 ORDER BY i.codigo";
$stmt = $conexion->prepare($sql_iglesias);
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $iglesias[] = $row;
}
$stmt->close();

// ============================================
// TOP 5 IGLESIAS POR MIEMBROS
// ============================================
$top_iglesias = [];
$sql_top = "SELECT i.codigo, i.nombre,
                   (SELECT COUNT(*) FROM miembros m WHERE m.iglesia_id = i.id 
                    AND m.estado = 'activo') AS total_miembros
            FROM iglesias i
            WHERE i.distrito_id = ? AND i.activo = 1
            ORDER BY total_miembros DESC
            LIMIT 5";
$stmt = $conexion->prepare($sql_top);
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $top_iglesias[] = $row;
}
$stmt->close();

$titulo_pagina = "Panel Distrito " . $distrito['codigo'];
$page_title = $titulo_pagina;
include __DIR__ . '/../includes/header.php';
?>

<style>
        .header-distrito { 
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); 
            color: #212529; 
            border-radius: 15px;
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card.bg-primary { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important; }
        .stat-card.bg-success { background: linear-gradient(135deg, #198754 0%, #157347 100%) !important; }
        .stat-card.bg-info { background: linear-gradient(135deg, #0dcaf0 0%, #31d2f2 100%) !important; }
        .stat-card.bg-warning { background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%) !important; }
        
        .table-iglesias th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .sin-pastor {
            color: #dc3545;
            font-style: italic;
        }
        .badge-miembros {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
        
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
    </style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="margin-left: 0; padding: 1.5rem;">
                
                <!-- Header del Distrito -->
                <div class="card header-distrito mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-1">
                                    <i class="fas fa-map-marked-alt me-2"></i>
                                    Distrito <?php echo htmlspecialchars($distrito['codigo']); ?> - <?php echo htmlspecialchars($distrito['nombre']); ?>
                                </h2>
                                <p class="mb-1">
                                    <i class="fas fa-globe-americas me-1"></i>
                                    Conferencia: <?php echo htmlspecialchars($distrito['conferencia_codigo'] . ' - ' . $distrito['conferencia_nombre']); ?>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-user-tie me-1"></i>
                                    Supervisor: <strong><?php echo htmlspecialchars($distrito['supervisor_nombre'] ?? 'Sin asignar'); ?></strong>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <i class="fas fa-church" style="font-size: 5rem; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $total_iglesias; ?></h2>
                                        <p class="mb-0">Iglesias</p>
                                    </div>
                                    <i class="fas fa-church stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $total_pastores; ?></h2>
                                        <p class="mb-0">Pastores</p>
                                    </div>
                                    <i class="fas fa-user-tie stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-info text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $total_miembros; ?></h2>
                                        <p class="mb-0">Miembros</p>
                                    </div>
                                    <i class="fas fa-users stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0"><?php echo $cobertura_pastores; ?>%</h2>
                                        <p class="mb-0">Cobertura Pastoral</p>
                                    </div>
                                    <i class="fas fa-chart-pie stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tabla de Iglesias -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2 text-primary"></i>Iglesias del Distrito
                                </h5>
                                <span class="badge bg-primary"><?php echo count($iglesias); ?> iglesias</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-iglesias" id="tablaIglesias">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Iglesia</th>
                                                <th>Pastor</th>
                                                <th class="text-center">Miembros</th>
                                                <th>Contacto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($iglesias as $igl): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($igl['codigo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($igl['nombre']); ?></td>
                                                <td>
                                                    <?php if ($igl['pastor_nombre']): ?>
                                                        <i class="fas fa-user-tie text-success me-1"></i>
                                                        <?php echo htmlspecialchars($igl['pastor_nombre']); ?>
                                                    <?php else: ?>
                                                        <span class="sin-pastor">
                                                            <i class="fas fa-user-slash me-1"></i>Sin asignar
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $igl['total_miembros'] > 0 ? 'success' : 'secondary'; ?> badge-miembros">
                                                        <?php echo $igl['total_miembros']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($igl['pastor_telefono']): ?>
                                                        <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($igl['pastor_telefono']); ?></small>
                                                    <?php elseif ($igl['telefono']): ?>
                                                        <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($igl['telefono']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light fw-bold">
                                                <td colspan="3" class="text-end">Total de Miembros:</td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary badge-miembros"><?php echo $total_miembros; ?></span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <div class="col-lg-4">
                        <!-- Top 5 Iglesias -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 5 Iglesias por Miembros</h6>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <?php 
                                    $posicion = 1;
                                    $max_miembros = !empty($top_iglesias) ? $top_iglesias[0]['total_miembros'] : 1;
                                    foreach ($top_iglesias as $top): 
                                        $porcentaje = $max_miembros > 0 ? ($top['total_miembros'] / $max_miembros) * 100 : 0;
                                    ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="badge bg-<?php echo $posicion <= 3 ? 'warning text-dark' : 'secondary'; ?> me-2">
                                                    #<?php echo $posicion; ?>
                                                </span>
                                                <strong><?php echo htmlspecialchars($top['codigo']); ?></strong>
                                            </div>
                                            <span class="badge bg-success"><?php echo $top['total_miembros']; ?></span>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $porcentaje; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($top['nombre']); ?></small>
                                    </li>
                                    <?php 
                                    $posicion++;
                                    endforeach; 
                                    ?>
                                    <?php if (empty($top_iglesias)): ?>
                                    <li class="list-group-item text-center text-muted">
                                        <i class="fas fa-info-circle me-1"></i>No hay datos disponibles
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Cobertura Pastoral -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Cobertura Pastoral</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <h1 class="display-4 mb-0 text-<?php echo $cobertura_pastores >= 80 ? 'success' : ($cobertura_pastores >= 50 ? 'warning' : 'danger'); ?>">
                                        <?php echo $cobertura_pastores; ?>%
                                    </h1>
                                    <p class="text-muted">de iglesias con pastor</p>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $cobertura_pastores; ?>%">
                                        <?php echo $iglesias_con_pastor; ?> con pastor
                                    </div>
                                    <div class="progress-bar bg-danger" style="width: <?php echo 100 - $cobertura_pastores; ?>%">
                                        <?php echo $total_iglesias - $iglesias_con_pastor; ?> sin pastor
                                    </div>
                                </div>
                                <div class="mt-2 d-flex justify-content-between">
                                    <small><i class="fas fa-circle text-success me-1"></i>Con pastor: <?php echo $iglesias_con_pastor; ?></small>
                                    <small><i class="fas fa-circle text-danger me-1"></i>Sin pastor: <?php echo $total_iglesias - $iglesias_con_pastor; ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones Rápidas -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Acciones Rápidas</h6>
                            </div>
                            <div class="card-body">
                                <a href="distritos/iglesias.php" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-church me-2"></i>Ver todas las iglesias
                                </a>
                                <a href="distritos/reportes.php" class="btn btn-outline-success w-100 mb-2">
                                    <i class="fas fa-file-pdf me-2"></i>Generar reporte
                                </a>
                                <a href="miembros/index.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-users me-2"></i>Ver miembros
                                </a>
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
            $('#tablaIglesias').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[3, 'desc']], // Ordenar por miembros descendente
                columnDefs: [
                    { orderable: false, targets: [4] }
                ]
            });
        });
    </script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
