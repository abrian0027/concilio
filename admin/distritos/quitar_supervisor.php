<?php
/**
 * Quitar Supervisor de un Distrito
 * Solo quita el distrito_id del usuario, NO lo desactiva
 * (porque puede seguir siendo pastor de una iglesia)
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

$distrito_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$distrito_id) {
    header('Location: index.php?error=' . urlencode('ID de distrito no válido'));
    exit;
}

// Obtener distrito con supervisor actual
$stmt = $conexion->prepare("SELECT d.*, p.cedula AS sup_cedula 
                            FROM distritos d 
                            LEFT JOIN pastores p ON d.supervisor_id = p.id
                            WHERE d.id = ?");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$distrito) {
    header('Location: index.php?error=' . urlencode('Distrito no encontrado'));
    exit;
}

if (!$distrito['supervisor_id']) {
    header('Location: index.php?error=' . urlencode('Este distrito no tiene supervisor'));
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Cerrar historial
    $stmt = $conexion->prepare("UPDATE distrito_supervisores_historial 
                                SET fecha_fin = CURDATE(), motivo_fin = 'Remoción del cargo'
                                WHERE distrito_id = ? AND pastor_id = ? AND fecha_fin IS NULL");
    $stmt->bind_param("ii", $distrito_id, $distrito['supervisor_id']);
    $stmt->execute();
    $stmt->close();
    
    // 2. Quitar distrito_id del usuario (NO desactivar, puede seguir siendo pastor)
    if ($distrito['sup_cedula']) {
        $stmt = $conexion->prepare("UPDATE usuarios SET distrito_id = NULL WHERE usuario = ?");
        $stmt->bind_param("s", $distrito['sup_cedula']);
        $stmt->execute();
        $stmt->close();
    }
    
    // 3. Quitar supervisor del distrito
    $stmt = $conexion->prepare("UPDATE distritos SET supervisor_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $distrito_id);
    $stmt->execute();
    $stmt->close();
    
    $conexion->commit();
    
    header('Location: index.php?success=' . urlencode('Supervisor removido del distrito (el usuario mantiene su acceso como pastor si tiene iglesia asignada)'));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al quitar supervisor: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
