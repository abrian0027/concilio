<?php
/**
 * Guardar nueva iglesia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crear.php');
    exit;
}

// Obtener datos del formulario
$distrito_id = (int)$_POST['distrito_id'];
$codigo = strtoupper(trim($_POST['codigo']));
$nombre = trim($_POST['nombre']);
$categoria = $_POST['categoria'] ?? 'Circuito';
$direccion = trim($_POST['direccion'] ?? '');
$provincia_id = !empty($_POST['provincia_id']) ? (int)$_POST['provincia_id'] : null;
$municipio_id = !empty($_POST['municipio_id']) ? (int)$_POST['municipio_id'] : null;
$activo = (int)$_POST['activo'];

// Validar categoria
$categorias_validas = ['Circuito', 'Capilla', 'Proyecto Evangelístico'];
if (!in_array($categoria, $categorias_validas)) {
    $categoria = 'Circuito';
}

// Validaciones
if ($distrito_id <= 0 || empty($codigo) || empty($nombre)) {
    header('Location: crear.php?error=' . urlencode('Faltan campos obligatorios'));
    exit;
}

// Verificar que el código no exista
$check = $conexion->prepare("SELECT id FROM iglesias WHERE codigo = ?");
$check->bind_param("s", $codigo);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    header('Location: crear.php?error=' . urlencode('El código ya existe') . '&codigo=' . urlencode($codigo) . '&nombre=' . urlencode($nombre));
    exit;
}
$check->close();

// Verificar si existe la columna categoria
$tiene_categoria = false;
$result = $conexion->query("SHOW COLUMNS FROM iglesias LIKE 'categoria'");
if ($result && $result->num_rows > 0) {
    $tiene_categoria = true;
}

// Verificar si existen las columnas de ubicación
$tiene_ubicacion = false;
$result = $conexion->query("SHOW COLUMNS FROM iglesias LIKE 'provincia_id'");
if ($result && $result->num_rows > 0) {
    $tiene_ubicacion = true;
}

// Insertar iglesia
try {
    if ($tiene_categoria && $tiene_ubicacion) {
        // Versión completa
        $sql = "INSERT INTO iglesias (distrito_id, codigo, nombre, categoria, direccion, provincia_id, municipio_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issssiis", $distrito_id, $codigo, $nombre, $categoria, $direccion, $provincia_id, $municipio_id, $activo);
    } elseif ($tiene_categoria) {
        // Con categoria pero sin ubicación
        $sql = "INSERT INTO iglesias (distrito_id, codigo, nombre, categoria, direccion, activo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issssi", $distrito_id, $codigo, $nombre, $categoria, $direccion, $activo);
    } elseif ($tiene_ubicacion) {
        // Con ubicación pero sin categoria
        $sql = "INSERT INTO iglesias (distrito_id, codigo, nombre, direccion, provincia_id, municipio_id, activo) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssiis", $distrito_id, $codigo, $nombre, $direccion, $provincia_id, $municipio_id, $activo);
    } else {
        // Versión básica
        $sql = "INSERT INTO iglesias (distrito_id, codigo, nombre, direccion, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssi", $distrito_id, $codigo, $nombre, $direccion, $activo);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?msg=' . urlencode('Iglesia creada exitosamente'));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Error al crear iglesia: " . $e->getMessage());
    header('Location: crear.php?error=' . urlencode('Error al guardar: ' . $e->getMessage()));
    exit;
}