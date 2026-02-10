<?php
/**
 * Aprobar Solicitud de Membresía
 * Sistema Concilio - Panel del Pastor
 * Crea el miembro a partir de la solicitud
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/auditoria.php';
require_once __DIR__ . '/../../includes/notificaciones.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$ROL_NOMBRE = strtolower($_SESSION['rol_nombre'] ?? '');
$IGLESIA_ID = $_SESSION['iglesia_id'] ?? 0;
$USUARIO_ID = $_SESSION['usuario_id'];

// Solo pastor y secretaria pueden aprobar
$puede_aprobar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_aprobar) {
    header('Location: index.php?error=sin_permiso');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?error=id_invalido');
    exit;
}

// Obtener solicitud
$sql = "SELECT * FROM solicitudes_membresia WHERE id = ? AND estado = 'pendiente'";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql .= " AND iglesia_id = ?";
}

$stmt = $conexion->prepare($sql);
if ($ROL_NOMBRE !== 'super_admin') {
    $stmt->bind_param("ii", $id, $IGLESIA_ID);
} else {
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$solicitud = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$solicitud) {
    header('Location: index.php?error=no_encontrado');
    exit;
}

// Verificar que no exista ya un miembro con esa cédula en esa iglesia
$cedula_limpia = preg_replace('/[^0-9]/', '', $solicitud['numero_documento']);
$stmt = $conexion->prepare("
    SELECT id FROM miembros 
    WHERE REPLACE(REPLACE(numero_documento, '-', ''), ' ', '') = ?
    AND iglesia_id = ? 
    AND estado = 'activo'
");
$stmt->bind_param("si", $cedula_limpia, $solicitud['iglesia_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header('Location: index.php?error=ya_existe_miembro');
    exit;
}
$stmt->close();

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Crear el miembro
    $stmt = $conexion->prepare("
        INSERT INTO miembros (
            iglesia_id, nombre, apellido, sexo, fecha_nacimiento,
            nacionalidad_id, tipo_documento, numero_documento, telefono, direccion,
            estado_civil, nivel_estudio_id, carrera_id,
            es_bautizado, fecha_bautismo,
            estado_miembro, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_preparacion', 'activo')
    ");
    
    $stmt->bind_param(
        "issssisssssiiis",
        $solicitud['iglesia_id'],
        $solicitud['nombre'],
        $solicitud['apellido'],
        $solicitud['sexo'],
        $solicitud['fecha_nacimiento'],
        $solicitud['nacionalidad_id'],
        $solicitud['tipo_documento'],
        $solicitud['numero_documento'],
        $solicitud['telefono'],
        $solicitud['direccion'],
        $solicitud['estado_civil'],
        $solicitud['nivel_estudio_id'],
        $solicitud['carrera_id'],
        $solicitud['es_bautizado'],
        $solicitud['fecha_bautismo']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al crear miembro: " . $stmt->error);
    }
    
    $miembro_id = $conexion->insert_id;
    $stmt->close();
    
    // 2. Actualizar solicitud como aprobada
    $stmt = $conexion->prepare("
        UPDATE solicitudes_membresia 
        SET estado = 'aprobado', 
            fecha_revision = NOW(), 
            revisado_por = ?,
            observaciones = CONCAT(IFNULL(observaciones, ''), '\nMiembro creado con ID: $miembro_id')
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $USUARIO_ID, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar solicitud: " . $stmt->error);
    }
    $stmt->close();
    
    // 3. Registrar auditoría
    registrar_auditoria(
        'crear',
        'miembros',
        'miembros',
        $miembro_id,
        'Miembro creado desde solicitud de membresía #' . $id,
        null,
        [
            'nombre' => $solicitud['nombre'] . ' ' . $solicitud['apellido'],
            'cedula' => $solicitud['numero_documento'],
            'solicitud_id' => $id
        ]
    );
    
    // Confirmar transacción
    $conexion->commit();
    
    // Notificar al solicitante por correo
    notificarSolicitudAprobada($conexion, $id);
    
    // Redirigir con éxito
    header('Location: index.php?exito=aprobado&miembro_id=' . $miembro_id);
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al aprobar solicitud: " . $e->getMessage());
    header('Location: index.php?error=error_aprobar');
    exit;
}
