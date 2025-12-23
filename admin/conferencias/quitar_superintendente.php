<?php
/**
 * Quitar Superintendente de una Conferencia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

$conferencia_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$conferencia_id) {
    header('Location: index.php?error=' . urlencode('ID de conferencia no válido'));
    exit;
}

// Obtener conferencia con superintendente actual
$stmt = $conexion->prepare("SELECT c.*, p.cedula AS super_cedula 
                            FROM conferencias c 
                            LEFT JOIN pastores p ON c.superintendente_id = p.id
                            WHERE c.id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

if (!$conferencia['superintendente_id']) {
    header('Location: index.php?error=' . urlencode('Esta conferencia no tiene superintendente'));
    exit;
}

$rol_super_conferencia = 3;

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Cerrar historial
    $stmt = $conexion->prepare("UPDATE conferencia_superintendentes_historial 
                                SET fecha_fin = CURDATE(), motivo_fin = 'Remoción del cargo'
                                WHERE conferencia_id = ? AND pastor_id = ? AND fecha_fin IS NULL");
    $stmt->bind_param("ii", $conferencia_id, $conferencia['superintendente_id']);
    $stmt->execute();
    $stmt->close();
    
    // 2. Desactivar usuario
    if ($conferencia['super_cedula']) {
        $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 
                                    WHERE usuario = ? AND rol_id = ? AND conferencia_id = ?");
        $stmt->bind_param("sii", $conferencia['super_cedula'], $rol_super_conferencia, $conferencia_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // 3. Quitar superintendente de la conferencia
    $stmt = $conexion->prepare("UPDATE conferencias SET superintendente_id = NULL WHERE id = ?");
    $stmt->bind_param("i", $conferencia_id);
    $stmt->execute();
    $stmt->close();
    
    $conexion->commit();
    
    header('Location: index.php?success=' . urlencode('Superintendente removido de la conferencia'));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al quitar superintendente: " . $e->getMessage());
    header('Location: index.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
