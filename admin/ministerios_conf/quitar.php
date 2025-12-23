<?php
/**
 * Quitar Líder de Ministerio de Conferencia
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
$roles_permitidos = ['super_admin', 'obispo', 'super_conferencia'];
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], $roles_permitidos)) {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?error=' . urlencode('ID no válido'));
    exit;
}

// Obtener datos del líder
$stmt = $conexion->prepare("SELECT mlc.*, m.nombre, m.apellido, m.numero_documento,
                                   c.nombre AS conferencia_nombre, min.nombre AS ministerio_nombre
                            FROM ministerio_lideres_conferencia mlc
                            INNER JOIN miembros m ON mlc.miembro_id = m.id
                            INNER JOIN conferencias c ON mlc.conferencia_id = c.id
                            INNER JOIN ministerios min ON mlc.ministerio_id = min.id
                            WHERE mlc.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$lider = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lider) {
    header('Location: index.php?error=' . urlencode('Líder no encontrado'));
    exit;
}

$conferencia_id = $lider['conferencia_id'];

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Copiar a historial
    $stmt = $conexion->prepare("INSERT INTO ministerio_lideres_conferencia_historial 
                                (conferencia_id, ministerio_id, miembro_id, cargo, fecha_inicio, fecha_fin, periodo_conferencia, motivo_fin)
                                VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 'Remoción del cargo')");
    $stmt->bind_param("iiisss", 
        $lider['conferencia_id'], 
        $lider['ministerio_id'], 
        $lider['miembro_id'], 
        $lider['cargo'], 
        $lider['fecha_inicio'],
        $lider['periodo_conferencia']
    );
    $stmt->execute();
    $stmt->close();
    
    // Desactivar líder actual
    $stmt = $conexion->prepare("UPDATE ministerio_lideres_conferencia SET activo = 0, fecha_fin = CURDATE() WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Si era presidente, desactivar usuario (si no tiene otro rol)
    if ($lider['cargo'] === 'presidente' && !empty($lider['numero_documento'])) {
        // Verificar si tiene iglesia asignada como pastor
        $stmt = $conexion->prepare("SELECT iglesia_id FROM usuarios WHERE usuario = ? AND iglesia_id IS NOT NULL");
        $stmt->bind_param("s", $lider['numero_documento']);
        $stmt->execute();
        $tiene_iglesia = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$tiene_iglesia) {
            // No tiene iglesia, desactivar usuario
            $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE usuario = ? AND rol_id = 8");
            $stmt->bind_param("s", $lider['numero_documento']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conexion->commit();
    
    $mensaje = "Líder removido: " . $lider['nombre'] . " " . $lider['apellido'] . " de " . $lider['ministerio_nombre'];
    header('Location: index.php?conferencia=' . $conferencia_id . '&success=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al quitar líder: " . $e->getMessage());
    header('Location: index.php?conferencia=' . $conferencia_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
