<?php
declare(strict_types=1);

$page_title = "Cuentas - Finanzas";
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
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $saldo_inicial = (float)$_POST['saldo_inicial'];
        
        $stmt = $conexion->prepare("SELECT id FROM fin_cuentas WHERE codigo = ? AND id_iglesia = ?");
        $stmt->bind_param("si", $codigo, $iglesia_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $mensaje = "El código '$codigo' ya existe.";
            $tipo_mensaje = 'danger';
        } else {
            $stmt = $conexion->prepare("INSERT INTO fin_cuentas (id_iglesia, codigo, nombre, descripcion, saldo_inicial, saldo_actual) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdd", $iglesia_id, $codigo, $nombre, $descripcion, $saldo_inicial, $saldo_inicial);
            
            if ($stmt->execute()) {
                $mensaje = "Cuenta creada exitosamente.";
                $tipo_mensaje = 'success';
            }
        }
        $stmt->close();
    }
    
    if ($accion === 'editar') {
        $id = (int)$_POST['id'];
        $codigo = strtoupper(trim($_POST['codigo']));
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $stmt = $conexion->prepare("UPDATE fin_cuentas SET codigo = ?, nombre = ?, descripcion = ?, activo = ? WHERE id = ? AND id_iglesia = ?");
        $stmt->bind_param("sssiii", $codigo, $nombre, $descripcion, $activo, $id, $iglesia_id);
        
        if ($stmt->execute()) {
            $mensaje = "Cuenta actualizada.";
            $tipo_mensaje = 'success';
        }
        $stmt->close();
    }
    
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM fin_entradas WHERE id_cuenta = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $en_uso = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        
        if ($en_uso > 0) {
            $mensaje = "No se puede eliminar: tiene movimientos. Desactívela en su lugar.";
            $tipo_mensaje = 'warning';
        } else {
            $stmt = $conexion->prepare("DELETE FROM fin_cuentas WHERE id = ? AND id_iglesia = ?");
            $stmt->bind_param("ii", $id, $iglesia_id);
            $stmt->execute();
            $mensaje = "Cuenta eliminada.";
            $tipo_mensaje = 'success';
            $stmt->close();
        }
    }
}

$cuentas_array = [];
$total_general = 0;

if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM fin_cuentas WHERE id_iglesia = ? ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $cuentas_array = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($cuentas_array as $c) {
        $total_general += (float)$c['saldo_actual'];
    }
}

$editar = null;
if (isset($_GET['editar']) && $iglesia_id > 0) {
    $id = (int)$_GET['editar'];
    $stmt = $conexion->prepare("SELECT * FROM fin_cuentas WHERE id = ? AND id_iglesia = ?");
    $stmt->bind_param("ii", $id, $iglesia_id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!-- Header -->
<div class="row mb-3">
    <div class="col-md-8">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-wallet text-primary me-2"></i>Cuentas Bancarias</h1>
        <p class="text-muted small mb-0">Gestión de cuentas financieras</p>
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
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 rounded p-3 me-3">
                        <i class="fas fa-wallet fa-2x text-primary"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-1">Saldo Total</p>
                        <h3 class="mb-0 fw-bold">RD$ <?php echo number_format($total_general, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2">
                    <i class="fas fa-university me-1"></i> <?php echo count($cuentas_array); ?> cuenta(s) registrada(s)
                </span>
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
                    <i class="fas fa-<?php echo $editar ? 'edit' : 'plus-circle'; ?> text-primary me-2"></i><?php echo $editar ? 'Editar' : 'Nueva'; ?> Cuenta
                </h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <?php if ($editar): ?><input type="hidden" name="id" value="<?php echo $editar['id']; ?>"><?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Código *</label>
                        <input type="text" name="codigo" class="form-control form-control-sm text-uppercase" required maxlength="20" 
                               value="<?php echo $editar ? htmlspecialchars($editar['codigo']) : ''; ?>" 
                               placeholder="Ej: FC, FG">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nombre *</label>
                        <input type="text" name="nombre" class="form-control form-control-sm" required 
                               value="<?php echo $editar ? htmlspecialchars($editar['nombre']) : ''; ?>" 
                               placeholder="Nombre de la cuenta">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Descripción</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2" 
                                  placeholder="Descripción opcional"><?php echo $editar ? htmlspecialchars($editar['descripcion']) : ''; ?></textarea>
                    </div>
                    
                <?php if (!$editar): ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Saldo Inicial (RD$)</label>
                    <input type="number" name="saldo_inicial" class="form-control form-control-sm" step="0.01" min="0" value="0.00">
                </div>
                <?php else: ?>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="activo" id="activo" <?php echo $editar['activo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="activo">Cuenta Activa</label>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> <?php echo $editar ? 'Actualizar' : 'Guardar'; ?>
                    </button>
                    <?php if ($editar): ?>
                    <a href="cuentas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary btn-sm">
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
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-list text-primary me-2"></i>Listado de Cuentas
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($cuentas_array) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-3">Código</th>
                                <th class="border-0">Nombre</th>
                                <th class="border-0 text-end">Saldo Actual</th>
                                <th class="border-0 text-center">Estado</th>
                                <th class="border-0 text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cuentas_array as $c): ?>
                            <tr>
                                <td class="ps-3"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['codigo']); ?></span></td>
                                <td>
                                    <i class="fas fa-piggy-bank text-muted me-1"></i>
                                    <strong><?php echo htmlspecialchars($c['nombre']); ?></strong>
                                    <?php if ($c['descripcion']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($c['descripcion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong class="<?php echo (float)$c['saldo_actual'] >= 0 ? 'text-primary' : 'text-danger'; ?>">
                                        RD$ <?php echo number_format((float)$c['saldo_actual'], 2); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['activo']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Activa</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?iglesia_id=<?php echo $iglesia_id; ?>&editar=<?php echo $c['id']; ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta cuenta?\n\nNOTA: Solo se puede eliminar si no tiene movimientos.');">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="fw-bold border-0 ps-3">Total</td>
                                <td class="text-end fw-bold border-0 text-primary">RD$ <?php echo number_format($total_general, 2); ?></td>
                                <td colspan="2" class="border-0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-5">
                    <i class="fas fa-wallet fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-0">No hay cuentas registradas</p>
                    <small class="text-muted">Cree su primera cuenta usando el formulario</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>