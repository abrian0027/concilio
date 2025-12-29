<?php
/**
 * Guardar Nueva Zona - Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión y permisos
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
$codigo = mb_strtoupper(trim($_POST['codigo'] ?? ''), 'UTF-8');
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

// Determinar iglesia_id
if ($_SESSION['rol_nombre'] === 'super_admin') {
    $iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)($_SESSION['iglesia_id'] ?? 0);
}

// Validaciones
$errores = [];

if (empty($codigo)) {
    $errores[] = "El código es requerido";
}
if (empty($nombre)) {
    $errores[] = "El nombre es requerido";
}
if ($iglesia_id === 0) {
    $errores[] = "Debe seleccionar una iglesia";
}

// Verificar que el código no exista en esa iglesia
if (empty($errores)) {
    $stmt = $conexion->prepare("SELECT id FROM zonas WHERE iglesia_id = ? AND codigo = ?");
    $stmt->bind_param("is", $iglesia_id, $codigo);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = "Ya existe una zona con ese código en esta iglesia";
    }
}

if (!empty($errores)) {
    $error_msg = implode(", ", $errores);
    header("Location: crear.php?error=" . urlencode($error_msg));
    exit;
}

// Insertar zona
try {
    $stmt = $conexion->prepare("INSERT INTO zonas (iglesia_id, codigo, nombre, descripcion) VALUES (?, ?, ?, ?)");
    $desc = $descripcion !== '' ? $descripcion : null;
    $stmt->bind_param("isss", $iglesia_id, $codigo, $nombre, $desc);
    
    if ($stmt->execute()) {
        header("Location: index.php?exito=" . urlencode("Zona '$nombre' creada correctamente"));
        exit;
    } else {
        throw new Exception("Error al guardar: " . $conexion->error);
    }
} catch (Exception $e) {
    header("Location: crear.php?error=" . urlencode($e->getMessage()));
    exit;
}
