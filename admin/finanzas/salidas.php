<?php
declare(strict_types=1);

/**
 * Salidas - Finanzas
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
function esMesCerradoSalidas($conexion, $id_iglesia, $fecha) {
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
    
    // CREAR nueva salida
    if ($accion === 'crear') {
        $fecha = $_POST['fecha'];
        $id_categoria = (int)$_POST['id_categoria'];
        $id_cuenta = (int)$_POST['id_cuenta'];
        $monto = (float)$_POST['monto'];
        $beneficiario = trim($_POST['beneficiario']);
        $numero_documento = trim($_POST['numero_documento'] ?? '');
        $forma_pago = $_POST['forma_pago'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        
        // Validar saldo disponible en la cuenta
        $stmt_saldo = $conexion->prepare("SELECT saldo_actual, nombre FROM fin_cuentas WHERE id = ? AND id_iglesia = ?");
        $stmt_saldo->bind_param("ii", $id_cuenta, $iglesia_id);
        $stmt_saldo->execute();
        $cuenta_info = $stmt_saldo->get_result()->fetch_assoc();
        $stmt_saldo->close();
        
        $saldo_disponible = (float)($cuenta_info['saldo_actual'] ?? 0);
        
        if ($monto > $saldo_disponible) {
            $_SESSION['mensaje_finanzas'] = "Fondos insuficientes. El saldo disponible en " . htmlspecialchars($cuenta_info['nombre']) . " es RD$ " . number_format($saldo_disponible, 2) . " y está intentando retirar RD$ " . number_format($monto, 2);
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } elseif (esMesCerradoSalidas($conexion, $iglesia_id, $fecha)) {
            $_SESSION['mensaje_finanzas'] = "No se puede registrar: el mes está cerrado.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else {
            $stmt = $conexion->prepare("INSERT INTO fin_salidas (id_iglesia, id_categoria, id_cuenta, fecha, monto, beneficiario, numero_documento, forma_pago, descripcion, id_usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisdssssi", $iglesia_id, $id_categoria, $id_cuenta, $fecha, $monto, $beneficiario, $numero_documento, $forma_pago, $descripcion, $usuario_id);
            
            if ($stmt->execute()) {
                $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual - $monto WHERE id = $id_cuenta");
                $_SESSION['mensaje_finanzas'] = "Salida registrada exitosamente.";
                $_SESSION['tipo_mensaje_finanzas'] = 'success';
            } else {
                $_SESSION['mensaje_finanzas'] = "Error al registrar la salida: " . $conexion->error;
                $_SESSION['tipo_mensaje_finanzas'] = 'danger';
            }
            $stmt->close();
        }
        
        // REDIRECT para evitar reenvío del formulario (Patrón PRG)
        $redirect_url = "salidas.php";
        if ($ROL_NOMBRE_TEMP === 'super_admin' && $iglesia_id > 0) {
            $redirect_url .= "?iglesia_id=" . $iglesia_id;
        }
        header("Location: " . $redirect_url);
        exit;
    }
    
    // ELIMINAR salida
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        $stmt = $conexion->prepare("SELECT fecha, monto, id_cuenta FROM fin_salidas WHERE id = ? AND id_iglesia = ?");
        $stmt->bind_param("ii", $id, $iglesia_id);
        $stmt->execute();
        $salida = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($salida && esMesCerradoSalidas($conexion, $iglesia_id, $salida['fecha'])) {
            $_SESSION['mensaje_finanzas'] = "No se puede eliminar: el mes está cerrado.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else if ($salida) {
            $stmt = $conexion->prepare("DELETE FROM fin_salidas WHERE id = ? AND id_iglesia = ?");
            $stmt->bind_param("ii", $id, $iglesia_id);
            
            if ($stmt->execute()) {
                $monto_devolver = (float)$salida['monto'];
                $cuenta_id = (int)$salida['id_cuenta'];
                $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual + $monto_devolver WHERE id = $cuenta_id");
                $_SESSION['mensaje_finanzas'] = "Salida eliminada exitosamente.";
                $_SESSION['tipo_mensaje_finanzas'] = 'success';
            }
            $stmt->close();
        }
        
        // REDIRECT para evitar reenvío del formulario
        $redirect_url = "salidas.php";
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
$page_title = "Salidas - Finanzas";
require_once __DIR__ . '/../includes/header.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
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

// Paginación
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 7;
$offset = ($pagina - 1) * $por_pagina;

// Obtener datos
$categorias = [];
$cuentas = [];
$salidas_array = [];
$total_periodo = 0;
$total_registros = 0;
$filtro_fecha_desde = date('Y-m-01');
$filtro_fecha_hasta = date('Y-m-d');

if ($iglesia_id > 0) {
    // Categorías de salida
    $stmt = $conexion->prepare("SELECT id, nombre FROM fin_categorias WHERE id_iglesia = ? AND tipo = 'SALIDA' AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Cuentas activas
    $stmt = $conexion->prepare("SELECT id, nombre, saldo_actual FROM fin_cuentas WHERE id_iglesia = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $cuentas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $filtro_fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $filtro_fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    
    // Contar registros
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta);
    $stmt->execute();
    $total_registros = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Total del período
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta);
    $stmt->execute();
    $total_periodo = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Obtener salidas con paginación
    $sql = "SELECT s.*, c.nombre as categoria, cu.nombre as cuenta
            FROM fin_salidas s
            LEFT JOIN fin_categorias c ON s.id_categoria = c.id
            LEFT JOIN fin_cuentas cu ON s.id_cuenta = cu.id
            WHERE s.id_iglesia = ? AND s.fecha BETWEEN ? AND ?
            ORDER BY s.fecha DESC, s.id DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issii", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta, $por_pagina, $offset);
    $stmt->execute();
    $salidas_array = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $total_paginas = ceil($total_registros / $por_pagina);
}

// Para super_admin: obtener lista de iglesias
$iglesias = [];
if ($ROL_NOMBRE === 'super_admin') {
    $result = $conexion->query("SELECT id, nombre FROM iglesias WHERE activo = 1 ORDER BY nombre");
    if ($result) {
        $iglesias = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

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

<?php else: ?>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
    <?php echo htmlspecialchars($mensaje); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header Compacto -->
<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-1"><i class="fas fa-arrow-down text-danger me-2"></i>Egresos / Salidas</h4>
        <p class="text-muted mb-0 small">Registro de gastos y desembolsos</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="index.php<?php echo $iglesia_id > 0 ? '?iglesia_id='.$iglesia_id : ''; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chart-pie me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- Formulario Izquierda -->
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-danger text-white py-2">
                <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nueva Salida</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="formSalida">
                    <input type="hidden" name="accion" value="crear">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Fecha *</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Monto *</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">RD$</span>
                                <input type="number" name="monto" id="montoSalida" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2 mt-2">
                        <label class="form-label small fw-bold mb-1">Categoría *</label>
                        <select name="id_categoria" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Cuenta Origen *</label>
                        <select name="id_cuenta" id="selectCuenta" class="form-select form-select-sm" required>
                            <option value="" data-saldo="0">-- Seleccionar --</option>
                            <?php foreach ($cuentas as $cue): ?>
                                <option value="<?php echo $cue['id']; ?>" data-saldo="<?php echo (float)$cue['saldo_actual']; ?>">
                                    <?php echo htmlspecialchars($cue['nombre']); ?> 
                                    (RD$ <?php echo number_format((float)$cue['saldo_actual'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="saldoInfo" class="small text-muted mt-1" style="display:none;">
                            Saldo disponible: <strong id="saldoDisponible" class="text-success">RD$ 0.00</strong>
                        </div>
                        <div id="alertaSaldo" class="alert alert-danger py-1 px-2 mt-1 small" style="display:none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <span id="mensajeAlerta"></span>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">Beneficiario *</label>
                        <input type="text" name="beneficiario" class="form-control form-control-sm" placeholder="Nombre del beneficiario" required>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">N° Documento</label>
                            <input type="text" name="numero_documento" class="form-control form-control-sm" placeholder="Factura/Recibo">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Forma de Pago</label>
                            <select name="forma_pago" class="form-select form-select-sm">
                                <option value="EFECTIVO">💵 Efectivo</option>
                                <option value="TRANSFERENCIA">🏦 Transferencia</option>
                                <option value="CHEQUE">📄 Cheque</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Descripción</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2" placeholder="Observaciones opcionales..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-save me-1"></i> Guardar Salida
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Listado Derecha -->
    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Últimas Salidas</h6>
            </div>
            <div class="card-body p-0">
                <!-- Filtro -->
                <div class="p-2 bg-light border-bottom">
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <div class="col-5">
                        <label class="form-label small mb-0">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo $filtro_fecha_desde; ?>">
                    </div>
                    <div class="col-5">
                        <label class="form-label small mb-0">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo $filtro_fecha_hasta; ?>">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </form>
            
                <!-- Resumen Lado a Lado -->
                <div class="row g-2 mb-3 mt-2">
                    <div class="col-md-8">
                        <div class="alert alert-danger bg-opacity-10 border border-danger mb-0 py-2">
                            <i class="fas fa-chart-line me-1"></i>
                            <strong>Total del período:</strong> RD$ <?php echo number_format($total_periodo, 2); ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-light border mb-0 py-2">
                            <i class="fas fa-database me-1"></i>
                            <strong><?php echo $total_registros; ?></strong> registro(s)
                        </div>
                    </div>
                </div>
                </div>
            
                <!-- Tabla Profesional -->
                <?php if (count($salidas_array) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-3">Fecha</th>
                                <th class="border-0">Categoría</th>
                                <th class="border-0">Beneficiario</th>
                                <th class="border-0">Cuenta</th>
                                <th class="border-0 text-end">Monto</th>
                                <th class="border-0 text-center pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($salidas_array as $sal): 
                                $subtotal += (float)$sal['monto'];
                            ?>
                            <tr>
                                <td class="ps-3">
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($sal['fecha'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                        <?php echo htmlspecialchars($sal['categoria'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sal['beneficiario']); ?></strong>
                                    <?php if ($sal['descripcion']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($sal['descripcion']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($sal['numero_documento']): ?>
                                    <br><small class="badge bg-light text-dark">Doc: <?php echo htmlspecialchars($sal['numero_documento']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-university me-1"></i><?php echo htmlspecialchars($sal['cuenta'] ?? '-'); ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <strong class="text-danger">RD$ <?php echo number_format((float)$sal['monto'], 2); ?></strong>
                                    <br><small class="text-muted"><?php 
                                        $forma = $sal['forma_pago'] ?? 'EFECTIVO';
                                        echo $forma === 'EFECTIVO' ? '💵' : ($forma === 'TRANSFERENCIA' ? '🏦' : '📄');
                                    ?> <?php echo $forma; ?></small>
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?php echo $sal['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="ps-3 fw-semibold">Subtotal (página actual):</td>
                                <td class="text-end fw-bold text-danger">RD$ <?php echo number_format($subtotal, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
            
                <!-- Paginación Compacta -->
                <?php if ($total_paginas > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php if ($pagina > 1): ?>
                        <li class="page-item">
                            <a href="?iglesia_id=<?php echo $iglesia_id; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>&pagina=<?php echo $pagina - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="page-item active">
                            <span class="page-link">Pág. <?php echo $pagina; ?> / <?php echo $total_paginas; ?></span>
                        </li>
                        <?php if ($pagina < $total_paginas): ?>
                        <li class="page-item">
                            <a href="?iglesia_id=<?php echo $iglesia_id; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>&pagina=<?php echo $pagina + 1; ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No hay salidas registradas en este período</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bootstrap 5 Nativo -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger bg-opacity-10 border-0">
                <h6 class="modal-title fw-semibold" id="modalEliminarLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Eliminación
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Está seguro de eliminar esta salida? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <form method="POST" id="formEliminar" class="d-flex gap-2">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <input type="hidden" name="id" id="idEliminar">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Confirmar eliminación
function confirmarEliminar(id) {
    document.getElementById('idEliminar').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

// ============================================
// VALIDACIÓN DE SALDO EN TIEMPO REAL
// ============================================
const selectCuenta = document.getElementById('selectCuenta');
const inputMonto = document.getElementById('montoSalida');
const saldoInfo = document.getElementById('saldoInfo');
const saldoDisponible = document.getElementById('saldoDisponible');
const alertaSaldo = document.getElementById('alertaSaldo');
const mensajeAlerta = document.getElementById('mensajeAlerta');
const btnSubmit = document.querySelector('#formSalida button[type="submit"]');

let saldoActual = 0;

function formatearMoneda(valor) {
    return 'RD$ ' + parseFloat(valor).toLocaleString('es-DO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function actualizarSaldo() {
    const opcionSeleccionada = selectCuenta.options[selectCuenta.selectedIndex];
    saldoActual = parseFloat(opcionSeleccionada.dataset.saldo) || 0;
    
    if (selectCuenta.value) {
        saldoInfo.style.display = 'block';
        saldoDisponible.textContent = formatearMoneda(saldoActual);
    } else {
        saldoInfo.style.display = 'none';
    }
    
    validarMonto();
}

function validarMonto() {
    const monto = parseFloat(inputMonto.value) || 0;
    
    if (monto > 0 && monto > saldoActual && selectCuenta.value) {
        alertaSaldo.style.display = 'block';
        mensajeAlerta.textContent = 'Fondos insuficientes. Disponible: ' + formatearMoneda(saldoActual) + ', Solicitado: ' + formatearMoneda(monto);
        btnSubmit.disabled = true;
        btnSubmit.classList.add('btn-secondary');
        btnSubmit.classList.remove('btn-danger');
        inputMonto.classList.add('is-invalid');
    } else {
        alertaSaldo.style.display = 'none';
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('btn-secondary');
        btnSubmit.classList.add('btn-danger');
        inputMonto.classList.remove('is-invalid');
    }
}

selectCuenta?.addEventListener('change', actualizarSaldo);
inputMonto?.addEventListener('input', validarMonto);
inputMonto?.addEventListener('change', validarMonto);

// Prevenir doble envío del formulario
document.getElementById('formSalida')?.addEventListener('submit', function(e) {
    const monto = parseFloat(inputMonto.value) || 0;
    
    // Validar una vez más antes de enviar
    if (monto > saldoActual && selectCuenta.value) {
        e.preventDefault();
        alert('Error: El monto solicitado (' + formatearMoneda(monto) + ') excede el saldo disponible (' + formatearMoneda(saldoActual) + ')');
        return false;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    if (btn.disabled) {
        e.preventDefault();
        return false;
    }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
