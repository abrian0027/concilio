<?php
/**
 * Rechazar Solicitud de Membresía
 * Sistema Concilio - Panel del Pastor
 */

$page_title = "Rechazar Solicitud";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

// Solo pastor puede rechazar
$puede_rechazar = in_array($ROL_NOMBRE, ['super_admin', 'pastor']);

if (!$puede_rechazar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para rechazar solicitudes.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID no válido</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener solicitud
$sql = "SELECT s.*, i.nombre AS iglesia_nombre 
        FROM solicitudes_membresia s
        LEFT JOIN iglesias i ON i.id = s.iglesia_id
        WHERE s.id = ? AND s.estado = 'pendiente'";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND s.iglesia_id = ?";
}

$stmt = $conexion->prepare($sql);
if ($ROL_NOMBRE !== 'super_admin') {
    $stmt->bind_param("ii", $id, $IGLESIA_ID);
} else {
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    echo "<div class='alert alert-danger'>Solicitud no encontrada o ya fue procesada</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$mensaje_error = '';
$mensaje_exito = '';

// Procesar rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = trim($_POST['motivo'] ?? '');
    
    if (empty($motivo)) {
        $mensaje_error = "Debe indicar el motivo del rechazo.";
    } else {
        $stmt = $conexion->prepare("
            UPDATE solicitudes_membresia 
            SET estado = 'rechazado', 
                fecha_revision = NOW(), 
                revisado_por = ?,
                motivo_rechazo = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $USUARIO_ID, $motivo, $id);
        
        if ($stmt->execute()) {
            // Notificar al solicitante por correo
            notificarSolicitudRechazada($conexion, $id, $motivo);
            
            header('Location: index.php?exito=rechazado');
            exit;
        } else {
            $mensaje_error = "Error al rechazar la solicitud.";
        }
        $stmt->close();
    }
}
?>

<div class="content-header">
    <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header bg-danger text-white">
        <span class="card-title"><i class="fas fa-times-circle"></i> Rechazar Solicitud</span>
    </div>
    <div class="card-body">
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <strong>Atención:</strong> Está por rechazar la solicitud de 
            <strong><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellido']); ?></strong>.
        </div>
        
        <form method="post">
            <div class="form-group">
                <label class="form-label">Motivo del rechazo <span class="text-danger">*</span></label>
                <textarea name="motivo" class="form-control" rows="4" required 
                          placeholder="Explique brevemente el motivo del rechazo..."><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
                <small class="text-muted">Este motivo quedará registrado en el sistema.</small>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Confirmar Rechazo
                </button>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
