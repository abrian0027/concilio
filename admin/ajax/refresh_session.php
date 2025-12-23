<?php
session_start();
header('Content-Type: application/json');

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();

// Si se solicita refrescar completamente la sesión
if (isset($_POST['refresh'])) {
    // Resetear tiempo de sesión
    $_SESSION['session_start'] = time();
}

echo json_encode([
    'success' => true,
    'timestamp' => time(),
    'message' => 'Sesión actualizada'
]);
exit;