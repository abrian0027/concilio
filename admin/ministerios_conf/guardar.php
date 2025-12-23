<?php
/**
 * Guardar asignación de Líder de Ministerio de Conferencia
 * NO crea usuario duplicado - el menú detecta automáticamente el rol
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos del formulario
$conferencia_id = (int)($_POST['conferencia_id'] ?? 0);
$ministerio_id = (int)($_POST['ministerio_id'] ?? 0);
$miembro_id = (int)($_POST['miembro_id'] ?? 0);
$cargo = $_POST['cargo'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
$periodo_conferencia = trim($_POST['periodo_conferencia'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// Validaciones básicas
if ($conferencia_id <= 0 || $ministerio_id <= 0 || $miembro_id <= 0 || empty($cargo)) {
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&ministerio=' . $ministerio_id . '&error=' . urlencode('Datos incompletos'));
    exit;
}

// Validar cargo
$cargos_validos = ['presidente', 'vicepresidente', 'secretario', 'tesorero', 'vocal'];
if (!in_array($cargo, $cargos_validos)) {
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&error=' . urlencode('Cargo no válido'));
    exit;
}

// Verificar que la conferencia existe
$stmt = $conexion->prepare("SELECT * FROM conferencias WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$conferencia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$conferencia) {
    header('Location: index.php?error=' . urlencode('Conferencia no encontrada'));
    exit;
}

// Verificar que el ministerio existe
$stmt = $conexion->prepare("SELECT * FROM ministerios WHERE id = ? AND activo = 1");
$stmt->bind_param("i", $ministerio_id);
$stmt->execute();
$ministerio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ministerio) {
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&error=' . urlencode('Ministerio no encontrado'));
    exit;
}

// Verificar que el miembro existe
$stmt = $conexion->prepare("SELECT m.*, i.nombre AS iglesia_nombre 
                            FROM miembros m 
                            LEFT JOIN iglesias i ON m.iglesia_id = i.id
                            WHERE m.id = ?");
$stmt->bind_param("i", $miembro_id);
$stmt->execute();
$miembro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro) {
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&ministerio=' . $ministerio_id . '&error=' . urlencode('Miembro no encontrado'));
    exit;
}

// Verificar que no haya presidente o vicepresidente duplicado (solo puede haber 1)
if (in_array($cargo, ['presidente', 'vicepresidente'])) {
    $stmt = $conexion->prepare("SELECT mlc.id, CONCAT(m.nombre, ' ', m.apellido) AS nombre_actual
                                FROM ministerio_lideres_conferencia mlc
                                INNER JOIN miembros m ON mlc.miembro_id = m.id
                                WHERE mlc.conferencia_id = ? 
                                  AND mlc.ministerio_id = ? 
                                  AND mlc.cargo = ?
                                  AND mlc.activo = 1");
    $stmt->bind_param("iis", $conferencia_id, $ministerio_id, $cargo);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($existente) {
        header('Location: asignar.php?conferencia=' . $conferencia_id . '&ministerio=' . $ministerio_id . '&error=' . urlencode("Ya existe un $cargo para este ministerio: " . $existente['nombre_actual']));
        exit;
    }
}

// Verificar que el miembro no tenga ya un cargo en este ministerio
$stmt = $conexion->prepare("SELECT id, cargo FROM ministerio_lideres_conferencia 
                            WHERE conferencia_id = ? 
                              AND ministerio_id = ? 
                              AND miembro_id = ?
                              AND activo = 1");
$stmt->bind_param("iii", $conferencia_id, $ministerio_id, $miembro_id);
$stmt->execute();
$ya_asignado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($ya_asignado) {
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&ministerio=' . $ministerio_id . '&error=' . urlencode('Este miembro ya está asignado como ' . $ya_asignado['cargo'] . ' en este ministerio'));
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Insertar nuevo líder
    $stmt = $conexion->prepare("INSERT INTO ministerio_lideres_conferencia 
                                (conferencia_id, ministerio_id, miembro_id, cargo, fecha_inicio, periodo_conferencia, observaciones)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissss", $conferencia_id, $ministerio_id, $miembro_id, $cargo, $fecha_inicio, $periodo_conferencia, $observaciones);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al asignar líder: " . $stmt->error);
    }
    $stmt->close();
    
    // Verificar si el miembro ya tiene usuario
    $cedula = $miembro['numero_documento'];
    $mensaje_usuario = "";
    
    if (!empty($cedula)) {
        $stmt = $conexion->prepare("SELECT id, rol_id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $usuario_existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($usuario_existente) {
            // YA TIENE USUARIO - Actualizar miembro_id para que el menú detecte su rol
            $stmt = $conexion->prepare("UPDATE usuarios SET miembro_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $miembro_id, $usuario_existente['id']);
            $stmt->execute();
            $stmt->close();
            $mensaje_usuario = " (Usuario vinculado - el menú mostrará su rol de líder automáticamente)";
        } else {
            // NO TIENE USUARIO - Solo crear si es presidente
            if ($cargo === 'presidente') {
                $rol_lider = 8; // lider_ministerio
                $clave_sin_guiones = str_replace('-', '', $cedula);
                $clave_hash = password_hash($clave_sin_guiones, PASSWORD_DEFAULT);
                
                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellido, usuario, clave, rol_id, conferencia_id, miembro_id, activo)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssssiis", $miembro['nombre'], $miembro['apellido'], $cedula, $clave_hash, $rol_lider, $conferencia_id, $miembro_id);
                $stmt->execute();
                $stmt->close();
                
                $mensaje_usuario = " | Usuario creado - Cédula: $cedula, Contraseña: $clave_sin_guiones";
            } else {
                $mensaje_usuario = " (No se creó usuario - solo el presidente obtiene acceso automático)";
            }
        }
    } else {
        // Intentar vincular por nombre/apellido si no tiene cédula
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE UPPER(nombre) = UPPER(?) AND UPPER(apellido) = UPPER(?)");
        $stmt->bind_param("ss", $miembro['nombre'], $miembro['apellido']);
        $stmt->execute();
        $usuario_por_nombre = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($usuario_por_nombre) {
            $stmt = $conexion->prepare("UPDATE usuarios SET miembro_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $miembro_id, $usuario_por_nombre['id']);
            $stmt->execute();
            $stmt->close();
            $mensaje_usuario = " (Usuario vinculado por nombre)";
        }
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    $mensaje = ucfirst($cargo) . " asignado: " . $miembro['nombre'] . " " . $miembro['apellido'] . " → " . $ministerio['nombre'] . $mensaje_usuario;
    header('Location: index.php?conferencia=' . $conferencia_id . '&success=' . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al asignar líder: " . $e->getMessage());
    header('Location: asignar.php?conferencia=' . $conferencia_id . '&ministerio=' . $ministerio_id . '&error=' . urlencode($e->getMessage()));
    exit;
}
