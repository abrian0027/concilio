<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$nacionalidad_id = !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null;
$clave = $_POST['clave'] ?? '';
$clave_confirmar = $_POST['clave_confirmar'] ?? '';
$rol_id = (int)($_POST['rol_id'] ?? 0);
$conferencia_id = !empty($_POST['conferencia_id']) ? (int)$_POST['conferencia_id'] : null;
$distrito_id = !empty($_POST['distrito_id']) ? (int)$_POST['distrito_id'] : null;
$iglesia_id = !empty($_POST['iglesia_id']) ? (int)$_POST['iglesia_id'] : null;
$ministerio_id = !empty($_POST['ministerio_id']) ? (int)$_POST['ministerio_id'] : null;
$activo = (int)($_POST['activo'] ?? 1);

// El usuario será la cédula
$usuario = $cedula;

// Validaciones básicas
if ($nombre === '' || $apellido === '' || $cedula === '' || $telefono === '' || $rol_id === 0) {
    header("Location: crear.php?error=Faltan campos obligatorios");
    exit;
}

// Validar formato cédula
if (!preg_match('/^\d{3}-\d{7}-\d{1}$/', $cedula)) {
    header("Location: crear.php?error=La cédula debe tener el formato: 000-0000000-0");
    exit;
}

if ($clave === '' || strlen($clave) < 6) {
    header("Location: crear.php?error=La contraseña debe tener al menos 6 caracteres");
    exit;
}

if ($clave !== $clave_confirmar) {
    header("Location: crear.php?error=Las contraseñas no coinciden");
    exit;
}

// Verificar que el usuario (cédula) no exista
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: crear.php?error=Ya existe un usuario con esta cédula");
    exit;
}
$stmt->close();

// Obtener nombre del rol para validar asignaciones
$stmt = $conexion->prepare("SELECT nombre FROM roles WHERE id = ?");
$stmt->bind_param("i", $rol_id);
$stmt->execute();
$rol_result = $stmt->get_result()->fetch_assoc();
$rol_nombre = $rol_result['nombre'] ?? '';
$stmt->close();

// Determinar si es un rol que requiere crear miembro
$roles_iglesia = ['pastor', 'secretaria', 'tesorero', 'lider_ministerio'];
$crear_miembro = in_array($rol_nombre, $roles_iglesia);

// Limpiar asignaciones según el rol
switch ($rol_nombre) {
    case 'super_admin':
    case 'obispo':
        $conferencia_id = null;
        $distrito_id = null;
        $iglesia_id = null;
        $ministerio_id = null;
        break;
    case 'super_conferencia':
        $distrito_id = null;
        $iglesia_id = null;
        $ministerio_id = null;
        if (!$conferencia_id) {
            header("Location: crear.php?error=Debe seleccionar una conferencia para este rol");
            exit;
        }
        break;
    case 'super_distrito':
        $iglesia_id = null;
        $ministerio_id = null;
        if (!$conferencia_id || !$distrito_id) {
            header("Location: crear.php?error=Debe seleccionar conferencia y distrito para este rol");
            exit;
        }
        break;
    case 'pastor':
    case 'secretaria':
    case 'tesorero':
        $ministerio_id = null;
        if (!$conferencia_id || !$distrito_id || !$iglesia_id) {
            header("Location: crear.php?error=Debe seleccionar conferencia, distrito e iglesia para este rol");
            exit;
        }
        break;
    case 'lider_ministerio':
        if (!$iglesia_id || !$ministerio_id) {
            header("Location: crear.php?error=Debe seleccionar iglesia y ministerio para este rol");
            exit;
        }
        break;
}

