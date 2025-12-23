<?php
/**
 * Activar/Desactivar usuario
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Solo pastor
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'pastor') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

$iglesia_id = $_SESSION['iglesia_id'] ?? null;
$usuario_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$usuario_id || !$iglesia_id) {
    header('Location: index.php?error=' . urlencode('Datos inválidos'));
    exit;
}

// Verificar que el usuario pertenezca a la iglesia y no sea pastor
$stmt = $conexion->prepare("
    SELECT u.*, r.nombre AS rol_nombre 
    FROM usuarios u 
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.id = ? AND u.iglesia_id = ? AND r.nombre != 'pastor'
");
$stmt->bind_param("ii", $usuario_id, $iglesia_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header('Location: index.php?error=' . urlencode('Usuario no encontrado o sin permisos'));
    exit;
}

// Cambiar estado
$nuevo_estado = $usuario['activo'] ? 0 : 1;
$stmt = $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
$stmt->bind_param("ii", $nuevo_estado, $usuario_id);

if ($stmt->execute()) {
    $accion = $nuevo_estado ? 'activado' : 'desactivado';
    header('Location: index.php?msg=' . urlencode("Usuario {$usuario['nombre']} $accion"));
} else {
    header('Location: index.php?error=' . urlencode('Error al cambiar estado'));
}

$stmt->close();
