<?php
/**
 * Crear Nueva Visita - Sistema Concilio
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Nueva Visita";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden crear
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_crear) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener nacionalidades
$nacionalidades = $conexion->query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY id");

// Obtener miembros de la iglesia para "invitado por"
$miembros_sql = "SELECT id, nombre, apellido FROM miembros WHERE estado = 'activo'";
if ($ROL_NOMBRE !== 'super_admin') {
    $miembros_sql .= " AND iglesia_id = " . (int)$IGLESIA_ID;
}
$miembros_sql .= " ORDER BY nombre, apellido";
$miembros = $conexion->query($miembros_sql);

// Si es super_admin, puede elegir iglesia
$iglesias = null;
if ($ROL_NOMBRE === 'super_admin') {
    $iglesias = $conexion->query("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
                                   FROM iglesias i 
                                   INNER JOIN distritos d ON d.id = i.distrito_id 
                                   INNER JOIN conferencias c ON c.id = d.conferencia_id 
                                   WHERE i.activo = 1 
                                   ORDER BY c.nombre, d.nombre, i.nombre");
}

// Categorías disponibles
$categorias = [
    'damas' => 'Damas (Mujeres adultas)',
    'caballeros' => 'Caballeros (Hombres adultos)',
    'jovenes' => 'Jóvenes (18-30 años)',
    'jovencitos' => 'Jovencitos (12-17 años)',
    'ninos' => 'Niños (Menores de 12)'
];
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0"><i class="fas fa-user-plus text-primary"></i> Nueva Visita</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos de la Visita</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="post" action="guardar.php" id="formVisita">
            
            <!-- DATOS PERSONALES -->
            <h5 class="mb-3 pb-2 text-primary border-bottom">
                <i class="fas fa-user"></i> Datos Personales
            </h5>
            
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" class="form-control" 
                           required maxlength="100" placeholder="Nombre de la visita"
                           style="text-transform: uppercase;">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Apellido <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="apellido" id="apellido" class="form-control" 
                           required maxlength="100" placeholder="Apellido"
                           style="text-transform: uppercase;">
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span>
                    </label>
                    <select name="sexo" id="sexo" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-flag"></i> Nacionalidad
                    </label>
                    <select name="nacionalidad_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                        <option value="<?php echo $nac['id']; ?>">
                            <?php echo htmlspecialchars($nac['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           maxlength="20" placeholder="809-000-0000">
                </div>
            </div>

            <!-- CATEGORÍA Y VISITA -->
            <h5 class="mb-3 pb-2 text-primary border-bottom mt-4">
                <i class="fas fa-users"></i> Categoría y Datos de Visita
            </h5>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-layer-group"></i> Categoría <span class="text-danger">*</span>
                    </label>
                    <select name="categoria" id="categoria" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($categorias as $key => $texto): ?>
                        <option value="<?php echo $key; ?>"><?php echo $texto; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Seleccione según la edad y género de la visita</small>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Visita <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="fecha_visita" class="form-control" 
                           required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-user-friends"></i> Invitado por (Miembro)
                    </label>
                    <select name="invitado_por" class="form-select" id="selectInvitadoPor">
                        <option value="">-- Sin registro / No aplica --</option>
                        <?php while ($m = $miembros->fetch_assoc()): ?>
                        <option value="<?php echo $m['id']; ?>">
                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Opcional: Seleccione el miembro que trajo a la visita</small>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-sticky-note"></i> Observaciones
                    </label>
                    <textarea name="observaciones" class="form-control" rows="2" 
                              maxlength="500" placeholder="Notas adicionales sobre la visita..."></textarea>
                </div>
            </div>

            <?php if ($ROL_NOMBRE === 'super_admin'): ?>
            <!-- IGLESIA (Solo Super Admin) -->
            <h5 class="mb-3 pb-2 text-primary border-bottom mt-4">
                <i class="fas fa-church"></i> Iglesia
            </h5>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-church"></i> Iglesia <span class="text-danger">*</span>
                    </label>
                    <select name="iglesia_id" class="form-select" required>
                        <option value="">-- Seleccione Iglesia --</option>
                        <?php while ($ig = $iglesias->fetch_assoc()): ?>
                        <option value="<?php echo $ig['id']; ?>">
                            <?php echo htmlspecialchars($ig['conferencia_nombre'] . ' / ' . $ig['distrito_nombre'] . ' / ' . $ig['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <?php else: ?>
            <input type="hidden" name="iglesia_id" value="<?php echo $IGLESIA_ID; ?>">
            <?php endif; ?>

            <!-- Botones -->
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Visita
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
    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const telefono = document.getElementById('telefono');
    const sexo = document.getElementById('sexo');
    const categoria = document.getElementById('categoria');

    // Convertir a mayúsculas
    function toUpperCase(e) {
        e.target.value = e.target.value.toUpperCase();
    }
    nombre.addEventListener('input', toUpperCase);
    apellido.addEventListener('input', toUpperCase);

    // Formato teléfono dominicano
    function formatTelefono(value) {
        let numbers = value.replace(/\D/g, '');
        if (numbers.length > 10) numbers = numbers.substr(0, 10);
        
        if (numbers.length > 6) {
            return numbers.substr(0, 3) + '-' + numbers.substr(3, 3) + '-' + numbers.substr(6);
        } else if (numbers.length > 3) {
            return numbers.substr(0, 3) + '-' + numbers.substr(3);
        }
        return numbers;
    }

    telefono.addEventListener('input', function() {
        this.value = formatTelefono(this.value);
    });

    // Sugerir categoría según sexo
    sexo.addEventListener('change', function() {
        const val = this.value;
        const catActual = categoria.value;
        
        // Solo sugerir si no hay categoría seleccionada
        if (catActual === '') {
            if (val === 'F') {
                categoria.value = 'damas';
            } else if (val === 'M') {
                categoria.value = 'caballeros';
            }
        }
    });

    // Validar coherencia sexo/categoría
    document.getElementById('formVisita').addEventListener('submit', function(e) {
        const sexoVal = sexo.value;
        const catVal = categoria.value;
        
        // Validar coherencia
        if (sexoVal === 'M' && catVal === 'damas') {
            if (!confirm('Ha seleccionado Masculino pero categoría Damas. ¿Continuar de todos modos?')) {
                e.preventDefault();
                return false;
            }
        }
        if (sexoVal === 'F' && catVal === 'caballeros') {
            if (!confirm('Ha seleccionado Femenino pero categoría Caballeros. ¿Continuar de todos modos?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
