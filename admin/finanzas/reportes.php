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

$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$reporte = $_GET['reporte'] ?? 'balance';
$buscar_miembro = trim($_GET['buscar_miembro'] ?? '');

$iglesia_nombre = '';
$miembros_lista = [];

if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $iglesia_nombre = $stmt->get_result()->fetch_assoc()['nombre'] ?? '';
    $stmt->close();
    
    // Lista de miembros para el autocomplete
    $stmt = $conexion->prepare("SELECT DISTINCT COALESCE(CONCAT(m.nombre, ' ', m.apellido), e.nombre_manual) as nombre_completo
            FROM fin_entradas e
            LEFT JOIN miembros m ON e.id_miembro = m.id
            WHERE e.id_iglesia = ?
            ORDER BY nombre_completo");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $miembros_lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$meses_nombre = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 
                 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
?>

<style>
@media print {
    .no-print { display: none !important; }
    .sidebar { display: none !important; }
    .content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    body { background: white !important; }
    .print-header { display: block !important; }
}
.print-header { display: none; text-align: center; margin-bottom: 20px; }
</style>

<div class="content-header no-print">
    <h1><i class="fas fa-chart-bar"></i> Reportes Financieros</h1>
</div>

<?php if ($iglesia_id === 0): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Seleccione una iglesia desde el <a href="index.php">Dashboard</a>.</div>
<?php else: ?>

<div class="card no-print mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="iglesia_id" value="<?php echo $iglesia_id; ?>">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Tipo de Reporte</label>
                <select name="reporte" class="form-select">
                    <option value="balance" <?php echo $reporte === 'balance' ? 'selected' : ''; ?>>Balance por Cuenta</option>
                    <option value="entradas" <?php echo $reporte === 'entradas' ? 'selected' : ''; ?>>Entradas por Categoría</option>
                    <option value="salidas" <?php echo $reporte === 'salidas' ? 'selected' : ''; ?>>Salidas por Categoría</option>
                    <option value="miembros" <?php echo $reporte === 'miembros' ? 'selected' : ''; ?>>Historial por Miembro</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?php echo $fecha_desde; ?>">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $fecha_hasta; ?>">
            </div>
            <?php if ($reporte === 'miembros'): ?>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Buscar Miembro</label>
                <input type="text" name="buscar_miembro" class="form-control" value="<?php echo htmlspecialchars($buscar_miembro); ?>" placeholder="Nombre del miembro..." list="listaMiembros">
                <datalist id="listaMiembros">
                    <?php foreach ($miembros_lista as $m): ?>
                    <option value="<?php echo htmlspecialchars($m['nombre_completo'] ?? ''); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <?php endif; ?>
            <div class="col-lg-2 col-md-6">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generar</button>
                    <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="print-header">
    <h2><?php echo htmlspecialchars($iglesia_nombre); ?></h2>
    <h3><?php echo ucfirst(str_replace('_', ' ', $reporte)); ?></h3>
    <p>Período: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?></p>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-file-alt"></i> 
        <?php 
        switch($reporte) {
            case 'balance': echo 'Balance por Cuenta'; break;
            case 'entradas': echo 'Entradas por Categoría'; break;
            case 'salidas': echo 'Salidas por Categoría'; break;
            case 'miembros': echo 'Historial por Miembro'; break;
        }
        ?>
        </span>
    </div>
    <div class="card-body p-0">
        
        <?php if ($reporte === 'balance'): ?>
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
        <table class="table">
            <thead>
                <tr><th>Cuenta</th><th class="text-right">Saldo Inicial</th><th class="text-right">Entradas</th><th class="text-right">Salidas</th><th class="text-right">Saldo Final</th></tr>
            </thead>
            <tbody>
                <?php 
                $total_inicial = $total_entradas = $total_salidas = $total_final = 0;
                while ($row = $datos->fetch_assoc()): 
                    $entradas = (float)$row['entradas'] + (float)$row['trans_in'];
                    $salidas = (float)$row['salidas'] + (float)$row['trans_out'];
                    $saldo_final = (float)$row['saldo_inicial'] + $entradas - $salidas;
                    $total_inicial += (float)$row['saldo_inicial'];
                    $total_entradas += $entradas;
                    $total_salidas += $salidas;
                    $total_final += $saldo_final;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                    <td class="text-right">RD$ <?php echo number_format((float)$row['saldo_inicial'], 2); ?></td>
                    <td class="text-right text-success">+<?php echo number_format($entradas, 2); ?></td>
                    <td class="text-right text-danger">-<?php echo number_format($salidas, 2); ?></td>
                    <td class="text-right"><strong>RD$ <?php echo number_format($saldo_final, 2); ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr>
                    <td>TOTALES</td>
                    <td class="text-end">RD$ <?php echo number_format($total_inicial, 2); ?></td>
                    <td class="text-end text-success">+<?php echo number_format($total_entradas, 2); ?></td>
                    <td class="text-end text-danger">-<?php echo number_format($total_salidas, 2); ?></td>
                    <td class="text-end">RD$ <?php echo number_format($total_final, 2); ?></td>
                </tr>
            </tfoot>
        </table>
        <?php $stmt->close(); ?>
        
        <?php elseif ($reporte === 'entradas'): ?>
        <?php
        $stmt = $conexion->prepare("SELECT c.nombre as categoria, COUNT(e.id) as cantidad, COALESCE(SUM(e.monto), 0) as total
                FROM fin_entradas e
                LEFT JOIN fin_categorias c ON e.id_categoria = c.id
                WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ?
                GROUP BY e.id_categoria ORDER BY total DESC");
        $stmt->bind_param("iss", $iglesia_id, $fecha_desde, $fecha_hasta);
        $stmt->execute();
        $datos = $stmt->get_result();
        ?>
        <table class="table">
            <thead><tr><th>Categoría</th><th class="text-center">Cantidad</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                <tr>
                    <td><span class="badge bg-success"><?php echo htmlspecialchars($row['categoria'] ?? 'Sin categoría'); ?></span></td>
                    <td class="text-center"><?php echo $row['cantidad']; ?></td>
                    <td class="text-end"><strong class="text-success">RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr><td colspan="2">TOTAL ENTRADAS</td><td class="text-end text-success">RD$ <?php echo number_format($total, 2); ?></td></tr>
            </tfoot>
        </table>
        <?php $stmt->close(); ?>
        
        <?php elseif ($reporte === 'salidas'): ?>
        <?php
        $stmt = $conexion->prepare("SELECT c.nombre as categoria, COUNT(s.id) as cantidad, COALESCE(SUM(s.monto), 0) as total
                FROM fin_salidas s
                LEFT JOIN fin_categorias c ON s.id_categoria = c.id
                WHERE s.id_iglesia = ? AND s.fecha BETWEEN ? AND ?
                GROUP BY s.id_categoria ORDER BY total DESC");
        $stmt->bind_param("iss", $iglesia_id, $fecha_desde, $fecha_hasta);
        $stmt->execute();
        $datos = $stmt->get_result();
        ?>
        <table class="table">
            <thead><tr><th>Categoría</th><th class="text-center">Cantidad</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                <tr>
                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($row['categoria'] ?? 'Sin categoría'); ?></span></td>
                    <td class="text-center"><?php echo $row['cantidad']; ?></td>
                    <td class="text-end"><strong class="text-danger">RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr><td colspan="2">TOTAL SALIDAS</td><td class="text-end text-danger">RD$ <?php echo number_format($total, 2); ?></td></tr>
            </tfoot>
        </table>
        <?php $stmt->close(); ?>
        
        <?php elseif ($reporte === 'miembros'): ?>
        <?php
        $sql = "SELECT 
                COALESCE(CONCAT(m.nombre, ' ', m.apellido), e.nombre_manual, 'Anónimo') as nombre,
                c.nombre as categoria,
                COALESCE(SUM(e.monto), 0) as total,
                COUNT(e.id) as cantidad
                FROM fin_entradas e
                LEFT JOIN miembros m ON e.id_miembro = m.id
                LEFT JOIN fin_categorias c ON e.id_categoria = c.id
                WHERE e.id_iglesia = ? AND e.fecha BETWEEN ? AND ?";
        
        $params = [$iglesia_id, $fecha_desde, $fecha_hasta];
        $types = "iss";
        
        if (!empty($buscar_miembro)) {
            $sql .= " AND (CONCAT(m.nombre, ' ', m.apellido) LIKE ? OR e.nombre_manual LIKE ?)";
            $buscar_param = "%$buscar_miembro%";
            $params[] = $buscar_param;
            $params[] = $buscar_param;
            $types .= "ss";
        }
        
        $sql .= " GROUP BY COALESCE(e.id_miembro, e.nombre_manual), e.id_categoria ORDER BY nombre, categoria";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $datos = $stmt->get_result();
        ?>
        <table class="table">
            <thead><tr><th>Nombre</th><th>Categoría</th><th class="text-center">Aportes</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                <?php $total = 0; while ($row = $datos->fetch_assoc()): $total += (float)$row['total']; ?>
                <tr>
                    <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['categoria'] ?? 'Sin categoría'); ?></span></td>
                    <td class="text-center"><?php echo $row['cantidad']; ?></td>
                    <td class="text-end"><strong>RD$ <?php echo number_format((float)$row['total'], 2); ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot class="table-secondary fw-bold">
                <tr><td colspan="3">TOTAL APORTES</td><td class="text-end">RD$ <?php echo number_format($total, 2); ?></td></tr>
            </tfoot>
        </table>
        <?php $stmt->close(); ?>
        <?php endif; ?>
        
    </div>
</div>

<div class="print-header mt-4 small">
    <p>Generado el <?php echo date('d/m/Y H:i'); ?> - Sistema CEND</p>
</div>

<div class="mt-3 no-print">
    <a href="index.php?iglesia_id=<?php echo $iglesia_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>