<?php
/**
 * Configuración de Solicitudes de Membresía
 * Permite habilitar/deshabilitar el formulario público
 */

$page_title = "Configuración de Solicitudes";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor y super_admin pueden configurar
$puede_configurar = in_array($ROL_NOMBRE, ['super_admin', 'pastor']);

if (!$puede_configurar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a esta configuración.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'toggle_solicitudes') {
        $nuevo_estado = isset($_POST['solicitudes_habilitadas']) ? 1 : 0;
        
        if ($ROL_NOMBRE === 'super_admin' && isset($_POST['iglesia_id'])) {
            $iglesia_id_update = (int)$_POST['iglesia_id'];
        } else {
            $iglesia_id_update = $IGLESIA_ID;
        }
        
        $stmt = $conexion->prepare("UPDATE iglesias SET solicitudes_habilitadas = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_estado, $iglesia_id_update);
        
        if ($stmt->execute()) {
            $mensaje = $nuevo_estado 
                ? 'Formulario de solicitudes HABILITADO correctamente.' 
                : 'Formulario de solicitudes DESHABILITADO correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar la configuración.';
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    }
}

// Obtener datos de la iglesia
if ($ROL_NOMBRE === 'super_admin') {
    $iglesias = $conexion->query("
        SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
        FROM iglesias i 
        INNER JOIN distritos d ON d.id = i.distrito_id 
        INNER JOIN conferencias c ON c.id = d.conferencia_id 
        WHERE i.activo = 1 
        ORDER BY c.nombre, d.nombre, i.nombre
    ");
} else {
    $stmt = $conexion->prepare("SELECT * FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $IGLESIA_ID);
    $stmt->execute();
    $iglesia = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Construir URL del formulario
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1><i class="fas fa-cog"></i> Configuración de Solicitudes</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
        <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<style>
/* Responsive styles */
@media (max-width: 767.98px) {
    .content-header h1 {
        font-size: 1.3rem;
    }
    .content-header .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    .card-body {
        padding: 1rem;
    }
    .estado-container {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
    }
    .badge.fs-6 {
        font-size: 0.8rem !important;
    }
    .btn-action {
        min-height: 48px;
        font-size: 1rem;
    }
    .input-group {
        flex-direction: column;
    }
    .input-group .form-control {
        border-radius: 0.375rem !important;
        margin-bottom: 0.5rem;
    }
    .input-group .btn {
        border-radius: 0.375rem !important;
        width: 100%;
    }
    .botones-compartir {
        flex-direction: column;
    }
    .botones-compartir .btn {
        width: 100%;
        justify-content: center;
    }
    /* Tabla responsive */
    .table-mobile thead {
        display: none;
    }
    .table-mobile tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1rem;
        background: #fff;
    }
    .table-mobile tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
        border-bottom: 1px solid #f3f4f6;
    }
    .table-mobile tbody td:last-child {
        border-bottom: none;
        padding-top: 0.75rem;
    }
    .table-mobile tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #374151;
        font-size: 0.85rem;
    }
    .table-mobile tbody td:last-child::before {
        content: none;
    }
    .table-mobile tbody td:last-child {
        justify-content: center;
    }
}

/* Touch-friendly buttons */
.btn-action {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* QR section */
.qr-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.25rem;
    margin-top: 1rem;
}
</style>

<?php if ($ROL_NOMBRE === 'super_admin'): ?>
<!-- Vista Super Admin: Todas las iglesias -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-church"></i> Estado del Formulario por Iglesia</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-mobile">
                <thead>
                    <tr>
                        <th>Iglesia</th>
                        <th>Código</th>
                        <th class="d-none d-md-table-cell">Distrito</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($igl = $iglesias->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Iglesia"><?php echo htmlspecialchars($igl['nombre']); ?></td>
                        <td data-label="Código"><code><?php echo htmlspecialchars($igl['codigo']); ?></code></td>
                        <td data-label="Distrito" class="d-none d-md-table-cell"><?php echo htmlspecialchars($igl['distrito_nombre']); ?></td>
                        <td data-label="Estado" class="text-center text-md-center">
                            <?php if ($igl['solicitudes_habilitadas']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Habilitado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-ban"></i> Deshabilitado</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="" class="text-center">
                            <form method="POST" class="d-inline" onsubmit="return false;">
                                <input type="hidden" name="accion" value="toggle_solicitudes">
                                <input type="hidden" name="iglesia_id" value="<?php echo $igl['id']; ?>">
                                <?php if ($igl['solicitudes_habilitadas']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-action" 
                                            onclick="confirmarDeshabilitar(this.form, '<?php echo htmlspecialchars($igl['nombre'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-toggle-off me-1"></i> Deshabilitar
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="solicitudes_habilitadas" value="1">
                                    <button type="button" class="btn btn-sm btn-outline-success btn-action"
                                            onclick="confirmarHabilitar(this.form, '<?php echo htmlspecialchars($igl['nombre'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-toggle-on me-1"></i> Habilitar
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Vista Pastor: Solo su iglesia -->
<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-toggle-on"></i> Estado del Formulario</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4 estado-container">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($iglesia['nombre']); ?></h5>
                        <code class="text-muted"><?php echo htmlspecialchars($iglesia['codigo']); ?></code>
                    </div>
                    <div>
                        <?php if ($iglesia['solicitudes_habilitadas']): ?>
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-check me-1"></i> HABILITADO
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary fs-6 px-3 py-2">
                                <i class="fas fa-ban me-1"></i> DESHABILITADO
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form method="POST" onsubmit="return false;">
                    <input type="hidden" name="accion" value="toggle_solicitudes">
                    
                    <?php if ($iglesia['solicitudes_habilitadas']): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            El formulario público está <strong>activo</strong>. Los miembros pueden enviar solicitudes.
                        </div>
                        <button type="button" class="btn btn-danger w-100 btn-action" 
                                onclick="confirmarDeshabilitar(this.form, '')">
                            <i class="fas fa-toggle-off me-2"></i> Deshabilitar Formulario
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            El formulario público está <strong>desactivado</strong>. Nadie puede enviar solicitudes.
                        </div>
                        <input type="hidden" name="solicitudes_habilitadas" value="1">
                        <button type="button" class="btn btn-success w-100 btn-action"
                                onclick="confirmarHabilitar(this.form, '')">
                            <i class="fas fa-toggle-on me-2"></i> Habilitar Formulario
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <?php if ($iglesia['solicitudes_habilitadas']): ?>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-link"></i> Enlace del Formulario</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Comparte este enlace con los miembros que deseen solicitar membresía:
                </p>
                
                <?php $url_formulario = $base_url . '/concilio/solicitud/' . $iglesia['codigo']; ?>
                
                <div class="mb-3">
                    <input type="text" class="form-control mb-2" id="urlFormulario" 
                           value="<?php echo $url_formulario; ?>" readonly 
                           style="font-size: 0.85rem;">
                    <button class="btn btn-primary w-100 btn-action" type="button" onclick="copiarEnlace('<?php echo $url_formulario; ?>')">
                        <i class="fas fa-copy me-2"></i> Copiar Enlace
                    </button>
                </div>
                
                <div class="d-flex gap-2 flex-wrap botones-compartir">
                    <a href="<?php echo $url_formulario; ?>" target="_blank" class="btn btn-outline-primary btn-action flex-fill">
                        <i class="fas fa-external-link-alt me-1"></i> Abrir
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode('Solicita tu membresía en nuestra iglesia: ' . $url_formulario); ?>" 
                       target="_blank" class="btn btn-outline-success btn-action flex-fill">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </a>
                </div>
                
                <div class="qr-section text-center">
                    <p class="mb-2 fw-semibold"><i class="fas fa-qrcode me-1"></i> Código QR</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($url_formulario); ?>" 
                         alt="QR Code" class="img-fluid" style="max-width: 130px;">
                    <p class="text-muted mt-2 small mb-0">
                        Escanea para acceder al formulario
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function copiarEnlace(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Mostrar notificación
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Copiado</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Enlace copiado al portapapeles
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });
}

// Modal de confirmación moderno
function confirmarDeshabilitar(form, nombreIglesia) {
    const modalHtml = `
    <div class="modal fade" id="modalConfirmar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #fee2e2, #fecaca); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-toggle-off" style="font-size: 2rem; color: #dc2626;"></i>
                    </div>
                    <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">¿Deshabilitar formulario?</h4>
                    <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                        ${nombreIglesia ? 'El formulario de <strong>' + nombreIglesia + '</strong> será desactivado.' : 'El formulario de solicitudes será desactivado.'}<br>
                        <small>Los miembros no podrán enviar nuevas solicitudes.</small>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger px-4" id="btnConfirmarDeshabilitar" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-toggle-off me-1"></i> Deshabilitar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // Remover modal anterior si existe
    const existingModal = document.getElementById('modalConfirmar');
    if (existingModal) existingModal.remove();
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmar'));
    
    document.getElementById('btnConfirmarDeshabilitar').addEventListener('click', function() {
        modal.hide();
        form.submit();
    });
    
    document.getElementById('modalConfirmar').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
    
    modal.show();
}

function confirmarHabilitar(form, nombreIglesia) {
    const modalHtml = `
    <div class="modal fade" id="modalConfirmarHabilitar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden;">
                <div class="modal-body text-center p-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-toggle-on" style="font-size: 2rem; color: #059669;"></i>
                    </div>
                    <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">¿Habilitar formulario?</h4>
                    <p style="color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6;">
                        ${nombreIglesia ? 'El formulario de <strong>' + nombreIglesia + '</strong> será activado.' : 'El formulario de solicitudes será activado.'}<br>
                        <small>Los miembros podrán enviar solicitudes de membresía.</small>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-success px-4" id="btnConfirmarHabilitar" style="min-width: 120px; border-radius: 10px; font-weight: 500;">
                            <i class="fas fa-toggle-on me-1"></i> Habilitar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    const existingModal = document.getElementById('modalConfirmarHabilitar');
    if (existingModal) existingModal.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarHabilitar'));
    
    document.getElementById('btnConfirmarHabilitar').addEventListener('click', function() {
        modal.hide();
        form.submit();
    });
    
    document.getElementById('modalConfirmarHabilitar').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
    
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
