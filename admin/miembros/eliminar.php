<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
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

// Verificar que el miembro existe y obtener datos
$stmt = $conexion->prepare("SELECT iglesia_id, foto FROM miembros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    header("Location: index.php?error=Miembro no encontrado");
    exit;
}

// Verificar acceso si no es super_admin
if ($_SESSION['rol_nombre'] !== 'super_admin') {
    if ($miembro['iglesia_id'] != $_SESSION['iglesia_id']) {
        header("Location: index.php?error=No tienes permiso para eliminar este miembro");
        exit;
    }
}

try {
    // Eliminar miembro
    $stmt = $conexion->prepare("DELETE FROM miembros WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Eliminar foto si existe
    if ($miembro['foto']) {
        $ruta_foto = __DIR__ . '/../../uploads/miembros/' . $miembro['foto'];
        if (file_exists($ruta_foto)) {
            unlink($ruta_foto);
        }
    }
    
    header("Location: index.php?success=eliminado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al eliminar miembro: " . $e->getMessage());
    header("Location: index.php?error=Error al eliminar el miembro");
    exit;
}