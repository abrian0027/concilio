<?php
/**
 * Actualizar Zona - Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión y permisos
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
$codigo = mb_strtoupper(trim($_POST['codigo'] ?? ''), 'UTF-8');
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Validaciones
$errores = [];

if (empty($codigo)) {
    $errores[] = "El código es requerido";
}
if (empty($nombre)) {
    $errores[] = "El nombre es requerido";
}

// Verificar que la zona existe y obtener iglesia_id
$sql = "SELECT id, iglesia_id FROM zonas WHERE id = ?";
if ($_SESSION['rol_nombre'] !== 'super_admin') {
    $sql .= " AND iglesia_id = " . (int)$_SESSION['iglesia_id'];
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Zona no encontrada o sin permisos");
    exit;
}

$zona = $result->fetch_assoc();
$iglesia_id = $zona['iglesia_id'];

// Verificar que el código no exista en otra zona de esa iglesia
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id FROM zonas WHERE iglesia_id = ? AND codigo = ? AND id != ?");
    $stmt->bind_param("isi", $iglesia_id, $codigo, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = "Ya existe otra zona con ese código en esta iglesia";
    }
}

if (!empty($errores)) {
    $error_msg = implode(", ", $errores);
    header("Location: editar.php?id=" . $id . "&error=" . urlencode($error_msg));
    exit;
}

// Actualizar zona
try {
    $stmt = $conexion->prepare("UPDATE zonas SET codigo = ?, nombre = ?, descripcion = ?, activo = ? WHERE id = ?");
    $desc = $descripcion !== '' ? $descripcion : null;
    $stmt->bind_param("sssii", $codigo, $nombre, $desc, $activo, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?exito=" . urlencode("Zona '$nombre' actualizada correctamente"));
        exit;
    } else {
        throw new Exception("Error al actualizar: " . $conexion->error);
    }
} catch (Exception $e) {
    header("Location: editar.php?id=" . $id . "&error=" . urlencode($e->getMessage()));
    exit;
}
