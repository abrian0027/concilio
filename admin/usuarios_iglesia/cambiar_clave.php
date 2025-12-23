<?php
declare(strict_types=1);

$page_title = "Cambiar Contraseña";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor
if ($ROL_NOMBRE !== 'pastor') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Sin permisos.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$iglesia_id = $_SESSION['iglesia_id'] ?? null;
$usuario_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$usuario_id || !$iglesia_id) {
    header('Location: index.php?error=' . urlencode('Datos inválidos'));
    exit;
}

// Obtener usuario (debe ser de la misma iglesia y no ser pastor)
$stmt = $conexion->prepare("
    SELECT u.*, r.nombre AS rol_nombre 
    FROM usuarios u 
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.id = ? AND u.iglesia_id = ? AND r.nombre != 'pastor'
");
$stmt->bind_param("ii", $usuario_id, $iglesia_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header('Location: index.php?error=' . urlencode('Usuario no encontrado'));
    exit;
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_clave = $_POST['nueva_clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';
    
    if (strlen($nueva_clave) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($nueva_clave !== $confirmar_clave) {
        $error = "Las contraseñas no coinciden";
    } else {
        $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
        $stmt->bind_param("si", $clave_hash, $usuario_id);
        
        if ($stmt->execute()) {
            header('Location: index.php?msg=' . urlencode('Contraseña actualizada para ' . $usuario['nombre']));
            exit;
        } else {
            $error = "Error al actualizar contraseña";
        }
        $stmt->close();
    }
}
?>

<!-- Header Compacto -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-key text-warning me-2"></i>Cambiar Contraseña</h1>
        <p class="text-muted small mb-0">Asignar nueva contraseña al usuario</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 600px;">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-semibold">
            <i class="fas fa-user text-primary me-2"></i><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
        </h6>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-light border mb-3">
            <div class="row">
                <div class="col-6">
                    <small class="text-muted d-block">Usuario</small>
                    <strong><?php echo htmlspecialchars($usuario['usuario']); ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Rol</small>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $usuario['rol_nombre']))); ?>
                    </span>
                </div>
            </div>
        </div>

        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-lock text-warning me-1"></i>Nueva Contraseña <span class="text-danger">*</span>
                </label>
                <input type="password" name="nueva_clave" class="form-control" 
                       required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-lock text-warning me-1"></i>Confirmar Contraseña <span class="text-danger">*</span>
                </label>
                <input type="password" name="confirmar_clave" class="form-control" 
                       required minlength="6" placeholder="Repetir contraseña">
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generarClave()">
                    <i class="fas fa-magic"></i> Generar Automática
                </button>
                <span id="clave_generada" class="ms-3 badge bg-light text-dark border"></span>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function generarClave() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let clave = '';
    for (let i = 0; i < 8; i++) {
        clave += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    document.querySelector('input[name="nueva_clave"]').value = clave;
    document.querySelector('input[name="confirmar_clave"]').value = clave;
    document.getElementById('clave_generada').textContent = clave;
    
    navigator.clipboard.writeText(clave).then(() => {
        alert('Contraseña generada y copiada: ' + clave);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
