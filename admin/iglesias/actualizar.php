<?php
/**
 * Actualizar iglesia existente
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
    header('Location: index.php');
    exit;
}

// Obtener datos del formulario
$id = (int)$_POST['id'];
$distrito_id = (int)$_POST['distrito_id'];
$codigo = strtoupper(trim($_POST['codigo']));
$nombre = trim($_POST['nombre']);
$categoria = $_POST['categoria'] ?? 'Circuito';
$direccion = trim($_POST['direccion'] ?? '');
$activo = (int)$_POST['activo'];

// Campos opcionales de ubicación
$provincia_id = !empty($_POST['provincia_id']) ? (int)$_POST['provincia_id'] : null;
$municipio_id = !empty($_POST['municipio_id']) ? (int)$_POST['municipio_id'] : null;

// Validar categoria
$categorias_validas = ['Circuito', 'Capilla', 'Proyecto Evangelístico'];
if (!in_array($categoria, $categorias_validas)) {
    $categoria = 'Circuito';
}

// Validaciones
if ($id <= 0 || $distrito_id <= 0 || empty($codigo) || empty($nombre)) {
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Faltan campos obligatorios'));
    exit;
}

// Verificar que el código no exista en otra iglesia
$check = $conexion->prepare("SELECT id FROM iglesias WHERE codigo = ? AND id != ?");
$check->bind_param("si", $codigo, $id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('El código ya existe en otra iglesia'));
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

// Actualizar iglesia
try {
    if ($tiene_categoria && $tiene_ubicacion) {
        // Versión completa
        $sql = "UPDATE iglesias SET 
                distrito_id = ?, 
                codigo = ?, 
                nombre = ?, 
                categoria = ?,
                direccion = ?, 
                provincia_id = ?, 
                municipio_id = ?, 
                activo = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issssiiii", $distrito_id, $codigo, $nombre, $categoria, $direccion, $provincia_id, $municipio_id, $activo, $id);
    } elseif ($tiene_categoria) {
        // Con categoria pero sin ubicación
        $sql = "UPDATE iglesias SET 
                distrito_id = ?, 
                codigo = ?, 
                nombre = ?, 
                categoria = ?,
                direccion = ?, 
                activo = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issssii", $distrito_id, $codigo, $nombre, $categoria, $direccion, $activo, $id);
    } elseif ($tiene_ubicacion) {
        // Con ubicación pero sin categoria
        $sql = "UPDATE iglesias SET 
                distrito_id = ?, 
                codigo = ?, 
                nombre = ?, 
                direccion = ?, 
                provincia_id = ?, 
                municipio_id = ?, 
                activo = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssiii", $distrito_id, $codigo, $nombre, $direccion, $provincia_id, $municipio_id, $activo, $id);
    } else {
        // Versión básica
        $sql = "UPDATE iglesias SET 
                distrito_id = ?, 
                codigo = ?, 
                nombre = ?, 
                direccion = ?, 
                activo = ?
                WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssii", $distrito_id, $codigo, $nombre, $direccion, $activo, $id);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?msg=' . urlencode('Iglesia actualizada exitosamente'));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Error al actualizar iglesia: " . $e->getMessage());
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error al actualizar: ' . $e->getMessage()));
    exit;
}