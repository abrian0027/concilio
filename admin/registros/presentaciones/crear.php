<?php
/**
 * Crear Nueva Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Solo accesible para la iglesia local
 */

$page_title = "Nueva Presentación";
require_once __DIR__ . '/../../includes/header.php';

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
if (!in_array($ROL_NOMBRE, $roles_permitidos)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección. Solo Pastor o Secretaria.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($iglesia_id <= 0) {
    echo '<div class="alert alert-warning">No tiene una iglesia asignada.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener datos de la iglesia para el lugar por defecto
$stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();
$lugar_defecto = $iglesia['nombre'] ?? '';

// Generar próximo número de acta
$anio_actual = date('Y');
$sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_acta, '-', 1) AS UNSIGNED)) as ultimo 
        FROM presentacion_ninos 
        WHERE iglesia_id = ? AND numero_acta LIKE ?";
$stmt = $conexion->prepare($sql);
$like_anio = "%-{$anio_actual}";
$stmt->bind_param("is", $iglesia_id, $like_anio);
$stmt->execute();
$resultado = $stmt->get_result()->fetch_assoc();
$stmt->close();

$siguiente_numero = ($resultado['ultimo'] ?? 0) + 1;
$numero_acta = str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT) . '-' . $anio_actual;

// Meses en español
$meses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-baby text-primary me-2"></i>Nueva Presentación</h4>
        <p class="text-muted mb-0 small">Registrar acta de presentación de niño al Señor</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<form action="guardar.php" method="POST" id="formPresentacion">
    <input type="hidden" name="numero_acta" value="<?php echo htmlspecialchars($numero_acta); ?>">
    
    <div class="row">
        <!-- Columna Principal -->
        <div class="col-lg-8">
            <!-- Datos del Niño -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-child me-2"></i>Datos del Niño/a</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombres" class="form-control" required 
                                   placeholder="Nombres del niño/a">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" 
                                   placeholder="Apellidos del niño/a">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" class="form-control" required 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select name="sexo" class="form-select">
                                <option value="M">Masculino (Niño)</option>
                                <option value="F">Femenino (Niña)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" 
                                   value="Dominicana" placeholder="Nacionalidad">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Datos de los Padres -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Padres / Tutores</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Si deja vacío algún campo, en el acta impresa aparecerá una línea para firma.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Padre</label>
                            <input type="text" name="nombre_padre" class="form-control" 
                                   placeholder="Nombre completo del padre">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la Madre</label>
                            <input type="text" name="nombre_madre" class="form-control" 
                                   placeholder="Nombre completo de la madre">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Datos del Acto -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-church me-2"></i>Datos del Acto de Presentación</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Presentación <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_presentacion" class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lugar</label>
                            <input type="text" name="lugar" class="form-control" 
                                   value="<?php echo htmlspecialchars($lugar_defecto); ?>" 
                                   placeholder="Lugar de la ceremonia">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ministro Oficiante</label>
                            <input type="text" name="ministro" class="form-control" 
                                   placeholder="Nombre del ministro que ofició la ceremonia">
                            <small class="text-muted">Deje vacío si firmará en el documento impreso</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Testigo 1</label>
                            <input type="text" name="testigo1" class="form-control" 
                                   placeholder="Nombre del primer testigo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Testigo 2</label>
                            <input type="text" name="testigo2" class="form-control" 
                                   placeholder="Nombre del segundo testigo">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Datos de Registro Civil (Opcionales) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Datos de Registro Civil 
                        <span class="badge bg-light text-dark ms-2">Opcional</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label">Libro No.</label>
                            <input type="text" name="libro_no" class="form-control" placeholder="Ej: 5">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Folio</label>
                            <input type="text" name="folio" class="form-control" placeholder="Ej: 123">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Acta No.</label>
                            <input type="text" name="acta_civil_no" class="form-control" placeholder="Ej: 456">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Año del Acta</label>
                            <input type="number" name="acta_civil_anio" class="form-control" 
                                   min="1900" max="<?php echo date('Y'); ?>" placeholder="Ej: 2024">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Oficialía Civil / Registrado en</label>
                            <input type="text" name="oficilia_civil" class="form-control" 
                                   placeholder="Ej: Oficialía Civil de Santo Domingo Este">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Observaciones -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-comment-alt me-2"></i>Observaciones</h6>
                </div>
                <div class="card-body">
                    <textarea name="observaciones" class="form-control" rows="3" 
                              placeholder="Observaciones adicionales (opcional)"></textarea>
                </div>
            </div>
        </div>
        
        <!-- Columna Lateral -->
        <div class="col-lg-4">
            <!-- Resumen -->
            <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Resumen del Acta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Número de Acta</label>
                        <h4 class="text-primary mb-0"><?php echo htmlspecialchars($numero_acta); ?></h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Fecha de Registro</label>
                        <p class="mb-0"><?php echo date('d \d\e F \d\e Y'); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Registrado por</label>
                        <p class="mb-0"><?php echo htmlspecialchars($USUARIO_NOMBRE); ?></p>
                    </div>
                    <hr>
                    <p class="text-muted small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Los campos marcados con <span class="text-danger">*</span> son obligatorios.
                    </p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Presentación
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-eraser me-1"></i>Limpiar Formulario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('formPresentacion').addEventListener('submit', function(e) {
    const nombres = document.querySelector('[name="nombres"]').value.trim();
    const fechaNac = document.querySelector('[name="fecha_nacimiento"]').value;
    const fechaPres = document.querySelector('[name="fecha_presentacion"]').value;
    
    if (!nombres) {
        e.preventDefault();
        alert('El nombre del niño/a es obligatorio');
        return;
    }
    
    if (!fechaNac) {
        e.preventDefault();
        alert('La fecha de nacimiento es obligatoria');
        return;
    }
    
    if (!fechaPres) {
        e.preventDefault();
        alert('La fecha de presentación es obligatoria');
        return;
    }
    
    // Validar que fecha de presentación sea posterior a fecha de nacimiento
    if (new Date(fechaPres) < new Date(fechaNac)) {
        e.preventDefault();
        alert('La fecha de presentación no puede ser anterior a la fecha de nacimiento');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
