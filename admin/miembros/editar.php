<?php
declare(strict_types=1);

$page_title = "Editar Miembro";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden editar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener miembro
$stmt = $conexion->prepare("SELECT m.*, f.codigo AS familia_codigo, f.apellido_familia 
                            FROM miembros m 
                            LEFT JOIN familias f ON f.id = m.familia_id 
                            WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    header("Location: index.php?error=Miembro no encontrado");
    exit;
}

// Verificar que el usuario tenga acceso a este miembro
if ($ROL_NOMBRE === 'pastor' || $ROL_NOMBRE === 'secretaria') {
    if ($miembro['iglesia_id'] != $IGLESIA_ID) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para editar este miembro.</div>";
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
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

// Obtener familias de la iglesia del miembro
$stmt = $conexion->prepare("SELECT f.*, COUNT(m.id) AS total_miembros 
                            FROM familias f 
                            LEFT JOIN miembros m ON m.familia_id = f.id 
                            WHERE f.iglesia_id = ? 
                            GROUP BY f.id 
                            ORDER BY f.codigo");
$stmt->bind_param("i", $miembro['iglesia_id']);
$stmt->execute();
$familias = $stmt->get_result();
$stmt->close();

// Obtener zonas de la iglesia del miembro
$stmt = $conexion->prepare("SELECT z.*, COUNT(m.id) AS total_miembros 
                            FROM zonas z 
                            LEFT JOIN miembros m ON m.zona_id = z.id AND m.estado = 'activo'
                            WHERE z.iglesia_id = ? AND z.activo = 1
                            GROUP BY z.id 
                            ORDER BY z.codigo");
$stmt->bind_param("i", $miembro['iglesia_id']);
$stmt->execute();
$zonas = $stmt->get_result();
$stmt->close();
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Editar Miembro</h1>
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

        <form method="post" action="actualizar.php" id="formMiembro" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $miembro['id']; ?>">
            <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($miembro['foto'] ?? ''); ?>">
            
            <!-- DATOS PERSONALES -->
            <h4 class="mb-3 pb-2 text-primary border-bottom">
                <i class="fas fa-user"></i> Datos Personales
            </h4>
            
            <!-- Foto actual -->
            <?php if ($miembro['foto']): ?>
                <div class="mb-3">
                    <label class="form-label">Foto Actual</label>
                    <div>
                        <img src="../../uploads/miembros/<?php echo htmlspecialchars($miembro['foto']); ?>" 
                             alt="Foto actual" class="rounded" style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #ddd;">
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="nombre" class="form-control text-uppercase" required maxlength="100" 
                           value="<?php echo htmlspecialchars($miembro['nombre']); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Apellido <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="apellido" class="form-control text-uppercase" required maxlength="100" 
                           value="<?php echo htmlspecialchars($miembro['apellido']); ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span>
                    </label>
                    <select name="sexo" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="M" <?php echo $miembro['sexo'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $miembro['sexo'] === 'F' ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Nacimiento
                    </label>
                    <input type="date" name="fecha_nacimiento" class="form-control" 
                           value="<?php echo $miembro['fecha_nacimiento'] ?? ''; ?>">
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
                            <option value="<?php echo $nac['id']; ?>" <?php echo $miembro['nacionalidad_id'] == $nac['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nac['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-id-card"></i> Tipo de Documento
                    </label>
                    <select name="tipo_documento" id="tipo_documento" class="form-select">
                        <option value="cedula" <?php echo $miembro['tipo_documento'] === 'cedula' ? 'selected' : ''; ?>>Cédula</option>
                        <option value="pasaporte" <?php echo $miembro['tipo_documento'] === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-id-badge"></i> Número de Documento
                    </label>
                    <input type="text" name="numero_documento" id="numero_documento" class="form-control" 
                           placeholder="<?php echo $miembro['tipo_documento'] === 'cedula' ? '000-0000000-0' : 'Número de pasaporte'; ?>" 
                           maxlength="15" value="<?php echo htmlspecialchars($miembro['numero_documento'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" class="form-control" 
                           placeholder="809-000-0000" maxlength="12" 
                           value="<?php echo htmlspecialchars($miembro['telefono'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Dirección
                    </label>
                    <input type="text" name="direccion" class="form-control" maxlength="255" 
                           value="<?php echo htmlspecialchars($miembro['direccion'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-camera"></i> Cambiar Foto
                    </label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <small class="text-muted d-block">Dejar vacío para mantener la foto actual. Formatos: JPG, PNG. Máximo: 2MB</small>
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
                        <option value="soltero" <?php echo $miembro['estado_civil'] === 'soltero' ? 'selected' : ''; ?>>Soltero/a</option>
                        <option value="casado" <?php echo $miembro['estado_civil'] === 'casado' ? 'selected' : ''; ?>>Casado/a</option>
                        <option value="union_libre" <?php echo $miembro['estado_civil'] === 'union_libre' ? 'selected' : ''; ?>>Unión Libre</option>
                        <option value="divorciado" <?php echo $miembro['estado_civil'] === 'divorciado' ? 'selected' : ''; ?>>Divorciado/a</option>
                        <option value="viudo" <?php echo $miembro['estado_civil'] === 'viudo' ? 'selected' : ''; ?>>Viudo/a</option>
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
                            <option value="<?php echo $fam['id']; ?>" <?php echo $miembro['familia_id'] == $fam['id'] ? 'selected' : ''; ?>>
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
                    <input type="text" name="apellido_familia" class="form-control text-uppercase" 
                           placeholder="Ej: FAMILIA PÉREZ" maxlength="150">
                    <small class="text-muted d-block">Opcional, para identificar la familia</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Zona / Grupo
                    </label>
                    <select name="zona_id" id="zona_id" class="form-select">
                        <option value="">-- Sin asignar zona --</option>
                        <?php while ($zon = $zonas->fetch_assoc()): ?>
                            <option value="<?php echo $zon['id']; ?>" <?php echo ($miembro['zona_id'] ?? 0) == $zon['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zon['codigo']); ?>
                                - <?php echo htmlspecialchars($zon['nombre']); ?>
                                (<?php echo $zon['total_miembros']; ?> miembros)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted d-block">Zona geográfica o grupo de la iglesia</small>
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
                            <option value="<?php echo $ne['id']; ?>" 
                                    data-requiere-carrera="<?php echo $ne['requiere_carrera']; ?>"
                                    <?php echo $miembro['nivel_estudio_id'] == $ne['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ne['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-6" id="div_carrera" style="display: <?php echo $miembro['carrera_id'] ? 'block' : 'none'; ?>;">
                    <label class="form-label">
                        <i class="fas fa-briefcase"></i> Carrera
                    </label>
                    <select name="carrera_id" id="carrera_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($car = $carreras->fetch_assoc()): ?>
                            <option value="<?php echo $car['id']; ?>" <?php echo $miembro['carrera_id'] == $car['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($car['nombre']); ?>
                            </option>
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
                                <option value="<?php echo $igl['id']; ?>" <?php echo $miembro['iglesia_id'] == $igl['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($igl['conferencia_nombre'] . ' → ' . $igl['distrito_nombre'] . ' → ' . $igl['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            <?php else: ?>
                <input type="hidden" name="iglesia_id" value="<?php echo $miembro['iglesia_id']; ?>">
            <?php endif; ?>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user-check"></i> Estado de Membresía <span class="text-danger">*</span>
                    </label>
                    <select name="estado_miembro" class="form-select" required>
                        <option value="en_preparacion" <?php echo $miembro['estado_miembro'] === 'en_preparacion' ? 'selected' : ''; ?>>En Preparación</option>
                        <option value="en_plena" <?php echo $miembro['estado_miembro'] === 'en_plena' ? 'selected' : ''; ?>>En Plena Comunión</option>
                        <option value="miembro_menor" <?php echo $miembro['estado_miembro'] === 'miembro_menor' ? 'selected' : ''; ?>>Miembro Menor</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-hands-praying"></i> Ministerio
                    </label>
                    <select name="ministerio_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($min = $ministerios->fetch_assoc()): ?>
                            <option value="<?php echo $min['id']; ?>" <?php echo $miembro['ministerio_id'] == $min['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($min['nombre']); ?>
                            </option>
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
                        <option value="0" <?php echo $miembro['es_bautizado'] == 0 ? 'selected' : ''; ?>>No</option>
                        <option value="1" <?php echo $miembro['es_bautizado'] == 1 ? 'selected' : ''; ?>>Sí</option>
                    </select>
                </div>

                <div class="col-md-6" id="div_fecha_bautismo" style="display: <?php echo $miembro['es_bautizado'] ? 'block' : 'none'; ?>;">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Bautismo
                    </label>
                    <input type="date" name="fecha_bautismo" class="form-control" 
                           value="<?php echo $miembro['fecha_bautismo'] ?? ''; ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-star"></i> ¿Es Líder?
                    </label>
                    <select name="es_lider" class="form-select">
                        <option value="0" <?php echo $miembro['es_lider'] == 0 ? 'selected' : ''; ?>>No</option>
                        <option value="1" <?php echo $miembro['es_lider'] == 1 ? 'selected' : ''; ?>>Sí</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        <i class="fas fa-toggle-on"></i> Estado
                    </label>
                    <select name="estado" class="form-select">
                        <option value="activo" <?php echo $miembro['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $miembro['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="fallecido" <?php echo $miembro['estado'] === 'fallecido' ? 'selected' : ''; ?>>Fallecido</option>
                        <option value="trasladado" <?php echo $miembro['estado'] === 'trasladado' ? 'selected' : ''; ?>>Trasladado</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Actualizar Miembro
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

    // Verificar estado inicial del nivel de estudio
    if (nivelEstudio.selectedIndex > 0) {
        const requiereCarrera = nivelEstudio.options[nivelEstudio.selectedIndex].dataset.requiereCarrera === '1';
        divCarrera.style.display = requiereCarrera ? 'block' : 'none';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>