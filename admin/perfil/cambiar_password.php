<?php
declare(strict_types=1);

/**
 * Cambiar contraseña del usuario
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
$password_actual = $_POST['password_actual'] ?? '';
$password_nuevo = $_POST['password_nuevo'] ?? '';
$password_confirmar = $_POST['password_confirmar'] ?? '';

// Validaciones
$errores = [];

if (empty($password_actual)) {
    $errores[] = "Debe ingresar su contraseña actual.";
}

if (empty($password_nuevo)) {
    $errores[] = "Debe ingresar la nueva contraseña.";
}

if (strlen($password_nuevo) < 6) {
    $errores[] = "La nueva contraseña debe tener al menos 6 caracteres.";
}

if ($password_nuevo !== $password_confirmar) {
    $errores[] = "Las contraseñas no coinciden.";
}

if (!empty($errores)) {
    $_SESSION['perfil_mensaje'] = implode('<br>', $errores);
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Verificar contraseña actual
$stmt = $conexion->prepare("SELECT clave FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($password_actual, $usuario['clave'])) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> La contraseña actual es incorrecta.";
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Actualizar contraseña
$nueva_clave_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
$stmt = $conexion->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
$stmt->bind_param("si", $nueva_clave_hash, $usuario_id);

if ($stmt->execute()) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-check-circle me-1'></i> Contraseña actualizada correctamente.";
    $_SESSION['perfil_tipo'] = 'success';
} else {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al actualizar la contraseña.";
    $_SESSION['perfil_tipo'] = 'danger';
}
$stmt->close();

header('Location: index.php');
exit;
