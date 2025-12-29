<?php
/**
 * Editar Presentación de Niño
 * Sistema Concilio - Módulo Registros
 */

$page_title = "Editar Presentación";
require_once __DIR__ . '/../../includes/header.php';

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
if (!in_array($ROL_NOMBRE, $roles_permitidos)) {
    echo '<div class="alert alert-danger">No tiene permisos para acceder a esta sección. Solo Pastor o Secretaria.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($id <= 0) {
    echo '<div class="alert alert-warning">ID no válido.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if ($iglesia_id <= 0) {
    echo '<div class="alert alert-warning">No tiene una iglesia asignada.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Obtener registro - Solo de la iglesia del usuario
$stmt = $conexion->prepare("SELECT * FROM presentacion_ninos WHERE id = ? AND iglesia_id = ?");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    echo '<div class="alert alert-warning">Registro no encontrado.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Meses en español
$meses = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="fas fa-edit text-warning me-2"></i>Editar Presentación</h4>
        <p class="text-muted mb-0 small">Acta No. <?php echo htmlspecialchars($registro['numero_acta']); ?></p>
    </div>
    <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<form action="actualizar.php" method="POST" id="formPresentacion">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="numero_acta" value="<?php echo htmlspecialchars($registro['numero_acta']); ?>">
    
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
                                   value="<?php echo htmlspecialchars($registro['nombres']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['apellidos']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" class="form-control" required 
                                   value="<?php echo $registro['fecha_nacimiento']; ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sexo</label>
                            <select name="sexo" class="form-select">
                                <option value="M" <?php echo $registro['sexo'] == 'M' ? 'selected' : ''; ?>>Masculino (Niño)</option>
                                <option value="F" <?php echo $registro['sexo'] == 'F' ? 'selected' : ''; ?>>Femenino (Niña)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <input type="text" name="nacionalidad" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['nacionalidad']); ?>">
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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del Padre</label>
                            <input type="text" name="nombre_padre" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['nombre_padre']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la Madre</label>
                            <input type="text" name="nombre_madre" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['nombre_madre']); ?>">
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
                                   value="<?php echo $registro['fecha_presentacion']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lugar</label>
                            <input type="text" name="lugar" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['lugar']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ministro Oficiante</label>
                            <input type="text" name="ministro" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['ministro']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Testigo 1</label>
                            <input type="text" name="testigo1" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['testigo1']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Testigo 2</label>
                            <input type="text" name="testigo2" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['testigo2']); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Datos de Registro Civil -->
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
                            <input type="text" name="libro_no" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['libro_no']); ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Folio</label>
                            <input type="text" name="folio" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['folio']); ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Acta No.</label>
                            <input type="text" name="acta_civil_no" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['acta_civil_no']); ?>">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Año del Acta</label>
                            <input type="number" name="acta_civil_anio" class="form-control" 
                                   min="1900" max="<?php echo date('Y'); ?>"
                                   value="<?php echo htmlspecialchars($registro['acta_civil_anio']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Oficialía Civil / Registrado en</label>
                            <input type="text" name="oficilia_civil" class="form-control" 
                                   value="<?php echo htmlspecialchars($registro['oficilia_civil']); ?>">
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
                    <textarea name="observaciones" class="form-control" rows="3"><?php echo htmlspecialchars($registro['observaciones']); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Columna Lateral -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-warning">
                    <h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Información del Acta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Número de Acta</label>
                        <h4 class="text-primary mb-0"><?php echo htmlspecialchars($registro['numero_acta']); ?></h4>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Estado</label>
                        <p class="mb-0">
                            <?php if ($registro['estado'] == 'activo'): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Anulado</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Fecha de Registro</label>
                        <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($registro['creado_en'])); ?></p>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                        <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                    </div>
                    
                    <?php if ($registro['estado'] == 'activo'): ?>
                    <hr>
                    <a href="eliminar.php?id=<?php echo $id; ?>" class="btn btn-outline-danger w-100"
                       onclick="return confirm('¿Está seguro de anular esta acta?');">
                        <i class="fas fa-ban me-1"></i>Anular Acta
                    </a>
                    <?php endif; ?>
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
    
    if (new Date(fechaPres) < new Date(fechaNac)) {
        e.preventDefault();
        alert('La fecha de presentación no puede ser anterior a la fecha de nacimiento');
        return;
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
