<?php
declare(strict_types=1);

/**
 * Transferencias - Finanzas
 * CORREGIDO: Patrón PRG (Post-Redirect-Get) para evitar duplicación de registros
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración primero para tener $conexion disponible
require_once __DIR__ . '/../../config/config.php';

// Determinar iglesia_id ANTES del procesamiento POST
$ROL_NOMBRE_TEMP = $_SESSION['rol_nombre'] ?? '';
$IGLESIA_ID_TEMP = $_SESSION['iglesia_id'] ?? 0;

if ($ROL_NOMBRE_TEMP === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? $_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)$IGLESIA_ID_TEMP;
}

$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Función para verificar mes cerrado
function esMesCerradoTransf($conexion, $id_iglesia, $fecha) {
    $mes = (int)date('n', strtotime($fecha));
    $ano = (int)date('Y', strtotime($fecha));
    $stmt = $conexion->prepare("SELECT cerrado FROM fin_cierres WHERE id_iglesia = ? AND mes = ? AND ano = ? AND cerrado = 1");
    $stmt->bind_param("iii", $id_iglesia, $mes, $ano);
    $stmt->execute();
    $result = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $result;
}

// ============================================
// PROCESAR FORMULARIO POST (antes de cualquier output)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $iglesia_id > 0) {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'transferir') {
        $fecha = $_POST['fecha'];
        $id_cuenta_origen = (int)$_POST['id_cuenta_origen'];
        $id_cuenta_destino = (int)$_POST['id_cuenta_destino'];
        $monto = (float)$_POST['monto'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        if ($id_cuenta_origen === $id_cuenta_destino) {
            $_SESSION['mensaje_finanzas'] = "La cuenta origen y destino deben ser diferentes.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else if (esMesCerradoTransf($conexion, $iglesia_id, $fecha)) {
            $_SESSION['mensaje_finanzas'] = "No se puede registrar: el mes está cerrado.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else {
            // Verificar saldo suficiente
            $stmt = $conexion->prepare("SELECT saldo_actual FROM fin_cuentas WHERE id = ? AND id_iglesia = ?");
            $stmt->bind_param("ii", $id_cuenta_origen, $iglesia_id);
            $stmt->execute();
            $cuenta_origen = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ((float)$cuenta_origen['saldo_actual'] < $monto) {
                $_SESSION['mensaje_finanzas'] = "Saldo insuficiente en la cuenta origen.";
                $_SESSION['tipo_mensaje_finanzas'] = 'danger';
            } else {
                // Registrar transferencia
                $stmt = $conexion->prepare("INSERT INTO fin_transferencias (id_iglesia, id_cuenta_origen, id_cuenta_destino, fecha, monto, descripcion, id_usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisdsi", $iglesia_id, $id_cuenta_origen, $id_cuenta_destino, $fecha, $monto, $descripcion, $usuario_id);
                
                if ($stmt->execute()) {
                    // Actualizar saldos
                    $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual - $monto WHERE id = $id_cuenta_origen");
                    $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual + $monto WHERE id = $id_cuenta_destino");
                    $_SESSION['mensaje_finanzas'] = "Transferencia realizada exitosamente.";
                    $_SESSION['tipo_mensaje_finanzas'] = 'success';
                } else {
                    $_SESSION['mensaje_finanzas'] = "Error al registrar la transferencia: " . $conexion->error;
                    $_SESSION['tipo_mensaje_finanzas'] = 'danger';
                }
                $stmt->close();
            }
        }
        
        // REDIRECT para evitar reenvío del formulario (Patrón PRG)
        $redirect_url = "transferencias.php";
        if ($ROL_NOMBRE_TEMP === 'super_admin' && $iglesia_id > 0) {
            $redirect_url .= "?iglesia_id=" . $iglesia_id;
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

// ============================================
// AHORA SÍ CARGAR EL HEADER (después del procesamiento POST)
// ============================================
$page_title = "Transferencias - Finanzas";
require_once __DIR__ . '/../includes/header.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Recuperar mensaje de sesión si existe
$mensaje = '';
$tipo_mensaje = '';
if (isset($_SESSION['mensaje_finanzas'])) {
    $mensaje = $_SESSION['mensaje_finanzas'];
    $tipo_mensaje = $_SESSION['tipo_mensaje_finanzas'] ?? 'info';
    unset($_SESSION['mensaje_finanzas']);
    unset($_SESSION['tipo_mensaje_finanzas']);
}

// Obtener datos
$cuentas = [];
$transferencias = [];
$filtro_fecha_desde = date('Y-m-01');
$filtro_fecha_hasta = date('Y-m-d');

if ($iglesia_id > 0) {
    // Cuentas activas
    $stmt = $conexion->prepare("SELECT id, nombre, saldo_actual FROM fin_cuentas WHERE id_iglesia = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $cuentas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $filtro_fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $filtro_fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    
    // Historial de transferencias
    $stmt = $conexion->prepare("SELECT t.*, co.nombre as cuenta_origen, cd.nombre as cuenta_destino
            FROM fin_transferencias t
            LEFT JOIN fin_cuentas co ON t.id_cuenta_origen = co.id
            LEFT JOIN fin_cuentas cd ON t.id_cuenta_destino = cd.id
            WHERE t.id_iglesia = ? AND t.fecha BETWEEN ? AND ?
            ORDER BY t.fecha DESC, t.id DESC");
    $stmt->bind_param("iss", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta);
    $stmt->execute();
    $transferencias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Para super_admin: obtener lista de iglesias
$iglesias = [];
if ($ROL_NOMBRE === 'super_admin' && $iglesia_id === 0) {
    $result = $conexion->query("SELECT id, nombre FROM iglesias WHERE activo = 1 ORDER BY nombre");
    if ($result) {
        $iglesias = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="fas fa-exchange-alt text-primary me-2"></i>Transferencias</h4>
        <p class="text-muted mb-0 small">Movimientos entre cuentas</p>
    </div>
    <a href="index.php<?php echo $iglesia_id > 0 ? '?iglesia_id='.$iglesia_id : ''; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver
    </a>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
    <?php echo htmlspecialchars($mensaje); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($ROL_NOMBRE === 'super_admin' && $iglesia_id === 0): ?>
<!-- Selector de iglesia para super_admin -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-church me-2"></i>Seleccione una Iglesia</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Iglesia</label>
                <select name="iglesia_id" class="form-select" required>
                    <option value="">-- Seleccionar Iglesia --</option>
                    <?php foreach ($iglesias as $ig): ?>
                        <option value="<?php echo $ig['id']; ?>"><?php echo htmlspecialchars($ig['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-arrow-right me-1"></i> Ir
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif (count($cuentas) < 2): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i> 
    Necesita al menos 2 cuentas para realizar transferencias. 
    <a href="cuentas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Ir a Cuentas</a>
</div>

<?php else: ?>

<div class="row g-3">
    <!-- Formulario -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Nueva Transferencia</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="formTransferencia">
                    <input type="hidden" name="accion" value="transferir">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cuenta Origen *</label>
                        <select name="id_cuenta_origen" id="cuentaOrigen" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($cuentas as $cta): ?>
                            <option value="<?php echo $cta['id']; ?>" data-saldo="<?php echo $cta['saldo_actual']; ?>">
                                <?php echo htmlspecialchars($cta['nombre']); ?> (RD$ <?php echo number_format((float)$cta['saldo_actual'], 2); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" id="saldoDisponible"></small>
                    </div>
                    
                    <div class="text-center mb-3">
                        <i class="fas fa-arrow-down fa-2x text-primary"></i>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cuenta Destino *</label>
                        <select name="id_cuenta_destino" id="cuentaDestino" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($cuentas as $cta): ?>
                            <option value="<?php echo $cta['id']; ?>"><?php echo htmlspecialchars($cta['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Monto (RD$) *</label>
                        <input type="number" name="monto" id="montoTransf" class="form-control" required step="0.01" min="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Descripción / Motivo</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Motivo de la transferencia..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-exchange-alt me-2"></i>Realizar Transferencia
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Historial -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white py-2">
                <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Transferencias</h6>
            </div>
            <div class="card-body p-0">
                <!-- Filtros -->
                <div class="p-3 bg-light border-bottom">
                    <form method="GET" class="row g-2 align-items-end">
                        <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                        <div class="col-5">
                            <label class="form-label small mb-1">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo $filtro_fecha_desde; ?>">
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo $filtro_fecha_hasta; ?>">
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <?php if (count($transferencias) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-3">Fecha</th>
                                <th class="border-0">Origen</th>
                                <th class="border-0 text-center"><i class="fas fa-arrow-right"></i></th>
                                <th class="border-0">Destino</th>
                                <th class="border-0 text-end">Monto</th>
                                <th class="border-0 pe-3">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transferencias as $t): ?>
                            <tr>
                                <td class="ps-3">
                                    <small><?php echo date('d/m/Y', strtotime($t['fecha'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                        <?php echo htmlspecialchars($t['cuenta_origen'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <?php echo htmlspecialchars($t['cuenta_destino'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-primary">RD$ <?php echo number_format((float)$t['monto'], 2); ?></strong>
                                </td>
                                <td class="pe-3">
                                    <small class="text-muted"><?php echo htmlspecialchars($t['descripcion'] ?: '-'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-0">Sin transferencias en este período</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar saldo disponible al seleccionar cuenta origen
document.getElementById('cuentaOrigen')?.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const saldo = option.getAttribute('data-saldo');
    const saldoEl = document.getElementById('saldoDisponible');
    
    if (saldo && saldoEl) {
        saldoEl.textContent = 'Saldo disponible: RD$ ' + parseFloat(saldo).toLocaleString('es-DO', {minimumFractionDigits: 2});
    } else if (saldoEl) {
        saldoEl.textContent = '';
    }
});

// Validar que origen y destino sean diferentes
document.getElementById('formTransferencia')?.addEventListener('submit', function(e) {
    const origen = document.getElementById('cuentaOrigen').value;
    const destino = document.getElementById('cuentaDestino').value;
    
    if (origen === destino && origen !== '') {
        e.preventDefault();
        alert('La cuenta origen y destino deben ser diferentes.');
        return false;
    }
    
    // Prevenir doble envío
    const btn = this.querySelector('button[type="submit"]');
    if (btn.disabled) {
        e.preventDefault();
        return false;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
