<?php
declare(strict_types=1);

/**
 * Mi Perfil - Ver y editar datos del usuario
 * Sistema Concilio
 */

$page_title = "Mi Perfil";
require_once __DIR__ . '/../includes/header.php';

// Obtener datos completos del usuario
$usuario_id = $_SESSION['usuario_id'] ?? 0;

$stmt = $conexion->prepare("
    SELECT u.*, r.nombre as rol_nombre_completo,
           c.nombre as conferencia_nombre,
           d.nombre as distrito_nombre,
           i.nombre as iglesia_nombre
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN conferencias c ON u.conferencia_id = c.id
    LEFT JOIN distritos d ON u.distrito_id = d.id
    LEFT JOIN iglesias i ON u.iglesia_id = i.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    echo "<div class='alert alert-danger'>Usuario no encontrado.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Mensajes de sesión
$mensaje = $_SESSION['perfil_mensaje'] ?? '';
$tipo_mensaje = $_SESSION['perfil_tipo'] ?? 'info';
unset($_SESSION['perfil_mensaje'], $_SESSION['perfil_tipo']);

// Determinar foto a mostrar
$foto_perfil = !empty($usuario['foto']) ? '../../uploads/usuarios/' . $usuario['foto'] : null;
$iniciales = strtoupper(substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1));
?>

<style>
/* ===== MI PERFIL - ESTILOS ===== */
.profile-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
    border-radius: 12px;
    padding: 2rem;
    color: white;
    margin-bottom: 1.5rem;
}

.profile-avatar-container {
    position: relative;
    display: inline-block;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 14px;
    object-fit: cover;
    border: 4px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.2);
}

.profile-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 14px;
    background: rgba(255,255,255,0.2);
    border: 4px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
}

.btn-change-photo {
    position: absolute;
    bottom: -5px;
    right: -5px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    color: var(--primary-dark);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}

