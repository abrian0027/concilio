<?php
declare(strict_types=1);

$page_title = "Ver Miembro";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Verificar acceso
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener miembro con todos sus datos relacionados
$sql = "SELECT m.*, 
               i.nombre AS iglesia_nombre,
               d.nombre AS distrito_nombre,
               c.nombre AS conferencia_nombre,
               n.nombre AS nacionalidad_nombre,
               min.nombre AS ministerio_nombre,
               ne.nombre AS nivel_estudio_nombre,
               car.nombre AS carrera_nombre,
               f.codigo AS familia_codigo,
               f.apellido_familia,
               z.codigo AS zona_codigo,
               z.nombre AS zona_nombre
        FROM miembros m
        INNER JOIN iglesias i ON i.id = m.iglesia_id
        INNER JOIN distritos d ON d.id = i.distrito_id
        INNER JOIN conferencias c ON c.id = d.conferencia_id
        LEFT JOIN nacionalidades n ON n.id = m.nacionalidad_id
        LEFT JOIN ministerios min ON min.id = m.ministerio_id
        LEFT JOIN niveles_estudio ne ON ne.id = m.nivel_estudio_id
        LEFT JOIN carreras car ON car.id = m.carrera_id
        LEFT JOIN familias f ON f.id = m.familia_id
        LEFT JOIN zonas z ON z.id = m.zona_id
        WHERE m.id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    header("Location: index.php?error=Miembro no encontrado");
    exit;
}

