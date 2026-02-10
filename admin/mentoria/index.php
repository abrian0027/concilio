<?php
/**
 * Listado de Mentorías - Sistema Concilio
 * Bootstrap 5 - 100% Responsivo
 */

$page_title = "Mentoría";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden ver
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);
$puede_crear = in_array($ROL_NOMBRE, ['pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Filtrar por iglesia
$iglesia_id = ($ROL_NOMBRE === 'super_admin') ? 0 : (int)$IGLESIA_ID;

// Filtros
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_mentor = isset($_GET['mentor']) ? (int)$_GET['mentor'] : 0;

// Query para obtener mentorías
$sql = "SELECT mt.*, 
               mentor.nombre AS mentor_nombre, mentor.apellido AS mentor_apellido, mentor.foto AS mentor_foto,
               mentoreado.nombre AS mentoreado_nombre, mentoreado.apellido AS mentoreado_apellido, mentoreado.foto AS mentoreado_foto,
               (SELECT COUNT(*) FROM mentoria_seguimientos WHERE mentoria_id = mt.id) AS total_seguimientos,
               i.nombre AS iglesia_nombre
        FROM mentorias mt
        INNER JOIN miembros mentor ON mt.mentor_id = mentor.id
        INNER JOIN miembros mentoreado ON mt.mentoreado_id = mentoreado.id
        LEFT JOIN iglesias i ON mt.iglesia_id = i.id
        WHERE 1=1";

if ($iglesia_id > 0) {
    $sql .= " AND mt.iglesia_id = $iglesia_id";
}
if ($filtro_estado) {
    $sql .= " AND mt.estado = '" . $conexion->real_escape_string($filtro_estado) . "'";
}
if ($filtro_mentor > 0) {
    $sql .= " AND mt.mentor_id = $filtro_mentor";
}

$sql .= " ORDER BY mt.estado ASC, mt.fecha_inicio DESC";

$result = $conexion->query($sql);
$mentorias = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener mentores para filtro
$mentores = [];
$sql_mentores = "SELECT DISTINCT m.id, m.nombre, m.apellido 
                 FROM miembros m 
                 INNER JOIN mentorias mt ON mt.mentor_id = m.id";
if ($iglesia_id > 0) $sql_mentores .= " WHERE mt.iglesia_id = $iglesia_id";
$sql_mentores .= " ORDER BY m.nombre";
$res_mentores = $conexion->query($sql_mentores);
if ($res_mentores) $mentores = $res_mentores->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$total_activas = 0;
$total_pausadas = 0;
$total_finalizadas = 0;
foreach ($mentorias as $m) {
    if ($m['estado'] === 'activa') $total_activas++;
    elseif ($m['estado'] === 'pausada') $total_pausadas++;
    else $total_finalizadas++;
}

// Mensajes
$mensaje_exito = isset($_GET['exito']) ? $_GET['exito'] : '';
$mensaje_error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
    .mentoria-header {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }
    @media (min-width: 768px) {
        .mentoria-header { padding: 25px; }
    }
    .stat-box {
        background: rgba(255,255,255,0.15);
        border-radius: 10px;
        padding: 12px;
        text-align: center;
    }
    .stat-box .number {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stat-box .label {
        font-size: 0.75rem;
        opacity: 0.9;
    }
    .avatar-sm {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: #6c757d;
    }
    .avatar-sm img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    .estado-activa { background-color: #198754; color: white; }
    .estado-pausada { background-color: #ffc107; color: #000; }
    .estado-finalizada { background-color: #6c757d; color: white; }
    .arrow-icon { color: #6f42c1; }
    .list-group-item { border-left: none; border-right: none; }
    .min-width-0 { min-width: 0; }
</style>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="mentoria-header">
    <div class="row align-items-center">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <h4 class="mb-1"><i class="fas fa-hands-helping me-2"></i>Mentoría</h4>
            <p class="mb-0 opacity-75 small">Acompañamiento espiritual entre miembros</p>
        </div>
        <div class="col-12 col-md-6">
            <div class="row g-2">
                <div class="col-4">
                    <div class="stat-box">
                        <div class="number"><?php echo $total_activas; ?></div>
                        <div class="label">Activas</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-box">
                        <div class="number"><?php echo $total_pausadas; ?></div>
                        <div class="label">Pausadas</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="stat-box">
                        <div class="number"><?php echo $total_finalizadas; ?></div>
                        <div class="label">Finalizadas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" id="filtroEstado" onchange="aplicarFiltros()">
                    <option value="">Todos los estados</option>
                    <option value="activa" <?php echo $filtro_estado === 'activa' ? 'selected' : ''; ?>>Activas</option>
                    <option value="pausada" <?php echo $filtro_estado === 'pausada' ? 'selected' : ''; ?>>Pausadas</option>
                    <option value="finalizada" <?php echo $filtro_estado === 'finalizada' ? 'selected' : ''; ?>>Finalizadas</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <select class="form-select form-select-sm" id="filtroMentor" onchange="aplicarFiltros()">
                    <option value="">Todos los mentores</option>
                    <?php foreach ($mentores as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo $filtro_mentor == $m['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5 text-md-end mt-2 mt-md-0">
                <?php if ($puede_crear): ?>
                <a href="asignar.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nueva Mentoría
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Mentorías -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($mentorias)): ?>
        <div class="text-center py-5">
            <i class="fas fa-hands-helping fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay mentorías registradas</h5>
            <p class="text-muted small">Crea una nueva mentoría asignando un mentor a un miembro.</p>
            <?php if ($puede_crear): ?>
            <a href="asignar.php" class="btn btn-success mt-2">
                <i class="fas fa-plus"></i> Crear Primera Mentoría
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($mentorias as $mt): ?>
            <li class="list-group-item py-3">
                <div class="d-flex align-items-center gap-2">
                    <!-- Mentor -->
                    <div class="avatar-sm flex-shrink-0">
                        <?php if ($mt['mentor_foto']): ?>
                        <img src="../../uploads/miembros/<?php echo htmlspecialchars($mt['mentor_foto']); ?>" alt="">
                        <?php else: ?>
                        <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <span class="fw-semibold text-truncate">
                                <?php echo htmlspecialchars($mt['mentor_nombre'] . ' ' . $mt['mentor_apellido']); ?>
                            </span>
                            <i class="fas fa-arrow-right arrow-icon mx-1" style="font-size: 0.7rem;"></i>
                            <span class="text-truncate">
                                <?php echo htmlspecialchars($mt['mentoreado_nombre'] . ' ' . $mt['mentoreado_apellido']); ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                            <span class="badge estado-<?php echo $mt['estado']; ?>" style="font-size: 0.65rem;">
                                <?php echo ucfirst($mt['estado']); ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($mt['fecha_inicio'])); ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-clipboard-list"></i> <?php echo $mt['total_seguimientos']; ?> seguimiento(s)
                            </small>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="d-flex gap-1 flex-shrink-0">
                        <a href="ver.php?id=<?php echo $mt['id']; ?>" class="btn btn-outline-primary btn-sm px-2" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="editar.php?id=<?php echo $mt['id']; ?>" class="btn btn-outline-secondary btn-sm px-2" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<script>
function aplicarFiltros() {
    const estado = document.getElementById('filtroEstado').value;
    const mentor = document.getElementById('filtroMentor').value;
    
    let url = 'index.php?';
    if (estado) url += 'estado=' + estado + '&';
    if (mentor) url += 'mentor=' + mentor + '&';
    
    window.location.href = url.slice(0, -1);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
