<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../admin/includes/auditoria.php';

// Registrar logout antes de destruir la sesión
auditoria_logout();

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>