<?php
declare(strict_types=1);

$page_title = "Categorías - Finanzas";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? $_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $iglesia_id > 0) {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre']);
        $tipo = $_POST['tipo'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        $stmt = $conexion->prepare("SELECT id FROM fin_categorias WHERE nombre = ? AND tipo = ? AND id_iglesia = ?");
        $stmt->bind_param("ssi", $nombre, $tipo, $iglesia_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $mensaje = "La categoría '$nombre' ya existe para este tipo.";
            $tipo_mensaje = 'danger';
        } else {
            $stmt = $conexion->prepare("INSERT INTO fin_categorias (id_iglesia, nombre, tipo, descripcion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $iglesia_id, $nombre, $tipo, $descripcion);
            
            if ($stmt->execute()) {
                $mensaje = "Categoría creada.";
                $tipo_mensaje = 'success';
            }
        }
        $stmt->close();
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $tipo = $_POST['tipo'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $stmt = $conexion->prepare("UPDATE fin_categorias SET nombre = ?, tipo = ?, descripcion = ?, activo = ? WHERE id = ? AND id_iglesia = ?");
        $stmt->bind_param("sssiii", $nombre, $tipo, $descripcion, $activo, $id, $iglesia_id);
        
        if ($stmt->execute()) {
            $mensaje = "Categoría actualizada.";
            $tipo_mensaje = 'success';
        }
        $stmt->close();
    }
    
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM fin_entradas WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $en_entradas = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM fin_salidas WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $en_salidas = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        if ($en_entradas > 0 || $en_salidas > 0) {
            $mensaje = "No se puede eliminar: tiene movimientos. Desactívela.";
            $tipo_mensaje = 'warning';
        } else {
            $stmt = $conexion->prepare("DELETE FROM fin_categorias WHERE id = ? AND id_iglesia = ?");
            $stmt->bind_param("ii", $id, $iglesia_id);
            $stmt->execute();
            $mensaje = "Categoría eliminada.";
            $tipo_mensaje = 'success';
            $stmt->close();
        }
    }
}

$filtro_tipo = $_GET['tipo'] ?? '';
$categorias = [];
$total_entradas = $total_salidas = 0;

if ($iglesia_id > 0) {
    $sql = "SELECT * FROM fin_categorias WHERE id_iglesia = ?";
    if ($filtro_tipo) $sql .= " AND tipo = ?";
    $sql .= " ORDER BY tipo, nombre";
    
    $stmt = $conexion->prepare($sql);
    if ($filtro_tipo) {
        $stmt->bind_param("is", $iglesia_id, $filtro_tipo);
    } else {
        $stmt->bind_param("i", $iglesia_id);
    }
    $stmt->execute();
    $categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT tipo, COUNT(*) as total FROM fin_categorias WHERE id_iglesia = ? GROUP BY tipo");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $conteos = $stmt->get_result();
    while ($cont = $conteos->fetch_assoc()) {
        if ($cont['tipo'] === 'ENTRADA') $total_entradas = (int)$cont['total'];
        if ($cont['tipo'] === 'SALIDA') $total_salidas = (int)$cont['total'];
    }
    $stmt->close();
}

$editar = null;
if (isset($_GET['editar']) && $iglesia_id > 0) {
    $id = (int)$_GET['editar'];
    $stmt = $conexion->prepare("SELECT * FROM fin_categorias WHERE id = ? AND id_iglesia = ?");
    $stmt->bind_param("ii", $id, $iglesia_id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!-- Header -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-tags text-primary me-2"></i>Categorías</h1>
        <p class="text-muted small mb-0">Clasificación de ingresos y egresos</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> 
    <?php echo $mensaje; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-light border">
        <i class="fas fa-info-circle text-primary"></i> Seleccione una iglesia desde el <a href="index.php" class="alert-link">Dashboard</a>.
    </div>
<?php else: ?>

<!-- Resumen -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Categorías de Entrada</p>
                        <h3 class="mb-0 fw-bold text-success"><?php echo $total_entradas; ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded p-3">
                        <i class="fas fa-arrow-down fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Categorías de Salida</p>
                        <h3 class="mb-0 fw-bold text-danger"><?php echo $total_salidas; ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 rounded p-3">
                        <i class="fas fa-arrow-up fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-<?php echo $editar ? 'edit' : 'plus-circle'; ?> text-primary me-2"></i><?php echo $editar ? 'Editar' : 'Nueva'; ?> Categoría
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <?php if ($editar): ?><input type="hidden" name="id" value="<?php echo $editar['id']; ?>"><?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nombre *</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" required 
                               value="<?php echo $editar ? htmlspecialchars($editar['nombre']) : ''; ?>" 
                               placeholder="Ej: Diezmo, Ofrenda, Luz, Agua">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="ENTRADA" <?php echo ($editar && $editar['tipo'] === 'ENTRADA') ? 'selected' : ''; ?>>
                                ⬇️ ENTRADA (Ingreso)
                            </option>
                            <option value="SALIDA" <?php echo ($editar && $editar['tipo'] === 'SALIDA') ? 'selected' : ''; ?>>
                                ⬆️ SALIDA (Egreso)
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Descripción</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2" 
                                  placeholder="Descripción opcional"><?php echo $editar ? htmlspecialchars($editar['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <?php if ($editar): ?>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="activo" id="activo" <?php echo $editar['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="activo">Categoría Activa</label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> <?php echo $editar ? 'Actualizar' : 'Guardar'; ?>
                        </button>
                        <?php if ($editar): ?>
                        <a href="categorias.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Listado -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-0 fw-semibold">
                            <i class="fas fa-list text-primary me-2"></i>Listado de Categorías
                        </h6>
                    </div>
                    <div class="col-md-6 mt-2 mt-md-0">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <a href="categorias.php?iglesia_id=<?php echo $iglesia_id; ?>" 
                               class="btn <?php echo !$filtro_tipo ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                                Todas
                            </a>
                            <a href="categorias.php?iglesia_id=<?php echo $iglesia_id; ?>&tipo=ENTRADA" 
                               class="btn <?php echo $filtro_tipo === 'ENTRADA' ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                Entradas
                            </a>
                            <a href="categorias.php?iglesia_id=<?php echo $iglesia_id; ?>&tipo=SALIDA" 
                               class="btn <?php echo $filtro_tipo === 'SALIDA' ? 'btn-danger' : 'btn-outline-secondary'; ?>">
                                Salidas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (count($categorias) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-3">Nombre</th>
                                <th class="border-0 text-center">Tipo</th>
                                <th class="border-0 text-center">Estado</th>
                                <th class="border-0 text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td class="ps-3">
                                    <strong><?php echo htmlspecialchars($cat['nombre']); ?></strong>
                                    <?php if ($cat['descripcion']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($cat['descripcion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($cat['tipo'] === 'ENTRADA'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <i class="fas fa-arrow-down"></i> Entrada
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                        <i class="fas fa-arrow-up"></i> Salida
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($cat['activo']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Activa</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?iglesia_id=<?php echo $iglesia_id; ?>&editar=<?php echo $cat['id']; ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta categoría?\n\nNOTA: Solo se puede eliminar si no tiene movimientos.');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-0">No hay categorías registradas</p>
                    <small class="text-muted">Cree su primera categoría usando el formulario</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>