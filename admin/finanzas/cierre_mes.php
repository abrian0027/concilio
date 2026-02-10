<?php
declare(strict_types=1);

$page_title = "Cierre de Mes - Finanzas";
require_once __DIR__ . '/../includes/header.php';

$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'>No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? $_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$tipo_mensaje = '';

$meses_nombre = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$ano_actual = (int)date('Y');
$mes_actual = (int)date('n');

// Por defecto mostrar el mes y año actual
$mes_default = $mes_actual;
$ano_default = $ano_actual;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $iglesia_id > 0) {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'cerrar') {
        $mes = (int)$_POST['mes'];
        $ano = (int)$_POST['ano'];
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        $stmt = $conexion->prepare("SELECT id FROM fin_cierres WHERE id_iglesia = ? AND mes = ? AND ano = ? AND cerrado = 1");
        $stmt->bind_param("iii", $iglesia_id, $mes, $ano);
        $stmt->execute();
        $ya_existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        if ($ya_existe) {
            $mensaje = "Este mes ya esta cerrado.";
            $tipo_mensaje = 'warning';
        } else {
            $fecha_inicio = "$ano-" . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . "-01";
            $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
            
            $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
            $stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $total_entradas = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
            $stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $total_salidas = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $saldo_final = $total_entradas - $total_salidas;
            
            $stmt = $conexion->prepare("INSERT INTO fin_cierres (id_iglesia, mes, ano, total_entradas, total_salidas, saldo_final, cerrado, fecha_cierre, id_usuario_cierre, observaciones) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?) ON DUPLICATE KEY UPDATE total_entradas = VALUES(total_entradas), total_salidas = VALUES(total_salidas), saldo_final = VALUES(saldo_final), cerrado = 1, fecha_cierre = NOW(), id_usuario_cierre = VALUES(id_usuario_cierre), observaciones = VALUES(observaciones)");
            $stmt->bind_param("iiidddis", $iglesia_id, $mes, $ano, $total_entradas, $total_salidas, $saldo_final, $usuario_id, $observaciones);
            
            if ($stmt->execute()) {
                $mensaje = "Mes cerrado exitosamente.";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al cerrar el mes.";
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    }
}

$mes_preview = (int)($_GET['mes'] ?? $mes_default);
$ano_preview = (int)($_GET['ano'] ?? $ano_default);
$preview_entradas = 0;
$preview_salidas = 0;
$ya_cerrado = false;
$cierres = [];

if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM fin_cierres WHERE id_iglesia = ? ORDER BY ano DESC, mes DESC");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $cierres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $fecha_inicio_preview = "$ano_preview-" . str_pad((string)$mes_preview, 2, '0', STR_PAD_LEFT) . "-01";
    $fecha_fin_preview = date('Y-m-t', strtotime($fecha_inicio_preview));
    
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $fecha_inicio_preview, $fecha_fin_preview);
    $stmt->execute();
    $preview_entradas = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $fecha_inicio_preview, $fecha_fin_preview);
    $stmt->execute();
    $preview_salidas = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT cerrado FROM fin_cierres WHERE id_iglesia = ? AND mes = ? AND ano = ?");
    $stmt->bind_param("iii", $iglesia_id, $mes_preview, $ano_preview);
    $stmt->execute();
    $resultado_cierre = $stmt->get_result()->fetch_assoc();
    $ya_cerrado = $resultado_cierre && $resultado_cierre['cerrado'] == 1;
    $stmt->close();
}

$balance_preview = $preview_entradas - $preview_salidas;
?>

<style>
.card-stat { border-radius: 1rem; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.preview-card { background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; border-radius: 1rem; padding: 1.5rem; }
.balance-box { background: rgba(255,255,255,0.3); border-radius: 0.75rem; padding: 1rem; margin-top: 1rem; }
@media (max-width: 576px) { .preview-card { padding: 1rem; } .preview-card h2 { font-size: 1.5rem; } .stat-value { font-size: 1.2rem !important; } }
</style>

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0"><i class="fas fa-lock text-primary me-2"></i>Cierre de Mes</h4>
            <small class="text-muted">Bloquear periodos contables</small>
        </div>
        <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info">Seleccione una iglesia desde el <a href="index.php">Dashboard</a>.</div>
    <?php else: ?>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card card-stat">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Seleccionar Periodo</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                        <div class="row g-2">
                            <div class="col-5">
                                <label class="form-label small">Mes</label>
                                <select name="mes" class="form-select">
                                    <?php foreach ($meses_nombre as $num => $nombre): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $num == $mes_preview ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Año</label>
                                <select name="ano" class="form-select">
                                    <?php for ($a = $ano_actual; $a >= $ano_actual - 1; $a--): ?>
                                    <option value="<?php echo $a; ?>" <?php echo $a == $ano_preview ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="preview-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><?php echo $meses_nombre[$mes_preview]; ?> <?php echo $ano_preview; ?></h4>
                            <?php if ($ya_cerrado): ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-lock"></i> Cerrado</span>
                            <?php else: ?>
                            <span class="badge bg-light text-dark"><i class="fas fa-unlock"></i> Abierto</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded p-2">
                                    <small class="d-block opacity-75">Entradas</small>
                                    <strong class="stat-value" style="font-size:1.3rem;">RD$ <?php echo number_format($preview_entradas, 2); ?></strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-white bg-opacity-10 rounded p-2">
                                    <small class="d-block opacity-75">Salidas</small>
                                    <strong class="stat-value" style="font-size:1.3rem;">RD$ <?php echo number_format($preview_salidas, 2); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="balance-box text-center">
                            <small class="d-block opacity-75">Balance del Mes</small>
                            <h2 class="mb-0">RD$ <?php echo number_format($balance_preview, 2); ?></h2>
                        </div>
                    </div>
                    
                    <?php if (!$ya_cerrado): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="accion" value="cerrar">
                        <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                        <input type="hidden" name="mes" value="<?php echo $mes_preview; ?>">
                        <input type="hidden" name="ano" value="<?php echo $ano_preview; ?>">
                        <div class="mb-3">
                            <label class="form-label small">Observaciones (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Cerrar este mes?')">
                                <i class="fas fa-lock me-2"></i>Cerrar <?php echo $meses_nombre[$mes_preview] . ' ' . $ano_preview; ?>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-success mt-3 mb-0"><i class="fas fa-check-circle me-2"></i>Este periodo ya fue cerrado.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-6">
            <div class="card card-stat">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="fas fa-history text-primary me-2"></i>Historial de Cierres</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($cierres)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Periodo</th><th class="text-end">Balance</th><th class="text-center">Estado</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cierres as $c): ?>
                                <tr>
                                    <td><strong><?php echo substr($meses_nombre[$c['mes']], 0, 3); ?>.</strong> <?php echo $c['ano']; ?></td>
                                    <td class="text-end"><strong>RD$ <?php echo number_format((float)$c['saldo_final'], 0); ?></strong></td>
                                    <td class="text-center">
                                        <?php if ($c['cerrado']): ?>
                                        <span class="badge bg-success"><i class="fas fa-lock"></i></span>
                                        <?php else: ?>
                                        <span class="badge bg-warning"><i class="fas fa-unlock"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <p class="mb-0">No hay cierres registrados</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>