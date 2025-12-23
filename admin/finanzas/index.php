<?php
declare(strict_types=1);

$page_title = "Finanzas - Dashboard";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Determinar iglesia según rol
if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

// Obtener iglesias para super_admin
$iglesias = null;
if ($ROL_NOMBRE === 'super_admin') {
    $iglesias = $conexion->query("SELECT i.*, d.nombre AS distrito_nombre, c.nombre AS conferencia_nombre 
                                   FROM iglesias i 
                                   INNER JOIN distritos d ON d.id = i.distrito_id 
                                   INNER JOIN conferencias c ON c.id = d.conferencia_id 
                                   WHERE i.activo = 1 
                                   ORDER BY c.nombre, d.nombre, i.nombre");
}

// Variables para el dashboard
$cuentas = [];
$total_entradas_mes = 0;
$total_salidas_mes = 0;
$saldo_general = 0;

if ($iglesia_id > 0) {
    // Obtener todas las cuentas con sus saldos
    $stmt = $conexion->prepare("SELECT id, codigo, nombre, saldo_inicial, saldo_actual FROM fin_cuentas WHERE id_iglesia = ? AND activo = 1 ORDER BY nombre");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cuentas[] = $row;
        $saldo_general += (float)$row['saldo_actual'];
    }
    $stmt->close();
    
    // Entradas del mes actual
    $mes_inicio = date('Y-m-01');
    $mes_fin = date('Y-m-t');
    
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_entradas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $mes_inicio, $mes_fin);
    $stmt->execute();
    $total_entradas_mes = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Salidas del mes actual
    $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto), 0) as total FROM fin_salidas WHERE id_iglesia = ? AND fecha BETWEEN ? AND ?");
    $stmt->bind_param("iss", $iglesia_id, $mes_inicio, $mes_fin);
    $stmt->execute();
    $total_salidas_mes = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

$meses_nombre = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 
                 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$mes_actual = $meses_nombre[(int)date('n')];
?>

