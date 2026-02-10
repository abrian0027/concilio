<?php
/**
 * Ver Zona/Grupo - Sistema Concilio
 * Muestra detalles y miembros asignados
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Ver Zona";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../config/config.php';

// Solo pastor, secretaria o super_admin pueden ver
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener zona
$sql = "SELECT z.*, i.nombre AS iglesia_nombre FROM zonas z 
        LEFT JOIN iglesias i ON i.id = z.iglesia_id 
        WHERE z.id = ?";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND z.iglesia_id = " . (int)$IGLESIA_ID;
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$zona = $result->fetch_assoc();

if (!$zona) {
    header("Location: index.php?error=Zona no encontrada");
    exit;
}

// Obtener miembros de esta zona
$stmt = $conexion->prepare("SELECT m.id, m.nombre, m.apellido, m.telefono, m.sexo, m.estado_miembro, m.foto,
                                   am.nombre AS ministerio_nombre
                            FROM miembros m
                            LEFT JOIN areas_ministeriales am ON am.id = m.ministerio_id
                            WHERE m.zona_id = ? AND m.estado = 'activo'
                            ORDER BY m.nombre, m.apellido");
$stmt->bind_param("i", $id);
$stmt->execute();
$miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_miembros = count($miembros);

// Estadísticas de la zona
$hombres = 0;
$mujeres = 0;
foreach ($miembros as $m) {
    if ($m['sexo'] === 'M') $hombres++;
    else $mujeres++;
}

// Mensajes
$mensaje_exito = isset($_GET['exito']) ? $_GET['exito'] : '';
$mensaje_error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
    .zona-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }
    @media (min-width: 768px) {
        .zona-header { padding: 25px; margin-bottom: 20px; }
    }
    .zona-codigo {
        font-size: 0.75rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .zona-nombre {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    @media (min-width: 768px) {
        .zona-nombre { font-size: 1.8rem; }
    }
    .stat-circle {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    @media (min-width: 768px) {
        .stat-circle { width: 70px; height: 70px; }
    }
    .stat-circle .number {
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1;
    }
    @media (min-width: 768px) {
        .stat-circle .number { font-size: 1.6rem; }
    }
    .stat-circle .label {
        font-size: 0.6rem;
        opacity: 0.8;
    }
    @media (min-width: 768px) {
        .stat-circle .label { font-size: 0.7rem; }
    }
    .member-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .member-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    .min-width-0 { min-width: 0; }
    .text-pink { color: #e83e8c; }
    .list-group-item:active { background-color: #f8f9fa; }
    /* Botones compactos en móvil */
    .btn-sm.px-2 { padding-left: 0.4rem !important; padding-right: 0.4rem !important; }
    @media (max-width: 360px) {
        .btn-sm { font-size: 0.75rem; }
        .member-avatar { width: 32px; height: 32px; font-size: 0.8rem; }
    }
</style>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
        <?php if ($puede_editar): ?>
        <a href="editar.php?id=<?php echo $zona['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Editar</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Encabezado de la zona -->
