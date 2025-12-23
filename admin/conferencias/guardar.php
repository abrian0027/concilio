<?php
/**
 * Guardar nueva conferencia
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
$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones
if (empty($codigo) || empty($nombre)) {
    header('Location: crear.php?error=' . urlencode('Código y nombre son obligatorios'));
    exit;
}

// Verificar código único
$stmt = $conexion->prepare("SELECT id FROM conferencias WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header('Location: crear.php?error=' . urlencode('Ya existe una conferencia con ese código'));
    exit;
}
$stmt->close();

// Insertar
try {
    $stmt = $conexion->prepare("INSERT INTO conferencias (codigo, nombre, telefono, correo, activo) VALUES (?, ?, ?, ?, ?)");
    $telefono_db = !empty($telefono) ? $telefono : null;
    $correo_db = !empty($correo) ? $correo : null;
    $stmt->bind_param("ssssi", $codigo, $nombre, $telefono_db, $correo_db, $activo);
    
    if ($stmt->execute()) {
        $conferencia_id = $conexion->insert_id;
        header('Location: asignar_superintendente.php?id=' . $conferencia_id . '&nuevo=1');
        exit;
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Error al crear conferencia: " . $e->getMessage());
    header('Location: crear.php?error=' . urlencode('Error al guardar: ' . $e->getMessage()));
    exit;
}
