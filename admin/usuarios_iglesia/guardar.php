<?php
/**
 * Guardar usuario creado por el pastor
 * El usuario se crea a partir de un miembro existente
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar que sea pastor
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'pastor') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos de sesión
$iglesia_id = $_SESSION['iglesia_id'] ?? null;
$conferencia_id = $_SESSION['conferencia_id'] ?? null;
$distrito_id = $_SESSION['distrito_id'] ?? null;

if (!$iglesia_id) {
    header('Location: index.php?error=' . urlencode('No tienes iglesia asignada'));
    exit;
}

// Obtener datos del formulario
$miembro_id = (int)($_POST['miembro_id'] ?? 0);
$rol_id = (int)($_POST['rol_id'] ?? 0);
$ministerio_id = !empty($_POST['ministerio_id']) ? (int)$_POST['ministerio_id'] : null;
$clave = $_POST['clave'] ?? '';
$clave_confirmar = $_POST['clave_confirmar'] ?? '';

// Validaciones básicas
if ($miembro_id <= 0 || $rol_id <= 0) {
    header('Location: crear.php?error=' . urlencode('Debe seleccionar miembro y rol'));
    exit;
}

if (strlen($clave) < 6) {
    header('Location: crear.php?error=' . urlencode('La contraseña debe tener al menos 6 caracteres'));
    exit;
}

if ($clave !== $clave_confirmar) {
    header('Location: crear.php?error=' . urlencode('Las contraseñas no coinciden'));
    exit;
}

// Verificar que el rol sea válido (solo secretaria, tesorero, lider_ministerio)
$stmt = $conexion->prepare("SELECT nombre FROM roles WHERE id = ?");
$stmt->bind_param("i", $rol_id);
$stmt->execute();
$rol_result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rol_result || !in_array($rol_result['nombre'], ['secretaria', 'tesorero', 'lider_ministerio'])) {
    header('Location: crear.php?error=' . urlencode('Rol no válido'));
    exit;
}

$rol_nombre = $rol_result['nombre'];

// Si es líder de ministerio, debe tener ministerio
if ($rol_nombre === 'lider_ministerio' && !$ministerio_id) {
    header('Location: crear.php?error=' . urlencode('Debe seleccionar un ministerio'));
    exit;
}

// Obtener datos del miembro
$stmt = $conexion->prepare("SELECT * FROM miembros WHERE id = ? AND iglesia_id = ?");
$stmt->bind_param("ii", $miembro_id, $iglesia_id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    header('Location: crear.php?error=' . urlencode('Miembro no encontrado'));
    exit;
}

if (empty($miembro['numero_documento'])) {
    header('Location: crear.php?error=' . urlencode('El miembro no tiene cédula registrada'));
    exit;
}

// El usuario será la cédula
$usuario = $miembro['numero_documento'];

// Verificar que no exista usuario con esa cédula en esta iglesia
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? AND iglesia_id = ?");
$stmt->bind_param("si", $usuario, $iglesia_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header('Location: crear.php?error=' . urlencode('Este miembro ya tiene usuario asignado'));
    exit;
}
$stmt->close();

// Hash de la contraseña
$clave_hash = password_hash($clave, PASSWORD_DEFAULT);

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. CREAR USUARIO
    $ministerio_id_esc = $ministerio_id !== null ? (int)$ministerio_id : "NULL";
    
    $sql_usuario = "INSERT INTO usuarios (
                        nombre, apellido, usuario, clave, correo,
                        rol_id, conferencia_id, distrito_id, iglesia_id, ministerio_id, activo
                    ) VALUES (
                        '" . $conexion->real_escape_string($miembro['nombre']) . "', 
                        '" . $conexion->real_escape_string($miembro['apellido']) . "', 
                        '" . $conexion->real_escape_string($usuario) . "', 
                        '" . $conexion->real_escape_string($clave_hash) . "', 
                        '',
                        $rol_id, 
                        " . ($conferencia_id ? (int)$conferencia_id : "NULL") . ", 
                        " . ($distrito_id ? (int)$distrito_id : "NULL") . ", 
                        $iglesia_id, 
                        $ministerio_id_esc, 
                        1
                    )";
    
    if (!$conexion->query($sql_usuario)) {
        throw new Exception("Error al crear usuario: " . $conexion->error);
    }
    
    // 2. ACTUALIZAR MIEMBRO (marcar como líder si aplica)
    $es_lider = in_array($rol_nombre, ['secretaria', 'tesorero']) ? 1 : 0;
    
    $sql_miembro = "UPDATE miembros SET 
                    es_lider = $es_lider,
                    estado_miembro = 'en_plena'";
    
    // Si es líder de ministerio, asignar el ministerio
    if ($rol_nombre === 'lider_ministerio' && $ministerio_id) {
        $sql_miembro .= ", ministerio_id = $ministerio_id";
    }
    
    $sql_miembro .= " WHERE id = $miembro_id";
    
    if (!$conexion->query($sql_miembro)) {
        throw new Exception("Error al actualizar miembro: " . $conexion->error);
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    $mensaje = "Usuario creado exitosamente para " . $miembro['nombre'] . " " . $miembro['apellido'];
    header('Location: index.php?msg=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al crear usuario: " . $e->getMessage());
    header('Location: crear.php?error=' . urlencode($e->getMessage()));
    exit;
}
