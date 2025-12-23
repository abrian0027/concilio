<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_crear = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_crear) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos
$iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
$nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
$descripcion = trim($_POST['descripcion'] ?? '');

// Validaciones
if ($iglesia_id === 0 || $nombre === '') {
    header("Location: crear.php?iglesia_id=$iglesia_id&error=El nombre es obligatorio");
    exit;
}

// Verificar que no exista otra área con el mismo nombre en la iglesia
$stmt = $conexion->prepare("SELECT id FROM areas_ministeriales WHERE nombre = ? AND (iglesia_id = ? OR iglesia_id IS NULL)");
$stmt->bind_param("si", $nombre, $iglesia_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Ya existe un área ministerial con ese nombre");
    exit;
}
$stmt->close();

try {
    $descripcion_db = $descripcion !== '' ? $descripcion : null;
    
    $stmt = $conexion->prepare("INSERT INTO areas_ministeriales (nombre, descripcion, tipo, iglesia_id) VALUES (?, ?, 'personalizado', ?)");
    $stmt->bind_param("ssi", $nombre, $descripcion_db, $iglesia_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=creado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al crear área ministerial: " . $e->getMessage());
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Error al guardar: " . urlencode($e->getMessage()));
    exit;
}