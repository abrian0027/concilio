<?php
/**
 * Archivo de Seguridad - Sistema Concilio
 * Verifica sesión y carga variables globales
 */

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    $script_path = $_SERVER['SCRIPT_NAME'];
    $admin_pos = strpos($script_path, '/admin/');
    if ($admin_pos !== false) {
        $after_admin = substr($script_path, $admin_pos + 7);
        $depth = substr_count($after_admin, '/');
    } else {
        $depth = 0;
    }
    $base = str_repeat('../', $depth);
    header('Location: ' . $base . '../auth/login.php');
    exit;
}

// Cargar configuración de base de datos
$config_paths = array(
    __DIR__ . '/../../config/config.php',
    __DIR__ . '/../config/config.php',
    dirname(__DIR__) . '/config/config.php'
);

foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

// Variables globales del usuario
$USUARIO_ID = $_SESSION['usuario_id'];
$USUARIO_NOMBRE = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : (isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario');
$ROL_NOMBRE = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : 'sin_rol';
$IGLESIA_ID = isset($_SESSION['iglesia_id']) ? $_SESSION['iglesia_id'] : null;
$IGLESIA_NOMBRE = isset($_SESSION['iglesia_nombre']) ? $_SESSION['iglesia_nombre'] : '';

// Función para obtener iniciales del nombre
function obtenerIniciales($nombre) {
    $palabras = explode(' ', trim($nombre));
    $iniciales = '';
    $count = 0;
    foreach ($palabras as $palabra) {
        if ($count < 2 && strlen($palabra) > 0) {
            $iniciales .= strtoupper(substr($palabra, 0, 1));
            $count++;
        }
    }
    return $iniciales ? $iniciales : 'U';
}

// Función para formatear fecha
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (empty($fecha)) return '-';
    return date($formato, strtotime($fecha));
}

// Función para formatear moneda RD
function formatearMoneda($monto, $simbolo = 'RD$') {
    return $simbolo . ' ' . number_format($monto, 2, '.', ',');
}
