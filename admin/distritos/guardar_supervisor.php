<?php
/**
 * Guardar asignación de Supervisor a Distrito
 * LÓGICA: Si el pastor ya tiene usuario (por ser pastor de iglesia),
 * solo actualizamos agregando el distrito_id, NO creamos usuario nuevo.
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos del formulario
$distrito_id = (int)($_POST['distrito_id'] ?? 0);
$conferencia_id = (int)($_POST['conferencia_id'] ?? 0);
$pastor_id = (int)($_POST['pastor_id'] ?? 0);
$supervisor_anterior = !empty($_POST['supervisor_anterior']) ? (int)$_POST['supervisor_anterior'] : null;
$fecha_asignacion = $_POST['fecha_asignacion'] ?? date('Y-m-d');
$motivo_cambio = trim($_POST['motivo_cambio'] ?? '');

// Validaciones básicas
if ($distrito_id <= 0 || $pastor_id <= 0 || $conferencia_id <= 0) {
    header('Location: asignar_supervisor.php?id=' . $distrito_id . '&error=' . urlencode('Datos incompletos'));
    exit;
}

// Verificar que el distrito existe
$stmt = $conexion->prepare("SELECT * FROM distritos WHERE id = ?");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$distrito) {
    header('Location: index.php?error=' . urlencode('Distrito no encontrado'));
    exit;
}

// Verificar que el pastor existe, es Presbítero y pertenece a la misma conferencia
$stmt = $conexion->prepare("SELECT * FROM pastores WHERE id = ? AND orden_ministerial = 'Presbítero' AND conferencia_id = ?");
$stmt->bind_param("ii", $pastor_id, $conferencia_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header('Location: asignar_supervisor.php?id=' . $distrito_id . '&error=' . urlencode('El pastor no existe, no es Presbítero o no pertenece a esta conferencia'));
    exit;
}

// Verificar que el pastor no sea supervisor de otro distrito
$stmt = $conexion->prepare("SELECT id, nombre FROM distritos WHERE supervisor_id = ? AND id != ?");
$stmt->bind_param("ii", $pastor_id, $distrito_id);
$stmt->execute();
$otro_distrito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($otro_distrito) {
    header('Location: asignar_supervisor.php?id=' . $distrito_id . '&error=' . urlencode('Este pastor ya es supervisor de: ' . $otro_distrito['nombre']));
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Si hay supervisor anterior diferente, limpiar su distrito_id del usuario
    if ($supervisor_anterior && $supervisor_anterior != $pastor_id) {
        // Cerrar historial del anterior
        $sql_cerrar = "UPDATE distrito_supervisores_historial 
                       SET fecha_fin = ?, motivo_fin = ?
                       WHERE distrito_id = ? AND pastor_id = ? AND fecha_fin IS NULL";
        $stmt = $conexion->prepare($sql_cerrar);
        $motivo = !empty($motivo_cambio) ? $motivo_cambio : 'Cambio de supervisor';
        $stmt->bind_param("ssii", $fecha_asignacion, $motivo, $distrito_id, $supervisor_anterior);
        $stmt->execute();
        $stmt->close();
        
        // Obtener cédula del anterior para quitar distrito_id de su usuario
        $stmt = $conexion->prepare("SELECT cedula FROM pastores WHERE id = ?");
        $stmt->bind_param("i", $supervisor_anterior);
        $stmt->execute();
        $pastor_anterior = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($pastor_anterior) {
            // Quitar distrito_id del usuario anterior (pero mantener su acceso como pastor)
            $stmt = $conexion->prepare("UPDATE usuarios SET distrito_id = NULL WHERE usuario = ?");
            $stmt->bind_param("s", $pastor_anterior['cedula']);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // 2. Actualizar distrito con nuevo supervisor
    $stmt = $conexion->prepare("UPDATE distritos SET supervisor_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $pastor_id, $distrito_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar distrito: " . $stmt->error);
    }
    $stmt->close();
    
    // 3. Buscar si el pastor ya tiene usuario (por ser pastor de iglesia)
    $cedula = $pastor['cedula'];
    
    $stmt = $conexion->prepare("SELECT id, rol_id, iglesia_id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $usuario_existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($usuario_existente) {
        // YA TIENE USUARIO - Solo agregar distrito_id (mantiene su rol de pastor)
        $stmt = $conexion->prepare("UPDATE usuarios SET 
                                    distrito_id = ?,
                                    conferencia_id = ?,
                                    activo = 1
                                    WHERE id = ?");
        $stmt->bind_param("iii", $distrito_id, $conferencia_id, $usuario_existente['id']);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar usuario: " . $stmt->error);
        }
        $stmt->close();
        
        $mensaje_usuario = "Usuario actualizado - ahora tiene acceso al distrito";
    } else {
        // NO TIENE USUARIO - Crear uno nuevo con rol super_distrito
        $rol_super_distrito = 4;
        $clave_sin_guiones = str_replace('-', '', $cedula);
        $clave_hash = password_hash($clave_sin_guiones, PASSWORD_DEFAULT);
        
        $sql_usuario = "INSERT INTO usuarios (
                            nombre, apellido, usuario, clave, correo,
                            rol_id, conferencia_id, distrito_id, iglesia_id, activo
                        ) VALUES (
                            '" . $conexion->real_escape_string($pastor['nombre']) . "',
                            '" . $conexion->real_escape_string($pastor['apellido']) . "',
                            '" . $conexion->real_escape_string($cedula) . "',
                            '" . $conexion->real_escape_string($clave_hash) . "',
                            '',
                            $rol_super_distrito,
                            $conferencia_id,
                            $distrito_id,
                            NULL,
                            1
                        )";
        
        if (!$conexion->query($sql_usuario)) {
            throw new Exception("Error al crear usuario: " . $conexion->error);
        }
        
        $mensaje_usuario = "Usuario creado - Contraseña: " . $clave_sin_guiones;
    }
    
    // 4. Registrar en historial (solo si es nuevo o diferente al anterior)
    if ($supervisor_anterior != $pastor_id) {
        $stmt = $conexion->prepare("INSERT INTO distrito_supervisores_historial 
                                    (distrito_id, pastor_id, fecha_inicio) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $distrito_id, $pastor_id, $fecha_asignacion);
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar historial: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    $mensaje = "Supervisor asignado exitosamente. " . $mensaje_usuario;
    header('Location: index.php?success=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al asignar supervisor: " . $e->getMessage());
    header('Location: asignar_supervisor.php?id=' . $distrito_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
