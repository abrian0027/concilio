<?php
/**
 * Eliminar conferencia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?error=' . urlencode('ID no válido'));
    exit;
}

// Verificar que existe
$stmt = $conexion->prepare("SELECT codigo, nombre FROM conferencias WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$conf = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conf) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

// Verificar que no tenga distritos
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM distritos WHERE conferencia_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$tiene_distritos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($tiene_distritos > 0) {
    header('Location: index.php?error=' . urlencode('No se puede eliminar: tiene ' . $tiene_distritos . ' distritos asociados'));
    exit;
}

// Verificar que no tenga pastores
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM pastores WHERE conferencia_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$tiene_pastores = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($tiene_pastores > 0) {
    header('Location: index.php?error=' . urlencode('No se puede eliminar: tiene ' . $tiene_pastores . ' pastores asociados'));
    exit;
}

// Eliminar
try {
    // Primero eliminar usuarios super_conferencia de esta conferencia
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE conferencia_id = ? AND rol_id = 3");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar historial
    $stmt = $conexion->prepare("DELETE FROM conferencia_superintendentes_historial WHERE conferencia_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar conferencia
    $stmt = $conexion->prepare("DELETE FROM conferencias WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: index.php?success=' . urlencode('Conferencia eliminada: ' . $conf['nombre']));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Error al eliminar conferencia: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Error al eliminar: ' . $e->getMessage()));
    exit;
}
