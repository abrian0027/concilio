<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_quitar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_quitar) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$junta_id = (int)($_GET['junta_id'] ?? 0);

if ($id === 0 || $junta_id === 0) {
    header("Location: index.php?error=Datos inválidos");
    exit;
}

// Obtener iglesia_id de la junta
$stmt = $conexion->prepare("SELECT iglesia_id FROM juntas WHERE id = ?");
$stmt->bind_param("i", $junta_id);
$stmt->execute();
$junta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$junta) {
    header("Location: index.php?error=Junta no encontrada");
    exit;
}

$iglesia_id = $junta['iglesia_id'];

try {
    $stmt = $conexion->prepare("UPDATE junta_miembros SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=miembro_eliminado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al quitar miembro de junta: " . $e->getMessage());
    header("Location: index.php?iglesia_id=$iglesia_id&error=Error al eliminar");
    exit;
}