// Hash de la contraseña
$clave_hash = password_hash($clave, PASSWORD_DEFAULT);

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. INSERTAR USUARIO
    // Escapar valores para manejar NULLs
    $correo_esc = !empty($correo) ? "'" . $conexion->real_escape_string($correo) . "'" : "NULL";
    $conferencia_id_esc = $conferencia_id !== null ? (int)$conferencia_id : "NULL";
    $distrito_id_esc = $distrito_id !== null ? (int)$distrito_id : "NULL";
    $iglesia_id_esc = $iglesia_id !== null ? (int)$iglesia_id : "NULL";
    $ministerio_id_esc = $ministerio_id !== null ? (int)$ministerio_id : "NULL";
    
    $sql_usuario = "INSERT INTO usuarios (
                        nombre, apellido, usuario, clave, correo, 
                        rol_id, conferencia_id, distrito_id, iglesia_id, ministerio_id, activo
                    ) VALUES (
                        '" . $conexion->real_escape_string($nombre) . "', 
                        '" . $conexion->real_escape_string($apellido) . "', 
                        '" . $conexion->real_escape_string($usuario) . "', 
                        '" . $conexion->real_escape_string($clave_hash) . "', 
                        $correo_esc,
                        $rol_id, 
                        $conferencia_id_esc, 
                        $distrito_id_esc, 
                        $iglesia_id_esc, 
                        $ministerio_id_esc, 
                        $activo
                    )";
    
    if (!$conexion->query($sql_usuario)) {
        throw new Exception("Error al crear usuario: " . $conexion->error);
    }
    
    $usuario_id = $conexion->insert_id;
    
    // 2. SI ES ROL DE IGLESIA, CREAR MIEMBRO
    if ($crear_miembro && $iglesia_id) {
        // Verificar si ya existe como miembro en esta iglesia
        $check_miembro = $conexion->prepare("SELECT id FROM miembros WHERE iglesia_id = ? AND numero_documento = ?");
        $check_miembro->bind_param("is", $iglesia_id, $cedula);
        $check_miembro->execute();
        $miembro_existente = $check_miembro->get_result()->fetch_assoc();
        $check_miembro->close();
        
        // Determinar si es líder según el rol
        $es_lider = in_array($rol_nombre, ['pastor', 'secretaria', 'tesorero']) ? 1 : 0;
        
        // Escapar valores para miembro
        $nacionalidad_id_esc = $nacionalidad_id !== null ? (int)$nacionalidad_id : "NULL";
        $ministerio_miembro_esc = $ministerio_id !== null ? (int)$ministerio_id : "NULL";
        
        if ($miembro_existente) {
            // Actualizar miembro existente
            $sql_miembro = "UPDATE miembros SET 
                            nombre = '" . $conexion->real_escape_string($nombre) . "', 
                            apellido = '" . $conexion->real_escape_string($apellido) . "',
                            telefono = '" . $conexion->real_escape_string($telefono) . "',
                            nacionalidad_id = $nacionalidad_id_esc,
                            estado_miembro = 'en_plena',
                            ministerio_id = $ministerio_miembro_esc,
                            es_lider = $es_lider, 
                            estado = 'activo'
                            WHERE id = " . $miembro_existente['id'];
            
            if (!$conexion->query($sql_miembro)) {
                throw new Exception("Error al actualizar miembro: " . $conexion->error);
            }
        } else {
            // Crear nuevo miembro
            $sql_miembro = "INSERT INTO miembros (
                            iglesia_id, nombre, apellido, sexo, 
                            nacionalidad_id, tipo_documento, numero_documento, telefono,
                            estado_miembro, ministerio_id, es_lider, estado
                            ) VALUES (
                            $iglesia_id, 
                            '" . $conexion->real_escape_string($nombre) . "', 
                            '" . $conexion->real_escape_string($apellido) . "', 
                            'M',
                            $nacionalidad_id_esc, 
                            'cedula', 
                            '" . $conexion->real_escape_string($cedula) . "', 
                            '" . $conexion->real_escape_string($telefono) . "',
                            'en_plena',
                            $ministerio_miembro_esc,
                            $es_lider, 
                            'activo'
                            )";
            
            if (!$conexion->query($sql_miembro)) {
                throw new Exception("Error al crear miembro: " . $conexion->error);
            }
        }
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    $mensaje = "Usuario creado exitosamente.";
    if ($crear_miembro) {
        $mensaje .= " También se registró como miembro de la iglesia.";
    }
    
    header("Location: index.php?success=" . urlencode($mensaje));
    exit;
    
} catch (Exception $e) {
    // Revertir transacción
    $conexion->rollback();
    
    error_log("Error al crear usuario: " . $e->getMessage());
    header("Location: crear.php?error=" . urlencode($e->getMessage()));
    exit;
}