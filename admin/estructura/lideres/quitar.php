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
$iglesia_id = (int)($_GET['iglesia_id'] ?? 0);

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener iglesia_id si no viene
if ($iglesia_id === 0) {
    $stmt = $conexion->prepare("SELECT iglesia_id FROM area_lideres WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        $iglesia_id = $result['iglesia_id'];
    }
}

try {
    $stmt = $conexion->prepare("UPDATE area_lideres SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=eliminado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al quitar líder: " . $e->getMessage());
    header("Location: index.php?iglesia_id=$iglesia_id&error=Error al eliminar");
    exit;
}