<?php
/**
 * Quitar asignación de iglesia al pastor
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['super_admin', 'obispo', 'super_conferencia'])) {
    header('Location: index.php');
    exit;
}

$pastor_id = (int)($_GET['pastor_id'] ?? 0);
$asignacion_id = (int)($_GET['asignacion_id'] ?? 0);

if ($pastor_id <= 0 || $asignacion_id <= 0) {
    header('Location: index.php?error=' . urlencode('Datos incompletos'));
    exit;
}

// Obtener datos de la asignación
$stmt = $conexion->prepare("SELECT * FROM pastor_iglesias WHERE id = ? AND pastor_id = ?");
$stmt->bind_param("ii", $asignacion_id, $pastor_id);
$stmt->execute();
$asignacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$asignacion) {
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('Asignación no encontrada'));
    exit;
}

// Obtener datos del pastor para la cédula
$stmt = $conexion->prepare("SELECT cedula FROM pastores WHERE id = ?");
$stmt->bind_param("i", $pastor_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Desactivar la asignación
    $sql = "UPDATE pastor_iglesias SET activo = 0, fecha_fin = CURDATE() WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $asignacion_id);
    $stmt->execute();
    $stmt->close();
    
    // 2. Actualizar historial
    $sql_historial = "UPDATE pastor_historial_asignaciones 
                      SET fecha_fin = CURDATE(), motivo_fin = 'Reasignación' 
                      WHERE pastor_id = ? AND iglesia_id = ? AND fecha_fin IS NULL";
    $stmt = $conexion->prepare($sql_historial);
    $stmt->bind_param("ii", $pastor_id, $asignacion['iglesia_id']);
    $stmt->execute();
    $stmt->close();
    
    // 3. Desactivar usuario de esta iglesia
    $sql_usuario = "UPDATE usuarios SET activo = 0 
                    WHERE iglesia_id = ? AND rol_id = 5 AND usuario = ?";
    $stmt = $conexion->prepare($sql_usuario);
    $stmt->bind_param("is", $asignacion['iglesia_id'], $pastor['cedula']);
    $stmt->execute();
    $stmt->close();
    
    // 4. Actualizar estado del miembro (opcional: mantenerlo o inactivarlo)
    $sql_miembro = "UPDATE miembros SET es_lider = 0 
                    WHERE iglesia_id = ? AND numero_documento = ?";
    $stmt = $conexion->prepare($sql_miembro);
    $stmt->bind_param("is", $asignacion['iglesia_id'], $pastor['cedula']);
    $stmt->execute();
    $stmt->close();
    
    // Confirmar
    $conexion->commit();
    
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&msg=' . urlencode('Asignación removida exitosamente'));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al quitar asignación: " . $e->getMessage());
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}