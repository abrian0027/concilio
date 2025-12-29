<?php
/**
 * MINISTERIO DE CONFERENCIA - Dashboard para Presidente/Directiva de Ministerio (Nivel Conferencia)
 * Sistema Concilio - Módulo de Ministerios
 * 
 * Este dashboard es para líderes de ministerio a NIVEL DE CONFERENCIA
 * Ejemplo: Darling - Presidente de Jóvenes de Conferencia Este
 * Ve TODOS los jóvenes de TODAS las iglesias de la conferencia
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$conferencia_id = $_SESSION['conferencia_id'] ?? 0;

// Verificar que el usuario tiene una conferencia asignada
if (!$conferencia_id) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> No tienes una conferencia asignada.</div>";
    exit;
}

// Buscar al miembro asociado a este usuario usando miembro_id directamente
$stmt = $conexion->prepare("SELECT u.miembro_id, u.nombre AS u_nombre, u.apellido AS u_apellido, u.iglesia_id AS u_iglesia_id
                            FROM usuarios u
                            WHERE u.id = ?
                            LIMIT 1");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No se encontró información del usuario.</div>";
    exit;
}

$miembro_id = $result['miembro_id'] ?? 0;
$iglesia_usuario = $result['u_iglesia_id'];

// Verificar si este usuario es presidente o parte de la directiva de algún ministerio de conferencia
// Usando la tabla ministerio_lideres_conferencia (estructura actual)
$stmt = $conexion->prepare("SELECT mlc.*, 
                            m.nombre AS ministerio_nombre, m.id AS ministerio_id,
                            mlc.cargo AS cargo_nombre
                            FROM ministerio_lideres_conferencia mlc
                            INNER JOIN ministerios m ON mlc.ministerio_id = m.id
                            WHERE mlc.miembro_id = ? AND mlc.conferencia_id = ? AND mlc.activo = 1
                            LIMIT 1");
$stmt->bind_param("ii", $miembro_id, $conferencia_id);
$stmt->execute();
$liderazgo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si no es parte de la directiva de conferencia, mostrar mensaje
if (!$liderazgo) {
    $page_title = "Ministerio de Conferencia";
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="alert alert-info border-0">
        <i class="fas fa-info-circle me-2"></i>
        <strong>No tienes un cargo en la directiva de ministerio de conferencia</strong><br>
        <small>Este panel es para presidentes, vicepresidentes, secretarios y demás miembros de la directiva de ministerios a nivel de conferencia.</small>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Usar nombres de la nueva estructura
$ministerio_id = $liderazgo['ministerio_id'];
$area_nombre = $liderazgo['ministerio_nombre'];  // Para compatibilidad con el resto del código
$cargo_nombre = ucfirst($liderazgo['cargo_nombre']);

// Obtener información de la conferencia
$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conferencia_nombre = $conferencia['nombre'] ?? 'Conferencia';

// Obtener el area_id correspondiente en areas_ministeriales
$area_id_ministerio = $ministerio_id; // Por defecto
$sql_area = "SELECT am.id FROM areas_ministeriales am 
             INNER JOIN ministerios mn ON am.nombre = mn.nombre 
             WHERE mn.id = ? AND am.activo = 1 LIMIT 1";
$stmt_area = $conexion->prepare($sql_area);
$stmt_area->bind_param("i", $ministerio_id);
$stmt_area->execute();
$result_area = $stmt_area->get_result()->fetch_assoc();
if ($result_area) {
    $area_id_ministerio = $result_area['id'];
}
$stmt_area->close();

// Determinar si es un ministerio demográfico (por edad/sexo) o de servicio
// Ministerios demográficos: Damas, Caballeros, Jóvenes, Adolescentes, Niños (IDs 1-5 en ministerios)
$es_ministerio_demografico = in_array($ministerio_id, [1, 2, 3, 4, 5]);

if ($es_ministerio_demografico) {
    // Obtener estadísticas GLOBALES del ministerio basadas en MIEMBROS
    $sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN m.sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN m.sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            SUM(CASE WHEN m.es_bautizado = 1 THEN 1 ELSE 0 END) AS bautizados,
            ROUND(AVG(TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()))) AS edad_promedio,
            COUNT(DISTINCT m.iglesia_id) AS total_iglesias
            FROM miembros m
            INNER JOIN iglesias i ON m.iglesia_id = i.id
            INNER JOIN distritos d ON i.distrito_id = d.id
            WHERE d.conferencia_id = ? AND m.ministerio_id = ? AND m.estado = 'activo'";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $conferencia_id, $ministerio_id);
    $stmt->execute();
    $stats_generales = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Obtener distribución por DISTRITOS basada en miembros
    $sql = "SELECT 
            d.id, d.nombre AS distrito_nombre,
            COUNT(m.id) AS total_miembros,
            SUM(CASE WHEN m.sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN m.sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            COUNT(DISTINCT m.iglesia_id) AS total_iglesias
            FROM distritos d
            LEFT JOIN iglesias i ON d.id = i.distrito_id
            LEFT JOIN miembros m ON i.id = m.iglesia_id AND m.ministerio_id = ? AND m.estado = 'activo'
            WHERE d.conferencia_id = ?
            GROUP BY d.id, d.nombre
            ORDER BY d.nombre";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $ministerio_id, $conferencia_id);
    $stmt->execute();
    $distritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // MINISTERIO DE SERVICIO: Estadísticas basadas en LÍDERES LOCALES asignados
    $sql = "SELECT 
            COUNT(DISTINCT al.id) AS total,
            SUM(CASE WHEN m.sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN m.sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            SUM(CASE WHEN m.es_bautizado = 1 THEN 1 ELSE 0 END) AS bautizados,
            0 AS edad_promedio,
            COUNT(DISTINCT al.iglesia_id) AS total_iglesias
            FROM area_lideres al
            INNER JOIN miembros m ON al.miembro_id = m.id
            INNER JOIN iglesias i ON al.iglesia_id = i.id
            INNER JOIN distritos d ON i.distrito_id = d.id
            WHERE d.conferencia_id = ? AND al.area_id = ? AND al.activo = 1";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $conferencia_id, $area_id_ministerio);
    $stmt->execute();
    $stats_generales = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Obtener distribución por DISTRITOS basada en líderes asignados
    $sql = "SELECT 
            d.id, d.nombre AS distrito_nombre,
            COUNT(DISTINCT al.id) AS total_miembros,
            SUM(CASE WHEN m.sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN m.sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            COUNT(DISTINCT al.iglesia_id) AS total_iglesias
            FROM distritos d
            LEFT JOIN iglesias i ON d.id = i.distrito_id AND i.activo = 1
            LEFT JOIN area_lideres al ON i.id = al.iglesia_id AND al.area_id = ? AND al.activo = 1
            LEFT JOIN miembros m ON al.miembro_id = m.id
            WHERE d.conferencia_id = ?
            GROUP BY d.id, d.nombre
            ORDER BY d.nombre";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $area_id_ministerio, $conferencia_id);
    $stmt->execute();
    $distritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Obtener TODA la directiva del ministerio de conferencia
$sql = "SELECT mlc.*, 
        CONCAT(m.nombre, ' ', m.apellido) AS miembro_nombre,
        m.telefono,
        mlc.cargo AS cargo_nombre,
        i.nombre AS iglesia_nombre, i.codigo AS iglesia_codigo
        FROM ministerio_lideres_conferencia mlc
        INNER JOIN miembros m ON mlc.miembro_id = m.id
        INNER JOIN iglesias i ON m.iglesia_id = i.id
        WHERE mlc.conferencia_id = ? AND mlc.ministerio_id = ? AND mlc.activo = 1
        ORDER BY FIELD(mlc.cargo, 'presidente', 'vicepresidente', 'secretario', 'tesorero', 'vocal')";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $conferencia_id, $ministerio_id);
$stmt->execute();
$directiva = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Usar el area_id que ya obtuvimos arriba
$area_id_para_lideres = $area_id_ministerio;

// Obtener LÍDERES LOCALES (de cada iglesia)
// Usamos el area_id correcto de areas_ministeriales
$sql = "SELECT 
        al.id, al.tipo,
        CONCAT(m.nombre, ' ', m.apellido) AS lider_nombre,
        m.telefono,
        i.id AS iglesia_id,
        i.nombre AS iglesia_nombre, i.codigo AS iglesia_codigo,
        d.nombre AS distrito_nombre,
        COUNT(m2.id) AS total_miembros_local
        FROM area_lideres al
        INNER JOIN miembros m ON al.miembro_id = m.id
        INNER JOIN iglesias i ON al.iglesia_id = i.id
        INNER JOIN distritos d ON i.distrito_id = d.id
        LEFT JOIN miembros m2 ON m2.iglesia_id = i.id AND m2.ministerio_id = ? AND m2.estado = 'activo'
        WHERE al.area_id = ? AND al.activo = 1 AND al.tipo = 'lider' AND d.conferencia_id = ?
        GROUP BY al.id, al.tipo, m.id, m.nombre, m.apellido, m.telefono, i.id, i.nombre, i.codigo, d.nombre
        ORDER BY d.nombre, i.nombre";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iii", $ministerio_id, $area_id_para_lideres, $conferencia_id);
$stmt->execute();
$lideres_locales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Configuración de página
$page_title = "Ministerio de Conferencia - " . $area_nombre;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Encabezado del Ministerio -->
<div class="mb-3 mb-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="mb-1 fs-4 fs-md-3">
                <i class="fas fa-users-cog text-primary"></i>
                <span class="d-none d-md-inline">Ministerio de Conferencia - </span><?php echo htmlspecialchars($area_nombre); ?>
            </h2>
            <p class="text-muted mb-0 small">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($conferencia_nombre); ?>
                <span class="mx-2 d-none d-sm-inline">•</span>
                <span class="d-block d-sm-inline"><strong><?php echo htmlspecialchars($cargo_nombre); ?></strong></span>
            </p>
        </div>
        <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm w-100 w-md-auto">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<!-- Estadísticas Generales -->
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-6 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-2 p-md-3 rounded">
                        <i class="fas fa-users fa-lg fa-md-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-2 ms-md-3">
                        <h6 class="text-muted mb-1 small"><?php echo $es_ministerio_demografico ? 'Total Miembros' : 'Total Líderes'; ?></h6>
                        <h3 class="mb-0 fs-5 fs-md-3"><?php echo number_format($stats_generales['total']); ?></h3>
                        <small class="text-muted d-none d-lg-block">En <?php echo $stats_generales['total_iglesias']; ?> iglesias</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info bg-opacity-10 p-2 p-md-3 rounded">
                        <i class="fas fa-male fa-lg fa-md-2x text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-2 ms-md-3">
                        <h6 class="text-muted mb-1 small">Hombres</h6>
                        <h3 class="mb-0 fs-5 fs-md-3"><?php echo number_format($stats_generales['hombres']); ?></h3>
                        <small class="text-muted">
                            <?php 
                            $porcentaje_h = $stats_generales['total'] > 0 ? round(($stats_generales['hombres'] / $stats_generales['total']) * 100) : 0;
                            echo $porcentaje_h . '%';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 p-2 p-md-3 rounded">
                        <i class="fas fa-female fa-lg fa-md-2x text-danger"></i>
                    </div>
                    <div class="flex-grow-1 ms-2 ms-md-3">
                        <h6 class="text-muted mb-1 small">Mujeres</h6>
                        <h3 class="mb-0 fs-5 fs-md-3"><?php echo number_format($stats_generales['mujeres']); ?></h3>
                        <small class="text-muted">
                            <?php 
                            $porcentaje_m = $stats_generales['total'] > 0 ? round(($stats_generales['mujeres'] / $stats_generales['total']) * 100) : 0;
                            echo $porcentaje_m . '%';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2 p-md-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-2 p-md-3 rounded">
                        <i class="fas fa-water fa-lg fa-md-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-2 ms-md-3">
                        <h6 class="text-muted mb-1 small">Bautizados</h6>
                        <h3 class="mb-0 fs-5 fs-md-3"><?php echo number_format($stats_generales['bautizados']); ?></h3>
                        <small class="text-muted">
                            <?php 
                            $porcentaje_b = $stats_generales['total'] > 0 ? round(($stats_generales['bautizados'] / $stats_generales['total']) * 100) : 0;
                            echo $porcentaje_b . '%';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Distribución por Distritos y Directiva -->
<div class="row g-3 mb-4">
    <!-- Distribución por Distritos -->
    <div class="col-12 col-lg-8 mb-3 mb-lg-0">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0 fs-6 fs-md-5"><i class="fas fa-map-marked-alt"></i> Distribución por Distritos</h5>
            </div>
            <div class="card-body p-2 p-md-3">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-sm">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">Distrito</th>
                                <th class="text-center text-nowrap"><?php echo $es_ministerio_demografico ? 'Iglesias' : 'Iglesias c/Líder'; ?></th>
                                <th class="text-center text-nowrap"><?php echo $es_ministerio_demografico ? 'Total' : 'Líderes'; ?></th>
                                <th class="text-center text-nowrap d-none d-sm-table-cell">Hombres</th>
                                <th class="text-center text-nowrap d-none d-sm-table-cell">Mujeres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($distritos) > 0): ?>
                                <?php foreach ($distritos as $distrito): ?>
                                    <tr>
                                        <td class="text-nowrap"><strong><?php echo htmlspecialchars($distrito['distrito_nombre']); ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                <?php echo $distrito['total_iglesias']; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                <?php echo number_format($distrito['total_miembros']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center d-none d-sm-table-cell">
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                <?php echo number_format($distrito['hombres']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center d-none d-sm-table-cell">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                                <?php echo number_format($distrito['mujeres']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay distritos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Directiva del Ministerio -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #10b981, #34d399); color: white;">
                <h5 class="mb-0 fs-6 fs-md-5"><i class="fas fa-crown"></i> Directiva de Conferencia</h5>
            </div>
            <div class="card-body">
                <?php if (count($directiva) > 0): ?>
                    <?php foreach ($directiva as $miembro): ?>
                        <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                            <div class="flex-shrink-0">
                                <?php if ($miembro['cargo_orden'] == 1): ?>
                                    <i class="fas fa-crown fa-2x text-warning"></i>
                                <?php else: ?>
                                    <i class="fas fa-user-tie fa-2x text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold"><?php echo htmlspecialchars($miembro['miembro_nombre']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($miembro['cargo_nombre']); ?></div>
                                <div class="text-muted small">
                                    <i class="fas fa-church"></i> <?php echo htmlspecialchars($miembro['iglesia_codigo']); ?>
                                </div>
                                <?php if (!empty($miembro['telefono'])): ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($miembro['telefono']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No hay directiva asignada</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Líderes Locales -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fs-6 fs-md-5">
            <i class="fas fa-users"></i> Líderes Locales 
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary ms-2">
                <?php echo count($lideres_locales); ?>
            </span>
        </h5>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
                <thead class="table-light">
                    <tr>
                        <th class="text-nowrap">Líder</th>
                        <th class="text-nowrap d-none d-md-table-cell">Distrito</th>
                        <th class="text-nowrap">Iglesia</th>
                        <th class="text-center text-nowrap">Miembros</th>
                        <th class="text-nowrap d-none d-lg-table-cell">Contacto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($lideres_locales) > 0): ?>
                        <?php foreach ($lideres_locales as $lider): ?>
                            <tr>
                                <td class="text-nowrap"><strong><?php echo htmlspecialchars($lider['lider_nombre']); ?></strong></td>
                                <td class="text-nowrap d-none d-md-table-cell"><?php echo htmlspecialchars($lider['distrito_nombre']); ?></td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary d-inline-block d-sm-none">
                                        <?php echo htmlspecialchars($lider['iglesia_codigo']); ?>
                                    </span>
                                    <a href="#" 
                                       class="btn-ver-estructura text-primary text-decoration-none d-inline-block" 
                                       data-iglesia-id="<?php echo $lider['iglesia_id']; ?>"
                                       data-iglesia-nombre="<?php echo htmlspecialchars($lider['iglesia_nombre']); ?>"
                                       data-iglesia-codigo="<?php echo htmlspecialchars($lider['iglesia_codigo']); ?>"
                                       data-ministerio-id="<?php echo $ministerio_id; ?>"
                                       data-ministerio-nombre="<?php echo htmlspecialchars($area_nombre); ?>"
                                       data-distrito-nombre="<?php echo htmlspecialchars($lider['distrito_nombre']); ?>"
                                       style="cursor: pointer;"
                                       title="Ver estructura del ministerio">
                                        <?php echo htmlspecialchars($lider['iglesia_nombre']); ?>
                                        <i class="fas fa-external-link-alt ms-1 small"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                        <?php echo number_format($lider['total_miembros_local']); ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if (!empty($lider['telefono'])): ?>
                                        <i class="fas fa-phone text-success"></i> 
                                        <span class="d-none d-xl-inline"><?php echo htmlspecialchars($lider['telefono']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No hay líderes locales registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Estructura Local del Ministerio -->
<div class="modal fade" id="modalEstructuraLocal" tabindex="-1" aria-labelledby="modalEstructuraLocalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEstructuraLocalLabel">
                    <i class="fas fa-church me-2"></i>
                    <span id="modal-titulo">Estructura del Ministerio</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading -->
                <div id="modal-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando información...</p>
                </div>

                <!-- Contenido del Modal -->
                <div id="modal-contenido" style="display: none;">
                    
                    <!-- Estructura Directiva Local -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-users-cog text-primary"></i> Estructura Directiva Local
                        </h6>
                        
                        <div class="row g-3">
                            <!-- Líder Principal -->
                            <div class="col-12 col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-crown fa-2x text-warning"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Líder Principal</h6>
                                                <p class="mb-1 fw-bold" id="lider-nombre">-</p>
                                                <small class="text-muted d-block" id="lider-contacto"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Co-Líder -->
                            <div class="col-12 col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-user-tie fa-2x text-info"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Co-Líder</h6>
                                                <p class="mb-1 fw-bold" id="colider-nombre">-</p>
                                                <small class="text-muted d-block" id="colider-contacto"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-chart-bar text-primary"></i> Estadísticas del Ministerio
                        </h6>
                        <div class="row g-2">
                            <div class="col-6 col-sm-6 col-md-3">
                                <div class="text-center p-2 p-sm-3 bg-primary bg-opacity-10 rounded">
                                    <i class="fas fa-users fa-lg fa-md-2x text-primary mb-1 mb-md-2"></i>
                                    <h4 class="mb-0 fs-5 fs-md-4" id="stat-total">0</h4>
                                    <small class="text-muted d-block">Total</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 col-md-3">
                                <div class="text-center p-2 p-sm-3 bg-info bg-opacity-10 rounded">
                                    <i class="fas fa-male fa-lg fa-md-2x text-info mb-1 mb-md-2"></i>
                                    <h4 class="mb-0 fs-5 fs-md-4" id="stat-hombres">0</h4>
                                    <small class="text-muted d-block">Hombres</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 col-md-3">
                                <div class="text-center p-2 p-sm-3 bg-danger bg-opacity-10 rounded">
                                    <i class="fas fa-female fa-lg fa-md-2x text-danger mb-1 mb-md-2"></i>
                                    <h4 class="mb-0 fs-5 fs-md-4" id="stat-mujeres">0</h4>
                                    <small class="text-muted d-block">Mujeres</small>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 col-md-3">
                                <div class="text-center p-2 p-sm-3 bg-success bg-opacity-10 rounded">
                                    <i class="fas fa-water fa-lg fa-md-2x text-success mb-1 mb-md-2"></i>
                                    <h4 class="mb-0 fs-5 fs-md-4" id="stat-bautizados">0</h4>
                                    <small class="text-muted d-block">Bautizados</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Miembros -->
                    <div class="mb-3">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="fas fa-list text-primary"></i> Miembros del Ministerio
                        </h6>
                        <div id="lista-miembros" class="list-group">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div id="modal-error" style="display: none;" class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="modal-error-msg">Error al cargar la información</span>
                </div>
            </div>
            <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-secondary w-100 w-sm-auto" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <a href="#" id="btn-ver-dashboard" class="btn btn-primary w-100 w-sm-auto" style="display: none;">
                    <i class="fas fa-tachometer-alt"></i> Ver Dashboard Completo
                </a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para el Modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Capturar clicks en los enlaces de iglesias
    document.querySelectorAll('.btn-ver-estructura').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const iglesiaId = this.dataset.iglesiaId;
            const iglesiaNombre = this.dataset.iglesiaNombre;
            const iglesiaCodigo = this.dataset.iglesiaCodigo;
            const ministerioId = this.dataset.ministerioId;
            const ministerioNombre = this.dataset.ministerioNombre;
            const distritoNombre = this.dataset.distritoNombre;
            
            // Actualizar título del modal
            document.getElementById('modal-titulo').innerHTML = 
                `Ministerio de ${ministerioNombre} - ${iglesiaCodigo}`;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalEstructuraLocal'));
            modal.show();
            
            // Mostrar loading, ocultar contenido y error
            document.getElementById('modal-loading').style.display = 'block';
            document.getElementById('modal-contenido').style.display = 'none';
            document.getElementById('modal-error').style.display = 'none';
            
            // Hacer petición AJAX
            fetch(`ajax/get_estructura_local.php?iglesia_id=${iglesiaId}&ministerio_id=${ministerioId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        poblarModal(data.data);
                        document.getElementById('modal-loading').style.display = 'none';
                        document.getElementById('modal-contenido').style.display = 'block';
                    } else {
                        mostrarError(data.message || 'Error al cargar datos');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarError('Error de conexión');
                });
        });
    });
    
    function poblarModal(data) {
        // Líder Principal
        document.getElementById('lider-nombre').textContent = data.lider.nombre || '[No asignado]';
        document.getElementById('lider-contacto').innerHTML = data.lider.telefono ? 
            `<i class="fas fa-phone"></i> ${data.lider.telefono}` : '';
        
        // Co-Líder
        document.getElementById('colider-nombre').textContent = data.colider.nombre || '[No asignado]';
        document.getElementById('colider-contacto').innerHTML = data.colider.telefono ? 
            `<i class="fas fa-phone"></i> ${data.colider.telefono}` : '';
        
        // Estadísticas
        document.getElementById('stat-total').textContent = data.stats.total || 0;
        document.getElementById('stat-hombres').textContent = data.stats.hombres || 0;
        document.getElementById('stat-mujeres').textContent = data.stats.mujeres || 0;
        document.getElementById('stat-bautizados').textContent = data.stats.bautizados || 0;
        
        // Lista de miembros
        const listaMiembros = document.getElementById('lista-miembros');
        listaMiembros.innerHTML = '';
        
        if (data.miembros && data.miembros.length > 0) {
            data.miembros.forEach(miembro => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${miembro.nombre}</strong>
                            <span class="text-muted">(${miembro.edad} años)</span>
                            ${miembro.es_lider ? '<span class="badge bg-warning text-dark ms-2">Líder</span>' : ''}
                            ${miembro.es_colider ? '<span class="badge bg-info ms-2">Co-Líder</span>' : ''}
                        </div>
                        <div>
                            <i class="fas fa-${miembro.sexo === 'M' ? 'male text-info' : 'female text-danger'}"></i>
                            ${miembro.es_bautizado ? '<i class="fas fa-water text-success ms-2" title="Bautizado"></i>' : ''}
                        </div>
                    </div>
                `;
                listaMiembros.appendChild(item);
            });
        } else {
            listaMiembros.innerHTML = '<p class="text-muted text-center py-3">No hay miembros registrados</p>';
        }
    }
    
    function mostrarError(mensaje) {
        document.getElementById('modal-loading').style.display = 'none';
        document.getElementById('modal-contenido').style.display = 'none';
        document.getElementById('modal-error').style.display = 'block';
        document.getElementById('modal-error-msg').textContent = mensaje;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
