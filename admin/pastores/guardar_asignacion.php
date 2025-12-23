<?php
/**
 * Guardar asignación de iglesia al pastor
 * Crea automáticamente: miembro (en_plena) + usuario
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['super_admin', 'obispo', 'super_conferencia'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos
$pastor_id = (int)($_POST['pastor_id'] ?? 0);
$iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
$fecha_asignacion = $_POST['fecha_asignacion'] ?? date('Y-m-d');
$es_principal = (int)($_POST['es_principal'] ?? 1);

// Validaciones básicas
if ($pastor_id <= 0 || $iglesia_id <= 0) {
    header('Location: index.php?error=' . urlencode('Datos incompletos'));
    exit;
}

// Obtener datos del pastor
$stmt = $conexion->prepare("SELECT * FROM pastores WHERE id = ?");
$stmt->bind_param("i", $pastor_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pastor) {
    header('Location: index.php?error=' . urlencode('Pastor no encontrado'));
    exit;
}

// Obtener datos de la iglesia
$stmt = $conexion->prepare("SELECT i.*, d.conferencia_id FROM iglesias i INNER JOIN distritos d ON i.distrito_id = d.id WHERE i.id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$iglesia) {
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('Iglesia no encontrada'));
    exit;
}

// Verificar que no esté ya asignado a esta iglesia
$check = $conexion->prepare("SELECT id FROM pastor_iglesias WHERE pastor_id = ? AND iglesia_id = ? AND activo = 1");
$check->bind_param("ii", $pastor_id, $iglesia_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $check->close();
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('El pastor ya está asignado a esta iglesia'));
    exit;
}
$check->close();

// Contar asignaciones activas
$count = $conexion->prepare("SELECT COUNT(*) as total FROM pastor_iglesias WHERE pastor_id = ? AND activo = 1");
$count->bind_param("i", $pastor_id);
$count->execute();
$total_asignaciones = $count->get_result()->fetch_assoc()['total'];
$count->close();

if ($total_asignaciones >= 2) {
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('El pastor ya tiene 2 iglesias asignadas'));
    exit;
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. CREAR REGISTRO EN pastor_iglesias
    $sql_asignacion = "INSERT INTO pastor_iglesias (pastor_id, iglesia_id, fecha_asignacion, es_principal, activo) VALUES (?, ?, ?, ?, 1)";
    $stmt = $conexion->prepare($sql_asignacion);
    $stmt->bind_param("iisi", $pastor_id, $iglesia_id, $fecha_asignacion, $es_principal);
    $stmt->execute();
    $stmt->close();
    
    // 2. CREAR/ACTUALIZAR REGISTRO EN miembros
    // Verificar si ya existe como miembro en esta iglesia
    $check_miembro = $conexion->prepare("SELECT id FROM miembros WHERE iglesia_id = ? AND numero_documento = ?");
    $check_miembro->bind_param("is", $iglesia_id, $pastor['cedula']);
    $check_miembro->execute();
    $miembro_existente = $check_miembro->get_result()->fetch_assoc();
    $check_miembro->close();
    
    // Convertir tipo_documento y estado_civil para formato de miembros
    $tipo_doc = ($pastor['tipo_documento'] == 'Cédula') ? 'cedula' : 'pasaporte';
    $estado_civil_miembro = strtolower($pastor['estado_civil']);
    
    // Escapar valores para consulta directa (manejo de NULLs)
    $nivel_estudio_id_esc = !empty($pastor['nivel_estudio_id']) ? (int)$pastor['nivel_estudio_id'] : "NULL";
    $carrera_id_esc = !empty($pastor['carrera_id']) ? (int)$pastor['carrera_id'] : "NULL";
    $formacion_continuada_esc = !empty($pastor['formacion_continuada']) ? "'" . $conexion->real_escape_string($pastor['formacion_continuada']) . "'" : "NULL";
    
    if ($miembro_existente) {
        // Actualizar miembro existente
        $miembro_id = $miembro_existente['id'];
        $sql_miembro = "UPDATE miembros SET 
                        nombre = '" . $conexion->real_escape_string($pastor['nombre']) . "', 
                        apellido = '" . $conexion->real_escape_string($pastor['apellido']) . "', 
                        sexo = '" . $conexion->real_escape_string($pastor['sexo']) . "', 
                        fecha_nacimiento = '" . $conexion->real_escape_string($pastor['fecha_nacimiento']) . "',
                        nacionalidad_id = " . (!empty($pastor['nacionalidad_id']) ? (int)$pastor['nacionalidad_id'] : "NULL") . ", 
                        tipo_documento = '$tipo_doc', 
                        telefono = '" . $conexion->real_escape_string($pastor['telefono']) . "',
                        estado_civil = '$estado_civil_miembro', 
                        nivel_estudio_id = $nivel_estudio_id_esc,
                        carrera_id = $carrera_id_esc,
                        formacion_continuada = $formacion_continuada_esc,
                        estado_miembro = 'en_plena',
                        es_lider = 1, 
                        estado = 'activo'
                        WHERE id = $miembro_id";
        
        if (!$conexion->query($sql_miembro)) {
            throw new Exception("Error al actualizar miembro: " . $conexion->error);
        }
    } else {
        // Crear nuevo miembro con estado_miembro = 'en_plena'
        $sql_miembro = "INSERT INTO miembros (
                        iglesia_id, nombre, apellido, sexo, fecha_nacimiento,
                        nacionalidad_id, tipo_documento, numero_documento, telefono,
                        estado_civil, nivel_estudio_id, carrera_id, formacion_continuada,
                        estado_miembro, es_lider, estado
                        ) VALUES (
                        $iglesia_id, 
                        '" . $conexion->real_escape_string($pastor['nombre']) . "', 
                        '" . $conexion->real_escape_string($pastor['apellido']) . "', 
                        '" . $conexion->real_escape_string($pastor['sexo']) . "', 
                        '" . $conexion->real_escape_string($pastor['fecha_nacimiento']) . "',
                        " . (!empty($pastor['nacionalidad_id']) ? (int)$pastor['nacionalidad_id'] : "NULL") . ", 
                        '$tipo_doc', 
                        '" . $conexion->real_escape_string($pastor['cedula']) . "', 
                        '" . $conexion->real_escape_string($pastor['telefono']) . "',
                        '$estado_civil_miembro', 
                        $nivel_estudio_id_esc,
                        $carrera_id_esc,
                        $formacion_continuada_esc,
                        'en_plena', 
                        1, 
                        'activo'
                        )";
        
        if (!$conexion->query($sql_miembro)) {
            throw new Exception("Error al crear miembro: " . $conexion->error);
        }
        $miembro_id = $conexion->insert_id;
    }
    
    // 3. CREAR/ACTUALIZAR REGISTRO EN usuarios
    // Verificar si ya existe usuario para esta iglesia con esta cédula
    $check_usuario = $conexion->prepare("
        SELECT u.id FROM usuarios u 
        WHERE u.iglesia_id = ? AND u.rol_id = 5 
        AND (u.usuario = ? OR u.nombre = ?)
    ");
    $check_usuario->bind_param("iss", $iglesia_id, $pastor['cedula'], $pastor['nombre']);
    $check_usuario->execute();
    $usuario_existente = $check_usuario->get_result()->fetch_assoc();
    $check_usuario->close();
    
    // Obtener rol pastor (id = 5)
    $rol_pastor_id = 5;
    
    if ($usuario_existente) {
        // Actualizar usuario existente
        $sql_usuario = "UPDATE usuarios SET 
                        nombre = ?, apellido = ?, activo = 1,
                        conferencia_id = ?, distrito_id = ?
                        WHERE id = ?";
        $stmt = $conexion->prepare($sql_usuario);
        $stmt->bind_param(
            "ssiii",
            $pastor['nombre'],
            $pastor['apellido'],
            $iglesia['conferencia_id'],
            $iglesia['distrito_id'],
            $usuario_existente['id']
        );
        $stmt->execute();
        $stmt->close();
    } else {
        // Crear nuevo usuario
        // Contraseña inicial = cédula sin guiones
        $clave_limpia = str_replace('-', '', $pastor['cedula']);
        $clave_hash = password_hash($clave_limpia, PASSWORD_DEFAULT);
        
        $sql_usuario = "INSERT INTO usuarios (
                        nombre, apellido, usuario, clave, correo,
                        rol_id, conferencia_id, distrito_id, iglesia_id, activo
                        ) VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, 1)";
        $stmt = $conexion->prepare($sql_usuario);
        $stmt->bind_param(
            "ssssiiii",
            $pastor['nombre'],
            $pastor['apellido'],
            $pastor['cedula'],
            $clave_hash,
            $rol_pastor_id,
            $iglesia['conferencia_id'],
            $iglesia['distrito_id'],
            $iglesia_id
        );
        $stmt->execute();
        $stmt->close();
    }
    
    // 4. GUARDAR EN HISTORIAL
    $sql_historial = "INSERT INTO pastor_historial_asignaciones (pastor_id, iglesia_id, fecha_inicio) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql_historial);
    $stmt->bind_param("iis", $pastor_id, $iglesia_id, $fecha_asignacion);
    $stmt->execute();
    $stmt->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&msg=' . urlencode('Iglesia asignada exitosamente. Se creó el acceso al sistema y el miembro en plena relación.'));
    exit;
    
} catch (Exception $e) {
    // Revertir transacción
    $conexion->rollback();
    
    error_log("Error al asignar iglesia: " . $e->getMessage());
    header('Location: asignar_iglesia.php?id=' . $pastor_id . '&error=' . urlencode('Error al asignar: ' . $e->getMessage()));
    exit;
}
