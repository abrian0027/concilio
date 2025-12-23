<?php
declare(strict_types=1);

$page_title = "Nuevo Miembro";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden crear
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_crear) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener datos para los selects
$nacionalidades = $conexion->query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY id");
$niveles_estudio = $conexion->query("SELECT * FROM niveles_estudio WHERE activo = 1 ORDER BY id");
$carreras = $conexion->query("SELECT * FROM carreras WHERE activo = 1 ORDER BY nombre");
$ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 ORDER BY nombre");

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

// Obtener familias de la iglesia del usuario (o todas si es super_admin)
$familias_sql = "SELECT f.*, COUNT(m.id) AS total_miembros 
                 FROM familias f 
                 LEFT JOIN miembros m ON m.familia_id = f.id";
if ($ROL_NOMBRE !== 'super_admin') {
    $familias_sql .= " WHERE f.iglesia_id = " . (int)$IGLESIA_ID;
}
$familias_sql .= " GROUP BY f.id ORDER BY f.codigo";
$familias = $conexion->query($familias_sql);
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Nuevo Miembro</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos del Miembro</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="guardar.php" id="formMiembro" enctype="multipart/form-data">
            
            <!-- DATOS PERSONALES -->
            <h4 class="mb-3 pb-2 text-primary border-bottom">
                <i class="fas fa-user"></i> Datos Personales
            </h4>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control" required maxlength="100" placeholder="Nombre">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Apellido <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="apellido" class="form-control" required maxlength="100" placeholder="Apellido">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span>
                    </label>
                    <select name="sexo" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Nacimiento
                    </label>
                    <input type="date" name="fecha_nacimiento" class="form-control">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-flag"></i> Nacionalidad
                    </label>
                    <select name="nacionalidad_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                            <option value="<?php echo $nac['id']; ?>"><?php echo htmlspecialchars($nac['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Tipo de Documento
                    </label>
                    <select name="tipo_documento" id="tipo_documento" class="form-select">
                        <option value="cedula">Cédula</option>
                        <option value="pasaporte">Pasaporte</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-id-badge"></i> Número de Documento
                    </label>
                    <input type="text" name="numero_documento" id="numero_documento" class="form-control" 
                           placeholder="000-0000000-0" maxlength="15">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="809-000-0000" maxlength="12">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Dirección
                    </label>
                    <input type="text" name="direccion" class="form-control" maxlength="255" placeholder="Dirección completa">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-camera"></i> Foto
                    </label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <small class="text-muted d-block">Formatos: JPG, PNG. Tamaño máximo: 2MB</small>
                </div>
            </div>

            <!-- ESTADO CIVIL Y FAMILIA -->
            <h4 class="mb-3 pb-2 text-primary border-bottom mt-4">
                <i class="fas fa-heart"></i> Estado Civil y Familia
            </h4>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-ring"></i> Estado Civil
                    </label>
                    <select name="estado_civil" class="form-select">
                        <option value="soltero">Soltero/a</option>
                        <option value="casado">Casado/a</option>
                        <option value="union_libre">Unión Libre</option>
                        <option value="divorciado">Divorciado/a</option>
                        <option value="viudo">Viudo/a</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-home"></i> Familia
                    </label>
                    <select name="familia_opcion" id="familia_opcion" class="form-select">
                        <option value="">-- Sin asignar familia --</option>
                        <option value="nueva">➕ Crear nueva familia</option>
                        <?php while ($fam = $familias->fetch_assoc()): ?>
                            <option value="<?php echo $fam['id']; ?>">
                                <?php echo htmlspecialchars($fam['codigo']); ?>
                                <?php if ($fam['apellido_familia']): ?>
                                    - <?php echo htmlspecialchars($fam['apellido_familia']); ?>
                                <?php endif; ?>
                                (<?php echo $fam['total_miembros']; ?> miembros)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4" id="div_apellido_familia" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Apellido de la Familia
                    </label>
                    <input type="text" name="apellido_familia" class="form-control" 
                           placeholder="Ej: Familia Pérez" maxlength="150">
                    <small class="text-muted d-block">Opcional, para identificar la familia</small>
                </div>
            </div>

            <!-- NIVEL DE ESTUDIOS -->
            <h4 class="mb-3 pb-2 text-primary border-bottom mt-4">
                <i class="fas fa-graduation-cap"></i> Nivel de Estudios
            </h4>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-book"></i> Nivel de Estudio
                    </label>
                    <select name="nivel_estudio_id" id="nivel_estudio_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($ne = $niveles_estudio->fetch_assoc()): ?>
                            <option value="<?php echo $ne['id']; ?>" data-requiere-carrera="<?php echo $ne['requiere_carrera']; ?>">
                                <?php echo htmlspecialchars($ne['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6" id="div_carrera" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-briefcase"></i> Carrera
                    </label>
                    <select name="carrera_id" id="carrera_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($car = $carreras->fetch_assoc()): ?>
                            <option value="<?php echo $car['id']; ?>"><?php echo htmlspecialchars($car['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- DATOS ECLESIÁSTICOS -->
            <h4 class="mb-3 pb-2 text-primary border-bottom mt-4">
                <i class="fas fa-church"></i> Datos Eclesiásticos
            </h4>

            <?php if ($ROL_NOMBRE === 'super_admin'): ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">
                            <i class="fas fa-church"></i> Iglesia <span class="text-danger">*</span>
                        </label>
                        <select name="iglesia_id" id="iglesia_id" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php while ($igl = $iglesias->fetch_assoc()): ?>
                            <option value="<?php echo $igl['id']; ?>">
                                <?php echo htmlspecialchars($igl['conferencia_nombre'] . ' → ' . $igl['distrito_nombre'] . ' → ' . $igl['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    </div>
                </div>
            <?php else: ?>
                <input type="hidden" name="iglesia_id" value="<?php echo $IGLESIA_ID; ?>">
            <?php endif; ?>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user-check"></i> Estado de Membresía <span class="text-danger">*</span>
                    </label>
                    <select name="estado_miembro" class="form-select" required>
                        <option value="en_preparacion">En Preparación</option>
                        <option value="en_plena">En Plena Comunión</option>
                        <option value="miembro_menor">Miembro Menor</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-hands-praying"></i> Ministerio
                    </label>
                    <select name="ministerio_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($min = $ministerios->fetch_assoc()): ?>
                            <option value="<?php echo $min['id']; ?>"><?php echo htmlspecialchars($min['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-water"></i> ¿Es Bautizado?
                    </label>
                    <select name="es_bautizado" id="es_bautizado" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>

                <div class="col-md-6" id="div_fecha_bautismo" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Bautismo
                    </label>
                    <input type="date" name="fecha_bautismo" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-star"></i> ¿Es Líder?
                    </label>
                    <select name="es_lider" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-toggle-on"></i> Estado
                    </label>
                    <select name="estado" class="form-select">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="fallecido">Fallecido</option>
                        <option value="trasladado">Trasladado</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Miembro
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
    const tipoDocumento = document.getElementById('tipo_documento');
    const numeroDocumento = document.getElementById('numero_documento');
    const telefono = document.getElementById('telefono');
    const familiaOpcion = document.getElementById('familia_opcion');
    const divApellidoFamilia = document.getElementById('div_apellido_familia');
    const nivelEstudio = document.getElementById('nivel_estudio_id');
    const divCarrera = document.getElementById('div_carrera');
    const esBautizado = document.getElementById('es_bautizado');
    const divFechaBautismo = document.getElementById('div_fecha_bautismo');
    
    // Campos de nombre y apellido
    const campoNombre = document.querySelector('input[name="nombre"]');
    const campoApellido = document.querySelector('input[name="apellido"]');
    const campoApellidoFamilia = document.querySelector('input[name="apellido_familia"]');

    // Función para convertir a mayúsculas
    function convertirMayusculas(e) {
        e.target.value = e.target.value.toUpperCase();
    }

    // Aplicar mayúsculas a nombre y apellido
    campoNombre.addEventListener('input', convertirMayusculas);
    campoApellido.addEventListener('input', convertirMayusculas);
    if (campoApellidoFamilia) {
        campoApellidoFamilia.addEventListener('input', convertirMayusculas);
    }

    // Formato cédula dominicana
    function formatCedula(value) {
        let numbers = value.replace(/\D/g, '');
        if (numbers.length > 11) numbers = numbers.substr(0, 11);
        
        if (numbers.length > 10) {
            return numbers.substr(0, 3) + '-' + numbers.substr(3, 7) + '-' + numbers.substr(10, 1);
        } else if (numbers.length > 3) {
            return numbers.substr(0, 3) + '-' + numbers.substr(3);
        }
        return numbers;
    }

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

    // Cambiar placeholder según tipo documento
    tipoDocumento.addEventListener('change', function() {
        if (this.value === 'cedula') {
            numeroDocumento.placeholder = '000-0000000-0';
            numeroDocumento.maxLength = 13;
        } else {
            numeroDocumento.placeholder = 'Número de pasaporte';
            numeroDocumento.maxLength = 20;
        }
        numeroDocumento.value = '';
    });

    // Formatear cédula mientras escribe
    numeroDocumento.addEventListener('input', function() {
        if (tipoDocumento.value === 'cedula') {
            this.value = formatCedula(this.value);
        }
    });

    // Formatear teléfono mientras escribe
    telefono.addEventListener('input', function() {
        this.value = formatTelefono(this.value);
    });

    // Mostrar/ocultar campo apellido familia
    familiaOpcion.addEventListener('change', function() {
        divApellidoFamilia.style.display = this.value === 'nueva' ? 'block' : 'none';
    });

    // Mostrar/ocultar carrera según nivel de estudio
    nivelEstudio.addEventListener('change', function() {
        const requiereCarrera = this.options[this.selectedIndex].dataset.requiereCarrera === '1';
        divCarrera.style.display = requiereCarrera ? 'block' : 'none';
    });

    // Mostrar/ocultar fecha bautismo
    esBautizado.addEventListener('change', function() {
        divFechaBautismo.style.display = this.value === '1' ? 'block' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
