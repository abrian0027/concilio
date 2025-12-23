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

// Obtener período
$stmt = $conexion->prepare("SELECT iglesia_id, activo FROM periodos_iglesia WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$periodo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$periodo) {
    header("Location: index.php?error=Período no encontrado");
    exit;
}

// No permitir eliminar período activo
if ($periodo['activo']) {
    header("Location: index.php?iglesia_id=" . $periodo['iglesia_id'] . "&error=No se puede eliminar el período activo");
    exit;
}

// Verificar si tiene datos asociados
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM juntas WHERE periodo_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$juntas = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM area_lideres WHERE periodo_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$lideres = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($juntas['total'] > 0 || $lideres['total'] > 0) {
    header("Location: index.php?iglesia_id=" . $periodo['iglesia_id'] . "&error=No se puede eliminar, tiene juntas o líderes asociados");
    exit;
}

try {
    $stmt = $conexion->prepare("DELETE FROM periodos_iglesia WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=" . $periodo['iglesia_id'] . "&success=eliminado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al eliminar período: " . $e->getMessage());
    header("Location: index.php?iglesia_id=" . $periodo['iglesia_id'] . "&error=Error al eliminar");
    exit;
}
