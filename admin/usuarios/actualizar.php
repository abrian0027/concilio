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
$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$clave = $_POST['clave'] ?? '';
$clave_confirmar = $_POST['clave_confirmar'] ?? '';
$rol_id = (int)($_POST['rol_id'] ?? 0);
$conferencia_id = !empty($_POST['conferencia_id']) ? (int)$_POST['conferencia_id'] : null;
$distrito_id = !empty($_POST['distrito_id']) ? (int)$_POST['distrito_id'] : null;
$iglesia_id = !empty($_POST['iglesia_id']) ? (int)$_POST['iglesia_id'] : null;
$ministerio_id = !empty($_POST['ministerio_id']) ? (int)$_POST['ministerio_id'] : null;
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones básicas
if ($id === 0 || $nombre === '' || $apellido === '' || $usuario === '' || $rol_id === 0) {
    header("Location: editar.php?id=$id&error=Faltan campos obligatorios");
    exit;
}

// Si se ingresó contraseña, validar
if ($clave !== '') {
    if (strlen($clave) < 6) {
        header("Location: editar.php?id=$id&error=La contraseña debe tener al menos 6 caracteres");
        exit;
    }
    if ($clave !== $clave_confirmar) {
        header("Location: editar.php?id=$id&error=Las contraseñas no coinciden");
        exit;
    }
}

// Verificar que el usuario no exista (excepto el mismo)
$stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
$stmt->bind_param("si", $usuario, $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: editar.php?id=$id&error=El nombre de usuario ya existe");
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
            header("Location: editar.php?id=$id&error=Debe seleccionar una conferencia para este rol");
            exit;
        }
        break;
    case 'super_distrito':
        $iglesia_id = null;
        $ministerio_id = null;
        if (!$conferencia_id || !$distrito_id) {
            header("Location: editar.php?id=$id&error=Debe seleccionar conferencia y distrito para este rol");
            exit;
        }
        break;
    case 'pastor':
    case 'secretaria':
    case 'tesorero':
        $ministerio_id = null;
        if (!$conferencia_id || !$distrito_id || !$iglesia_id) {
            header("Location: editar.php?id=$id&error=Debe seleccionar conferencia, distrito e iglesia para este rol");
            exit;
        }
        break;
    case 'lider_ministerio':
        if (!$ministerio_id) {
            header("Location: editar.php?id=$id&error=Debe seleccionar un ministerio para este rol");
            exit;
        }
        break;
}

// Actualizar usuario
try {
    if ($clave !== '') {
        // Con cambio de contraseña
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET 
                    nombre = ?, 
                    apellido = ?, 
                    usuario = ?, 
                    clave = ?,
                    correo = ?, 
                    rol_id = ?, 
                    conferencia_id = ?, 
                    distrito_id = ?, 
                    iglesia_id = ?, 
                    ministerio_id = ?, 
                    activo = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "sssssiiiiiii",
            $nombre,
            $apellido,
            $usuario,
            $clave_hash,
            $correo,
            $rol_id,
            $conferencia_id,
            $distrito_id,
            $iglesia_id,
            $ministerio_id,
            $activo,
            $id
        );
    } else {
        // Sin cambio de contraseña
        $sql = "UPDATE usuarios SET 
                    nombre = ?, 
                    apellido = ?, 
                    usuario = ?, 
                    correo = ?, 
                    rol_id = ?, 
                    conferencia_id = ?, 
                    distrito_id = ?, 
                    iglesia_id = ?, 
                    ministerio_id = ?, 
                    activo = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "ssssiiiiiii",
            $nombre,
            $apellido,
            $usuario,
            $correo,
            $rol_id,
            $conferencia_id,
            $distrito_id,
            $iglesia_id,
            $ministerio_id,
            $activo,
            $id
        );
    }
    
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?success=editado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al actualizar usuario: " . $e->getMessage());
    header("Location: editar.php?id=$id&error=Error al actualizar el usuario");
    exit;
}