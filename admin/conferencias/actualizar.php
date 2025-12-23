<?php
/**
 * Actualizar conferencia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos
$id = (int)($_POST['id'] ?? 0);
$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones
if ($id <= 0 || empty($codigo) || empty($nombre)) {
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Datos incompletos'));
    exit;
}

// Verificar código único (excluyendo el actual)
$stmt = $conexion->prepare("SELECT id FROM conferencias WHERE codigo = ? AND id != ?");
$stmt->bind_param("si", $codigo, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Ya existe otra conferencia con ese código'));
    exit;
}
$stmt->close();

// Actualizar
try {
    $stmt = $conexion->prepare("UPDATE conferencias SET codigo = ?, nombre = ?, telefono = ?, correo = ?, activo = ? WHERE id = ?");
    $telefono_db = !empty($telefono) ? $telefono : null;
    $correo_db = !empty($correo) ? $correo : null;
    $stmt->bind_param("ssssii", $codigo, $nombre, $telefono_db, $correo_db, $activo, $id);
    
    if ($stmt->execute()) {
        header('Location: index.php?success=' . urlencode('Conferencia actualizada exitosamente'));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Error al actualizar conferencia: " . $e->getMessage());
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
