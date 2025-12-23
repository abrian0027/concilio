<?php
/**
 * Reportes del Distrito
 * Genera reportes en diferentes formatos
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener cédula del usuario
$usuario_cedula = '';
$stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
if ($row = $stmt->get_result()->fetch_assoc()) {
    $usuario_cedula = $row['usuario'];
}
$stmt->close();

// Buscar el distrito
$distrito = null;
$sql = "SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
        FROM distritos d
        INNER JOIN conferencias c ON d.conferencia_id = c.id
        INNER JOIN pastores p ON d.supervisor_id = p.id
        WHERE p.cedula = ? AND d.activo = 1
        LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $usuario_cedula);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$distrito && in_array($ROL_NOMBRE, ['super_admin', 'super_distrito'])) {
    $distrito_id = $_SESSION['distrito_id'] ?? 0;
    if ($distrito_id > 0) {
        $stmt = $conexion->prepare("SELECT d.*, c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
                                    FROM distritos d
                                    INNER JOIN conferencias c ON d.conferencia_id = c.id
                                    WHERE d.id = ?");
        $stmt->bind_param("i", $distrito_id);
        $stmt->execute();
        $distrito = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$distrito) {
    header('Location: ../panel_generico.php?error=' . urlencode('No tiene distrito asignado'));
    exit;
}

$titulo_pagina = "Reportes del Distrito " . $distrito['codigo'];

// Usar header unificado
$page_title = $titulo_pagina ?? 'Reportes del Distrito';
require_once __DIR__ . '/../includes/header.php';

// Continuar con el contenido (el header ya abrió el contenedor principal)
?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-chart-bar me-2"></i><?php echo $titulo_pagina; ?></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../panel_distrito.php">Panel Distrito</a></li>
                                <li class="breadcrumb-item active">Reportes</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="../panel_distrito.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>

                <!-- Info del distrito -->
                <div class="alert alert-info mb-4">
                    <strong><i class="fas fa-map-marked-alt me-2"></i>Distrito:</strong> 
                    <?php echo htmlspecialchars($distrito['codigo'] . ' - ' . $distrito['nombre']); ?>
                    <span class="ms-3">
                        <strong><i class="fas fa-globe-americas me-1"></i>Conferencia:</strong> 
                        <?php echo htmlspecialchars($distrito['conferencia_nombre']); ?>
                    </span>
                </div>

                <!-- Reportes disponibles -->
                <div class="row">
                    <!-- Reporte de Iglesias -->
                    <div class="col-md-4 mb-4">
                        <div class="card card-reporte h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-church icon-reporte text-primary mb-3"></i>
                                <h5 class="card-title">Iglesias del Distrito</h5>
                                <p class="card-text text-muted">Lista completa de iglesias con pastor y cantidad de miembros</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                <a href="reporte_iglesias.php" class="btn btn-primary">
                                    <i class="fas fa-file-pdf me-1"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Miembros -->
                    <div class="col-md-4 mb-4">
                        <div class="card card-reporte h-100 border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-users icon-reporte text-success mb-3"></i>
                                <h5 class="card-title">Resumen de Miembros</h5>
                                <p class="card-text text-muted">Cantidad de miembros por iglesia y estado de membresía</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                <a href="reporte_miembros.php" class="btn btn-success">
                                    <i class="fas fa-file-pdf me-1"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte Comparativo -->
                    <div class="col-md-4 mb-4">
                        <div class="card card-reporte h-100 border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-bar icon-reporte text-info mb-3"></i>
                                <h5 class="card-title">Comparativo de Iglesias</h5>
                                <p class="card-text text-muted">Gráfico comparativo de miembros entre iglesias</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                <a href="reporte_comparativo.php" class="btn btn-info">
                                    <i class="fas fa-chart-pie me-1"></i>Ver Gráfico
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reporte de Cobertura -->
                    <div class="col-md-4 mb-4">
                        <div class="card card-reporte h-100 border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-user-tie icon-reporte text-warning mb-3"></i>
                                <h5 class="card-title">Cobertura Pastoral</h5>
                                <p class="card-text text-muted">Iglesias con y sin pastor asignado</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                <a href="reporte_cobertura.php" class="btn btn-warning">
                                    <i class="fas fa-file-pdf me-1"></i>Generar PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Exportar a Excel -->
                    <div class="col-md-4 mb-4">
                        <div class="card card-reporte h-100 border-secondary">
                            <div class="card-body text-center">
                                <i class="fas fa-file-excel icon-reporte text-secondary mb-3"></i>
                                <h5 class="card-title">Exportar a Excel</h5>
                                <p class="card-text text-muted">Datos completos del distrito en formato Excel</p>
                            </div>
                            <div class="card-footer bg-white border-0 text-center">
                                <a href="exportar_excel.php" class="btn btn-secondary">
                                    <i class="fas fa-download me-1"></i>Descargar Excel
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Nota -->
                <div class="alert alert-secondary mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Nota:</strong> Los reportes se generan con los datos actualizados al momento de la consulta.
                    Algunos reportes pueden tardar unos segundos en generarse dependiendo de la cantidad de datos.
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
