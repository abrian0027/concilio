<?php
declare(strict_types=1);

$page_title = "Reportes - Finanzas";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

if ($ROL_NOMBRE === 'super_admin') {
    $iglesia_id = (int)($_GET['iglesia_id'] ?? 0);
} else {
    $iglesia_id = $IGLESIA_ID;
}

$reporte = $_GET['reporte'] ?? 'situacion';
$mes = (int)($_GET['mes'] ?? date('n'));
$ano = (int)($_GET['ano'] ?? date('Y'));
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$buscar_miembro = trim($_GET['buscar_miembro'] ?? '');

$meses_nombre = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$iglesia_nombre = '';
$miembros_lista = [];

if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $iglesia_nombre = $stmt->get_result()->fetch_assoc()['nombre'] ?? '';
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT DISTINCT COALESCE(CONCAT(m.nombre, ' ', m.apellido), e.nombre_manual) as nombre_completo
            FROM fin_entradas e LEFT JOIN miembros m ON e.id_miembro = m.id
            WHERE e.id_iglesia = ? ORDER BY nombre_completo");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $miembros_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<style>
.card-reporte { border-radius: 1rem; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.section-title { background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-weight: 600; }
.section-gastos-title { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.section-ingresos { border-left: 4px solid #28a745; }
.section-gastos { border-left: 4px solid #dc3545; }
.total-row { background: #f8f9fa; font-weight: bold; }
@media print {
    .no-print { display: none !important; }
    .sidebar { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    body { background: white !important; }
    .print-header { display: block !important; }
}
.print-header { display: none; text-align: center; margin-bottom: 20px; }
@media (max-width: 576px) {
    .table { font-size: 0.85rem; }
    .section-title { font-size: 0.95rem; padding: 0.5rem 0.75rem; }
    .card-reporte .card-body { padding: 0.75rem; }
}
</style>

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 no-print">
        <div>
            <h4 class="mb-0"><i class="fas fa-chart-bar text-primary me-2"></i>Reportes</h4>
            <small class="text-muted"><?php echo htmlspecialchars($iglesia_nombre); ?></small>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Seleccione una iglesia desde el <a href="index.php">Dashboard</a>.</div>
<?php else: ?>

<div class="card card-reporte no-print mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="form-label small mb-1">Tipo de Reporte</label>
                <select name="reporte" class="form-select form-select-sm" id="selectReporte" onchange="toggleFiltros()">
                    <option value="situacion" <?php echo $reporte === 'situacion' ? 'selected' : ''; ?>>📊 Situación Financiera</option>
                    <option value="balance" <?php echo $reporte === 'balance' ? 'selected' : ''; ?>>Balance por Cuenta</option>
                    <option value="entradas" <?php echo $reporte === 'entradas' ? 'selected' : ''; ?>>Entradas por Categoría</option>
                    <option value="salidas" <?php echo $reporte === 'salidas' ? 'selected' : ''; ?>>Salidas por Categoría</option>
                    <option value="miembros" <?php echo $reporte === 'miembros' ? 'selected' : ''; ?>>Historial por Miembro</option>
                </select>
            </div>
            
            <div class="col-5 col-sm-3 col-lg-2 filtro-situacion" <?php echo $reporte !== 'situacion' ? 'style="display:none;"' : ''; ?>>
                <label class="form-label small mb-1">Mes</label>
                <select name="mes" class="form-select form-select-sm">
                    <?php foreach ($meses_nombre as $num => $nombre): ?>
                    <option value="<?php echo $num; ?>" <?php echo $num == $mes ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-sm-2 col-lg-1 filtro-situacion" <?php echo $reporte !== 'situacion' ? 'style="display:none;"' : ''; ?>>
                <label class="form-label small mb-1">Año</label>
                <select name="ano" class="form-select form-select-sm">
                    <?php for ($a = date('Y'); $a >= date('Y') - 1; $a--): ?>
                    <option value="<?php echo $a; ?>" <?php echo $a == $ano ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-6 col-sm-3 col-lg-2 filtro-fechas" <?php echo $reporte === 'situacion' ? 'style="display:none;"' : ''; ?>>
                <label class="form-label small mb-1">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?php echo $fecha_desde; ?>">
            </div>
            <div class="col-6 col-sm-3 col-lg-2 filtro-fechas" <?php echo $reporte === 'situacion' ? 'style="display:none;"' : ''; ?>>
                <label class="form-label small mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?php echo $fecha_hasta; ?>">
            </div>
            
            <div class="col-12 col-sm-5 col-lg-2 filtro-miembro" <?php echo $reporte !== 'miembros' ? 'style="display:none;"' : ''; ?>>
                <label class="form-label small mb-1">Miembro</label>
                <input type="text" name="buscar_miembro" class="form-control form-control-sm" value="<?php echo htmlspecialchars($buscar_miembro); ?>" placeholder="Buscar..." list="listaMiembros">
                <datalist id="listaMiembros">
                    <?php foreach ($miembros_lista as $m): ?>
                    <option value="<?php echo htmlspecialchars($m['nombre_completo'] ?? ''); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="col-3 col-sm-2 col-lg-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFiltros() {
    const r = document.getElementById('selectReporte').value;
    document.querySelectorAll('.filtro-situacion').forEach(el => el.style.display = r === 'situacion' ? '' : 'none');
    document.querySelectorAll('.filtro-fechas').forEach(el => el.style.display = r !== 'situacion' ? '' : 'none');
    document.querySelectorAll('.filtro-miembro').forEach(el => el.style.display = r === 'miembros' ? '' : 'none');
}
</script>

<div class="print-header">
    <h2><?php echo htmlspecialchars($iglesia_nombre); ?></h2>
    <h3><?php echo $reporte === 'situacion' ? 'Situación Financiera - ' . $meses_nombre[$mes] . ' ' . $ano : ucfirst($reporte); ?></h3>
</div>

<?php if ($reporte === 'situacion'): ?>
<?php
$fecha_inicio = sprintf('%04d-%02d-01', $ano, $mes);
$fecha_fin = date('Y-m-t', strtotime($fecha_inicio));

$stmt = $conexion->prepare("SELECT c.nombre, COALESCE(SUM(e.monto), 0) as total
    FROM fin_entradas e LEFT JOIN fin_categorias c ON e.id_categoria = c.id
    WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ?
    GROUP BY e.id_categoria ORDER BY c.nombre");
$stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
$stmt->execute();
$ingresos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conexion->prepare("SELECT c.nombre, COALESCE(SUM(s.monto), 0) as total
    FROM fin_salidas s LEFT JOIN fin_categorias c ON s.id_categoria = c.id
    WHERE s.id_iglesia = ? AND s.fecha BETWEEN ? AND ?
    GROUP BY s.id_categoria ORDER BY c.nombre");
$stmt->bind_param("iss", $iglesia_id, $fecha_inicio, $fecha_fin);
$stmt->execute();
$gastos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_ingresos = array_sum(array_column($ingresos, 'total'));
$total_gastos = array_sum(array_column($gastos, 'total'));
$balance = $total_ingresos - $total_gastos;
?>

<div class="card card-reporte">
    <div class="card-header bg-white py-2">
        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Situación Financiera - <?php echo $meses_nombre[$mes] . ' ' . $ano; ?></h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="section-title"><i class="fas fa-arrow-down me-2"></i>INGRESOS</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 section-ingresos">
                        <tbody>
                            <?php if (empty($ingresos)): ?>
                            <tr><td class="text-muted text-center py-3">Sin ingresos</td></tr>
                            <?php else: ?>
                            <?php foreach ($ingresos as $ing): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ing['nombre'] ?? 'Sin categoría'); ?></td>
                                <td class="text-end text-success">RD$ <?php echo number_format((float)$ing['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td><strong>TOTAL INGRESOS</strong></td>
                                <td class="text-end text-success"><strong>RD$ <?php echo number_format($total_ingresos, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="col-12 col-lg-6">
                <div class="section-title section-gastos-title"><i class="fas fa-arrow-up me-2"></i>GASTOS</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 section-gastos">
                        <tbody>
                            <?php if (empty($gastos)): ?>
                            <tr><td class="text-muted text-center py-3">Sin gastos</td></tr>
                            <?php else: ?>
                            <?php foreach ($gastos as $gas): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gas['nombre'] ?? 'Sin categoría'); ?></td>
                                <td class="text-end text-danger">RD$ <?php echo number_format((float)$gas['total'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td><strong>TOTAL GASTOS</strong></td>
                                <td class="text-end text-danger"><strong>RD$ <?php echo number_format($total_gastos, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card <?php echo $balance >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>BALANCE DEL MES</h5>
                                <small><?php echo $balance >= 0 ? 'Superávit' : 'Déficit'; ?></small>
                            </div>
                            <h3 class="mb-0">RD$ <?php echo number_format($balance, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reporte === 'balance'): ?>
<?php
$stmt = $conexion->prepare("SELECT c.nombre, c.saldo_inicial,
        COALESCE((SELECT SUM(monto) FROM fin_entradas WHERE id_cuenta = c.id AND fecha BETWEEN ? AND ?), 0) as entradas,
        COALESCE((SELECT SUM(monto) FROM fin_salidas WHERE id_cuenta = c.id AND fecha BETWEEN ? AND ?), 0) as salidas,
        COALESCE((SELECT SUM(monto) FROM fin_transferencias WHERE id_cuenta_destino = c.id AND fecha BETWEEN ? AND ?), 0) as trans_in,
        COALESCE((SELECT SUM(monto) FROM fin_transferencias WHERE id_cuenta_origen = c.id AND fecha BETWEEN ? AND ?), 0) as trans_out
        FROM fin_cuentas c WHERE c.id_iglesia = ? AND c.activo = 1 ORDER BY c.nombre");
$stmt->bind_param("ssssssssi", $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $iglesia_id);
$stmt->execute();
$datos = $stmt->get_result();
?>
<div class="card card-reporte">
    <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="fas fa-university text-primary me-2"></i>Balance por Cuenta</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Cuenta</th><th class="text-end">Inicial</th><th class="text-end">Entradas</th><th class="text-end">Salidas</th><th class="text-end">Final</th></tr></thead>
                <tbody>
                    <?php 
                    $ti = $te = $ts = $tf = 0;
                    while ($row = $datos->fetch_assoc()): 
                        $ent = (float)$row['entradas'] + (float)$row['trans_in'];
                        $sal = (float)$row['salidas'] + (float)$row['trans_out'];
                        $sf = (float)$row['saldo_inicial'] + $ent - $sal;
                        $ti += (float)$row['saldo_inicial']; $te += $ent; $ts += $sal; $tf += $sf;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                        <td class="text-end">RD$ <?php echo number_format((float)$row['saldo_inicial'], 2); ?></td>
                        <td class="text-end text-success">+<?php echo number_format($ent, 2); ?></td>
                        <td class="text-end text-danger">-<?php echo number_format($sal, 2); ?></td>
                        <td class="text-end"><strong>RD$ <?php echo number_format($sf, 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr><td>TOTALES</td><td class="text-end">RD$ <?php echo number_format($ti, 2); ?></td><td class="text-end text-success">+<?php echo number_format($te, 2); ?></td><td class="text-end text-danger">-<?php echo number_format($ts, 2); ?></td><td class="text-end">RD$ <?php echo number_format($tf, 2); ?></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php $stmt->close(); ?>

<?php elseif ($reporte === 'entradas'): ?>
<?php
$stmt = $conexion->prepare("SELECT c.nombre as categoria, COUNT(e.id) as cantidad, COALESCE(SUM(e.monto), 0) as total
        FROM fin_entradas e LEFT JOIN fin_categorias c ON e.id_categoria = c.id
        WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ? GROUP BY e.id_categoria ORDER BY total DESC");
$stmt->bind_param("iss", $iglesia_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$datos = $stmt->get_result();
?>
<div class="card card-reporte">
    <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="fas fa-arrow-down text-success me-2"></i>Entradas por Categoría</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Categoría</th><th class="text-center">Cant.</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                    <tr>
                        <td><span class="badge bg-success"><?php echo htmlspecialchars($row['categoria'] ?? 'Sin categoría'); ?></span></td>
                        <td class="text-center"><?php echo $row['cantidad']; ?></td>
                        <td class="text-end text-success"><strong>RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold"><tr><td colspan="2">TOTAL</td><td class="text-end text-success">RD$ <?php echo number_format($total, 2); ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
<?php $stmt->close(); ?>

<?php elseif ($reporte === 'salidas'): ?>
<?php
$stmt = $conexion->prepare("SELECT c.nombre as categoria, COUNT(s.id) as cantidad, COALESCE(SUM(s.monto), 0) as total
        FROM fin_salidas s LEFT JOIN fin_categorias c ON s.id_categoria = c.id
        WHERE s.id_iglesia = ? AND s.fecha BETWEEN ? AND ? GROUP BY s.id_categoria ORDER BY total DESC");
$stmt->bind_param("iss", $iglesia_id, $fecha_desde, $fecha_hasta);
$stmt->execute();
$datos = $stmt->get_result();
?>
<div class="card card-reporte">
    <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="fas fa-arrow-up text-danger me-2"></i>Salidas por Categoría</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Categoría</th><th class="text-center">Cant.</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                    <tr>
                        <td><span class="badge bg-danger"><?php echo htmlspecialchars($row['categoria'] ?? 'Sin categoría'); ?></span></td>
                        <td class="text-center"><?php echo $row['cantidad']; ?></td>
                        <td class="text-end text-danger"><strong>RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold"><tr><td colspan="2">TOTAL</td><td class="text-end text-danger">RD$ <?php echo number_format($total, 2); ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
<?php $stmt->close(); ?>

<?php elseif ($reporte === 'miembros'): ?>
<?php
$sql = "SELECT COALESCE(CONCAT(m.nombre, ' ', m.apellido), e.nombre_manual, 'Anónimo') as nombre,
        c.nombre as categoria, COALESCE(SUM(e.monto), 0) as total, COUNT(e.id) as cantidad
        FROM fin_entradas e LEFT JOIN miembros m ON e.id_miembro = m.id
        LEFT JOIN fin_categorias c ON e.id_categoria = c.id
        WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ?";
$params = [$iglesia_id, $fecha_desde, $fecha_hasta]; $types = "iss";
if (!empty($buscar_miembro)) {
    $sql .= " AND (CONCAT(m.nombre, ' ', m.apellido) LIKE ? OR e.nombre_manual LIKE ?)";
    $bp = "%$buscar_miembro%"; $params[] = $bp; $params[] = $bp; $types .= "ss";
}
$sql .= " GROUP BY COALESCE(e.id_miembro, e.nombre_manual), e.id_categoria ORDER BY nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$datos = $stmt->get_result();
?>
<div class="card card-reporte">
    <div class="card-header bg-white py-2"><h6 class="mb-0"><i class="fas fa-users text-primary me-2"></i>Historial por Miembro</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Nombre</th><th>Categoría</th><th class="text-center">Aportes</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                    <tr>
                        <td><i class="fas fa-user text-muted me-1"></i><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['categoria'] ?? 'N/A'); ?></span></td>
                        <td class="text-center"><?php echo $row['cantidad']; ?></td>
                        <td class="text-end"><strong>RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-secondary fw-bold"><tr><td colspan="3">TOTAL</td><td class="text-end">RD$ <?php echo number_format($total, 2); ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>
<?php $stmt->close(); ?>
<?php endif; ?>

<div class="text-center text-muted small mt-3 d-none d-print-block">
    <p>Generado el <?php echo date('d/m/Y H:i'); ?> - Sistema CEND</p>
</div>

<?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>