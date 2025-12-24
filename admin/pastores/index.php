<?php
declare(strict_types=1);

$page_title = "Gestión de Pastores";
require_once __DIR__ . '/../includes/header.php';

// Solo Super Admin y Obispo pueden ver todos los pastores
if (!in_array($ROL_NOMBRE, ['super_admin', 'obispo', 'super_conferencia'])) {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Filtros
$filtro_conferencia = $_GET['conferencia'] ?? '';
$filtro_orden = $_GET['orden'] ?? '';
$filtro_estado = $_GET['estado'] ?? '1';
$buscar = trim($_GET['buscar'] ?? '');

// Paginación
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($filtro_estado !== '') {
    $where .= " AND p.activo = ?";
    $params[] = (int)$filtro_estado;
    $types .= "i";
}

if ($filtro_conferencia !== '') {
    $where .= " AND p.conferencia_id = ?";
    $params[] = (int)$filtro_conferencia;
    $types .= "i";
}

if ($filtro_orden !== '') {
    $where .= " AND p.orden_ministerial = ?";
    $params[] = $filtro_orden;
    $types .= "s";
}

if ($buscar !== '') {
    $where .= " AND (p.nombre LIKE ? OR p.apellido LIKE ? OR p.cedula LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $types .= "sss";
}

// Restricción por conferencia si es super_conferencia
if ($ROL_NOMBRE === 'super_conferencia' && isset($_SESSION['conferencia_id'])) {
    $where .= " AND p.conferencia_id = ?";
    $params[] = $_SESSION['conferencia_id'];
    $types .= "i";
}

// Contar total
$sql_count = "SELECT COUNT(*) as total FROM pastores p $where";
$stmt_count = $conexion->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);
$stmt_count->close();

// Obtener pastores
$sql = "
    SELECT p.*, 
           n.nombre AS nacionalidad,
           c.nombre AS conferencia,
           (SELECT GROUP_CONCAT(i.nombre SEPARATOR ', ') 
            FROM pastor_iglesias pi 
            INNER JOIN iglesias i ON pi.iglesia_id = i.id 
            WHERE pi.pastor_id = p.id AND pi.activo = 1) AS iglesias_asignadas,
           (SELECT COUNT(*) FROM pastor_iglesias pi WHERE pi.pastor_id = p.id AND pi.activo = 1) AS num_iglesias
    FROM pastores p
    LEFT JOIN nacionalidades n ON p.nacionalidad_id = n.id
    LEFT JOIN conferencias c ON p.conferencia_id = c.id
    $where
    ORDER BY p.apellido, p.nombre
    LIMIT ? OFFSET ?
";

$params[] = $por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$pastores = $stmt->get_result();

