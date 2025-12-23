<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_eliminar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_eliminar) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener área
$stmt = $conexion->prepare("SELECT iglesia_id, tipo FROM areas_ministeriales WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$area = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$area) {
    header("Location: index.php?error=Área no encontrada");
    exit;
}

// No permitir eliminar áreas generales
if ($area['tipo'] === 'general') {
    header("Location: index.php?error=No se pueden eliminar las áreas generales");
    exit;
}

$iglesia_id = $area['iglesia_id'];

// Verificar si tiene líderes asignados
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM area_lideres WHERE area_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$lideres = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($lideres['total'] > 0) {
    header("Location: index.php?iglesia_id=$iglesia_id&error=No se puede eliminar, tiene líderes asignados");
    exit;
}

try {
    $stmt = $conexion->prepare("DELETE FROM areas_ministeriales WHERE id = ? AND tipo = 'personalizado'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=eliminado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al eliminar área ministerial: " . $e->getMessage());
    header("Location: index.php?iglesia_id=$iglesia_id&error=Error al eliminar");
    exit;
}
