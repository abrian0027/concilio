<?php
/**
 * Editar Visita - Sistema Concilio
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Editar Visita";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden editar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener visita
$sql = "SELECT v.* FROM visitas v WHERE v.id = ?";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND v.iglesia_id = " . (int)$IGLESIA_ID;
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$visita = $result->fetch_assoc();

if (!$visita) {
    header("Location: index.php?error=Visita no encontrada");
    exit;
}

// No permitir editar si ya fue convertida
if ($visita['convertido_miembro']) {
    header("Location: index.php?error=Esta visita ya fue convertida a miembro y no puede editarse");
    exit;
}

// Obtener nacionalidades
$nacionalidades = $conexion->query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY id");

// Obtener miembros de la iglesia para "invitado por"
$miembros = $conexion->query("SELECT id, nombre, apellido FROM miembros 
                              WHERE estado = 'activo' AND iglesia_id = " . (int)$visita['iglesia_id'] . "
                              ORDER BY nombre, apellido");

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
        <h1 class="mb-0"><i class="fas fa-edit text-primary"></i> Editar Visita</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-user-edit"></i> Datos de la Visita</span>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="post" action="actualizar.php" id="formVisita">
            <input type="hidden" name="id" value="<?php echo $visita['id']; ?>">
            
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
                           required maxlength="100" 
                           value="<?php echo htmlspecialchars($visita['nombre']); ?>"
                           style="text-transform: uppercase;">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Apellido <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="apellido" id="apellido" class="form-control" 
                           required maxlength="100" 
                           value="<?php echo htmlspecialchars($visita['apellido']); ?>"
                           style="text-transform: uppercase;">
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-venus-mars"></i> Sexo <span class="text-danger">*</span>
                    </label>
                    <select name="sexo" id="sexo" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="M" <?php echo $visita['sexo'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $visita['sexo'] === 'F' ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label">
                        <i class="fas fa-flag"></i> Nacionalidad
                    </label>
                    <select name="nacionalidad_id" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                        <option value="<?php echo $nac['id']; ?>" <?php echo $visita['nacionalidad_id'] == $nac['id'] ? 'selected' : ''; ?>>
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
                           maxlength="20" value="<?php echo htmlspecialchars($visita['telefono']); ?>">
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
                        <option value="<?php echo $key; ?>" <?php echo $visita['categoria'] === $key ? 'selected' : ''; ?>>
                            <?php echo $texto; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i> Fecha de Visita <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="fecha_visita" class="form-control" 
                           required value="<?php echo $visita['fecha_visita']; ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-user-friends"></i> Invitado por (Miembro)
                    </label>
                    <select name="invitado_por" class="form-select">
                        <option value="">-- Sin registro / No aplica --</option>
                        <?php while ($m = $miembros->fetch_assoc()): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $visita['invitado_por'] == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['nombre'] . ' ' . $m['apellido']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">
                        <i class="fas fa-toggle-on"></i> Estado
                    </label>
                    <select name="estado" class="form-select">
                        <option value="activo" <?php echo $visita['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $visita['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        <i class="fas fa-sticky-note"></i> Observaciones
                    </label>
                    <textarea name="observaciones" class="form-control" rows="2" 
                              maxlength="500"><?php echo htmlspecialchars($visita['observaciones']); ?></textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