// Verificar acceso según rol
if ($ROL_NOMBRE === 'pastor' || $ROL_NOMBRE === 'secretaria') {
    if ($miembro['iglesia_id'] != $IGLESIA_ID) {
        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para ver este miembro.</div>";
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// Calcular edad
$edad = '';
if ($miembro['fecha_nacimiento']) {
    $nacimiento = new DateTime($miembro['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $nacimiento->diff($hoy)->y . ' años';
}

// Obtener otros miembros de la misma familia
$familiares = [];
if ($miembro['familia_id']) {
    $stmt = $conexion->prepare("SELECT id, nombre, apellido, sexo, fecha_nacimiento 
                                FROM miembros 
                                WHERE familia_id = ? AND id != ? 
                                ORDER BY fecha_nacimiento");
    $stmt->bind_param("ii", $miembro['familia_id'], $id);
    $stmt->execute();
    $familiares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Puede editar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

// Estados
$estados_civiles = [
    'soltero' => 'Soltero/a',
    'casado' => 'Casado/a',
    'union_libre' => 'Unión Libre',
    'divorciado' => 'Divorciado/a',
    'viudo' => 'Viudo/a'
];

$estados_membresia = [
    'en_plena' => ['texto' => 'En Plena Comunión', 'clase' => 'bg-success'],
    'en_preparacion' => ['texto' => 'En Preparación', 'clase' => 'bg-warning'],
    'miembro_menor' => ['texto' => 'Miembro Menor', 'clase' => 'bg-secondary']
];

$estados_miembro = [
    'activo' => ['texto' => 'Activo', 'clase' => 'bg-success', 'icono' => 'check-circle'],
    'inactivo' => ['texto' => 'Inactivo', 'clase' => 'bg-secondary', 'icono' => 'times-circle'],
    'fallecido' => ['texto' => 'Fallecido', 'clase' => 'bg-dark', 'icono' => 'cross'],
    'trasladado' => ['texto' => 'Trasladado', 'clase' => 'bg-info', 'icono' => 'exchange-alt']
];
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="fas fa-user text-primary"></i> Ficha del Miembro</h1>
    <div class="d-flex gap-2">
        <?php if ($puede_editar): ?>
            <a href="editar.php?id=<?php echo $miembro['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Editar</span>
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Volver</span>
        </a>
    </div>
</div>

<!-- Contenido Principal -->
<div class="row g-4">
    
    <!-- Tarjeta de Perfil (Foto + Estado) -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <?php if ($miembro['foto'] && file_exists(__DIR__ . '/../../uploads/miembros/' . $miembro['foto'])): ?>
                        <img src="../../uploads/miembros/<?php echo htmlspecialchars($miembro['foto']); ?>" 
                             alt="Foto" class="profile-photo-large" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0dcaf0; border-radius: 18px;">
                    <?php else: ?>
                        <div class="d-inline-flex align-items-center justify-content-center" 
                             style="width: 150px; height: 150px; border: 3px solid #0dcaf0; border-radius: 18px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%);">
                            <i class="fas fa-user fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h2 class="h4 mb-1"><?php echo htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']); ?></h2>
                <p class="text-muted mb-3">
                    <i class="fas fa-church"></i> <?php echo htmlspecialchars($miembro['iglesia_nombre']); ?>
                </p>
                
                <div class="d-flex flex-column gap-2">
                    <?php 
                    $estado = $estados_miembro[$miembro['estado']] ?? null;
                    if ($estado): ?>
                        <span class="badge <?php echo $estado['clase']; ?> py-2">
                            <i class="fas fa-<?php echo $estado['icono']; ?>"></i> <?php echo $estado['texto']; ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php 
                    $membresia = $estados_membresia[$miembro['estado_miembro']] ?? null;
                    if ($membresia): ?>
                        <span class="badge <?php echo $membresia['clase']; ?> py-2">
                            <?php echo $membresia['texto']; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Datos del Miembro -->
    <div class="col-lg-8">
        
        <!-- Datos Personales -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="h6 mb-0"><i class="fas fa-user"></i> Datos Personales</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Nombre Completo</label>
                        <div><?php echo htmlspecialchars($miembro['nombre'] . ' ' . $miembro['apellido']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Sexo</label>
                        <div>
                            <i class="fas fa-<?php echo $miembro['sexo'] === 'M' ? 'mars' : 'venus'; ?>"></i>
                            <?php echo $miembro['sexo'] === 'M' ? 'Masculino' : 'Femenino'; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Fecha de Nacimiento</label>
                        <div>
                            <?php 
                            if ($miembro['fecha_nacimiento']) {
                                echo '<i class="fas fa-birthday-cake"></i> ' . date('d/m/Y', strtotime($miembro['fecha_nacimiento'])) . ' (' . $edad . ')';
                            } else {
                                echo '<span class="text-muted">No registrada</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Nacionalidad</label>
                        <div><?php echo $miembro['nacionalidad_nombre'] ?? '<span class="text-muted">No registrada</span>'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small"><?php echo $miembro['tipo_documento'] === 'cedula' ? 'Cédula' : 'Pasaporte'; ?></label>
                        <div>
                            <?php 
                            if ($miembro['numero_documento']) {
                                echo '<i class="fas fa-id-card"></i> ' . htmlspecialchars($miembro['numero_documento']);
                            } else {
                                echo '<span class="text-muted">No registrado</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Teléfono</label>
                        <div>
                            <?php 
                            if ($miembro['telefono']) {
                                echo '<i class="fas fa-phone"></i> ' . htmlspecialchars($miembro['telefono']);
                            } else {
                                echo '<span class="text-muted">No registrado</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="fw-semibold text-muted small">Dirección</label>
                        <div>
                            <?php 
                            if ($miembro['direccion']) {
                                echo '<i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($miembro['direccion']);
                            } else {
                                echo '<span class="text-muted">No registrada</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estado Civil y Familia -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="h6 mb-0"><i class="fas fa-heart"></i> Estado Civil y Familia</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="fw-semibold text-muted small">Estado Civil</label>
                        <div><?php echo $estados_civiles[$miembro['estado_civil']] ?? '--'; ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold text-muted small">Familia</label>
                        <div>
                            <?php 
                            if ($miembro['familia_codigo']) {
                                echo '<i class="fas fa-home"></i> ' . htmlspecialchars($miembro['familia_codigo']);
                                if ($miembro['apellido_familia']) {
                                    echo ' - ' . htmlspecialchars($miembro['apellido_familia']);
                                }
                            } else {
                                echo '<span class="text-muted">Sin familia asignada</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold text-muted small">Zona / Grupo</label>
                        <div>
                            <?php 
                            if ($miembro['zona_codigo']) {
                                echo '<i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($miembro['zona_codigo']);
                                echo ' - ' . htmlspecialchars($miembro['zona_nombre']);
                            } else {
                                echo '<span class="text-muted">Sin zona asignada</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($familiares)): ?>
                <div class="mt-3">
                    <label class="fw-semibold text-muted small d-block mb-2">
                        <i class="fas fa-users"></i> Otros miembros de la familia:
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($familiares as $fam): ?>
                            <a href="ver.php?id=<?php echo $fam['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-<?php echo $fam['sexo'] === 'M' ? 'male' : 'female'; ?>"></i>
                                <?php echo htmlspecialchars($fam['nombre'] . ' ' . $fam['apellido']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Nivel de Estudios -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="h6 mb-0"><i class="fas fa-graduation-cap"></i> Nivel de Estudios</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Nivel de Estudio</label>
                        <div><?php echo $miembro['nivel_estudio_nombre'] ?? '<span class="text-muted">No registrado</span>'; ?></div>
                    </div>
                    <?php if ($miembro['carrera_nombre']): ?>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Carrera</label>
                        <div><?php echo htmlspecialchars($miembro['carrera_nombre']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Datos Eclesiásticos -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="h6 mb-0"><i class="fas fa-church"></i> Datos Eclesiásticos</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="fw-semibold text-muted small">Iglesia</label>
                        <div>
                            <i class="fas fa-church"></i> <?php echo htmlspecialchars($miembro['iglesia_nombre']); ?>
                            <small class="text-muted d-block">
                                <?php echo htmlspecialchars($miembro['distrito_nombre'] . ' • ' . $miembro['conferencia_nombre']); ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Ministerio</label>
                        <div>
                            <?php 
                            if ($miembro['ministerio_nombre']) {
                                echo '<span class="badge bg-info">' . htmlspecialchars($miembro['ministerio_nombre']) . '</span>';
                            } else {
                                echo '<span class="text-muted">No asignado</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Bautizado</label>
                        <div>
                            <?php 
                            if ($miembro['es_bautizado']) {
                                echo '<span class="badge bg-success"><i class="fas fa-check"></i> Sí</span>';
                                if ($miembro['fecha_bautismo']) {
                                    echo ' <small>' . date('d/m/Y', strtotime($miembro['fecha_bautismo'])) . '</small>';
                                }
                            } else {
                                echo '<span class="badge bg-secondary">No</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold text-muted small">Líder</label>
                        <div>
                            <?php 
                            if ($miembro['es_lider']) {
                                echo '<span class="badge bg-primary"><i class="fas fa-star"></i> Sí</span>';
                            } else {
                                echo '<span class="badge bg-secondary">No</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info de Registro -->
        <div class="card">
            <div class="card-body bg-light">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> Registrado: <?php echo date('d/m/Y H:i', strtotime($miembro['creado_en'])); ?>
                    <?php if (isset($miembro['actualizado_en']) && $miembro['actualizado_en'] !== $miembro['creado_en']): ?>
                        | Actualizado: <?php echo date('d/m/Y H:i', strtotime($miembro['actualizado_en'])); ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
