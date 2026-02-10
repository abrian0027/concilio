<?php
/**
 * Editar Solicitud de Membresía
 * Sistema Concilio - Panel del Pastor
 * Permite corregir datos antes de aprobar
 */

$page_title = "Editar Solicitud";
require_once __DIR__ . '/../includes/header.php';

// Solo pastor y secretaria pueden editar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para editar solicitudes.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<div class='alert alert-danger'>ID no válido</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener solicitud
$sql = "SELECT s.* FROM solicitudes_membresia s WHERE s.id = ? AND s.estado = 'pendiente'";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND s.iglesia_id = ?";
}

$stmt = $conexion->prepare($sql);
if ($ROL_NOMBRE !== 'super_admin') {
    $stmt->bind_param("ii", $id, $IGLESIA_ID);
} else {
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    echo "<div class='alert alert-danger'>Solicitud no encontrada o ya fue procesada</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener datos para los selects
$nacionalidades = $conexion->query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY id");
$niveles_estudio = $conexion->query("SELECT * FROM niveles_estudio WHERE activo = 1 ORDER BY id");
$carreras = $conexion->query("SELECT * FROM carreras WHERE activo = 1 ORDER BY nombre");

$mensaje_error = '';
$mensaje_exito = '';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8'),
        'apellido' => mb_strtoupper(trim($_POST['apellido'] ?? ''), 'UTF-8'),
        'sexo' => $_POST['sexo'] ?? '',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
        'nacionalidad_id' => !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null,
        'tipo_documento' => $_POST['tipo_documento'] ?? 'cedula',
        'numero_documento' => trim($_POST['numero_documento'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'estado_civil' => $_POST['estado_civil'] ?? 'soltero',
        'nivel_estudio_id' => !empty($_POST['nivel_estudio_id']) ? (int)$_POST['nivel_estudio_id'] : null,
        'carrera_id' => !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null,
        'es_bautizado' => isset($_POST['es_bautizado']) ? 1 : 0,
        'fecha_bautismo' => $_POST['fecha_bautismo'] ?: null,
        'iglesia_bautismo' => trim($_POST['iglesia_bautismo'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];
    
    // Validaciones
    $errores = [];
    if (empty($datos['nombre'])) $errores[] = "El nombre es obligatorio.";
    if (empty($datos['apellido'])) $errores[] = "El apellido es obligatorio.";
    if (empty($datos['sexo'])) $errores[] = "Debe seleccionar el sexo.";
    if (empty($datos['numero_documento'])) $errores[] = "El número de documento es obligatorio.";
    
    if (!empty($errores)) {
        $mensaje_error = implode("<br>", $errores);
        // Actualizar $solicitud con los datos enviados para mantener en el form
        $solicitud = array_merge($solicitud, $datos);
    } else {
        $stmt = $conexion->prepare("
            UPDATE solicitudes_membresia SET
                nombre = ?, apellido = ?, sexo = ?, fecha_nacimiento = ?,
                nacionalidad_id = ?, tipo_documento = ?, numero_documento = ?,
                telefono = ?, email = ?, direccion = ?, estado_civil = ?,
                nivel_estudio_id = ?, carrera_id = ?, es_bautizado = ?,
                fecha_bautismo = ?, iglesia_bautismo = ?, observaciones = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "ssssissssssiiiissi",
            $datos['nombre'], $datos['apellido'], $datos['sexo'], $datos['fecha_nacimiento'],
            $datos['nacionalidad_id'], $datos['tipo_documento'], $datos['numero_documento'],
            $datos['telefono'], $datos['email'], $datos['direccion'], $datos['estado_civil'],
            $datos['nivel_estudio_id'], $datos['carrera_id'], $datos['es_bautizado'],
            $datos['fecha_bautismo'], $datos['iglesia_bautismo'], $datos['observaciones'],
            $id
        );
        
        if ($stmt->execute()) {
            header('Location: ver.php?id=' . $id . '&editado=1');
            exit;
        } else {
            $mensaje_error = "Error al actualizar la solicitud.";
        }
        $stmt->close();
    }
}
?>

<style>
.form-section {
    margin-bottom: 25px;
}

.form-section h4 {
    color: var(--primary-color);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}
</style>

<div class="content-header">
    <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    <h1><i class="fas fa-edit"></i> Editar Solicitud</h1>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
        <?php endif; ?>
        
        <form method="post" id="formEditar">
            <!-- DATOS PERSONALES -->
            <div class="form-section">
                <h4><i class="fas fa-user"></i> Datos Personales</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?php echo htmlspecialchars($solicitud['nombre']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellido <span class="text-danger">*</span></label>
                        <input type="text" name="apellido" class="form-control" required
                               value="<?php echo htmlspecialchars($solicitud['apellido']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sexo <span class="text-danger">*</span></label>
                        <select name="sexo" class="form-select" required>
                            <option value="">-- Seleccione --</option>
                            <option value="M" <?php echo $solicitud['sexo'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo $solicitud['sexo'] === 'F' ? 'selected' : ''; ?>>Femenino</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha de Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control"
                               value="<?php echo $solicitud['fecha_nacimiento']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nacionalidad</label>
                        <select name="nacionalidad_id" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                                <option value="<?php echo $nac['id']; ?>" <?php echo $solicitud['nacionalidad_id'] == $nac['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nac['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Documento</label>
                        <select name="tipo_documento" id="tipo_documento" class="form-select">
                            <option value="cedula" <?php echo $solicitud['tipo_documento'] === 'cedula' ? 'selected' : ''; ?>>Cédula</option>
                            <option value="pasaporte" <?php echo $solicitud['tipo_documento'] === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Número de Documento <span class="text-danger">*</span></label>
                        <input type="text" name="numero_documento" id="numero_documento" class="form-control" required
                               value="<?php echo htmlspecialchars($solicitud['numero_documento']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="form-control"
                               value="<?php echo htmlspecialchars($solicitud['telefono']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- CONTACTO -->
            <div class="form-section">
                <h4><i class="fas fa-address-book"></i> Contacto</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($solicitud['email']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estado Civil</label>
                        <select name="estado_civil" class="form-select">
                            <option value="soltero" <?php echo $solicitud['estado_civil'] === 'soltero' ? 'selected' : ''; ?>>Soltero/a</option>
                            <option value="casado" <?php echo $solicitud['estado_civil'] === 'casado' ? 'selected' : ''; ?>>Casado/a</option>
                            <option value="union_libre" <?php echo $solicitud['estado_civil'] === 'union_libre' ? 'selected' : ''; ?>>Unión Libre</option>
                            <option value="divorciado" <?php echo $solicitud['estado_civil'] === 'divorciado' ? 'selected' : ''; ?>>Divorciado/a</option>
                            <option value="viudo" <?php echo $solicitud['estado_civil'] === 'viudo' ? 'selected' : ''; ?>>Viudo/a</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="<?php echo htmlspecialchars($solicitud['direccion']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- ESTUDIOS -->
            <div class="form-section">
                <h4><i class="fas fa-graduation-cap"></i> Nivel de Estudios</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nivel de Estudio</label>
                        <select name="nivel_estudio_id" id="nivel_estudio_id" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php while ($ne = $niveles_estudio->fetch_assoc()): ?>
                                <option value="<?php echo $ne['id']; ?>" 
                                        data-requiere-carrera="<?php echo $ne['requiere_carrera']; ?>"
                                        <?php echo $solicitud['nivel_estudio_id'] == $ne['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ne['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="div_carrera">
                        <label class="form-label">Carrera</label>
                        <select name="carrera_id" id="carrera_id" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <?php while ($car = $carreras->fetch_assoc()): ?>
                                <option value="<?php echo $car['id']; ?>" <?php echo $solicitud['carrera_id'] == $car['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($car['nombre']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- DATOS ECLESIÁSTICOS -->
            <div class="form-section">
                <h4><i class="fas fa-church"></i> Datos Eclesiásticos</h4>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="es_bautizado" id="es_bautizado" class="form-check-input"
                                   <?php echo $solicitud['es_bautizado'] ? 'checked' : ''; ?>>
                            <label for="es_bautizado" class="form-check-label">¿Es bautizado?</label>
                        </div>
                    </div>
                    <div class="col-md-6" id="div_fecha_bautismo">
                        <label class="form-label">Fecha de Bautismo</label>
                        <input type="date" name="fecha_bautismo" class="form-control"
                               value="<?php echo $solicitud['fecha_bautismo']; ?>">
                    </div>
                    <div class="col-md-6" id="div_iglesia_bautismo">
                        <label class="form-label">Iglesia donde fue bautizado</label>
                        <input type="text" name="iglesia_bautismo" class="form-control"
                               value="<?php echo htmlspecialchars($solicitud['iglesia_bautismo']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- OBSERVACIONES -->
            <div class="form-section">
                <h4><i class="fas fa-sticky-note"></i> Observaciones</h4>
                <div class="row g-3">
                    <div class="col-12">
                        <textarea name="observaciones" class="form-control" rows="3"
                                  placeholder="Notas adicionales..."><?php echo htmlspecialchars($solicitud['observaciones']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="ver.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const esBautizado = document.getElementById('es_bautizado');
    const divFechaBautismo = document.getElementById('div_fecha_bautismo');
    const divIglesiaBautismo = document.getElementById('div_iglesia_bautismo');
    
    function toggleBautismo() {
        const show = esBautizado.checked;
        divFechaBautismo.style.display = show ? 'block' : 'none';
        divIglesiaBautismo.style.display = show ? 'block' : 'none';
    }
    
    esBautizado.addEventListener('change', toggleBautismo);
    toggleBautismo();
    
    // Mayúsculas en nombre y apellido
    document.querySelector('input[name="nombre"]').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    document.querySelector('input[name="apellido"]').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
