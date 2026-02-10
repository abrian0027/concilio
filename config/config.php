<?php
declare(strict_types=1);

/**
 * Configuración principal del sistema
 * Compatible con PHP 8.1+
 * Detecta automáticamente entorno local/producción
 */

// ============================================
// DETECCIÓN DE ENTORNO
// ============================================
$is_local = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1']) 
            || strpos($_SERVER['SERVER_NAME'] ?? '', '.local') !== false;

// ============================================
// CONFIGURACIÓN DE ERRORES
// ============================================
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// ============================================
// CONFIGURACIÓN DE ZONA HORARIA
// ============================================
date_default_timezone_set('America/Santo_Domingo');

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
if ($is_local) {
    // Entorno LOCAL (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'concilio_multitenencia');
} else {
    // Entorno PRODUCCIÓN (cPanel)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'iglesiametodista_concilio');
    define('DB_PASS', 'lamisma2783m');
    define('DB_NAME', 'iglesiametodista_concilio');
}
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURACIÓN DE URLs
// ============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($is_local) {
    define('SITE_URL', $protocol . '://' . $host . '/concilio');
    define('BASE_PATH', '/concilio');
} else {
    define('SITE_URL', 'https://app.iglesiametodistalibrerd.com');
    define('BASE_PATH', '');
}

define('SITE_NAME', 'Concilio IML');

// ============================================
// CONFIGURACIÓN DE CORREO (SMTP)
// ============================================
if ($is_local) {
    // LOCAL: Puedes usar Mailtrap, o dejarlo deshabilitado
    define('MAIL_ENABLED', false); // Cambiar a true para probar
    define('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
    define('MAIL_PORT', 587);
    define('MAIL_USERNAME', ''); // Tu usuario de Mailtrap
    define('MAIL_PASSWORD', ''); // Tu password de Mailtrap
    define('MAIL_ENCRYPTION', 'tls');
} else {
    // PRODUCCIÓN: Configuración del servidor cPanel
    define('MAIL_ENABLED', true);
    define('MAIL_HOST', 'mail.iglesiametodistalibrerd.com');
    define('MAIL_PORT', 465); // Puerto SSL para cPanel
    define('MAIL_USERNAME', 'notificaciones@iglesiametodistalibrerd.com');
    define('MAIL_PASSWORD', 'Lamisma2783m');
    define('MAIL_ENCRYPTION', 'ssl'); // SSL para puerto 465
}
define('MAIL_FROM_EMAIL', 'notificaciones@iglesiametodistalibrerd.com');
define('MAIL_FROM_NAME', 'Sistema Concilio IML');

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
    
    if (!$is_local) {
        ini_set('session.cookie_secure', '1'); // HTTPS en producción
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    session_start();
}