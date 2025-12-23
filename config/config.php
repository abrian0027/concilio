<?php
declare(strict_types=1);

/**
 * Configuración principal del sistema
 * Compatible con PHP 8.1+
 */

// ============================================
// CONFIGURACIÓN DE ERRORES
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', '0'); // 0 en producción, 1 en desarrollo
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// ============================================
// CONFIGURACIÓN DE ZONA HORARIA
// ============================================
date_default_timezone_set('America/Santo_Domingo');

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'concilio_multitenencia');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONEXIÓN A BASE DE DATOS (MySQLi)
// ============================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conexion->set_charset(DB_CHARSET);
} catch (Exception $e) {
    error_log("Error de conexión a BD: " . $e->getMessage());
    die("Error al conectar con la base de datos. Por favor contacte al administrador.");
}

// ============================================
// CONFIGURACIÓN DE SESIONES
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', '0'); // Cambiar a 1 si usas HTTPS
    session_start();
}