<?php
/**
 * Agregar Ministerio de Servicio
 * Sistema Concilio
 * 
 * Agrega un área ministerial a la tabla de ministerios
 * para que pueda tener líderes a nivel de conferencia
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Verificar permisos
$roles_permitidos = array('super_admin', 'obispo', 'super_conferencia');
$rol = $_SESSION['rol_nombre'] ?? '';
if (!in_array($rol, $roles_permitidos)) {
    header('Location: index.php?error=' . urlencode('Sin permisos para esta acción'));
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=' . urlencode('Método no permitido'));
    exit;
}

// Obtener datos
$area_id = isset($_POST['area_id']) ? (int)$_POST['area_id'] : 0;
$conferencia = isset($_POST['conferencia']) ? (int)$_POST['conferencia'] : 0;

if ($area_id <= 0) {
    header('Location: index.php?conferencia=' . $conferencia . '&error=' . urlencode('Debe seleccionar un ministerio'));
    exit;
}

// Obtener información del área ministerial
$stmt = $conexion->prepare("SELECT id, nombre, descripcion FROM areas_ministeriales WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $area_id);
$stmt->execute();
$area = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$area) {
    header('Location: index.php?conferencia=' . $conferencia . '&error=' . urlencode('Área ministerial no encontrada'));
    exit;
}

// Verificar que no exista ya en ministerios
$stmt = $conexion->prepare("SELECT id FROM ministerios WHERE nombre = ? AND activo = 1");
$stmt->bind_param("s", $area['nombre']);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existe) {
    header('Location: index.php?conferencia=' . $conferencia . '&error=' . urlencode('Este ministerio ya existe'));
    exit;
}

// Insertar en la tabla ministerios
$stmt = $conexion->prepare("INSERT INTO ministerios (nombre, descripcion, activo) VALUES (?, ?, 1)");
$stmt->bind_param("ss", $area['nombre'], $area['descripcion']);

if ($stmt->execute()) {
    $nuevo_id = $stmt->insert_id;
    $stmt->close();
    
    // Registrar en auditoría si existe
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_nombre = $_SESSION['nombre'] ?? 'Sistema';
    $descripcion = "Agregó ministerio de servicio: " . $area['nombre'];
    $sql_audit = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, tabla_afectada, registro_id, descripcion, ip_address) 
                  VALUES (?, ?, 'crear', 'ministerios_conf', 'ministerios', ?, ?, ?)";
    $stmt_audit = $conexion->prepare($sql_audit);
    if ($stmt_audit) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt_audit->bind_param("isiss", $usuario_id, $usuario_nombre, $nuevo_id, $descripcion, $ip);
        $stmt_audit->execute();
        $stmt_audit->close();
    }
    
    header('Location: index.php?conferencia=' . $conferencia . '&success=' . urlencode('Ministerio "' . $area['nombre'] . '" agregado correctamente. Ahora puede asignar líderes.'));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    header('Location: index.php?conferencia=' . $conferencia . '&error=' . urlencode('Error al agregar: ' . $error));
    exit;
}
