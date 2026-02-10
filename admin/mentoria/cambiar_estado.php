<?php
/**
 * Cambiar Estado de Mentoría - Sistema Concilio
 */

require_once __DIR__ . '/../../config/config.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
$puede_editar = in_array($rol_nombre, ['pastor', 'secretaria']);

if (!$puede_editar) {
    header("Location: index.php?error=No tienes permiso");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$estados_validos = ['activa', 'pausada', 'finalizada'];

if ($id === 0 || !in_array($estado, $estados_validos)) {
    header("Location: index.php?error=Datos no válidos");
    exit;
}

// Actualizar estado
$fecha_fin = ($estado === 'finalizada') ? date('Y-m-d') : null;

$stmt = $conexion->prepare("UPDATE mentorias SET estado = ?, fecha_fin = ? WHERE id = ?");
$stmt->bind_param("ssi", $estado, $fecha_fin, $id);

if ($stmt->execute()) {
    header("Location: ver.php?id=$id&exito=" . urlencode("Estado actualizado a " . ucfirst($estado)));
} else {
    header("Location: ver.php?id=$id&error=" . urlencode("Error al actualizar"));
}
exit;
