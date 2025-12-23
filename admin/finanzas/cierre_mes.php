<?php
declare(strict_types=1);

$page_title = "Cierre de Mes - Finanzas";
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

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$tipo_mensaje = '';

$meses_nombre = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 
                 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$ano_actual = (int)date('Y');

// Procesar cierre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $iglesia_id > 0) {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'cerrar') {
        $mes = (int)$_POST['mes'];
        $ano = (int)$_POST['ano'];
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Verificar que no esté cerrado
        $stmt = $conexion->prepare("SELECT id FROM fin_cierres WHERE id_iglesia = ? AND mes = ? AND ano = ? AND cerrado = 1");
        $stmt->bind_param("iii", $iglesia_id, $mes, $ano);
        $stmt->execute();
        $ya_existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        if ($ya_existe) {
            $mensaje = "Este mes ya está cerrado.";
            $tipo_mensaje = 'warning';
        } else {
            $fecha_inicio = "$ano-" . str_pad((string)$mes, 2, '0', STR_PAD_LEFT) . "-01";
            $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
            
            // Total entradas
            $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
            $stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $total_entradas = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Total salidas
            $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
            $stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $total_salidas = (float)$stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            $saldo_final = $total_entradas - $total_salidas;
            
            $stmt = $conexion->prepare("INSERT INTO fin_cierres (id_iglesia, mes, ano, total_entradas, total_salidas, saldo_final, cerrado, fecha_cierre, id_usuario_cierre, observaciones) 
                                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?)
                                        ON DUPLICATE KEY UPDATE total_entradas = VALUES(total_entradas), total_salidas = VALUES(total_salidas), saldo_final = VALUES(saldo_final), cerrado = 1, fecha_cierre = NOW(), id_usuario_cierre = VALUES(id_usuario_cierre), observaciones = VALUES(observaciones)");
            $stmt->bind_param("iiidddis", $iglesia_id, $mes, $ano, $total_entradas, $total_salidas, $saldo_final, $usuario_id, $observaciones);
            
            if ($stmt->execute()) {
                $mensaje = "Mes cerrado exitosamente. Ya no se pueden editar movimientos de este período.";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error al cerrar el mes: " . $conexion->error;
                $tipo_mensaje = 'danger';
            }
            $stmt->close();
        }
    }
}

// Variables para vista
$cierres = [];
$mes_preview = (int)($_GET['mes'] ?? date('n'));
$ano_preview = (int)($_GET['ano'] ?? date('Y'));
$preview_entradas = 0;
$preview_salidas = 0;
$ya_cerrado = false;

if ($iglesia_id > 0) {
    // Historial de cierres
    $stmt = $conexion->prepare("SELECT * FROM fin_cierres WHERE id_iglesia = ? ORDER BY ano DESC, mes DESC");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $cierres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Vista previa del mes seleccionado
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
    
    // Verificar si ya está cerrado
    $stmt = $conexion->prepare("SELECT cerrado FROM fin_cierres WHERE id_iglesia = ? AND mes = ? AND ano = ?");
    $stmt->bind_param("iii", $iglesia_id, $mes_preview, $ano_preview);
    $stmt->execute();
    $resultado_cierre = $stmt->get_result()->fetch_assoc();
    $ya_cerrado = $resultado_cierre && $resultado_cierre['cerrado'] == 1;
    $stmt->close();
}
?>

<div class="content-header">
    <h1><i class="fas fa-lock"></i> Cierre de Mes</h1>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Seleccione una iglesia desde el <a href="index.php">Dashboard de Finanzas</a>.
    </div>
<?php else: ?>

<div class="row g-3">
    <!-- Selección y Vista Previa -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-calendar-alt"></i> Seleccionar Mes a Cerrar</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                    <div class="col-md-5">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-select">
                            <?php foreach ($meses_nombre as $num => $nombre): ?>
                            <option value="<?php echo $num; ?>" <?php echo $num == $mes_preview ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                            <?php endforeach; ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Año</label>
                        <select name="ano" class="form-select">
                            <?php for ($a = $ano_actual; $a >= $ano_actual - 2; $a--): ?>
                            <option value="<?php echo $a; ?>" <?php echo $a == $ano_preview ? 'selected' : ''; ?>><?php echo $a; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-eye"></i> Ver</button>
                    </div>
                </form>
                
                <!-- Vista previa -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h4 class="text-center mb-3"><?php echo $meses_nombre[$mes_preview]; ?> <?php echo $ano_preview; ?></h4>
                        
                        <?php if ($ya_cerrado): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-lock"></i> Este mes ya está cerrado
                        </div>
                        <?php endif; ?>
                    
                    <div class="row g-3 my-3">
                        <div class="col-6 text-center">
                            <p class="text-muted mb-0">Total Entradas</p>
                            <h3 class="text-success mb-0">RD$ <?php echo number_format($preview_entradas, 2); ?></h3>
                        </div>
                        <div class="col-6 text-center">
                            <p class="text-muted mb-0">Total Salidas</p>
                            <h3 class="text-danger mb-0">RD$ <?php echo number_format($preview_salidas, 2); ?></h3>
                        </div>
                    </div>
                    
                    <div class="text-center bg-primary text-white p-3 rounded">
                        <p class="mb-0">Balance del Mes</p>
                        <h2 class="mb-0">RD$ <?php echo number_format($preview_entradas - $preview_salidas, 2); ?></h2>
                    </div>
                    
                    <?php if (!$ya_cerrado): ?>
                    <form method="POST" class="mt-3">
                        <input type="hidden" name="accion" value="cerrar">
                        <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
                        <input type="hidden" name="mes" value="<?php echo $mes_preview; ?>">
                        <input type="hidden" name="ano" value="<?php echo $ano_preview; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Observaciones (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas del cierre..."></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('¿Está seguro de cerrar este mes? No podrá editar movimientos de este período.')">
                                <i class="fas fa-lock"></i> Cerrar <?php echo $meses_nombre[$mes_preview]; ?> <?php echo $ano_preview; ?>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Historial de Cierres -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-history"></i> Historial de Cierres</h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($cierres) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th class="text-end">Entradas</th>
                                <th class="text-end">Salidas</th>
                                <th class="text-end">Balance</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cierres as $c): ?>
                            <tr>
                                <td><strong><?php echo $meses_nombre[$c['mes']]; ?> <?php echo $c['ano']; ?></strong></td>
                                <td class="text-success text-end">RD$ <?php echo number_format((float)$c['total_entradas'], 2); ?></td>
                                <td class="text-danger text-end">RD$ <?php echo number_format((float)$c['total_salidas'], 2); ?></td>
                                <td class="text-end"><strong>RD$ <?php echo number_format((float)$c['saldo_final'], 2); ?></strong></td>
                                <td class="text-center">
                                    <?php if ($c['cerrado']): ?>
                                    <span class="badge bg-success"><i class="fas fa-lock"></i> Cerrado</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning"><i class="fas fa-unlock"></i> Abierto</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay cierres registrados</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>