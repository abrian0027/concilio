<?php
declare(strict_types=1);

$page_title = "Nuevo Pastor";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin y Obispo
if (!in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener catálogos
$nacionalidades = $conexion->query("SELECT id, nombre FROM nacionalidades WHERE activo = 1 ORDER BY nombre");
$conferencias = $conexion->query("SELECT id, codigo, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
$niveles_estudio = $conexion->query("SELECT id, nombre FROM niveles_estudio WHERE activo = 1 ORDER BY id");
$carreras = $conexion->query("SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Nuevo Pastor</h1>
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

        <form method="post" action="guardar.php" enctype="multipart/form-data" id="formPastor">
            
            <!-- SECCIÓN A: DATOS PERSONALES -->
            <fieldset style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <legend style="font-weight: bold; color: #333; padding: 0 0.5rem;">
                    <i class="fas fa-user"></i> A. Datos Personales
                </legend>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label">
                            Nombre <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="nombre" class="form-control" 
                               required maxlength="100" placeholder="Nombre(s)">
                    </div>
                    
                    <!-- Apellido -->
                    <div class="mb-3">
                        <label class="form-label">
                            Apellido <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="apellido" class="form-control" 
                               required maxlength="100" placeholder="Apellido(s)">
                    </div>
                    
                    <!-- Sexo -->
                    <div class="mb-3">
                        <label class="form-label">
                            Sexo <span style="color:red;">*</span>
                        </label>
                        <select name="sexo" class="form-control" required>
                            <option value="M" selected>Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <!-- Tipo de documento -->
                    <div class="mb-3">
                        <label class="form-label">
                            Tipo de Documento <span style="color:red;">*</span>
                        </label>
                        <select name="tipo_documento" id="tipo_documento" class="form-control" required>
                            <option value="Cédula" selected>Cédula</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    
                    <!-- Cédula -->
                    <div class="mb-3" id="campo_cedula">
                        <label class="form-label">
                            Cédula <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="cedula" id="cedula" class="form-control" 
                               maxlength="13" placeholder="000-0000000-0"
                               pattern="\d{3}-\d{7}-\d{1}">
                        <small class="text-muted">Formato: 000-0000000-0 (será el identificador de login)</small>
                    </div>
                    
                    <!-- Pasaporte -->
                    <div class="mb-3" id="campo_pasaporte" style="display: none;">
                        <label class="form-label">
                            Pasaporte
                        </label>
                        <input type="text" name="pasaporte" class="form-control" 
                               maxlength="30" placeholder="Número de pasaporte">
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="mb-3">
                        <label class="form-label">
                            Teléfono <span style="color:red;">*</span>
                        </label>
                        <input type="text" name="telefono" id="telefono" class="form-control" 
                               required maxlength="14" placeholder="(809) 000-0000">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <!-- Fecha de nacimiento -->
                    <div class="mb-3">
                        <label class="form-label">
                            Fecha de Nacimiento <span style="color:red;">*</span>
                        </label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" 
                               class="form-control" required>
                    </div>
                    
                    <!-- Edad (solo lectura) -->
                    <div class="mb-3">
                        <label class="form-label">
                            Edad
                        </label>
                        <input type="text" id="edad_mostrar" class="form-control" 
                               readonly placeholder="Se calcula automáticamente"
                               style="background-color: #f5f5f5;">
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
                                    <?php echo ($nac['nombre'] == 'Dominicana') ? 'selected' : ''; ?>>
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
                            <option value="Soltero">Soltero</option>
                            <option value="Casado" selected>Casado</option>
                            <option value="Divorciado">Divorciado</option>
                            <option value="Viudo">Viudo(a)</option>
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
                                <option value="<?php echo $conf['id']; ?>">
                                    <?php echo htmlspecialchars($conf['codigo'] . ' - ' . $conf['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Foto -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-camera"></i> Foto (opcional)
                        </label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <small class="text-muted">JPG, PNG. Máximo 2MB</small>
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
                               class="form-control" required>
                    </div>
                    
                    <!-- Años en servicio (solo lectura) -->
                    <div class="mb-3">
                        <label class="form-label">
                            Años en Servicio
                        </label>
                        <input type="text" id="anos_servicio_mostrar" class="form-control" 
                               readonly placeholder="Se calcula automáticamente"
                               style="background-color: #f5f5f5;">
                    </div>
                    
                    <!-- Orden ministerial -->
                    <div class="mb-3">
                        <label class="form-label">
                            Ministerio Ordenado <span style="color:red;">*</span>
                        </label>
                        <select name="orden_ministerial" class="form-control" required>
                            <option value="Candidato Ministerial">Candidato Ministerial</option>
                            <option value="Diácono">Diácono</option>
                            <option value="Presbítero">Presbítero</option>
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
                                <option value="<?php echo $nivel['id']; ?>">
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
                                <option value="<?php echo $carrera['id']; ?>">
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
                              placeholder="Ej: Maestría en Teología Pastoral, Diplomado en Consejería Familiar, Doctorado en Ministerio..."></textarea>
                    <small class="text-muted">Describa los estudios adicionales, especialidades o posgrados que posee</small>
                </div>
            </fieldset>
            
            <!-- Botones -->
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Guardar Pastor
                </button>
                <a href="index.php" class="btn btn-secondary btn-lg">
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
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(3"],
    div[style*="grid-template-columns: repeat(4"],
    div[style*="grid-template-columns: repeat(2"],
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

fieldset {
    background: #fafafa;
}

fieldset legend {
    background: white;
    width: auto;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
