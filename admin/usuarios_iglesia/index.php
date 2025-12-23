<?php
declare(strict_types=1);

$page_title = "Usuarios de la Iglesia";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor, secretaria pueden ver
if (!in_array($ROL_NOMBRE, ['pastor', 'secretaria', 'super_admin'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener iglesia_id de la sesión
$iglesia_id = $_SESSION['iglesia_id'] ?? null;

if (!$iglesia_id) {
    echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> No tienes una iglesia asignada.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener datos de la iglesia
$stmt = $conexion->prepare("SELECT i.*, d.nombre AS distrito_nombre FROM iglesias i INNER JOIN distritos d ON i.distrito_id = d.id WHERE i.id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtener usuarios de esta iglesia
$stmt = $conexion->prepare("
    SELECT u.*, r.nombre AS rol_nombre, r.descripcion AS rol_descripcion
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.iglesia_id = ?
    ORDER BY r.id, u.nombre
");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$usuarios = $stmt->get_result();
$total_usuarios = $usuarios->num_rows;
?>

<!-- Header Compacto -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-users-cog text-primary me-2"></i>Usuarios de la Iglesia</h1>
        <p class="text-muted small mb-0">
            <i class="fas fa-church me-1"></i><?php echo htmlspecialchars($iglesia['codigo'] . ' - ' . $iglesia['nombre']); ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($ROL_NOMBRE === 'pastor'): ?>
        <a href="crear.php" class="btn btn-sm btn-primary">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show border-0">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show border-0">
    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Card Resumen -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                <i class="fas fa-users fa-2x text-primary"></i>
            </div>
            <div>
                <p class="text-muted small mb-1">Total de Usuarios</p>
                <h3 class="mb-0 fw-bold"><?php echo $total_usuarios; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-semibold">
            <i class="fas fa-list text-primary me-2"></i>Usuarios con Acceso al Sistema
        </h6>
    </div>
    <div class="card-body p-0">
        <?php if ($total_usuarios > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 ps-3">Nombre</th>
                            <th class="border-0">Cédula (Usuario)</th>
                            <th class="border-0">Rol</th>
                            <th class="border-0 text-center">Estado</th>
                            <?php if ($ROL_NOMBRE === 'pastor'): ?>
                            <th class="border-0 text-center pe-3">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $usuarios->data_seek(0); // Reiniciar el puntero
                        while ($u = $usuarios->fetch_assoc()): 
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?></strong>
                                    <?php if (!empty($u['correo'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($u['correo']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($u['usuario']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $badge_color = 'secondary';
                                    $badge_bg = 'bg-secondary bg-opacity-10';
                                    $badge_border = 'border-secondary';
                                    
                                    if ($u['rol_nombre'] === 'pastor') {
                                        $badge_color = 'primary';
                                        $badge_bg = 'bg-primary bg-opacity-10';
                                        $badge_border = 'border-primary';
                                    } elseif ($u['rol_nombre'] === 'secretaria') {
                                        $badge_color = 'info';
                                        $badge_bg = 'bg-info bg-opacity-10';
                                        $badge_border = 'border-info';
                                    } elseif ($u['rol_nombre'] === 'tesorero') {
                                        $badge_color = 'success';
                                        $badge_bg = 'bg-success bg-opacity-10';
                                        $badge_border = 'border-success';
                                    } elseif ($u['rol_nombre'] === 'lider_ministerio') {
                                        $badge_color = 'warning';
                                        $badge_bg = 'bg-warning bg-opacity-10';
                                        $badge_border = 'border-warning';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_bg; ?> text-<?php echo $badge_color; ?> border <?php echo $badge_border; ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $u['rol_nombre']))); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['activo']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-ban me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($ROL_NOMBRE === 'pastor'): ?>
                                <td class="text-center pe-3">
                                    <?php if ($u['rol_nombre'] !== 'pastor'): ?>
                                        <a href="cambiar_clave.php?id=<?php echo $u['id']; ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Cambiar Contraseña">
                                            <i class="fas fa-key"></i>
                                        </a>
                                        <a href="toggle_estado.php?id=<?php echo $u['id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo $u['activo'] ? 'danger' : 'success'; ?>"
                                           onclick="return confirm('¿Está seguro de <?php echo $u['activo'] ? 'desactivar' : 'activar'; ?> este usuario?');"
                                           title="<?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                            <i class="fas fa-<?php echo $u['activo'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-crown me-1"></i>Pastor principal</small>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3" style="opacity: 0.3;"></i>
                <p class="text-muted mb-3">No hay usuarios registrados para esta iglesia.</p>
                <?php if ($ROL_NOMBRE === 'pastor'): ?>
                <a href="crear.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-user-plus"></i> Crear Primer Usuario
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-light border mt-3">
    <i class="fas fa-info-circle text-primary me-2"></i>
    <strong>Nota:</strong> Para crear un usuario, primero debe estar registrado como <strong>miembro</strong> de la iglesia. 
    Luego puede asignarle un rol y contraseña para acceso al sistema.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
