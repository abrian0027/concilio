<?php
/**
 * Eliminar Visita - Sistema Concilio
 * Solo el pastor puede eliminar (desactivar) visitas
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor o super_admin pueden eliminar
$puede_eliminar = in_array($ROL_NOMBRE, ['super_admin', 'pastor']);

if (!$puede_eliminar) {
    header("Location: index.php?error=No tienes permiso para eliminar visitas");
    exit;
}

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Verificar que la visita existe y pertenece a la iglesia
$sql = "SELECT id, nombre, apellido, convertido_miembro FROM visitas WHERE id = ?";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND iglesia_id = " . (int)$IGLESIA_ID;
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$visita = $result->fetch_assoc();

if (!$visita) {
    header("Location: index.php?error=Visita no encontrada");
    exit;
}

// No eliminar si ya fue convertida a miembro
if ($visita['convertido_miembro']) {
    header("Location: index.php?error=No se puede eliminar una visita que ya fue convertida a miembro");
    exit;
}

// Soft delete: cambiar estado a inactivo
$sql_delete = "UPDATE visitas SET estado = 'inactivo' WHERE id = ?";
$stmt_delete = $conexion->prepare($sql_delete);
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    $nombre_completo = $visita['nombre'] . ' ' . $visita['apellido'];
    header("Location: index.php?success=" . urlencode("Visita de " . $nombre_completo . " eliminada correctamente"));
    exit;
} else {
    header("Location: index.php?error=" . urlencode("Error al eliminar: " . $conexion->error));
    exit;
}
