<?php
declare(strict_types=1);

$page_title = "Editar Pastor";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin y Obispo
if (!in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener ID del pastor
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

// Obtener datos del pastor
$stmt = $conexion->prepare("SELECT * FROM pastores WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header("Location: index.php?error=" . urlencode("Pastor no encontrado"));
    exit;
}

// Obtener catálogos
$nacionalidades = $conexion->query("SELECT id, nombre FROM nacionalidades WHERE activo = 1 ORDER BY nombre");
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
$niveles_estudio = $conexion->query("SELECT id, nombre FROM niveles_estudio WHERE activo = 1 ORDER BY id");
$carreras = $conexion->query("SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre");

// Calcular edad y años de servicio
$edad = '';
if ($pastor['fecha_nacimiento']) {
    $fecha_nac = new DateTime($pastor['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y . ' años';
}

$anos_servicio = '';
if ($pastor['fecha_ingreso_ministerio']) {
    $fecha_ing = new DateTime($pastor['fecha_ingreso_ministerio']);
    $hoy = new DateTime();
    $anos_servicio = $hoy->diff($fecha_ing)->y . ' años';
}
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-user-edit"></i> Editar Pastor</h1>
        <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<div class="card" style="max-width: 1000px;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-edit"></i> Datos del Pastor</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php" enctype="multipart/form-data" id="formPastor">
            <input type="hidden" name="id" value="<?php echo $pastor['id']; ?>">
            
            <!-- SECCIÓN A: DATOS PERSONALES -->
            <fieldset>
                <legend>
                    <i class="fas fa-user"></i> A. Datos Personales
                </legend>
                
                <div class="form-grid-3">
                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nombre <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="nombre" class="form-control" 
                               required maxlength="100" placeholder="Nombre(s)"
                               value="<?php echo htmlspecialchars($pastor['nombre']); ?>">
                    </div>
                    
                    <!-- Apellido -->
                    <div class="mb-3">
                        <label class="form-label">
                            Apellido <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="apellido" class="form-control" 
                               required maxlength="100" placeholder="Apellido(s)"
                               value="<?php echo htmlspecialchars($pastor['apellido']); ?>">
                    </div>
                    
                    <!-- Sexo -->
                    <div class="mb-3">
                        <label class="form-label">
                            Sexo <span style="color:red;">*</span>
                        </label>
                        <select name="sexo" class="form-control" required>
                            <option value="M" <?php echo $pastor['sexo'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo $pastor['sexo'] === 'F' ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid-3">
                    <!-- Tipo de documento -->
                    <div class="mb-3">
                        <label class="form-label">
                            Tipo de Documento <span style="color:red;">*</span>
                        </label>
                        <select name="tipo_documento" id="tipo_documento" class="form-control" required>
                            <option value="Cédula" <?php echo $pastor['tipo_documento'] === 'Cédula' ? 'selected' : ''; ?>>Cédula</option>
                            <option value="Pasaporte" <?php echo $pastor['tipo_documento'] === 'Pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                        </select>
                    </div>
                    
                    <!-- Cédula -->
                    <div class="mb-3" id="campo_cedula" style="<?php echo $pastor['tipo_documento'] === 'Pasaporte' ? 'display:none;' : ''; ?>">
                        <label class="form-label">
                            Cédula <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="cedula" id="cedula" class="form-control" 
                               maxlength="13" placeholder="000-0000000-0"
                               pattern="\d{3}-\d{7}-\d{1}"
                               value="<?php echo htmlspecialchars($pastor['cedula']); ?>">
                        <small class="text-muted">Formato: 000-0000000-0</small>
                    </div>
                    
                    <!-- Pasaporte -->
                    <div class="mb-3" id="campo_pasaporte" style="<?php echo $pastor['tipo_documento'] === 'Cédula' ? 'display:none;' : ''; ?>">
                        <label class="form-label">
                            Pasaporte
                        </label>
                        <input type="text" name="pasaporte" class="form-control" 
                               maxlength="30" placeholder="Número de pasaporte"
                               value="<?php echo htmlspecialchars($pastor['pasaporte'] ?? ''); ?>">
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="mb-3">
                        <label class="form-label">
                            Teléfono <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="telefono" id="telefono" class="form-control" 
                               required maxlength="14" placeholder="(809) 000-0000"
                               value="<?php echo htmlspecialchars($pastor['telefono']); ?>">
                    </div>
                </div>
                
                <div class="form-grid-4">
                    <!-- Fecha de nacimiento -->
                    <div class="mb-3">
                        <label class="form-label">
                            Fecha de Nacimiento <span style="color:red;">*</span>
                        </label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" 
                               class="form-control" required
                               value="<?php echo $pastor['fecha_nacimiento']; ?>">
                    </div>
                    
                    <!-- Edad (solo lectura) -->
                    <div class="mb-3">
                        <label class="form-label">
                            Edad
                        </label>
                        <input type="text" id="edad_mostrar" class="form-control" 
                               readonly placeholder="Se calcula automáticamente"
                               style="background-color: #f5f5f5;"
                               value="<?php echo $edad; ?>">
                    </div>
                    
                    <!-- Nacionalidad -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nacionalidad <span style="color:red;">*</span>
                        </label>
                        <select name="nacionalidad_id" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                                <option value="<?php echo $nac['id']; ?>" 
                                    <?php echo ($nac['id'] == $pastor['nacionalidad_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nac['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Estado Civil -->
                    <div class="mb-3">
                        <label class="form-label">
                            Estado Civil <span style="color:red;">*</span>
                        </label>
                        <select name="estado_civil" class="form-control" required>
                            <option value="Soltero" <?php echo $pastor['estado_civil'] === 'Soltero' ? 'selected' : ''; ?>>Soltero</option>
                            <option value="Casado" <?php echo $pastor['estado_civil'] === 'Casado' ? 'selected' : ''; ?>>Casado</option>
                            <option value="Divorciado" <?php echo $pastor['estado_civil'] === 'Divorciado' ? 'selected' : ''; ?>>Divorciado</option>
                            <option value="Viudo" <?php echo $pastor['estado_civil'] === 'Viudo' ? 'selected' : ''; ?>>Viudo(a)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Conferencia y Foto -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-users"></i> Conferencia <span style="color:red;">*</span>
                        </label>
                        <select name="conferencia_id" class="form-control" required>
                            <option value="">Seleccione conferencia...</option>
                            <?php while ($conf = $conferencias->fetch_assoc()): ?>
                                <option value="<?php echo $conf['id']; ?>"
                                    <?php echo ($conf['id'] == $pastor['conferencia_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Foto -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-camera"></i> Foto
                        </label>
                        <?php if (!empty($pastor['foto'])): ?>
                            <div class="mb-2">
                                <img src="../../uploads/pastores/<?php echo htmlspecialchars($pastor['foto']); ?>" 
                                     alt="Foto actual" style="max-width: 100px; border-radius: 8px;">
                                <small class="d-block text-muted">Foto actual</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG. Máximo 2MB. Dejar vacío para mantener la foto actual.</small>
                    </div>
                </div>
            </fieldset>
            
            <!-- SECCIÓN B: DATOS MINISTERIALES -->
            <fieldset style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: bold; color: #333; padding: 0 0.5rem;">
                    <i class="fas fa-cross"></i> B. Datos Ministeriales
                </legend>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <!-- Fecha ingreso ministerio -->
                    <div class="mb-3">
                        <label class="form-label">
                            Fecha de Ingreso al Ministerio <span style="color:red;">*</span>
                        </label>
                        <input type="date" name="fecha_ingreso_ministerio" id="fecha_ingreso_ministerio" 
                               class="form-control" required
                               value="<?php echo $pastor['fecha_ingreso_ministerio']; ?>">
                    </div>
                    
                    <!-- Años en servicio (solo lectura) -->
                    <div class="mb-3">
                        <label class="form-label">
                            Años en Servicio
                        </label>
                        <input type="text" id="anos_servicio_mostrar" class="form-control" 
                               readonly placeholder="Se calcula automáticamente"
                               style="background-color: #f5f5f5;"
                               value="<?php echo $anos_servicio; ?>">
                    </div>
                    
                    <!-- Orden ministerial -->
                    <div class="mb-3">
                        <label class="form-label">
                            Ministerio Ordenado <span style="color:red;">*</span>
                        </label>
                        <select name="orden_ministerial" class="form-control" required>
                            <option value="Candidato Ministerial" <?php echo $pastor['orden_ministerial'] === 'Candidato Ministerial' ? 'selected' : ''; ?>>Candidato Ministerial</option>
                            <option value="Diácono" <?php echo $pastor['orden_ministerial'] === 'Diácono' ? 'selected' : ''; ?>>Diácono</option>
                            <option value="Presbítero" <?php echo $pastor['orden_ministerial'] === 'Presbítero' ? 'selected' : ''; ?>>Presbítero</option>
                        </select>
                    </div>
                </div>
            </fieldset>
            
            <!-- SECCIÓN C: FORMACIÓN ACADÉMICA -->
            <fieldset style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: bold; color: #333; padding: 0 0.5rem;">
                    <i class="fas fa-graduation-cap"></i> C. Formación Académica
                </legend>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <!-- Nivel de Estudio -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nivel de Estudio
                        </label>
                        <select name="nivel_estudio_id" id="nivel_estudio_id" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php while ($nivel = $niveles_estudio->fetch_assoc()): ?>
                                <option value="<?php echo $nivel['id']; ?>"
                                    <?php echo ($nivel['id'] == $pastor['nivel_estudio_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nivel['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Carrera/Profesión -->
                    <div class="mb-3" id="campo_carrera">
                        <label class="form-label">
                            Carrera / Profesión
                        </label>
                        <select name="carrera_id" id="carrera_id" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php while ($carrera = $carreras->fetch_assoc()): ?>
                                <option value="<?php echo $carrera['id']; ?>"
                                    <?php echo ($carrera['id'] == $pastor['carrera_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($carrera['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Formación Continuada -->
                <div class="mb-3">
                    <label class="form-label">
                        Formación Continuada (Especialidades, Maestrías, Doctorados, Diplomados)
                    </label>
                    <textarea name="formacion_continuada" class="form-control" rows="3"
                              placeholder="Ej: Maestría en Teología Pastoral, Diplomado en Consejería Familiar..."><?php echo htmlspecialchars($pastor['formacion_continuada'] ?? ''); ?></textarea>
                    <small class="text-muted">Describa los estudios adicionales, especialidades o posgrados que posee</small>
                </div>
            </fieldset>
            
            <!-- Botones -->
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
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

// Calcular edad
document.getElementById('fecha_nacimiento').addEventListener('change', function() {
    const fecha = new Date(this.value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fecha.getFullYear();
    const mes = hoy.getMonth() - fecha.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
        edad--;
    }
    
    document.getElementById('edad_mostrar').value = edad + ' años';
});

// Calcular años de servicio
document.getElementById('fecha_ingreso_ministerio').addEventListener('change', function() {
    const fecha = new Date(this.value);
    const hoy = new Date();
    let anos = hoy.getFullYear() - fecha.getFullYear();
    const mes = hoy.getMonth() - fecha.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
        anos--;
    }
    
    document.getElementById('anos_servicio_mostrar').value = anos + ' años';
});

// Mostrar/ocultar campos según tipo documento
document.getElementById('tipo_documento').addEventListener('change', function() {
    const esCedula = this.value === 'Cédula';
    document.getElementById('campo_cedula').style.display = esCedula ? 'block' : 'none';
    document.getElementById('campo_pasaporte').style.display = esCedula ? 'none' : 'block';
    
    document.getElementById('cedula').required = esCedula;
});

// Validar formulario
document.getElementById('formPastor').addEventListener('submit', function(e) {
    const tipoDoc = document.getElementById('tipo_documento').value;
    const cedula = document.getElementById('cedula').value;
    
    if (tipoDoc === 'Cédula' && cedula.length < 13) {
        e.preventDefault();
        alert('La cédula debe tener el formato completo: 000-0000000-0');
        document.getElementById('cedula').focus();
        return false;
    }
});
</script>

<style>
/* CSS Responsive para formulario de pastor */
.form-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.form-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.form-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
}

fieldset {
    background: #fafafa;
    border: 1px solid #ddd !important;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

fieldset legend {
    background: white;
    width: auto;
    font-weight: bold;
    color: #333;
    padding: 0 0.5rem;
    font-size: 1rem;
}

.card {
    max-width: 1000px;
}

/* Tablet */
@media (max-width: 992px) {
    .form-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Mobile */
@media (max-width: 768px) {
    .content-header > div {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .content-header h1 {
        font-size: 1.4rem;
        margin-bottom: 0.5rem;
    }
    
    .form-grid-2,
    .form-grid-3,
    .form-grid-4 {
        grid-template-columns: 1fr !important;
    }
    
    fieldset {
        padding: 1rem;
    }
    
    fieldset legend {
        font-size: 0.95rem;
    }
    
    .form-btn-group {
        flex-direction: column;
    }
    
    .form-btn-group .btn {
        width: 100%;
        justify-content: center;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .btn-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    
    /* Foto preview */
    .foto-preview img {
        max-width: 80px;
    }
}

/* Extra small screens */
@media (max-width: 480px) {
    .content-header h1 {
        font-size: 1.2rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
    
    .form-control {
        font-size: 0.95rem;
    }
    
    fieldset {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
