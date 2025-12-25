<?php
declare(strict_types=1);

/**
 * Entradas - Finanzas
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
function esMesCerrado($conexion, $id_iglesia, $fecha) {
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
    
    // CREAR nueva entrada
    if ($accion === 'crear') {
        $fecha = $_POST['fecha'];
        $id_categoria = (int)$_POST['id_categoria'];
        $id_cuenta = (int)$_POST['id_cuenta'];
        $monto = (float)$_POST['monto'];
        $forma_pago = $_POST['forma_pago'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $id_miembro = !empty($_POST['id_miembro']) ? (int)$_POST['id_miembro'] : null;
        $nombre_manual = trim($_POST['nombre_manual'] ?? '');
        
        if (esMesCerrado($conexion, $iglesia_id, $fecha)) {
            $_SESSION['mensaje_finanzas'] = "No se puede registrar: el mes está cerrado.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else {
            $stmt = $conexion->prepare("INSERT INTO fin_entradas (id_iglesia, id_categoria, id_cuenta, id_miembro, nombre_manual, fecha, monto, forma_pago, descripcion, id_usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiissdssi", $iglesia_id, $id_categoria, $id_cuenta, $id_miembro, $nombre_manual, $fecha, $monto, $forma_pago, $descripcion, $usuario_id);
            
            if ($stmt->execute()) {
                $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual + $monto WHERE id = $id_cuenta");
                $_SESSION['mensaje_finanzas'] = "Entrada registrada exitosamente.";
                $_SESSION['tipo_mensaje_finanzas'] = 'success';
            } else {
                $_SESSION['mensaje_finanzas'] = "Error al registrar la entrada: " . $conexion->error;
                $_SESSION['tipo_mensaje_finanzas'] = 'danger';
            }
            $stmt->close();
        }
        
        // REDIRECT para evitar reenvío del formulario (Patrón PRG)
        $redirect_url = "entradas.php";
        if ($ROL_NOMBRE_TEMP === 'super_admin' && $iglesia_id > 0) {
            $redirect_url .= "?iglesia_id=" . $iglesia_id;
        }
        header("Location: " . $redirect_url);
        exit;
    }
    
    // ELIMINAR entrada
    if ($accion === 'eliminar') {
        $id = (int)$_POST['id'];
        
        $stmt = $conexion->prepare("SELECT fecha, monto, id_cuenta FROM fin_entradas WHERE id = ? AND id_iglesia = ?");
        $stmt->bind_param("ii", $id, $iglesia_id);
        $stmt->execute();
        $entrada = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($entrada && esMesCerrado($conexion, $iglesia_id, $entrada['fecha'])) {
            $_SESSION['mensaje_finanzas'] = "No se puede eliminar: el mes está cerrado.";
            $_SESSION['tipo_mensaje_finanzas'] = 'danger';
        } else if ($entrada) {
            $stmt = $conexion->prepare("DELETE FROM fin_entradas WHERE id = ? AND id_iglesia = ?");
            $stmt->bind_param("ii", $id, $iglesia_id);
            
            if ($stmt->execute()) {
                $monto_devolver = (float)$entrada['monto'];
                $cuenta_id = (int)$entrada['id_cuenta'];
                $conexion->query("UPDATE fin_cuentas SET saldo_actual = saldo_actual - $monto_devolver WHERE id = $cuenta_id");
                $_SESSION['mensaje_finanzas'] = "Entrada eliminada exitosamente.";
                $_SESSION['tipo_mensaje_finanzas'] = 'success';
            }
            $stmt->close();
        }
        
        // REDIRECT para evitar reenvío del formulario
        $redirect_url = "entradas.php";
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
$page_title = "Entradas - Finanzas";
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

// Obtener datos para formulario
$categorias = [];
$cuentas = [];
$miembros = [];
$entradas_array = [];
$total_periodo = 0;
$total_registros = 0;

if ($iglesia_id > 0) {
    // Categorías de entrada
    $stmt = $conexion->prepare("SELECT id, nombre FROM fin_categorias WHERE id_iglesia = ? AND tipo = 'ENTRADA' AND activo = 1 ORDER BY nombre");
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
    
    // Miembros
    $stmt = $conexion->prepare("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM miembros WHERE iglesia_id = ? AND estado = 'activo' ORDER BY nombre, apellido");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Filtros
    $filtro_fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
    $filtro_fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    
    // Contar total de registros
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta);
    $stmt->execute();
    $total_registros = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Total del período
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta);
    $stmt->execute();
    $total_periodo = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Obtener entradas con paginación
    $sql = "SELECT e.*, c.nombre as categoria, cu.nombre as cuenta, 
            CONCAT(m.nombre, ' ', m.apellido) as miembro_nombre
            FROM fin_entradas e
            LEFT JOIN fin_categorias c ON e.id_categoria = c.id
            LEFT JOIN fin_cuentas cu ON e.id_cuenta = cu.id
            LEFT JOIN miembros m ON e.id_miembro = m.id
            WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ?
            ORDER BY e.fecha DESC, e.id DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issii", $iglesia_id, $filtro_fecha_desde, $filtro_fecha_hasta, $por_pagina, $offset);
    $stmt->execute();
    $entradas_array = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        <h4 class="mb-1"><i class="fas fa-arrow-up text-success me-2"></i>Ingresos / Entradas</h4>
        <p class="text-muted mb-0 small">Registro de ofrendas, diezmos y donaciones</p>
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
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nueva Entrada</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="formEntrada">
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
                                <input type="number" name="monto" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
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
                        <label class="form-label small fw-bold mb-1">Cuenta Destino *</label>
                        <select name="id_cuenta" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($cuentas as $cue): ?>
                                <option value="<?php echo $cue['id']; ?>">
                                    <?php echo htmlspecialchars($cue['nombre']); ?> 
                                    (RD$ <?php echo number_format((float)$cue['saldo_actual'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">Forma de Pago</label>
                            <select name="forma_pago" class="form-select form-select-sm">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="transferencia">🏦 Transferencia</option>
                                <option value="cheque">📄 Cheque</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold mb-1">¿Quién aporta?</label>
                            <select name="id_miembro" id="selectMiembro" class="form-select form-select-sm">
                                <option value="">-- Miembro --</option>
                                <?php foreach ($miembros as $miem): ?>
                                    <option value="<?php echo $miem['id']; ?>"><?php echo htmlspecialchars($miem['nombre_completo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <input type="text" name="nombre_manual" id="nombreManual" class="form-control form-control-sm" placeholder="O escriba nombre manual aquí">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Descripción</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="2" placeholder="Observaciones opcionales..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save me-1"></i> Guardar Entrada
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Listado Derecha -->
    <div class="col-lg-7 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Últimas Entradas</h6>
            </div>
            <div class="card-body p-0">
                
                <!-- Filtro -->
                <div class="p-2 bg-light border-bottom">
                    <form method="GET" class="row g-2 align-items-end">
                        <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                        <div class="col-5">
                            <label class="form-label small mb-1">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo $filtro_fecha_desde ?? date('Y-m-01'); ?>">
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo $filtro_fecha_hasta ?? date('Y-m-d'); ?>">
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Resumen Compacto -->
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom bg-white">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-coins text-success me-2"></i>
                        <span class="small fw-bold">Total Período</span>
                        <span class="badge bg-success ms-2">RD$ <?php echo number_format($total_periodo, 2); ?></span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-list-ol text-primary me-2"></i>
                        <span class="small">Registros</span>
                        <span class="badge bg-primary ms-2"><?php echo $total_registros; ?></span>
                    </div>
                </div>
            
                <?php if (count($entradas_array) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 ps-3">Fecha</th>
                                <th class="border-0">Categoría</th>
                                <th class="border-0">Quien Aporta</th>
                                <th class="border-0">Cuenta</th>
                                <th class="border-0 text-end">Monto</th>
                                <th class="border-0 text-center pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entradas_array as $ent): ?>
                            <tr>
                                <td class="ps-3">
                                    <small><?php echo date('d/m/Y', strtotime($ent['fecha'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <?php echo htmlspecialchars($ent['categoria'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($ent['miembro_nombre'])) {
                                        echo '<i class="fas fa-user text-primary me-1"></i><small>' . htmlspecialchars($ent['miembro_nombre']) . '</small>';
                                    } elseif (!empty($ent['nombre_manual'])) {
                                        echo '<i class="fas fa-user-tag text-secondary me-1"></i><small>' . htmlspecialchars($ent['nombre_manual']) . '</small>';
                                    } else {
                                        echo '<small class="text-muted">Anónimo</small>';
                                    }
                                    ?>
                                    <?php if (!empty($ent['descripcion'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($ent['descripcion']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($ent['cuenta'] ?? '-'); ?></small></td>
                                <td class="text-end">
                                    <strong class="text-success">RD$ <?php echo number_format((float)$ent['monto'], 2); ?></strong>
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarEliminar(<?php echo $ent['id']; ?>)" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="fw-bold border-0 ps-3">Total mostrado</td>
                                <td class="text-end fw-bold border-0 text-success">
                                    RD$ <?php 
                                    $suma_mostrada = array_sum(array_column($entradas_array, 'monto'));
                                    echo number_format($suma_mostrada, 2); 
                                    ?>
                                </td>
                                <td class="border-0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <?php if ($pagina > 1): ?>
                        <li class="page-item">
                            <a href="?iglesia_id=<?php echo $iglesia_id; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>&pagina=<?php echo $pagina - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="page-item active">
                            <span class="page-link">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
                        </li>
                        
                        <?php if ($pagina < $total_paginas): ?>
                        <li class="page-item">
                            <a href="?iglesia_id=<?php echo $iglesia_id; ?>&fecha_desde=<?php echo $filtro_fecha_desde; ?>&fecha_hasta=<?php echo $filtro_fecha_hasta; ?>&pagina=<?php echo $pagina + 1; ?>" class="page-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            
                <?php else: ?>
                <div class="text-center p-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-0">No hay entradas en el período seleccionado</p>
                    <small class="text-muted">Ajuste las fechas del filtro o registre una nueva entrada</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Está seguro de eliminar esta entrada?</p>
                <small class="text-muted">Esta acción no se puede deshacer y afectará el saldo de la cuenta.</small>
            </div>
            <div class="modal-footer">
                <form method="POST" id="formEliminar">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <input type="hidden" name="id" id="idEliminar">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Alternar entre miembro seleccionado y nombre manual
document.getElementById('selectMiembro')?.addEventListener('change', function() {
    if (this.value) document.getElementById('nombreManual').value = '';
});
document.getElementById('nombreManual')?.addEventListener('input', function() {
    if (this.value) document.getElementById('selectMiembro').value = '';
});

// Confirmar eliminación
function confirmarEliminar(id) {
    document.getElementById('idEliminar').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

// Prevenir doble envío del formulario
document.getElementById('formEntrada')?.addEventListener('submit', function(e) {
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
