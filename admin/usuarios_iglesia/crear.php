<?php
declare(strict_types=1);

$page_title = "Crear Usuario";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor puede crear usuarios de su iglesia
if ($ROL_NOMBRE !== 'pastor') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> Solo el pastor puede crear usuarios.</div>";
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

// Obtener miembros de la iglesia que NO tienen usuario
$stmt = $conexion->prepare("
    SELECT m.id, m.nombre, m.apellido, m.numero_documento, m.telefono, m.nacionalidad_id,
           n.nombre AS nacionalidad
    FROM miembros m
    LEFT JOIN nacionalidades n ON m.nacionalidad_id = n.id
    WHERE m.iglesia_id = ? 
    AND m.estado = 'activo'
    AND m.numero_documento IS NOT NULL
    AND m.numero_documento != ''
    AND NOT EXISTS (
        SELECT 1 FROM usuarios u WHERE u.usuario = m.numero_documento AND u.iglesia_id = m.iglesia_id
    )
    ORDER BY m.apellido, m.nombre
");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$miembros = $stmt->get_result();

// Obtener roles disponibles para la iglesia (secretaria, tesorero, lider_ministerio)
$roles = $conexion->query("SELECT * FROM roles WHERE nombre IN ('secretaria', 'tesorero', 'lider_ministerio') ORDER BY id");

// Obtener ministerios de la iglesia
$ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 ORDER BY nombre");
?>

<!-- Header Compacto -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-user-plus text-primary me-2"></i>Crear Usuario</h1>
        <p class="text-muted small mb-0">Seleccione un miembro de la iglesia para darle acceso al sistema</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm" style="max-width: 900px;">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-semibold">
            <i class="fas fa-edit text-primary me-2"></i>Asignar Acceso a Miembro
        </h6>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($miembros->num_rows === 0): ?>
            <div class="alert alert-warning border-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>No hay miembros disponibles.</strong><br>
                Todos los miembros con cédula ya tienen usuario asignado, o no hay miembros registrados con cédula.
            </div>
            <div class="text-center py-3">
                <a href="../miembros/crear.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Registrar Nuevo Miembro
                </a>
            </div>
        <?php else: ?>

        <form method="post" action="guardar.php" id="formUsuario">
            
            <!-- Seleccionar Miembro -->
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-user text-primary me-1"></i>Seleccionar Miembro <span class="text-danger">*</span>
                </label>
                <select name="miembro_id" id="miembro_id" class="form-select" required>
                    <option value="">-- Seleccione un miembro --</option>
                    <?php while ($m = $miembros->fetch_assoc()): ?>
                        <option value="<?php echo $m['id']; ?>" 
                                data-nombre="<?php echo htmlspecialchars($m['nombre']); ?>"
                                data-apellido="<?php echo htmlspecialchars($m['apellido']); ?>"
                                data-cedula="<?php echo htmlspecialchars($m['numero_documento']); ?>"
                                data-telefono="<?php echo htmlspecialchars($m['telefono'] ?? ''); ?>"
                                data-nacionalidad="<?php echo htmlspecialchars($m['nacionalidad'] ?? ''); ?>">
                            <?php echo htmlspecialchars($m['apellido'] . ', ' . $m['nombre'] . ' - ' . $m['numero_documento']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Datos del miembro (solo lectura) -->
            <div id="datos_miembro" class="d-none">
                <div class="alert alert-light border mb-3">
                    <h6 class="mb-3 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i>Datos del Miembro Seleccionado
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Nombre Completo</label>
                            <input type="text" id="show_nombre" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Cédula (será el usuario)</label>
                            <input type="text" id="show_cedula" class="form-control form-control-sm fw-bold" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Teléfono</label>
                            <input type="text" id="show_telefono" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Nacionalidad</label>
                            <input type="text" id="show_nacionalidad" class="form-control form-control-sm" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Rol -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tag"></i> Rol a Asignar <span style="color:red;">*</span>
                </label>
                <select name="rol_id" id="rol_id" class="form-control" required>
                    <option value="">-- Seleccione un rol --</option>
                    <?php while ($r = $roles->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>" data-rol="<?php echo $r['nombre']; ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $r['nombre']))); ?>
                            - <?php echo htmlspecialchars($r['descripcion']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Ministerio (solo para líder de ministerio) -->
            <div class="mb-3 d-none" id="div_ministerio">
                <label class="form-label fw-semibold">
                    <i class="fas fa-hands-praying text-primary me-1"></i>Ministerio <span class="text-danger">*</span>
                </label>
                <select name="ministerio_id" id="ministerio_id" class="form-select">
                    <option value="">-- Seleccione ministerio --</option>
                    <?php while ($min = $ministerios->fetch_assoc()): ?>
                        <option value="<?php echo $min['id']; ?>">
                            <?php echo htmlspecialchars($min['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <hr class="my-4">

            <!-- Contraseña -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-lock text-primary me-1"></i>Contraseña <span class="text-danger">*</span>
                    </label>
                    <input type="password" name="clave" id="clave" class="form-control" 
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-lock text-primary me-1"></i>Confirmar Contraseña <span class="text-danger">*</span>
                    </label>
                    <input type="password" name="clave_confirmar" class="form-control" 
                           placeholder="Repetir contraseña" required minlength="6">
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generarClave()">
                    <i class="fas fa-magic"></i> Generar Contraseña Automática
                </button>
                <span id="clave_generada" class="ms-3 badge bg-light text-dark border"></span>
            </div>

            <div class="alert alert-light border">
                <i class="fas fa-info-circle text-primary me-2"></i>
                <strong>Importante:</strong>
                <ul class="mb-0 mt-2">
                    <li>El usuario para login será la <strong>cédula</strong> del miembro</li>
                    <li>El miembro podrá entrar usando el <strong>código de la iglesia</strong> + su cédula</li>
                    <li>Asegúrese de comunicar la contraseña al usuario</li>
                </ul>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Usuario
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>

        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const miembroSelect = document.getElementById('miembro_id');
    const datosDiv = document.getElementById('datos_miembro');
    const rolSelect = document.getElementById('rol_id');
    const divMinisterio = document.getElementById('div_ministerio');

    // Mostrar datos del miembro seleccionado
    miembroSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        
        if (this.value) {
            document.getElementById('show_nombre').value = option.dataset.nombre + ' ' + option.dataset.apellido;
            document.getElementById('show_cedula').value = option.dataset.cedula;
            document.getElementById('show_telefono').value = option.dataset.telefono || 'No registrado';
            document.getElementById('show_nacionalidad').value = option.dataset.nacionalidad || 'No registrada';
            datosDiv.style.display = 'block';
        } else {
            datosDiv.style.display = 'none';
        }
    });

    // Mostrar/ocultar ministerio según rol
    rolSelect.addEventListener('change', function() {
        const rolNombre = this.options[this.selectedIndex].dataset.rol || '';
        
        if (rolNombre === 'lider_ministerio') {
            divMinisterio.classList.remove('d-none');
        } else {
            divMinisterio.classList.add('d-none');
            document.getElementById('ministerio_id').value = '';
        }
    });

    // Validar formulario
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        const clave = document.querySelector('input[name="clave"]').value;
        const claveConfirmar = document.querySelector('input[name="clave_confirmar"]').value;
        
        if (clave !== claveConfirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
            return false;
        }
        
        const rolNombre = rolSelect.options[rolSelect.selectedIndex].dataset.rol || '';
        if (rolNombre === 'lider_ministerio' && !document.getElementById('ministerio_id').value) {
            e.preventDefault();
            alert('Debe seleccionar un ministerio para el líder.');
            return false;
        }
    });
});

// Generar contraseña automática
function generarClave() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let clave = '';
    for (let i = 0; i < 8; i++) {
        clave += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    
    document.getElementById('clave').value = clave;
    document.querySelector('input[name="clave_confirmar"]').value = clave;
    document.getElementById('clave_generada').textContent = clave;
    
    // Copiar al portapapeles
    navigator.clipboard.writeText(clave).then(() => {
        alert('Contraseña generada y copiada: ' + clave);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
