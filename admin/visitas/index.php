<?php
/**
 * Listado de Visitas - Sistema Concilio
 * 100% Responsivo - Bootstrap 5
 */

$page_title = "Visitas";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Verificar si la tabla visitas existe
$tabla_existe = $conexion->query("SHOW TABLES LIKE 'visitas'");
if ($tabla_existe->num_rows === 0) {
    ?>
    <div class="content-header">
        <h1><i class="fas fa-user-plus text-primary"></i> Visitas</h1>
    </div>
    <div class="alert alert-warning">
        <h4><i class="fas fa-database me-2"></i> Tabla no encontrada</h4>
        <p>La tabla <code>visitas</code> no existe en la base de datos. Para crear el módulo de visitas, ejecuta el siguiente SQL:</p>
        <hr>
        <pre style="background:#f8f9fa; padding:15px; border-radius:5px; overflow-x:auto; font-size:12px;">
-- Ejecutar en phpMyAdmin o MySQL:
CREATE TABLE IF NOT EXISTS visitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iglesia_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    sexo ENUM('M', 'F') NOT NULL,
    nacionalidad_id INT NULL,
    telefono VARCHAR(20) NULL,
    categoria ENUM('damas', 'caballeros', 'jovenes', 'jovencitos', 'ninos') NOT NULL,
    invitado_por INT NULL COMMENT 'ID del miembro que invitó',
    fecha_visita DATE NOT NULL,
    observaciones TEXT NULL,
    convertido_miembro TINYINT(1) DEFAULT 0,
    miembro_id INT NULL COMMENT 'ID del miembro si fue convertido',
    fecha_conversion DATE NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (iglesia_id) REFERENCES iglesias(id),
    FOREIGN KEY (nacionalidad_id) REFERENCES nacionalidades(id),
    FOREIGN KEY (invitado_por) REFERENCES miembros(id),
    FOREIGN KEY (miembro_id) REFERENCES miembros(id),
    INDEX idx_iglesia (iglesia_id),
    INDEX idx_categoria (categoria),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_visita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        </pre>
        <p class="mt-3 mb-0">
            <a href="index.php" class="btn btn-primary"><i class="fas fa-sync"></i> Recargar después de crear la tabla</a>
        </p>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Solo pastor, secretaria, tesorero o super_admin pueden ver
$puede_ver = in_array($ROL_NOMBRE, ['super_admin', 'super_conferencia', 'super_distrito', 'pastor', 'secretaria', 'tesorero']);

if (!$puede_ver) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Permisos de acciones
$puede_crear = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);
$puede_eliminar = in_array($ROL_NOMBRE, ['super_admin', 'pastor']);
$puede_convertir = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Construir consulta base
$where = [];
$params = [];
$types = '';

// Filtrar por iglesia según rol
if ($ROL_NOMBRE === 'super_admin') {
    // Ver todas las visitas
} elseif ($ROL_NOMBRE === 'super_conferencia') {
    $where[] = "i.distrito_id IN (SELECT id FROM distritos WHERE conferencia_id = ?)";
    $params[] = $CONFERENCIA_ID;
    $types .= 'i';
} elseif ($ROL_NOMBRE === 'super_distrito') {
    $where[] = "i.distrito_id = ?";
    $params[] = $DISTRITO_ID;
    $types .= 'i';
} else {
    // Roles de iglesia local
    $where[] = "v.iglesia_id = ?";
    $params[] = $IGLESIA_ID;
    $types .= 'i';
}

// Solo visitas no convertidas por defecto
if (!isset($_GET['mostrar_convertidos'])) {
    $where[] = "v.convertido_miembro = 0";
}

// Filtro por categoría
if ($filtro_categoria !== '') {
    $where[] = "v.categoria = ?";
    $params[] = $filtro_categoria;
    $types .= 's';
}

