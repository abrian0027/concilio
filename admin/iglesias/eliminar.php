<?php
declare(strict_types=1);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo Super Admin
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header("Location: ../panel_superadmin.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Obtener y validar ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php?error=" . urlencode("ID no válido"));
    exit;
}

try {
    // Verificar si tiene miembros asociados
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();

    if ($row['total'] > 0) {
        header("Location: index.php?error=" . urlencode("No se puede eliminar. La iglesia tiene {$row['total']} miembro(s) asociado(s)."));
        exit;
    }

    // Si no tiene miembros, eliminar
    $stmt = $conexion->prepare("DELETE FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: index.php?success=eliminada");
} catch (Exception $e) {
    error_log("Error al eliminar iglesia: " . $e->getMessage());
    header("Location: index.php?error=" . urlencode("Error al eliminar. Intente nuevamente."));
}

exit;