<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_editar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_editar) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos
$id = (int)($_POST['id'] ?? 0);
$iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
$nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
$descripcion = trim($_POST['descripcion'] ?? '');

// Validaciones
if ($id === 0 || $iglesia_id === 0 || $nombre === '') {
    header("Location: editar.php?id=$id&error=El nombre es obligatorio");
    exit;
}

// Verificar que no exista otra área con el mismo nombre
$stmt = $conexion->prepare("SELECT id FROM areas_ministeriales WHERE nombre = ? AND (iglesia_id = ? OR iglesia_id IS NULL) AND id != ?");
$stmt->bind_param("sii", $nombre, $iglesia_id, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: editar.php?id=$id&error=Ya existe un área ministerial con ese nombre");
    exit;
}
$stmt->close();

try {
    $descripcion_db = $descripcion !== '' ? $descripcion : null;
    
    $stmt = $conexion->prepare("UPDATE areas_ministeriales SET nombre = ?, descripcion = ? WHERE id = ? AND tipo = 'personalizado'");
    $stmt->bind_param("ssi", $nombre, $descripcion_db, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=editado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al actualizar área ministerial: " . $e->getMessage());
    header("Location: editar.php?id=$id&error=Error al actualizar: " . urlencode($e->getMessage()));
    exit;
}