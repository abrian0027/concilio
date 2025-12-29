<?php
/**
 * Eliminar Zona - Sistema Concilio
 * Soft delete (desactiva la zona)
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Solo pastor o super_admin pueden eliminar
$puede_eliminar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor']);

if (!isset($_SESSION['usuario_id']) || !$puede_eliminar) {
    header("Location: index.php?error=Sin permisos para eliminar");
    exit;
}

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Verificar que la zona existe
$sql = "SELECT id, nombre, iglesia_id FROM zonas WHERE id = ?";
if ($_SESSION['rol_nombre'] !== 'super_admin') {
    $sql .= " AND iglesia_id = " . (int)$_SESSION['iglesia_id'];
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Zona no encontrada");
    exit;
}

$zona = $result->fetch_assoc();

// Desactivar zona (soft delete)
$stmt = $conexion->prepare("UPDATE zonas SET activo = 0 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Quitar zona_id de los miembros que tenían esta zona
    $stmt2 = $conexion->prepare("UPDATE miembros SET zona_id = NULL WHERE zona_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    
    header("Location: index.php?exito=" . urlencode("Zona '" . $zona['nombre'] . "' eliminada correctamente"));
    exit;
} else {
    header("Location: index.php?error=" . urlencode("Error al eliminar: " . $conexion->error));
    exit;
}
