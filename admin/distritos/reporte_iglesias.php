<?php
/**
 * Reporte de Iglesias del Distrito - CORREGIDO
 * Muestra listado de iglesias con cantidad de miembros
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$usuario_id = (int) ($_SESSION['usuario_id'] ?? 0);
$distrito_id = (int) ($_SESSION['distrito_id'] ?? ($_GET['distrito'] ?? 0));

// Función para ejecutar consultas con manejo de errores
function ejecutarConsulta($conexion, $sql, $types = null, $params = null, $fetchAll = false) {
    try {
        $stmt = $conexion->prepare($sql);
        if (!$stmt) return $fetchAll ? [] : null;
        
        if ($types && $params) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) return $fetchAll ? [] : null;
        
        $result = $stmt->get_result();
        
        if ($fetchAll) {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return $data;
        } else {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data ?: null;
        }
    } catch (Exception $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return $fetchAll ? [] : null;
    }
}

// Obtener información del distrito
$distrito = null;
$usuario_cedula = '';

// Obtener cédula del usuario
$usuario_info = ejecutarConsulta($conexion, "SELECT usuario FROM usuarios WHERE id = ?", "i", [$usuario_id]);
if ($usuario_info && isset($usuario_info['usuario'])) {
    $usuario_cedula = $usuario_info['usuario'];
}

// Buscar distrito como supervisor (IGUAL QUE EN mis_iglesias.php)
if ($usuario_cedula) {
    $sql_distrito = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
                     FROM distritos d
                     INNER JOIN conferencias c ON d.conferencia_id = c.id
                     WHERE d.supervisor_id IN (SELECT id FROM pastores WHERE cedula = ?)
                     AND d.activo = 1
                     LIMIT 1";
    $distrito = ejecutarConsulta($conexion, $sql_distrito, "s", [$usuario_cedula]);
}

// Si no se encontró como supervisor, buscar como pastor asignado
if (!$distrito) {
    $sql = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
            FROM distritos d
            INNER JOIN conferencias c ON d.conferencia_id = c.id
            INNER JOIN iglesias i ON d.id = i.distrito_id
            INNER JOIN pastor_iglesias pi ON i.id = pi.iglesia_id
            INNER JOIN pastores p ON pi.pastor_id = p.id
            WHERE p.cedula = ? 
            AND pi.activo = 1
            AND i.activo = 1
            LIMIT 1";
    $distrito = ejecutarConsulta($conexion, $sql, "s", [$usuario_cedula]);
}

// Fallback para super_admin / super_distrito
if (!$distrito && ($ROL_NOMBRE === 'super_admin' || $ROL_NOMBRE === 'super_distrito') && $distrito_id > 0) {
    $sql_distrito_admin = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
                           FROM distritos d
                           INNER JOIN conferencias c ON d.conferencia_id = c.id
                           WHERE d.id = ? AND d.activo = 1";
    $distrito = ejecutarConsulta($conexion, $sql_distrito_admin, "i", [$distrito_id]);
}

if (!$distrito) {
    header('Location: ../panel_generico.php?error=' . urlencode('No tiene distrito asignado'));
    exit;
}

$distrito_id = (int) $distrito['id'];

// Obtener supervisor del distrito
$supervisor_info = ejecutarConsulta($conexion, 
    "SELECT CONCAT(p.nombre, ' ', p.apellido) AS supervisor_nombre 
     FROM pastores p 
     WHERE p.id = ?", 
    "i", [$distrito['supervisor_id'] ?? 0]
);

$supervisor_nombre = $supervisor_info['supervisor_nombre'] ?? 'Sin asignar';

// Obtener iglesias usando la MISMA consulta que en mis_iglesias.php
$sql_iglesias = "SELECT 
        i.id,
        i.codigo,
        i.nombre,
        i.direccion,
        i.telefono,
        COALESCE(m.total_miembros, 0) AS total_miembros,
        CONCAT(p.nombre, ' ', p.apellido) AS pastor_nombre,
        p.telefono AS pastor_telefono
    FROM iglesias i
    LEFT JOIN (
        SELECT iglesia_id, COUNT(*) AS total_miembros
        FROM miembros
        WHERE estado = 'activo'
        GROUP BY iglesia_id
    ) m ON m.iglesia_id = i.id
    LEFT JOIN pastor_iglesias pi ON pi.iglesia_id = i.id AND pi.activo = 1
    LEFT JOIN pastores p ON p.id = pi.pastor_id
    WHERE i.distrito_id = ? AND i.activo = 1
    ORDER BY i.codigo";

$iglesias = ejecutarConsulta($conexion, $sql_iglesias, "i", [$distrito_id], true);
if (!is_array($iglesias)) $iglesias = [];

// Calcular estadísticas (CORREGIDO)
$total_iglesias = count($iglesias);
$total_miembros = 0;
$iglesias_con_pastor = 0;

foreach ($iglesias as $igl) {
    $miembros = (int) ($igl['total_miembros'] ?? 0);
    $total_miembros += $miembros;
    
    // Verificar si tiene pastor (usando la misma lógica que mis_iglesias.php)
    if (!empty($igl['pastor_nombre']) && $igl['pastor_nombre'] != 'No asignado') {
        $iglesias_con_pastor++;
    }
}

// Si no se encontraron pastores con el LEFT JOIN, intentar método alternativo
if ($iglesias_con_pastor === 0 && !empty($iglesias)) {
    foreach ($iglesias as &$igl) {
        $iglesia_id = (int) $igl['id'];
        
        // Consulta directa para pastor
        $sql_pastor = "SELECT CONCAT(p.nombre, ' ', p.apellido) AS pastor_nombre, p.telefono AS pastor_telefono
                       FROM pastor_iglesias pi
                       INNER JOIN pastores p ON p.id = pi.pastor_id
                       WHERE pi.iglesia_id = ? AND pi.activo = 1
                       LIMIT 1";
        
        $pastor_info = ejecutarConsulta($conexion, $sql_pastor, "i", [$iglesia_id]);
        
        if ($pastor_info && !empty($pastor_info['pastor_nombre'])) {
            $igl['pastor_nombre'] = $pastor_info['pastor_nombre'];
            $igl['pastor_telefono'] = $pastor_info['pastor_telefono'] ?? '';
            $iglesias_con_pastor++;
        }
    }
}

$cobertura_pastoral = $total_iglesias > 0 ? round(($iglesias_con_pastor / $total_iglesias) * 100) : 0;

// Obtener fecha actual para el reporte
$fecha_reporte = date('d/m/Y');
$hora_reporte = date('H:i:s');

// Configurar título del reporte
$titulo_reporte = "Reporte de Iglesias - Distrito " . htmlspecialchars($distrito['codigo']);
$titulo_pagina = $titulo_reporte;

// Usar header unificado (incluye menú)
$page_title = $titulo_pagina ?? 'Reporte de Iglesias';
require_once __DIR__ . '/../includes/header.php';

// Continuar con el contenido principal (el header ya abrió la estructura)
?>
                <!-- Encabezado con controles -->
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <div>
                        <h2><i class="fas fa-church me-2"></i><?php echo $titulo_reporte; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../panel_distrito.php">Panel Distrito</a></li>
                                <li class="breadcrumb-item"><a href="../distritos/reportes.php">Reportes</a></li>
                                <li class="breadcrumb-item active">Iglesias</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-success me-2">
                            <i class="fas fa-print me-1"></i>Imprimir / PDF
                        </button>
                        <a href="../distritos/reportes.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>
                </div>

                <!-- Encabezado del reporte (visible en pantalla e impresión) -->
                <div class="header-reporte">
                    <div class="row">
                        <div class="col-md-8">
                            <h3 class="mb-2">
                                <i class="fas fa-church text-primary me-2"></i>
                                Reporte de Iglesias del Distrito
                            </h3>
                            <p class="mb-1">
                                <strong>Distrito:</strong> <?php echo htmlspecialchars($distrito['codigo'] . ' - ' . $distrito['nombre']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Conferencia:</strong> <?php echo htmlspecialchars($distrito['conferencia_nombre']); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Supervisor:</strong> <?php echo htmlspecialchars($supervisor_nombre); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="print-only">
                                <p class="mb-1"><strong>Fecha:</strong> <?php echo $fecha_reporte; ?></p>
                                <p class="mb-0"><strong>Hora:</strong> <?php echo $hora_reporte; ?></p>
                            </div>
                            <div class="no-print">
                                <p class="text-muted mb-0">
                                    <i class="fas fa-calendar-alt me-1"></i>Generado: <?php echo $fecha_reporte; ?> <?php echo $hora_reporte; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stat-value"><?php echo $total_iglesias; ?></div>
                            <div class="stat-label">Total Iglesias</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stat-value"><?php echo $total_miembros; ?></div>
                            <div class="stat-label">Total Miembros</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stat-value"><?php echo $iglesias_con_pastor; ?></div>
                            <div class="stat-label">Con Pastor</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-box">
                            <div class="stat-value"><?php echo $cobertura_pastoral; ?>%</div>
                            <div class="stat-label">Cobertura Pastoral</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de iglesias -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-reporte">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="10%">Código</th>
                                        <th width="25%">Nombre de la Iglesia</th>
                                        <th width="25%">Pastor</th>
                                        <th width="10%" class="text-center">Miembros</th>
                                        <th width="15%">Contacto</th>
                                        <th width="10%">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($iglesias)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle me-1"></i> No hay iglesias registradas en este distrito
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php 
                                    $contador = 1;
                                    foreach ($iglesias as $igl): 
                                        $tiene_pastor = !empty($igl['pastor_nombre']) && $igl['pastor_nombre'] != 'No asignado';
                                        $miembros = (int) ($igl['total_miembros'] ?? 0);
                                        $pastor_nombre_display = $tiene_pastor ? $igl['pastor_nombre'] : 'Sin asignar';
                                    ?>
                                    <tr>
                                        <td><?php echo $contador++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($igl['codigo'] ?? ''); ?></strong></td>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($igl['nombre'] ?? ''); ?></strong></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($igl['direccion'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($tiene_pastor): ?>
                                                <i class="fas fa-user-tie text-success me-1"></i>
                                                <?php echo htmlspecialchars($pastor_nombre_display); ?>
                                            <?php else: ?>
                                                <span class="pastor-sin-asignar">
                                                    <i class="fas fa-user-slash me-1"></i><?php echo $pastor_nombre_display; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?php echo $miembros > 0 ? 'success' : 'secondary'; ?> badge-miembros">
                                                <?php echo $miembros; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($igl['pastor_telefono'])): ?>
                                                <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($igl['pastor_telefono']); ?></small>
                                            <?php elseif (!empty($igl['telefono'])): ?>
                                                <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($igl['telefono']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin teléfono</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tiene_pastor): ?>
                                                <span class="badge bg-success">Con Pastor</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Sin Pastor</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Fila de totales -->
                                    <tr class="table-light fw-bold">
                                        <td colspan="4" class="text-end">TOTALES:</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary badge-miembros"><?php echo $total_miembros; ?></span>
                                        </td>
                                        <td colspan="2">
                                            <small>Promedio: <?php echo $total_iglesias > 0 ? round($total_miembros / $total_iglesias) : 0; ?> miembros/iglesia</small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen adicional -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Resumen del Distrito</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-church text-primary me-2"></i>
                                        <strong>Total de Iglesias:</strong> <?php echo $total_iglesias; ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-user-tie text-success me-2"></i>
                                        <strong>Iglesias con Pastor:</strong> <?php echo $iglesias_con_pastor; ?> 
                                        (<?php echo $cobertura_pastoral; ?>%)
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-user-slash text-danger me-2"></i>
                                        <strong>Iglesias sin Pastor:</strong> <?php echo $total_iglesias - $iglesias_con_pastor; ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-users text-info me-2"></i>
                                        <strong>Total de Miembros:</strong> <?php echo $total_miembros; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Cobertura Pastoral</h6>
                            </div>
                            <div class="card-body">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $cobertura_pastoral; ?>%">
                                        <?php echo $iglesias_con_pastor; ?> con pastor
                                    </div>
                                    <div class="progress-bar bg-danger" style="width: <?php echo 100 - $cobertura_pastoral; ?>%">
                                        <?php echo $total_iglesias - $iglesias_con_pastor; ?> sin pastor
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Una cobertura pastoral del 100% significa que todas las iglesias tienen pastor asignado.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pie de página para impresión -->
                <div class="footer-reporte print-only mt-4">
                    <div class="row">
                        <div class="col-6">
                            <small>Sistema Concilio - Reporte generado automáticamente</small>
                        </div>
                        <div class="col-6 text-end">
                            <small>Página 1 de 1</small>
                        </div>
                    </div>
                </div>

                <!-- Instrucciones para imprimir -->
                <div class="alert alert-info mt-4 no-print">
                    <div class="d-flex">
                        <div class="me-3">
                            <i class="fas fa-print fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="alert-heading">Instrucciones para generar PDF:</h6>
                            <ol class="mb-0">
                                <li>Haz clic en el botón <strong>"Imprimir / PDF"</strong> arriba</li>
                                <li>En el diálogo de impresión, selecciona "Guardar como PDF" como impresora</li>
                                <li>Ajusta los márgenes a "Mínimo" para mejor presentación</li>
                                <li>Guarda el archivo en tu computadora</li>
                            </ol>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Función para optimizar la impresión (mantener comportamiento)
        function prepararImpresion() {
            document.body.classList.add('printing');
            window.print();
            setTimeout(function() {
                document.body.classList.remove('printing');
            }, 1000);
        }

        // Botón de imprimir mejorado
        document.addEventListener('DOMContentLoaded', function() {
            const printBtn = document.querySelector('button[onclick="window.print()"]');
            if (printBtn) {
                printBtn.onclick = function(e) {
                    e.preventDefault();
                    prepararImpresion();
                };
            }
        });

        // Mejora del estilo CSS para impresión
        const printStyles = `
            @media print {
                body { margin: 0; padding: 10mm; }
                .table { border-collapse: collapse; }
                .table th, .table td { border: 1px solid #ddd !important; }
                .badge { border: 1px solid #000; }
            }
        `;

        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = printStyles;
        document.head.appendChild(styleSheet);
    </script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>