<!-- Header -->
<div class="row mb-3">
    <div class="col-md-6">
        <h1 class="h4 mb-0 text-dark"><i class="fas fa-coins text-primary me-2"></i>Finanzas</h1>
        <p class="text-muted small mb-0">Gestión y control financiero</p>
    </div>
    <?php if ($ROL_NOMBRE === 'super_admin'): ?>
    <div class="col-md-6">
        <form method="get">
            <select name="iglesia_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Seleccione una iglesia --</option>
                <?php while ($igl = $iglesias->fetch_assoc()): ?>
                    <option value="<?php echo $igl['id']; ?>" <?php echo $iglesia_id == $igl['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($igl['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Seleccione una iglesia para gestionar sus finanzas.
    </div>
<?php else: ?>

<!-- Resumen Financiero -->
<div class="row g-3 mb-3">
    <!-- Saldo Total -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Saldo Total</p>
                        <h4 class="mb-0 fw-bold">RD$ <?php echo number_format($saldo_general, 2); ?></h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="fas fa-wallet text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Entradas del Mes -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Entradas - <?php echo $mes_actual; ?></p>
                        <h4 class="mb-0 fw-bold text-success">RD$ <?php echo number_format($total_entradas_mes, 2); ?></h4>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="fas fa-arrow-down text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Salidas del Mes -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Salidas - <?php echo $mes_actual; ?></p>
                        <h4 class="mb-0 fw-bold text-danger">RD$ <?php echo number_format($total_salidas_mes, 2); ?></h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="fas fa-arrow-up text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Balance del Mes -->
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Balance - <?php echo $mes_actual; ?></p>
                        <h4 class="mb-0 fw-bold <?php echo ($total_entradas_mes - $total_salidas_mes) >= 0 ? 'text-success' : 'text-danger'; ?>">
                            RD$ <?php echo number_format($total_entradas_mes - $total_salidas_mes, 2); ?>
                        </h4>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded p-2">
                        <i class="fas fa-chart-line text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small me-2"><i class="fas fa-bolt"></i> Acceso rápido:</span>
            <a href="cuentas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-wallet"></i> Cuentas
            </a>
            <a href="categorias.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-tags"></i> Categorías
            </a>
            <a href="entradas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-plus-circle"></i> Entradas
            </a>
            <a href="salidas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-minus-circle"></i> Salidas
            </a>
            <a href="transferencias.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-info">
                <i class="fas fa-exchange-alt"></i> Transferencias
            </a>
            <a href="reportes.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
            <a href="cierre_mes.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-sm btn-outline-dark">
                <i class="fas fa-lock"></i> Cierre
            </a>
        </div>
    </div>
</div>

<!-- Cuentas Bancarias -->
<?php if (count($cuentas) > 0): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-university text-primary me-2"></i>Cuentas Bancarias</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="border-0">Código</th>
                        <th class="border-0">Cuenta</th>
                        <th class="border-0 text-end">Saldo Actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $cuenta): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($cuenta['codigo']); ?></span></td>
                        <td><i class="fas fa-piggy-bank text-muted me-1"></i><?php echo htmlspecialchars($cuenta['nombre']); ?></td>
                        <td class="text-end"><strong class="text-primary">RD$ <?php echo number_format((float)$cuenta['saldo_actual'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="2" class="fw-bold border-0">Total</td>
                        <td class="text-end fw-bold border-0 text-primary">RD$ <?php echo number_format($saldo_general, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-light border mb-3">
    <i class="fas fa-info-circle text-primary"></i> No hay cuentas registradas. 
    <a href="cuentas.php?iglesia_id=<?php echo $iglesia_id; ?>" class="alert-link">Crear primera cuenta</a>
</div>
<?php endif; ?>

<!-- Últimos Movimientos -->
<div class="row g-3">
    <!-- Últimas Entradas -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-arrow-down text-success me-2"></i>Últimas Entradas</h6>
            </div>
            <div class="card-body p-0">
                <?php
                $stmt = $conexion->prepare("SELECT e.*, c.nombre as categoria FROM fin_entradas e 
                                            LEFT JOIN fin_categorias c ON e.id_categoria = c.id 
                                            WHERE e.id_iglesia = ? ORDER BY e.fecha DESC, e.id DESC LIMIT 5");
                $stmt->bind_param("i", $iglesia_id);
                $stmt->execute();
                $ultimas_entradas = $stmt->get_result();
                
                if ($ultimas_entradas->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <tbody>
                            <?php while ($ent = $ultimas_entradas->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3"><small class="text-muted"><?php echo date('d/m/Y', strtotime($ent['fecha'])); ?></small></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?php echo htmlspecialchars($ent['categoria'] ?? 'Sin categoría'); ?></span></td>
                                <td class="text-end pe-3"><strong class="text-success">+RD$ <?php echo number_format((float)$ent['monto'], 2); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                    <p class="mb-0 small">No hay entradas registradas</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Últimas Salidas -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-semibold"><i class="fas fa-arrow-up text-danger me-2"></i>Últimas Salidas</h6>
            </div>
            <div class="card-body p-0">
                <?php
                $stmt = $conexion->prepare("SELECT s.*, c.nombre as categoria FROM fin_salidas s 
                                            LEFT JOIN fin_categorias c ON s.id_categoria = c.id 
                                            WHERE s.id_iglesia = ? ORDER BY s.fecha DESC, s.id DESC LIMIT 5");
                $stmt->bind_param("i", $iglesia_id);
                $stmt->execute();
                $ultimas_salidas = $stmt->get_result();
                
                if ($ultimas_salidas->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <tbody>
                            <?php while ($sal = $ultimas_salidas->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3"><small class="text-muted"><?php echo date('d/m/Y', strtotime($sal['fecha'])); ?></small></td>
                                <td><span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><?php echo htmlspecialchars($sal['categoria'] ?? 'Sin categoría'); ?></span></td>
                                <td class="text-end pe-3"><strong class="text-danger">-RD$ <?php echo number_format((float)$sal['monto'], 2); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2 opacity-25"></i>
                    <p class="mb-0 small">No hay salidas registradas</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>