// Filtro por estado
if ($filtro_estado !== '') {
    $where[] = "v.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

// Búsqueda
if ($buscar !== '') {
    $where[] = "(v.nombre LIKE ? OR v.apellido LIKE ? OR v.telefono LIKE ?)";
    $buscar_like = "%$buscar%";
    $params[] = $buscar_like;
    $params[] = $buscar_like;
    $params[] = $buscar_like;
    $types .= 'sss';
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Consulta principal
$sql = "SELECT v.*, 
               n.nombre AS nacionalidad_nombre,
               CONCAT(inv.nombre, ' ', inv.apellido) AS invitado_por_nombre,
               i.nombre AS iglesia_nombre
        FROM visitas v
        LEFT JOIN nacionalidades n ON n.id = v.nacionalidad_id
        LEFT JOIN miembros inv ON inv.id = v.invitado_por
        LEFT JOIN iglesias i ON i.id = v.iglesia_id
        $where_sql
        ORDER BY v.creado_en DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$visitas = $result->fetch_all(MYSQLI_ASSOC);
$total_visitas = count($visitas);

// Mapeo de categorías
$categorias = [
    'damas' => ['texto' => 'Damas', 'icono' => 'fa-female', 'color' => 'bg-pink'],
    'caballeros' => ['texto' => 'Caballeros', 'icono' => 'fa-male', 'color' => 'bg-primary'],
    'jovenes' => ['texto' => 'Jóvenes', 'icono' => 'fa-user-graduate', 'color' => 'bg-success'],
    'jovencitos' => ['texto' => 'Jovencitos', 'icono' => 'fa-child', 'color' => 'bg-info'],
    'ninos' => ['texto' => 'Niños', 'icono' => 'fa-baby', 'color' => 'bg-warning']
];

// Mensajes
$mensaje_exito = $_GET['exito'] ?? '';
$mensaje_error = $_GET['error'] ?? '';
?>

<style>
    .bg-pink { background-color: #e91e8c !important; }
    .card-visita { transition: transform 0.2s, box-shadow 0.2s; }
    .card-visita:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .badge-categoria { font-size: 0.75rem; padding: 0.4em 0.8em; }
    .btn-convertir { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; }
    .btn-convertir:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
    .visita-avatar { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: white; }
    .table-visitas td { vertical-align: middle; }
    
    /* Responsivo */
    @media (max-width: 767.98px) {
        .table-visitas thead { display: none; }
        .table-visitas tbody tr { display: block; margin-bottom: 1rem; border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem; }
        .table-visitas tbody td { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border: none; }
        .table-visitas tbody td::before { content: attr(data-label); font-weight: 600; color: #6c757d; }
        .table-visitas tbody td:last-child { justify-content: flex-end; }
        .table-visitas tbody td:last-child::before { display: none; }
    }
</style>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0"><i class="fas fa-user-friends text-primary"></i> Visitas</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a href="formulario_imprimible.php" target="_blank" class="btn btn-outline-secondary" title="Formulario para imprimir">
                <i class="fas fa-print"></i> <span class="d-none d-sm-inline">Imprimir Formulario</span>
            </a>
            <?php if ($puede_crear): ?>
            <a href="crear.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Nueva Visita</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($mensaje_exito): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($mensaje_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Buscar</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, apellido o teléfono..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $key => $cat): ?>
                    <option value="<?php echo $key; ?>" <?php echo $filtro_categoria === $key ? 'selected' : ''; ?>>
                        <?php echo $cat['texto']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="activo" <?php echo $filtro_estado === 'activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="inactivo" <?php echo $filtro_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <div class="form-check">
                    <input type="checkbox" name="mostrar_convertidos" value="1" class="form-check-input" id="chkConvertidos" <?php echo isset($_GET['mostrar_convertidos']) ? 'checked' : ''; ?>>
                    <label class="form-check-label small" for="chkConvertidos">Ver convertidos</label>
                </div>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h4 mb-0"><?php echo $total_visitas; ?></div>
                        <small class="opacity-75">Total Visitas</small>
                    </div>
                    <i class="fas fa-user-friends fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php
    // Contar por categorías
    $conteo_categorias = [];
    foreach ($visitas as $v) {
        $cat = $v['categoria'];
        if (!isset($conteo_categorias[$cat])) $conteo_categorias[$cat] = 0;
        $conteo_categorias[$cat]++;
    }
    foreach ($categorias as $key => $cat):
        $cantidad = $conteo_categorias[$key] ?? 0;
    ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 <?php echo $cat['color']; ?> text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="h4 mb-0"><?php echo $cantidad; ?></div>
                        <small class="opacity-75"><?php echo $cat['texto']; ?></small>
                    </div>
                    <i class="fas <?php echo $cat['icono']; ?> fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabla de visitas -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list"></i> Listado de Visitas</span>
        <span class="badge bg-secondary"><?php echo $total_visitas; ?> registros</span>
    </div>
    <div class="card-body p-0">
        <?php if ($total_visitas === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-user-friends fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No hay visitas registradas</h5>
            <?php if ($puede_crear): ?>
            <a href="crear.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus"></i> Registrar Primera Visita
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-visitas mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visita</th>
                        <th>Categoría</th>
                        <th>Teléfono</th>
                        <th>Invitado por</th>
                        <th>Fecha Visita</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitas as $visita): 
                        $cat = $categorias[$visita['categoria']] ?? ['texto' => 'N/A', 'icono' => 'fa-user', 'color' => 'bg-secondary'];
                        $inicial = strtoupper(substr($visita['nombre'], 0, 1));
                        $color_avatar = $visita['sexo'] === 'F' ? '#e91e8c' : '#1a56db';
                    ?>
                    <tr>
                        <td data-label="Visita">
                            <div class="d-flex align-items-center gap-2">
                                <div class="visita-avatar" style="background-color: <?php echo $color_avatar; ?>">
                                    <?php echo $inicial; ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($visita['nombre'] . ' ' . $visita['apellido']); ?></div>
                                    <small class="text-muted"><?php echo $visita['nacionalidad_nombre'] ?? 'Sin nacionalidad'; ?></small>
                                </div>
                            </div>
                        </td>
                        <td data-label="Categoría">
                            <span class="badge <?php echo $cat['color']; ?> badge-categoria">
                                <i class="fas <?php echo $cat['icono']; ?> me-1"></i> <?php echo $cat['texto']; ?>
                            </span>
                        </td>
                        <td data-label="Teléfono">
                            <?php if ($visita['telefono']): ?>
                            <a href="tel:<?php echo $visita['telefono']; ?>" class="text-decoration-none">
                                <i class="fas fa-phone text-success me-1"></i><?php echo htmlspecialchars($visita['telefono']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Invitado por">
                            <?php if ($visita['invitado_por_nombre']): ?>
                            <i class="fas fa-user text-primary me-1"></i><?php echo htmlspecialchars($visita['invitado_por_nombre']); ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Fecha">
                            <i class="fas fa-calendar text-muted me-1"></i>
                            <?php echo date('d/m/Y', strtotime($visita['fecha_visita'])); ?>
                        </td>
                        <td data-label="Estado">
                            <?php if ($visita['convertido_miembro']): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Convertido</span>
                            <?php elseif ($visita['estado'] === 'activo'): ?>
                            <span class="badge bg-primary">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Acciones" class="text-end">
                            <div class="btn-group btn-group-sm">
                                <?php if (!$visita['convertido_miembro'] && $puede_convertir): ?>
                                <a href="convertir.php?id=<?php echo $visita['id']; ?>" 
                                   class="btn btn-convertir text-white" 
                                   title="Convertir a Miembro"
                                   onclick="return confirm('¿Convertir esta visita en miembro? Se creará con estado En Preparación.')">
                                    <i class="fas fa-user-plus"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puede_editar && !$visita['convertido_miembro']): ?>
                                <a href="editar.php?id=<?php echo $visita['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puede_eliminar && !$visita['convertido_miembro']): ?>
                                <a href="eliminar.php?id=<?php echo $visita['id']; ?>" 
                                   class="btn btn-outline-danger" 
                                   title="Eliminar"
                                   onclick="return confirm('¿Eliminar esta visita?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($visita['convertido_miembro'] && $visita['miembro_id']): ?>
                                <a href="../miembros/ver.php?id=<?php echo $visita['miembro_id']; ?>" class="btn btn-outline-success" title="Ver Miembro">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
