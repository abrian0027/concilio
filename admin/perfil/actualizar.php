<?php
declare(strict_types=1);

/**
 * Actualizar información de perfil (correo, teléfono)
 * Sistema Concilio
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

// Validaciones
$errores = [];

// Validar correo si se proporciona
if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo electrónico no es válido.";
}

// Verificar que el correo no esté en uso por otro usuario
if (!empty($correo)) {
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
    $stmt->bind_param("si", $correo, $usuario_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = "Este correo ya está registrado por otro usuario.";
    }
    $stmt->close();
}

// Limpiar teléfono (solo números y guiones)
$telefono = preg_replace('/[^0-9\-\s\+\(\)]/', '', $telefono);

if (!empty($errores)) {
    $_SESSION['perfil_mensaje'] = implode('<br>', $errores);
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Actualizar datos
$stmt = $conexion->prepare("UPDATE usuarios SET correo = ?, telefono = ? WHERE id = ?");
$correo_db = !empty($correo) ? $correo : null;
$telefono_db = !empty($telefono) ? $telefono : null;
$stmt->bind_param("ssi", $correo_db, $telefono_db, $usuario_id);

if ($stmt->execute()) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-check-circle me-1'></i> Información actualizada correctamente.";
    $_SESSION['perfil_tipo'] = 'success';
} else {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al actualizar la información.";
    $_SESSION['perfil_tipo'] = 'danger';
}
$stmt->close();

header('Location: index.php');
exit;
