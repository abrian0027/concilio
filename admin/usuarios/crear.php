<?php
declare(strict_types=1);

$page_title = "Nuevo Usuario";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener datos para los selects
$roles = $conexion->query("SELECT * FROM roles ORDER BY id");
$conferencias = $conexion->query("SELECT * FROM conferencias WHERE activo = 1 ORDER BY nombre");
$ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 ORDER BY nombre");
$nacionalidades = $conexion->query("SELECT id, nombre FROM nacionalidades WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Nuevo Usuario</h1>
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

        <form method="post" action="guardar.php" id="formUsuario">
            
            <!-- DATOS PERSONALES -->
            <h4 style="margin-bottom: 1rem; color: #333;"><i class="fas fa-user"></i> Datos Personales</h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        Nombre <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" 
                           placeholder="Nombre" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Apellido <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="apellido" class="form-control" 
                           placeholder="Apellido" required maxlength="100">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <!-- Cédula (será el usuario para login) -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Cédula <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="cedula" id="cedula" class="form-control" 
                           placeholder="000-0000000-0" required maxlength="13"
                           pattern="\d{3}-\d{7}-\d{1}">
                    <small class="text-muted">Será el identificador para el login</small>
                </div>

                <!-- Teléfono -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono <span style="color:red;">*</span>
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="(809) 000-0000" required maxlength="14">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Correo
                    </label>
                    <input type="email" name="correo" class="form-control" 
                           placeholder="correo@ejemplo.com" maxlength="150">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-flag"></i> Nacionalidad
                    </label>
                    <select name="nacionalidad_id" class="form-control">
                        <option value="">Seleccione...</option>
                        <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                            <option value="<?php echo $nac['id']; ?>"
                                <?php echo ($nac['nombre'] == 'Dominicana') ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nac['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <hr style="margin: 1.5rem 0;">
            <h4 style="margin-bottom: 1rem; color: #333;"><i class="fas fa-lock"></i> Credenciales de Acceso</h4>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="mb-3">
                    <label class="form-label">
                        Contraseña <span style="color:red;">*</span>
                    </label>
                    <input type="password" name="clave" class="form-control" 
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        Confirmar Contraseña <span style="color:red;">*</span>
                    </label>
                    <input type="password" name="clave_confirmar" class="form-control" 
                           placeholder="Repetir contraseña" required minlength="6">
                </div>
            </div>

            <hr style="margin: 1.5rem 0;">
            <h4 style="margin-bottom: 1rem; color: #333;"><i class="fas fa-id-badge"></i> Rol y Asignación</h4>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tag"></i> Rol <span style="color:red;">*</span>
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

            <!-- Campos dinámicos según rol -->
            <div id="campos_asignacion">
                <div class="mb-3" id="div_conferencia" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Conferencia
                    </label>
                    <select name="conferencia_id" id="conferencia_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php while ($c = $conferencias->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>">
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
                        <option value="">-- Primero seleccione conferencia --</option>
                    </select>
                </div>

                <div class="mb-3" id="div_iglesia" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Iglesia
                    </label>
                    <select name="iglesia_id" id="iglesia_id" class="form-control">
                        <option value="">-- Primero seleccione distrito --</option>
                    </select>
                </div>

                <div class="mb-3" id="div_ministerio" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-hands-praying"></i> Ministerio
                    </label>
                    <select name="ministerio_id" id="ministerio_id" class="form-control">
                        <option value="">-- Seleccione --</option>
                        <?php while ($m = $ministerios->fetch_assoc()): ?>
                            <option value="<?php echo $m['id']; ?>">
                                <?php echo htmlspecialchars($m['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- Aviso para roles de iglesia -->
            <div class="alert alert-info" id="aviso_miembro" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Al crear este usuario, automáticamente se registrará como <strong>miembro</strong> 
                de la iglesia seleccionada con estado "En Plena Relación". Esto permite que pueda hacer login 
                usando el código de la iglesia.
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-toggle-on"></i> Estado
                </label>
                <select name="activo" class="form-control">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Usuario
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
    const avisoMiembro = document.getElementById('aviso_miembro');
    const conferenciaSel = document.getElementById('conferencia_id');
    const distritoSel = document.getElementById('distrito_id');
    const iglesiaSel = document.getElementById('iglesia_id');

    // Formatear cédula dominicana
    document.getElementById('cedula').addEventListener('input', function(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 11) valor = valor.substring(0, 11);
        
        let formato = '';
        if (valor.length > 0) formato = valor.substring(0, 3);
        if (valor.length > 3) formato += '-' + valor.substring(3, 10);
        if (valor.length > 10) formato += '-' + valor.substring(10, 11);
        
        e.target.value = formato;
    });

    // Formatear teléfono dominicano
    document.getElementById('telefono').addEventListener('input', function(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 10) valor = valor.substring(0, 10);
        
        let formato = '';
        if (valor.length > 0) formato = '(' + valor.substring(0, 3);
        if (valor.length > 3) formato += ') ' + valor.substring(3, 6);
        if (valor.length > 6) formato += '-' + valor.substring(6, 10);
        
        e.target.value = formato;
    });

    // Mostrar/ocultar campos según rol
    rolSelect.addEventListener('change', function() {
        const rolNombre = this.options[this.selectedIndex].dataset.rol || '';
        
        // Ocultar todos
        divConferencia.style.display = 'none';
        divDistrito.style.display = 'none';
        divIglesia.style.display = 'none';
        divMinisterio.style.display = 'none';
        avisoMiembro.style.display = 'none';

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
            avisoMiembro.style.display = 'block'; // Mostrar aviso
        } else if (rolNombre === 'lider_ministerio') {
            divConferencia.style.display = 'block';
            divDistrito.style.display = 'block';
            divIglesia.style.display = 'block';
            divMinisterio.style.display = 'block';
            avisoMiembro.style.display = 'block'; // Mostrar aviso
        }
    });

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

    // Validar formulario
    document.getElementById('formUsuario').addEventListener('submit', function(e) {
        const clave = document.querySelector('input[name="clave"]').value;
        const claveConfirmar = document.querySelector('input[name="clave_confirmar"]').value;
        const cedula = document.getElementById('cedula').value;
        
        if (clave !== claveConfirmar) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
            return false;
        }
        
        if (cedula.length < 13) {
            e.preventDefault();
            alert('La cédula debe tener el formato completo: 000-0000000-0');
            document.getElementById('cedula').focus();
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>