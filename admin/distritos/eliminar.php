<?php
/**
 * Eliminar distrito
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
$stmt = $conexion->prepare("SELECT codigo, nombre FROM distritos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$dist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dist) {
    header('Location: index.php?error=' . urlencode('Distrito no encontrado'));
    exit;
}

// Verificar que no tenga iglesias
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$tiene_iglesias = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($tiene_iglesias > 0) {
    header('Location: index.php?error=' . urlencode('No se puede eliminar: tiene ' . $tiene_iglesias . ' iglesias asociadas'));
    exit;
}

// Eliminar
try {
    // Primero eliminar usuarios super_distrito de este distrito
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE distrito_id = ? AND rol_id = 4");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar historial
    $stmt = $conexion->prepare("DELETE FROM distrito_supervisores_historial WHERE distrito_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar distrito
    $stmt = $conexion->prepare("DELETE FROM distritos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Location: index.php?success=' . urlencode('Distrito eliminado: ' . $dist['nombre']));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Error al eliminar distrito: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Error al eliminar: ' . $e->getMessage()));
    exit;
}