.btn-change-photo:hover {
    transform: scale(1.1);
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.profile-role {
    opacity: 0.9;
    font-size: 0.95rem;
}

.profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.profile-card-header {
    background: var(--gray-50);
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-700);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-card-body {
    padding: 1.25rem;
}

.info-item {
    display: flex;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    width: 140px;
    color: var(--gray-500);
    font-size: 0.875rem;
    flex-shrink: 0;
}

.info-value {
    flex: 1;
    font-weight: 500;
    color: var(--gray-800);
}

.info-value.text-muted {
    color: var(--gray-400) !important;
    font-style: italic;
    font-weight: 400;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid var(--gray-200);
    background: white;
    color: var(--gray-700);
}

.action-btn:hover {
    background: var(--gray-50);
    border-color: var(--primary);
    color: var(--primary-dark);
}

.action-btn i {
    width: 20px;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header {
        text-align: center;
        padding: 1.5rem;
    }
    
    .profile-avatar, .profile-avatar-placeholder {
        width: 100px;
        height: 100px;
        font-size: 2rem;
    }
    
    .profile-name {
        font-size: 1.25rem;
    }
    
    .info-item {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .info-label {
        width: 100%;
        font-size: 0.8rem;
    }
}
</style>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header del Perfil -->
<div class="profile-header">
    <div class="row align-items-center">
        <div class="col-auto">
            <div class="profile-avatar-container">
                <?php if ($foto_perfil && file_exists(__DIR__ . '/../../uploads/usuarios/' . $usuario['foto'])): ?>
                    <img src="<?php echo $foto_perfil; ?>" alt="Foto" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder"><?php echo $iniciales; ?></div>
                <?php endif; ?>
                <button type="button" class="btn-change-photo" data-bs-toggle="modal" data-bs-target="#modalFoto" title="Cambiar foto">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
        </div>
        <div class="col">
            <div class="profile-name"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
            <div class="profile-role">
                <i class="fas fa-user-tag me-1"></i>
                <?php echo htmlspecialchars($usuario['rol_nombre_completo'] ?? 'Usuario'); ?>
            </div>
            <?php if ($usuario['iglesia_nombre']): ?>
            <div class="profile-role mt-1">
                <i class="fas fa-church me-1"></i>
                <?php echo htmlspecialchars($usuario['iglesia_nombre']); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Información Personal -->
    <div class="col-lg-8 mb-4">
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-user"></i> Información Personal
            </div>
            <div class="profile-card-body">
                <div class="info-item">
                    <div class="info-label">Nombre completo</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Usuario</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['usuario']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Correo electrónico</div>
                    <div class="info-value <?php echo empty($usuario['correo']) ? 'text-muted' : ''; ?>">
                        <?php echo !empty($usuario['correo']) ? htmlspecialchars($usuario['correo']) : 'No registrado'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value <?php echo empty($usuario['telefono']) ? 'text-muted' : ''; ?>">
                        <?php echo !empty($usuario['telefono']) ? htmlspecialchars($usuario['telefono']) : 'No registrado'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Rol</div>
                    <div class="info-value">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                            <?php echo htmlspecialchars($usuario['rol_nombre_completo'] ?? 'Usuario'); ?>
                        </span>
                    </div>
                </div>
                <?php if ($usuario['conferencia_nombre']): ?>
                <div class="info-item">
                    <div class="info-label">Conferencia</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['conferencia_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($usuario['distrito_nombre']): ?>
                <div class="info-item">
                    <div class="info-label">Distrito</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['distrito_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($usuario['iglesia_nombre']): ?>
                <div class="info-item">
                    <div class="info-label">Iglesia</div>
                    <div class="info-value"><?php echo htmlspecialchars($usuario['iglesia_nombre']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-label">Miembro desde</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($usuario['creado_en'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones Rápidas -->
    <div class="col-lg-4 mb-4">
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-cog"></i> Acciones
            </div>
            <div class="profile-card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="action-btn" data-bs-toggle="modal" data-bs-target="#modalEditar">
                        <i class="fas fa-edit text-primary"></i>
                        <span>Editar información</span>
                    </button>
                    <button type="button" class="action-btn" data-bs-toggle="modal" data-bs-target="#modalPassword">
                        <i class="fas fa-key text-warning"></i>
                        <span>Cambiar contraseña</span>
                    </button>
                    <button type="button" class="action-btn" data-bs-toggle="modal" data-bs-target="#modalFoto">
                        <i class="fas fa-camera text-success"></i>
                        <span>Cambiar foto</span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Estado de la cuenta -->
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-shield-alt"></i> Estado de la cuenta
            </div>
            <div class="profile-card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success"><i class="fas fa-check"></i></span>
                    <span>Cuenta activa</span>
                </div>
                <small class="text-muted">
                    Último acceso: <?php echo date('d/m/Y H:i'); ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Información -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Información</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actualizar.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Correo electrónico</label>
                        <input type="email" name="correo" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>" 
                               placeholder="ejemplo@correo.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Teléfono</label>
                        <input type="tel" name="telefono" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" 
                               placeholder="809-000-0000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Contraseña -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="cambiar_password.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contraseña actual *</label>
                        <div class="input-group">
                            <input type="password" name="password_actual" id="passActual" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('passActual')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nueva contraseña *</label>
                        <div class="input-group">
                            <input type="password" name="password_nuevo" id="passNuevo" class="form-control" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('passNuevo')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Confirmar nueva contraseña *</label>
                        <div class="input-group">
                            <input type="password" name="password_confirmar" id="passConfirmar" class="form-control" required minlength="6">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('passConfirmar')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Cambiar contraseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Foto -->
<div class="modal fade" id="modalFoto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title"><i class="fas fa-camera me-2"></i>Cambiar Foto de Perfil</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="cambiar_foto.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <?php if ($foto_perfil && file_exists(__DIR__ . '/../../uploads/usuarios/' . $usuario['foto'])): ?>
                            <img src="<?php echo $foto_perfil; ?>" alt="Foto actual" class="rounded" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder mx-auto" style="background: var(--gray-200); color: var(--gray-500);">
                                <?php echo $iniciales; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Seleccionar nueva foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                        <small class="text-muted">Formatos: JPG, PNG, WEBP. Máximo 2MB.</small>
                    </div>
                    
                    <div id="previewContainer" class="d-none mt-3">
                        <p class="small text-muted mb-2">Vista previa:</p>
                        <img id="previewImage" src="" alt="Preview" class="rounded" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i>Subir foto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Preview de imagen
document.querySelector('input[name="foto"]')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    }
});

// Validar que las contraseñas coincidan
document.querySelector('#modalPassword form')?.addEventListener('submit', function(e) {
    const nuevo = document.getElementById('passNuevo').value;
    const confirmar = document.getElementById('passConfirmar').value;
    
    if (nuevo !== confirmar) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