<div class="zona-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="zona-codigo"><?php echo htmlspecialchars($zona['codigo']); ?></div>
            <div class="zona-nombre"><?php echo htmlspecialchars($zona['nombre']); ?></div>
            <?php if ($zona['descripcion']): ?>
            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($zona['descripcion']); ?></p>
            <?php endif; ?>
            <?php if ($ROL_NOMBRE === 'super_admin'): ?>
            <small class="opacity-75"><i class="fas fa-church"></i> <?php echo htmlspecialchars($zona['iglesia_nombre']); ?></small>
            <?php endif; ?>
        </div>
        <div class="col-md-4 mt-3 mt-md-0">
            <div class="d-flex justify-content-md-end gap-3">
                <div class="stat-circle">
                    <span class="number"><?php echo $total_miembros; ?></span>
                    <span class="label">Total</span>
                </div>
                <div class="stat-circle">
                    <span class="number"><?php echo $hombres; ?></span>
                    <span class="label">Hombres</span>
                </div>
                <div class="stat-circle">
                    <span class="number"><?php echo $mujeres; ?></span>
                    <span class="label">Mujeres</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de miembros -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-users"></i> Miembros</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary"><?php echo $total_miembros; ?></span>
            <?php if ($puede_editar): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarMiembro">
                <i class="fas fa-user-plus"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($total_miembros === 0): ?>
        <div class="text-center py-4 px-3">
            <i class="fas fa-user-plus fa-2x text-muted mb-2"></i>
            <p class="text-muted mb-2">No hay miembros asignados</p>
            <?php if ($puede_editar): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarMiembro">
                <i class="fas fa-user-plus"></i> Agregar Miembro
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Lista tipo list-group optimizada para móvil -->
        <ul class="list-group list-group-flush">
            <?php foreach ($miembros as $m): ?>
            <li class="list-group-item px-3 py-2">
                <div class="d-flex align-items-center gap-2">
                    <!-- Avatar pequeño -->
                    <div class="member-avatar flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.9rem;">
                        <?php if ($m['foto']): ?>
                        <img src="../../../uploads/miembros/<?php echo htmlspecialchars($m['foto']); ?>" alt="">
                        <?php else: ?>
                        <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info principal -->
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="fw-semibold text-truncate">
                                <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                            </span>
                            <?php if ($m['sexo'] === 'M'): ?>
                            <i class="fas fa-male text-primary" style="font-size: 0.8rem;"></i>
                            <?php else: ?>
                            <i class="fas fa-female text-pink" style="font-size: 0.8rem;"></i>
                            <?php endif; ?>
                        </div>
                        <?php if ($m['telefono']): ?>
                        <small class="text-muted">
                            <i class="fas fa-phone" style="font-size: 0.7rem;"></i> 
                            <?php echo htmlspecialchars($m['telefono']); ?>
                        </small>
                        <?php endif; ?>
                        <?php if ($m['ministerio_nombre']): ?>
                        <span class="badge bg-light text-dark ms-1" style="font-size: 0.65rem;">
                            <?php echo htmlspecialchars($m['ministerio_nombre']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Acciones compactas -->
                    <div class="d-flex gap-1 flex-shrink-0">
                        <?php if ($m['telefono']): ?>
                        <a href="https://wa.me/1<?php echo preg_replace('/[^0-9]/', '', $m['telefono']); ?>" 
                           class="btn btn-success btn-sm px-2" target="_blank" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                        <a href="../../miembros/ver.php?id=<?php echo $m['id']; ?>" 
                           class="btn btn-outline-primary btn-sm px-2" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if ($puede_editar): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm px-2" title="Quitar" 
                                onclick="quitarMiembroZona(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars(addslashes($m['nombre'] . ' ' . $m['apellido'])); ?>')">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($puede_editar): 
    // Obtener miembros disponibles (no asignados a ninguna zona)
    $sql_disponibles = "SELECT id, nombre, apellido, telefono 
                        FROM miembros 
                        WHERE iglesia_id = ? 
                        AND (zona_id IS NULL OR zona_id = 0)
                        AND estado = 'activo'
                        ORDER BY nombre, apellido";
    $stmt_disp = $conexion->prepare($sql_disponibles);
    $stmt_disp->bind_param("i", $zona['iglesia_id']);
    $stmt_disp->execute();
    $miembros_disponibles = $stmt_disp->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- Modal Agregar Miembro - Optimizado para móvil -->
<div class="modal fade" id="modalAgregarMiembro" tabindex="-1" aria-labelledby="modalAgregarMiembroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title" id="modalAgregarMiembroLabel">
                    <i class="fas fa-user-plus"></i> Agregar Miembro
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAgregarMiembro" method="POST" action="agregar_miembro.php">
                <div class="modal-body">
                    <input type="hidden" name="zona_id" value="<?php echo $zona['id']; ?>">
                    
                    <?php if (count($miembros_disponibles) > 0): ?>
                    <div class="mb-3">
                        <label for="miembro_id" class="form-label">Seleccionar Miembro</label>
                        <select name="miembro_id" id="miembro_id" class="form-select form-select-lg" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($miembros_disponibles as $md): ?>
                            <option value="<?php echo $md['id']; ?>">
                                <?php echo htmlspecialchars($md['nombre'] . ' ' . $md['apellido']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">
                            <i class="fas fa-info-circle"></i> <?php echo count($miembros_disponibles); ?> miembro(s) disponible(s)
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> Todos los miembros ya tienen zona asignada.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?php if (count($miembros_disponibles) > 0): ?>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function quitarMiembroZona(miembroId, nombreMiembro) {
    if (confirm('¿Estás seguro de quitar a "' + nombreMiembro + '" de esta zona?')) {
        window.location.href = 'quitar_miembro.php?zona_id=<?php echo $zona['id']; ?>&miembro_id=' + miembroId;
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
