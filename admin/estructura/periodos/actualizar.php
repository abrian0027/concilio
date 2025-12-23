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
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones
if ($id === 0 || $iglesia_id === 0 || $nombre === '' || $fecha_inicio === '' || $fecha_fin === '') {
    header("Location: editar.php?id=$id&error=Faltan campos obligatorios");
    exit;
}

if ($fecha_fin <= $fecha_inicio) {
    header("Location: editar.php?id=$id&error=La fecha de fin debe ser mayor a la fecha de inicio");
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Si el período es activo, desactivar los demás
    if ($activo === 1) {
        $stmt = $conexion->prepare("UPDATE periodos_iglesia SET activo = 0 WHERE iglesia_id = ? AND id != ?");
        $stmt->bind_param("ii", $iglesia_id, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Actualizar período
    $stmt = $conexion->prepare("UPDATE periodos_iglesia SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, activo = ? WHERE id = ?");
    $stmt->bind_param("sssii", $nombre, $fecha_inicio, $fecha_fin, $activo, $id);
    $stmt->execute();
    $stmt->close();
    
    $conexion->commit();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=editado");
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al actualizar período: " . $e->getMessage());
    header("Location: editar.php?id=$id&error=Error al actualizar: " . urlencode($e->getMessage()));
    exit;
}