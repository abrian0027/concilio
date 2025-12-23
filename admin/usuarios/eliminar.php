<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header("Location: index.php?error=Sin permisos");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// No permitir eliminar el propio usuario
if ($id === (int)$_SESSION['usuario_id']) {
    header("Location: index.php?error=No puedes eliminar tu propio usuario");
    exit;
}

try {
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        header("Location: index.php?success=eliminado");
    } else {
        header("Location: index.php?error=Usuario no encontrado");
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error al eliminar usuario: " . $e->getMessage());
    header("Location: index.php?error=Error al eliminar el usuario");
}
exit;