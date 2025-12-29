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
?>

<style>
    .zona-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        color: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
    }
    .zona-codigo {
        font-size: 0.85rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .zona-nombre {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .stat-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .stat-circle .number {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-circle .label {
        font-size: 0.7rem;
        opacity: 0.8;
    }
    .member-card {
        border: none;
        border-radius: 10px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .member-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    .member-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #6c757d;
    }
    .member-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
</style>

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
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users"></i> Miembros de esta Zona</span>
        <span class="badge bg-primary"><?php echo $total_miembros; ?> miembros</span>
    </div>
    <div class="card-body">
        <?php if ($total_miembros === 0): ?>
        <div class="text-center py-4">
            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No hay miembros asignados</h5>
            <p class="text-muted">Para asignar miembros a esta zona, edita cada miembro y selecciona esta zona.</p>
            <a href="../../miembros/index.php" class="btn btn-outline-primary mt-2">
                <i class="fas fa-users"></i> Ir a Miembros
            </a>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($miembros as $m): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card member-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="member-avatar">
                            <?php if ($m['foto']): ?>
                            <img src="../../../uploads/miembros/<?php echo htmlspecialchars($m['foto']); ?>" alt="">
                            <?php else: ?>
                            <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">
                                <a href="../../miembros/ver.php?id=<?php echo $m['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                                </a>
                            </h6>
                            <?php if ($m['telefono']): ?>
                            <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($m['telefono']); ?></small>
                            <?php endif; ?>
                            <?php if ($m['ministerio_nombre']): ?>
                            <br><span class="badge bg-light text-dark"><?php echo htmlspecialchars($m['ministerio_nombre']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($m['sexo'] === 'M'): ?>
                            <i class="fas fa-male text-primary"></i>
                            <?php else: ?>
                            <i class="fas fa-female text-pink"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