// Obtener conferencias para filtro
$conferencias = $conexion->query("SELECT id, nombre FROM conferencias WHERE activo = 1 ORDER BY nombre");
?>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h1><i class="fas fa-user-tie"></i> Gestión de Pastores</h1>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Pastor
        </a>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-body">
        <form method="get" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label"><i class="fas fa-search"></i> Buscar</label>
                <input type="text" name="buscar" class="form-control" 
                       placeholder="Nombre, apellido o cédula..."
                       value="<?php echo htmlspecialchars($buscar); ?>">
            </div>
            
            <div style="min-width: 180px;">
                <label class="form-label"><i class="fas fa-users"></i> Conferencia</label>
                <select name="conferencia" class="form-control">
                    <option value="">Todas</option>
                    <?php while ($conf = $conferencias->fetch_assoc()): ?>
                        <option value="<?php echo $conf['id']; ?>" 
                            <?php echo ($filtro_conferencia == $conf['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($conf['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="min-width: 180px;">
                <label class="form-label"><i class="fas fa-graduation-cap"></i> Orden Ministerial</label>
                <select name="orden" class="form-control">
                    <option value="">Todos</option>
                    <option value="Candidato Ministerial" <?php echo ($filtro_orden == 'Candidato Ministerial') ? 'selected' : ''; ?>>Candidato Ministerial</option>
                    <option value="Diácono" <?php echo ($filtro_orden == 'Diácono') ? 'selected' : ''; ?>>Diácono</option>
                    <option value="Presbítero" <?php echo ($filtro_orden == 'Presbítero') ? 'selected' : ''; ?>>Presbítero</option>
                </select>
            </div>
            
            <div style="min-width: 120px;">
                <label class="form-label"><i class="fas fa-toggle-on"></i> Estado</label>
                <select name="estado" class="form-control">
                    <option value="1" <?php echo ($filtro_estado == '1') ? 'selected' : ''; ?>>Activos</option>
                    <option value="0" <?php echo ($filtro_estado == '0') ? 'selected' : ''; ?>>Inactivos</option>
                    <option value="" <?php echo ($filtro_estado === '') ? 'selected' : ''; ?>>Todos</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas rápidas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="card-body" style="text-align: center; padding: 1rem;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo $total_registros; ?></h3>
            <small>Total Pastores</small>
        </div>
    </div>
</div>

<!-- Tabla de pastores -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Pastores</span>
    </div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if ($pastores->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre Completo</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Orden</th>
                        <th>Años Servicio</th>
                        <th>Iglesia(s)</th>
                        <th>Conferencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pastor = $pastores->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($pastor['foto'])): ?>
                                    <img src="../../uploads/pastores/<?php echo htmlspecialchars($pastor['foto']); ?>" 
                                         alt="Foto" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 2px solid #0dcaf0;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #e0e0e0 0%, #c0c0c0 100%); display: flex; align-items: center; justify-content: center; border: 2px solid #0dcaf0;">
                                        <i class="fas fa-user" style="color: #999;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pastor['nombre'] . ' ' . $pastor['apellido']); ?></strong>
                                <br><small class="text-muted">Edad: <?php echo $pastor['edad'] ?? 'N/A'; ?> años</small>
                            </td>
                            <td><?php echo htmlspecialchars($pastor['cedula']); ?></td>
                            <td><?php echo htmlspecialchars($pastor['telefono']); ?></td>
                            <td>
                                <?php
                                $badge_class = 'secondary';
                                if ($pastor['orden_ministerial'] == 'Presbítero') $badge_class = 'success';
                                elseif ($pastor['orden_ministerial'] == 'Diácono') $badge_class = 'primary';
                                ?>
                                <span class="badge bg-<?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($pastor['orden_ministerial']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <strong><?php echo $pastor['anos_servicio'] ?? 0; ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($pastor['iglesias_asignadas'])): ?>
                                    <small><?php echo htmlspecialchars($pastor['iglesias_asignadas']); ?></small>
                                    <?php if ($pastor['num_iglesias'] > 1): ?>
                                        <br><span class="badge bg-info"><?php echo $pastor['num_iglesias']; ?> iglesias</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted"><em>Sin asignar</em></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($pastor['conferencia'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($pastor['activo']): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="ver.php?id=<?php echo $pastor['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $pastor['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="asignar_iglesia.php?id=<?php echo $pastor['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Asignar Iglesia">
                                        <i class="fas fa-church"></i>
                                    </a>
                                    <a href="ministerios.php?id=<?php echo $pastor['id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Ministerios Conferenciales">
                                        <i class="fas fa-hands-helping"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav style="margin-top: 1rem;">
                    <ul class="pagination" style="display: flex; gap: 0.25rem; flex-wrap: wrap; list-style: none; padding: 0;">
                        <?php if ($pagina > 1): ?>
                            <li>
                                <a href="?pagina=<?php echo ($pagina - 1); ?>&buscar=<?php echo urlencode($buscar); ?>&conferencia=<?php echo $filtro_conferencia; ?>&orden=<?php echo urlencode($filtro_orden); ?>&estado=<?php echo $filtro_estado; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li style="display: flex; align-items: center; padding: 0 1rem;">
                            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                        </li>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <li>
                                <a href="?pagina=<?php echo ($pagina + 1); ?>&buscar=<?php echo urlencode($buscar); ?>&conferencia=<?php echo $filtro_conferencia; ?>&orden=<?php echo urlencode($filtro_orden); ?>&estado=<?php echo $filtro_estado; ?>" 
                                   class="btn btn-sm btn-secondary">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666;">
                <i class="fas fa-user-tie" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p>No se encontraron pastores con los filtros seleccionados.</p>
                <a href="crear.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Registrar Primer Pastor
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>