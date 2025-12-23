<?php
/**
 * Guardar asignación de Superintendente a Conferencia
 * - Actualiza conferencia con superintendente_id
 * - Crea/actualiza usuario con rol super_conferencia
 * - Registra en historial
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
$conferencia_id = (int)($_POST['conferencia_id'] ?? 0);
$pastor_id = (int)($_POST['pastor_id'] ?? 0);
$superintendente_anterior = !empty($_POST['superintendente_anterior']) ? (int)$_POST['superintendente_anterior'] : null;
$fecha_asignacion = $_POST['fecha_asignacion'] ?? date('Y-m-d');
$motivo_cambio = trim($_POST['motivo_cambio'] ?? '');

// Validaciones básicas
if ($conferencia_id <= 0 || $pastor_id <= 0) {
    header('Location: asignar_superintendente.php?id=' . $conferencia_id . '&error=' . urlencode('Datos incompletos'));
    exit;
}

// Verificar que la conferencia existe
$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ?");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

// Verificar que el pastor existe y es Presbítero
$stmt = $conexion->prepare("SELECT * FROM pastores WHERE id = ? AND orden_ministerial = 'Presbítero'");
$stmt->bind_param("i", $pastor_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header('Location: asignar_superintendente.php?id=' . $conferencia_id . '&error=' . urlencode('El pastor no existe o no es Presbítero'));
    exit;
}

// Verificar que el pastor no sea superintendente de otra conferencia
$stmt = $conexion->prepare("SELECT id, nombre FROM conferencias WHERE superintendente_id = ? AND id != ?");
$stmt->bind_param("ii", $pastor_id, $conferencia_id);
$stmt->execute();
$otra_conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($otra_conferencia) {
    header('Location: asignar_superintendente.php?id=' . $conferencia_id . '&error=' . urlencode('Este pastor ya es superintendente de: ' . $otra_conferencia['nombre']));
    exit;
}

// ROL super_conferencia
$rol_super_conferencia = 3; // Ajustar según tu tabla de roles

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Si hay superintendente anterior, cerrar su historial y desactivar usuario
    if ($superintendente_anterior && $superintendente_anterior != $pastor_id) {
        // Cerrar historial del anterior
        $sql_cerrar = "UPDATE conferencia_superintendentes_historial 
                       SET fecha_fin = ?, motivo_fin = ?
                       WHERE conferencia_id = ? AND pastor_id = ? AND fecha_fin IS NULL";
        $stmt = $conexion->prepare($sql_cerrar);
        $motivo = !empty($motivo_cambio) ? $motivo_cambio : 'Cambio de superintendente';
        $stmt->bind_param("ssii", $fecha_asignacion, $motivo, $conferencia_id, $superintendente_anterior);
        $stmt->execute();
        $stmt->close();
        
        // Obtener cédula del anterior para desactivar usuario
        $stmt = $conexion->prepare("SELECT cedula FROM pastores WHERE id = ?");
        $stmt->bind_param("i", $superintendente_anterior);
        $stmt->execute();
        $pastor_anterior = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($pastor_anterior) {
            // Desactivar usuario del superintendente anterior
            $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE usuario = ? AND rol_id = ? AND conferencia_id = ?");
            $stmt->bind_param("sii", $pastor_anterior['cedula'], $rol_super_conferencia, $conferencia_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // 2. Actualizar conferencia con nuevo superintendente
    $stmt = $conexion->prepare("UPDATE conferencias SET superintendente_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $pastor_id, $conferencia_id);
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar conferencia: " . $stmt->error);
    }
    $stmt->close();
    
    // 3. Actualizar pastor con su conferencia
    $stmt = $conexion->prepare("UPDATE pastores SET conferencia_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $conferencia_id, $pastor_id);
    $stmt->execute();
    $stmt->close();
    
    // 4. Crear o actualizar usuario super_conferencia
    $cedula = $pastor['cedula'];
    $clave_sin_guiones = str_replace('-', '', $cedula);
    $clave_hash = password_hash($clave_sin_guiones, PASSWORD_DEFAULT);
    
    // Verificar si ya existe usuario con esa cédula y rol
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? AND rol_id = ?");
    $stmt->bind_param("si", $cedula, $rol_super_conferencia);
    $stmt->execute();
    $usuario_existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($usuario_existente) {
        // Actualizar usuario existente
        $stmt = $conexion->prepare("UPDATE usuarios SET 
                                    nombre = ?, apellido = ?, conferencia_id = ?, activo = 1
                                    WHERE id = ?");
        $stmt->bind_param("ssii", $pastor['nombre'], $pastor['apellido'], $conferencia_id, $usuario_existente['id']);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar usuario: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Crear nuevo usuario
        $sql_usuario = "INSERT INTO usuarios (
                            nombre, apellido, usuario, clave, correo,
                            rol_id, conferencia_id, distrito_id, iglesia_id, activo
                        ) VALUES (
                            '" . $conexion->real_escape_string($pastor['nombre']) . "',
                            '" . $conexion->real_escape_string($pastor['apellido']) . "',
                            '" . $conexion->real_escape_string($cedula) . "',
                            '" . $conexion->real_escape_string($clave_hash) . "',
                            '',
                            $rol_super_conferencia,
                            $conferencia_id,
                            NULL,
                            NULL,
                            1
                        )";
        
        if (!$conexion->query($sql_usuario)) {
            throw new Exception("Error al crear usuario: " . $conexion->error);
        }
    }
    
    // 5. Registrar en historial (solo si es nuevo o diferente al anterior)
    if ($superintendente_anterior != $pastor_id) {
        $stmt = $conexion->prepare("INSERT INTO conferencia_superintendentes_historial 
                                    (conferencia_id, pastor_id, fecha_inicio) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $conferencia_id, $pastor_id, $fecha_asignacion);
        if (!$stmt->execute()) {
            throw new Exception("Error al registrar historial: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    $mensaje = "Superintendente asignado exitosamente. Usuario: " . $cedula . " / Contraseña: " . $clave_sin_guiones;
    header('Location: index.php?success=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al asignar superintendente: " . $e->getMessage());
    header('Location: asignar_superintendente.php?id=' . $conferencia_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
