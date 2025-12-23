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
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones
if ($iglesia_id === 0 || $nombre === '' || $fecha_inicio === '' || $fecha_fin === '') {
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Faltan campos obligatorios");
    exit;
}

if ($fecha_fin <= $fecha_inicio) {
    header("Location: crear.php?iglesia_id=$iglesia_id&error=La fecha de fin debe ser mayor a la fecha de inicio");
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Si el nuevo período es activo, desactivar los demás
    if ($activo === 1) {
        $stmt = $conexion->prepare("UPDATE periodos_iglesia SET activo = 0 WHERE iglesia_id = ?");
        $stmt->bind_param("i", $iglesia_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insertar nuevo período
    $stmt = $conexion->prepare("INSERT INTO periodos_iglesia (iglesia_id, nombre, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $iglesia_id, $nombre, $fecha_inicio, $fecha_fin, $activo);
    $stmt->execute();
    $stmt->close();
    
    $conexion->commit();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=creado");
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al crear período: " . $e->getMessage());
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Error al guardar: " . urlencode($e->getMessage()));
    exit;
}