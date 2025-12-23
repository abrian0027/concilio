<?php
declare(strict_types=1);

$page_title = "Editar Usuario";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener usuario
$stmt = $conexion->prepare("SELECT u.*, r.nombre AS rol_nombre FROM usuarios u LEFT JOIN roles r ON r.id = u.rol_id WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: index.php?error=Usuario no encontrado");
    exit;
}

// Obtener datos para los selects
$roles = $conexion->query("SELECT * FROM roles ORDER BY id");
$conferencias = $conexion->query("SELECT * FROM conferencias WHERE activo = 1 ORDER BY nombre");
$ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 ORDER BY nombre");

// Obtener distritos de la conferencia seleccionada
$distritos = null;
if ($usuario['conferencia_id']) {
    $stmt = $conexion->prepare("SELECT * FROM distritos WHERE conferencia_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $usuario['conferencia_id']);
    $stmt->execute();
    $distritos = $stmt->get_result();
    $stmt->close();
}

// Obtener iglesias del distrito seleccionado
$iglesias = null;
if ($usuario['distrito_id']) {
    $stmt = $conexion->prepare("SELECT * FROM iglesias WHERE distrito_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $usuario['distrito_id']);
    $stmt->execute();
    $iglesias = $stmt->get_result();
    $stmt->close();
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Editar Usuario</h1>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos del Usuario</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php" id="formUsuario">
            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                           required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Apellido <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="apellido" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                           required maxlength="100">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-at"></i> Usuario <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="usuario" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['usuario']); ?>"
                           required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo
                    </label>
                    <input type="email" name="correo" class="form-control" 
                           value="<?php echo htmlspecialchars($usuario['correo'] ?? ''); ?>"
                           maxlength="150">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Nueva Contraseña
                    </label>
                    <input type="password" name="clave" class="form-control" 
                           placeholder="Dejar vacío para no cambiar" minlength="6">
                    <small class="text-muted">Solo si desea cambiar la contraseña</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                    </label>
                    <input type="password" name="clave_confirmar" class="form-control" 
                           placeholder="Repetir contraseña" minlength="6">
                </div>
            </div>

            <hr style="margin: 1.5rem 0;">
            <h4 style="margin-bottom: 1rem;"><i class="fas fa-id-badge"></i> Rol y Asignación</h4>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tag"></i> Rol <span style="color:red;">*</span>
                </label>
                <select name="rol_id" id="rol_id" class="form-control" required>
                    <option value="">-- Seleccione un rol --</option>
                    <?php while ($r = $roles->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>" 
                                data-rol="<?php echo $r['nombre']; ?>"
                                <?php echo $usuario['rol_id'] == $r['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $r['nombre']))); ?>
                            - <?php echo htmlspecialchars($r['descripcion']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Campos dinámicos según rol -->
            <div id="campos_asignacion">
                <div class="mb-3" id="div_conferencia" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Conferencia
                    </label>
                    <select name="conferencia_id" id="conferencia_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php while ($c = $conferencias->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"
                                    <?php echo $usuario['conferencia_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3" id="div_distrito" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-map-marked-alt"></i> Distrito
                    </label>
                    <select name="distrito_id" id="distrito_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php if ($distritos): while ($d = $distritos->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>"
                                    <?php echo $usuario['distrito_id'] == $d['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['codigo'] . ' - ' . $d['nombre']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="mb-3" id="div_iglesia" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Iglesia
                    </label>
                    <select name="iglesia_id" id="iglesia_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php if ($iglesias): while ($i = $iglesias->fetch_assoc()): ?>
                            <option value="<?php echo $i['id']; ?>"
                                    <?php echo $usuario['iglesia_id'] == $i['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($i['codigo'] . ' - ' . $i['nombre']); ?>
                            </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>

                <div class="mb-3" id="div_ministerio" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-hands-praying"></i> Ministerio
                    </label>
                    <select name="ministerio_id" id="ministerio_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php while ($m = $ministerios->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>"
                                    <?php echo $usuario['ministerio_id'] == $m['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control">
                    <option value="1" <?php echo $usuario['activo'] == 1 ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo $usuario['activo'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Usuario
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('rol_id');
    const divConferencia = document.getElementById('div_conferencia');
    const divDistrito = document.getElementById('div_distrito');
    const divIglesia = document.getElementById('div_iglesia');
    const divMinisterio = document.getElementById('div_ministerio');
    const conferenciaSel = document.getElementById('conferencia_id');
    const distritoSel = document.getElementById('distrito_id');
    const iglesiaSel = document.getElementById('iglesia_id');

    function mostrarCamposSegunRol() {
        const rolNombre = rolSelect.options[rolSelect.selectedIndex].dataset.rol || '';
        
        // Ocultar todos
        divConferencia.style.display = 'none';
        divDistrito.style.display = 'none';
        divIglesia.style.display = 'none';
        divMinisterio.style.display = 'none';

        // Mostrar según rol
        if (rolNombre === 'super_conferencia') {
            divConferencia.style.display = 'block';
        } else if (rolNombre === 'super_distrito') {
            divConferencia.style.display = 'block';
            divDistrito.style.display = 'block';
        } else if (['pastor', 'secretaria', 'tesorero'].includes(rolNombre)) {
            divConferencia.style.display = 'block';
            divDistrito.style.display = 'block';
            divIglesia.style.display = 'block';
        } else if (rolNombre === 'lider_ministerio') {
            divConferencia.style.display = 'block';
            divDistrito.style.display = 'block';
            divIglesia.style.display = 'block';
            divMinisterio.style.display = 'block';
        }
    }

    // Ejecutar al cargar
    mostrarCamposSegunRol();

    // Ejecutar al cambiar rol
    rolSelect.addEventListener('change', mostrarCamposSegunRol);

    // Cascada: Conferencia -> Distritos
    conferenciaSel.addEventListener('change', function() {
        const confId = this.value;
        distritoSel.innerHTML = '<option value="">Cargando...</option>';
        iglesiaSel.innerHTML = '<option value="">-- Primero seleccione distrito --</option>';

        if (confId) {
            fetch('ajax_distritos.php?conferencia_id=' + confId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Seleccione distrito --</option>';
                    data.forEach(d => {
                        options += `<option value="${d.id}">${d.codigo} - ${d.nombre}</option>`;
                    });
                    distritoSel.innerHTML = options;
                });
        } else {
            distritoSel.innerHTML = '<option value="">-- Primero seleccione conferencia --</option>';
        }
    });

    // Cascada: Distrito -> Iglesias
    distritoSel.addEventListener('change', function() {
        const distId = this.value;
        iglesiaSel.innerHTML = '<option value="">Cargando...</option>';

        if (distId) {
            fetch('ajax_iglesias.php?distrito_id=' + distId)
                .then(response => response.json())
                .then(data => {
                    let options = '<option value="">-- Seleccione iglesia --</option>';
                    data.forEach(i => {
                        options += `<option value="${i.id}">${i.codigo} - ${i.nombre}</option>`;
                    });
                    iglesiaSel.innerHTML = options;
                });
        } else {
            iglesiaSel.innerHTML = '<option value="">-- Primero seleccione distrito --</option>';
        }
    });

    // Validar contraseñas
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        const clave = document.querySelector('input[name="clave"]').value;
        const claveConfirmar = document.querySelector('input[name="clave_confirmar"]').value;
        
        if (clave !== '' && clave !== claveConfirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>