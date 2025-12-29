<?php
/**
 * Ver Detalle de Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Solo accesible para la iglesia local
 */

$page_title = "Ver Presentación";
require_once __DIR__ . '/../../includes/header.php';

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
if (!in_array($ROL_NOMBRE, $roles_permitidos)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección. Solo Pastor o Secretaria.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($id <= 0) {
    echo '<div class="alert alert-warning">ID no válido.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($iglesia_id <= 0) {
    echo '<div class="alert alert-warning">No tiene una iglesia asignada.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener registro - Solo de la iglesia del usuario
$stmt = $conexion->prepare("SELECT p.*, 
                            i.nombre AS iglesia_nombre,
                            u.nombre AS creado_por_nombre
                            FROM presentacion_ninos p
                            LEFT JOIN iglesias i ON p.iglesia_id = i.id
                            LEFT JOIN usuarios u ON p.creado_por = u.id
                            WHERE p.id = ? AND p.iglesia_id = ?");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    echo '<div class="alert alert-warning">Registro no encontrado.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener pastor de la iglesia (via pastor_iglesias)
$stmt = $conexion->prepare("SELECT CONCAT(p.nombre, ' ', p.apellido) AS pastor_nombre 
                            FROM pastores p 
                            INNER JOIN pastor_iglesias pi ON pi.pastor_id = p.id 
                            WHERE pi.iglesia_id = ? AND pi.activo = 1 AND pi.es_principal = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$pastor_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$pastor_nombre = $pastor_info['pastor_nombre'] ?? '';

// Obtener secretaria de la iglesia (si existe en usuarios)
$stmt = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) AS secretaria_nombre 
                            FROM usuarios 
                            WHERE iglesia_id = ? AND rol_id IN (SELECT id FROM roles WHERE nombre = 'secretaria') AND activo = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$secretaria_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$secretaria_nombre = $secretaria_info['secretaria_nombre'] ?? '';

// Calcular edad
$fecha_nac = new DateTime($registro['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $fecha_nac->diff($hoy);

// Meses en español
$meses = array(
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
);

$fecha_nac_formato = date('d', strtotime($registro['fecha_nacimiento'])) . ' de ' . 
                     $meses[(int)date('m', strtotime($registro['fecha_nacimiento']))] . ' de ' . 
                     date('Y', strtotime($registro['fecha_nacimiento']));

$fecha_pres_formato = date('d', strtotime($registro['fecha_presentacion'])) . ' de ' . 
                      $meses[(int)date('m', strtotime($registro['fecha_presentacion']))] . ' de ' . 
                      date('Y', strtotime($registro['fecha_presentacion']));
?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_GET['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="mb-1">
            <i class="fas fa-baby text-primary me-2"></i>
            Presentación #<?php echo htmlspecialchars($registro['numero_acta']); ?>
            <?php if ($registro['estado'] == 'anulado'): ?>
                <span class="badge bg-danger">ANULADA</span>
            <?php endif; ?>
        </h4>
        <p class="text-muted mb-0 small">Detalle del acta de presentación</p>
    </div>
    <div class="d-flex gap-2">
        <a href="imprimir.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-1"></i>Imprimir Acta
        </a>
        <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Datos del Niño -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-child me-2"></i>Datos del Niño/a</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small mb-0">Nombre Completo</label>
                        <h5 class="mb-0">
                            <?php echo htmlspecialchars($registro['nombres'] . ' ' . $registro['apellidos']); ?>
                            <?php if ($registro['sexo'] == 'M'): ?>
                                <i class="fas fa-mars text-info ms-1" title="Niño"></i>
                            <?php else: ?>
                                <i class="fas fa-venus text-danger ms-1" title="Niña"></i>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Fecha de Nacimiento</label>
                        <p class="mb-0"><?php echo $fecha_nac_formato; ?></p>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Nacionalidad</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['nacionalidad'] ?: 'No especificada'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Datos de los Padres -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-users me-2"></i>Padres / Tutores</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small mb-0">Padre</label>
                        <p class="mb-0">
                            <?php echo !empty($registro['nombre_padre']) ? htmlspecialchars($registro['nombre_padre']) : '<span class="text-muted fst-italic">No registrado</span>'; ?>
                        </p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small mb-0">Madre</label>
                        <p class="mb-0">
                            <?php echo !empty($registro['nombre_madre']) ? htmlspecialchars($registro['nombre_madre']) : '<span class="text-muted fst-italic">No registrada</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Datos del Acto -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-church me-2"></i>Datos del Acto de Presentación</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small mb-0">Fecha de Presentación</label>
                        <p class="mb-0 fw-bold"><?php echo $fecha_pres_formato; ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted small mb-0">Lugar</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['lugar'] ?: $registro['iglesia_nombre']); ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small mb-0">Ministro Oficiante</label>
                        <p class="mb-0">
                            <?php echo !empty($registro['ministro']) ? htmlspecialchars($registro['ministro']) : '<span class="text-muted fst-italic">No registrado</span>'; ?>
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small mb-0">Testigo 1</label>
                        <p class="mb-0">
                            <?php echo !empty($registro['testigo1']) ? htmlspecialchars($registro['testigo1']) : '<span class="text-muted fst-italic">No registrado</span>'; ?>
                        </p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted small mb-0">Testigo 2</label>
                        <p class="mb-0">
                            <?php echo !empty($registro['testigo2']) ? htmlspecialchars($registro['testigo2']) : '<span class="text-muted fst-italic">No registrado</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Datos de Registro Civil -->
        <?php if (!empty($registro['libro_no']) || !empty($registro['folio']) || !empty($registro['acta_civil_no'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Datos de Registro Civil</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Libro No.</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['libro_no'] ?: '-'); ?></p>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Folio</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['folio'] ?: '-'); ?></p>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Acta No.</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['acta_civil_no'] ?: '-'); ?></p>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <label class="form-label text-muted small mb-0">Año</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['acta_civil_anio'] ?: '-'); ?></p>
                    </div>
                    <?php if (!empty($registro['oficilia_civil'])): ?>
                    <div class="col-12">
                        <label class="form-label text-muted small mb-0">Oficialía Civil</label>
                        <p class="mb-0"><?php echo htmlspecialchars($registro['oficilia_civil']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Observaciones -->
        <?php if (!empty($registro['observaciones'])): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Observaciones</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Columna Lateral -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Información del Acta</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Número de Acta</label>
                    <h4 class="text-primary mb-0"><?php echo htmlspecialchars($registro['numero_acta']); ?></h4>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Iglesia</label>
                    <p class="mb-0"><?php echo htmlspecialchars($registro['iglesia_nombre']); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Pastor</label>
                    <p class="mb-0"><?php echo htmlspecialchars($pastor_nombre ?: 'No asignado'); ?></p>
                </div>
                <?php if (!empty($secretaria_nombre)): ?>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Secretario/a</label>
                    <p class="mb-0"><?php echo htmlspecialchars($secretaria_nombre); ?></p>
                </div>
                <?php endif; ?>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-muted small mb-0">Registrado por</label>
                    <p class="mb-0"><?php echo htmlspecialchars($registro['creado_por_nombre'] ?? 'Sistema'); ?></p>
                </div>
                <div class="mb-0">
                    <label class="form-label text-muted small mb-0">Fecha de Registro</label>
                    <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($registro['creado_en'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="d-grid gap-2">
            <a href="imprimir.php?id=<?php echo $id; ?>" class="btn btn-success btn-lg" target="_blank">
                <i class="fas fa-print me-2"></i>Imprimir Acta Oficial
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
