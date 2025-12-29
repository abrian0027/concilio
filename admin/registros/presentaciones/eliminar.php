<?php
/**
 * Anular Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Nota: No se elimina físicamente, solo se cambia el estado a 'anulado'
 * Solo accesible para la iglesia local
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
if (!in_array($rol_nombre, $roles_permitidos)) {
    header('Location: index.php?error=No tiene permisos. Solo Pastor o Secretaria.');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($id <= 0 || $iglesia_id <= 0) {
    header('Location: index.php?error=Datos no válidos o sin iglesia asignada');
    exit;
}

// Verificar que el registro existe
$stmt = $conexion->prepare("SELECT id, numero_acta, nombres, apellidos, estado FROM presentacion_ninos WHERE id = ? AND iglesia_id = ?");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    header('Location: index.php?error=Registro no encontrado');
    exit;
}

if ($registro['estado'] == 'anulado') {
    header('Location: index.php?error=Este registro ya está anulado');
    exit;
}

// Anular el registro (soft delete)
$stmt = $conexion->prepare("UPDATE presentacion_ninos SET estado = 'anulado', actualizado_en = NOW(), actualizado_por = ? WHERE id = ? AND iglesia_id = ?");
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$stmt->bind_param("iii", $usuario_id, $id, $iglesia_id);

if ($stmt->execute()) {
    // Registrar auditoría
    $accion = 'eliminar';
    $tabla = 'presentacion_ninos';
    $detalles = "Anuló presentación Acta No. " . $registro['numero_acta'] . ", Niño/a: " . $registro['nombres'] . " " . $registro['apellidos'];
    
    $stmt_audit = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, tabla_afectada, registro_id, detalles, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_audit->bind_param("issis", $usuario_id, $accion, $tabla, $id, $detalles);
    $stmt_audit->execute();
    $stmt_audit->close();
    
    header('Location: index.php?success=Acta anulada correctamente');
} else {
    header('Location: index.php?error=Error al anular: ' . $conexion->error);
}

$stmt->close();
$conexion->close();
?>
