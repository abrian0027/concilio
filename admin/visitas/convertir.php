<?php
/**
 * Convertir Visita a Miembro - Sistema Concilio
 * Conversión automática simplificada con estado "en_preparacion"
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden convertir
$rol = $_SESSION['rol_nombre'] ?? '';
$puede_convertir = in_array($rol, ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_convertir) {
    header("Location: index.php?error=No tienes permiso para esta acción");
    exit;
}

// Obtener ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

// Obtener iglesia del usuario
$iglesia_id_usuario = $_SESSION['iglesia_id'] ?? 0;

// Obtener la visita
$sql = "SELECT * FROM visitas WHERE id = ? AND estado = 'activo'";
if ($rol !== 'super_admin') {
    $sql .= " AND iglesia_id = " . (int)$iglesia_id_usuario;
}
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$visita = $result->fetch_assoc();

if (!$visita) {
    header("Location: index.php?error=Visita no encontrada o inactiva");
    exit;
}

// Verificar si ya fue convertida
if ($visita['convertido_miembro']) {
    header("Location: index.php?error=Esta visita ya fue convertida a miembro anteriormente");
    exit;
}

// Iniciar transacción para asegurar integridad
$conexion->begin_transaction();

try {
    // Determinar el estado del miembro basado en categoría
    $estado_miembro = ($visita['categoria'] === 'ninos') ? 'miembro_menor' : 'en_preparacion';
    
    // Crear el nuevo miembro con los datos de la visita
    $sql_miembro = "INSERT INTO miembros (
        iglesia_id,
        nombre,
        apellido,
        sexo,
        nacionalidad_id,
        telefono,
        estado_miembro,
        estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')";
    
    $stmt_miembro = $conexion->prepare($sql_miembro);
    if (!$stmt_miembro) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    
    $stmt_miembro->bind_param(
        "isssiss",
        $visita['iglesia_id'],
        $visita['nombre'],
        $visita['apellido'],
        $visita['sexo'],
        $visita['nacionalidad_id'],
        $visita['telefono'],
        $estado_miembro
    );
    
    if (!$stmt_miembro->execute()) {
        throw new Exception("Error al crear miembro: " . $stmt_miembro->error);
    }
    
    $nuevo_miembro_id = $conexion->insert_id;
    
    // Actualizar la visita marcándola como convertida
    $sql_update = "UPDATE visitas SET 
                   convertido_miembro = 1,
                   miembro_id = ?,
                   fecha_conversion = CURDATE()
                   WHERE id = ?";
    
    $stmt_update = $conexion->prepare($sql_update);
    if (!$stmt_update) {
        throw new Exception("Error preparando actualización: " . $conexion->error);
    }
    
    $stmt_update->bind_param("ii", $nuevo_miembro_id, $id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar visita: " . $stmt_update->error);
    }
    
    // Commit de la transacción
    $conexion->commit();
    
    // Mensaje de éxito según estado
    $nombre_completo = $visita['nombre'] . ' ' . $visita['apellido'];
    $tipo_miembro = ($estado_miembro === 'miembro_menor') ? 'miembro menor' : 'miembro en preparación';
    $msg = "¡Conversión exitosa! $nombre_completo ahora es $tipo_miembro.";
    
    header("Location: index.php?exito=" . urlencode($msg));
    exit;
    
} catch (Exception $e) {
    // Rollback en caso de error
    $conexion->rollback();
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit;
}
