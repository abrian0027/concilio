<?php
declare(strict_types=1);

// IMPORTANTE: Asegurar que la sesión esté iniciada y se tengan las variables necesarias
session_start();

$page_title = "Gestión de Usuarios";
require_once __DIR__ . '/../includes/header.php';

// Verificar autenticación - usando las variables de sesión establecidas en el sistema
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_nombre'])) {
    header('Location: ../../login.php');
    exit;
}

// Solo Super Admin puede gestionar usuarios
if ($_SESSION['rol_nombre'] !== 'super_admin') {
    echo "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle'></i> No tienes permiso para acceder a este módulo.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Definir variables que el código existente espera, basadas en la sesión
$ROL_NOMBRE = $_SESSION['rol_nombre'];
$USUARIO_ID = $_SESSION['usuario_id'];

require_once __DIR__ . '/../../config/config.php';

// Filtros
$filtro_rol = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
$filtro_conferencia = isset($_GET['conferencia']) ? (int)$_GET['conferencia'] : 0;
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta con filtros
$where = [];
$params = [];
$types = '';

if ($filtro_rol > 0) {
    $where[] = "u.rol_id = ?";
    $params[] = $filtro_rol;
    $types .= 'i';
}

if ($filtro_conferencia > 0) {
    $where[] = "u.conferencia_id = ?";
    $params[] = $filtro_conferencia;
    $types .= 'i';
}

if ($filtro_estado !== '') {
    $where[] = "u.activo = ?";
    $params[] = (int)$filtro_estado;
    $types .= 'i';
}

$sql = "SELECT u.*, 
               r.nombre AS rol_nombre,
               c.nombre AS conferencia_nombre,
               d.nombre AS distrito_nombre,
               i.nombre AS iglesia_nombre,
               m.nombre AS ministerio_nombre
        FROM usuarios u
        LEFT JOIN roles r ON r.id = u.rol_id
        LEFT JOIN conferencias c ON c.id = u.conferencia_id
        LEFT JOIN distritos d ON d.id = u.distrito_id
        LEFT JOIN iglesias i ON i.id = u.iglesia_id
        LEFT JOIN ministerios m ON m.id = u.ministerio_id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY u.nombre, u.apellido";

try {
    if (!empty($params)) {
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $resultado = $conexion->query($sql);
    }
    
    // Obtener roles y conferencias para filtros
    $roles = $conexion->query("SELECT * FROM roles ORDER BY id");
    $conferencias = $conexion->query("SELECT * FROM conferencias WHERE activo = 1 ORDER BY nombre");
    
} catch (Exception $e) {
    error_log("Error al obtener usuarios: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error al cargar los usuarios.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="content-header">
    <h1><i class="fas fa-user-shield"></i> Gestión de Usuarios</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        $success = htmlspecialchars($_GET['success']);
        if ($success === 'creado') echo "Usuario creado exitosamente.";
        if ($success === 'editado') echo "Usuario actualizado exitosamente.";
        if ($success === 'eliminado') echo "Usuario eliminado exitosamente.";
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-filter"></i> Filtros</span>
    </div>
    <div class="card-body">
        <form method="get" action="index.php" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label">Rol</label>
                <select name="rol" class="form-control">
                    <option value="0">-- Todos --</option>
                    <?php while ($r = $roles->fetch_assoc()): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $filtro_rol == $r['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label">Conferencia</label>
                <select name="conferencia" class="form-control">
                    <option value="0">-- Todas --</option>
                    <?php while ($c = $conferencias->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $filtro_conferencia == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">-- Todos --</option>
                    <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activos</option>
                    <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivos</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Listado -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-list"></i> Listado de Usuarios</span>
        <a href="crear.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </a>
    </div>
    <div class="card-body">
        <?php if ($resultado && $resultado->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Asignación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Usuario">
                                    <strong><?php echo htmlspecialchars($u['usuario']); ?></strong>
                                </td>
                                <td data-label="Nombre">
                                    <?php echo htmlspecialchars($u['nombre'] . ' ' . $u['apellido']); ?>
                                </td>
                                <td data-label="Rol">
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                        <?php echo htmlspecialchars($u['rol_nombre']); ?>
                                    </span>
                                </td>
                                <td data-label="Asignación">
                                    <?php 
                                    $asignacion = [];
                                    if ($u['conferencia_nombre']) $asignacion[] = $u['conferencia_nombre'];
                                    if ($u['distrito_nombre']) $asignacion[] = $u['distrito_nombre'];
                                    if ($u['iglesia_nombre']) $asignacion[] = $u['iglesia_nombre'];
                                    if ($u['ministerio_nombre']) $asignacion[] = "Min: " . $u['ministerio_nombre'];
                                    echo !empty($asignacion) ? htmlspecialchars(implode(' → ', $asignacion)) : '<span class="text-muted">Global</span>';
                                    ?>
                                </td>
                                <td data-label="Estado">
                                    <?php if ($u['activo'] == 1): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acciones">
                                    <a href="editar.php?id=<?php echo $u['id']; ?>" 
                                       class="btn btn-sm btn-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($u['id'] != $USUARIO_ID): ?>
                                        <a href="eliminar.php?id=<?php echo $u['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           title="Eliminar"
                                           onclick="return confirm('¿Está seguro de eliminar este usuario?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No hay usuarios registrados con los filtros seleccionados.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>