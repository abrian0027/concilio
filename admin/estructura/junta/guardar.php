<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_crear = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_crear) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos
$iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
$periodo_id = (int)($_POST['periodo_id'] ?? 0);
$tipo = $_POST['tipo'] ?? '5';

// Validaciones
if ($iglesia_id === 0 || $periodo_id === 0) {
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Datos incompletos");
    exit;
}

if (!in_array($tipo, ['5', '7'])) {
    $tipo = '5';
}

// Iniciar transacción
$conexion->begin_transaction();

try {
    // Desactivar juntas anteriores de esta iglesia
    $stmt = $conexion->prepare("UPDATE juntas SET activa = 0 WHERE iglesia_id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $stmt->close();
    
    // Crear nueva junta
    $stmt = $conexion->prepare("INSERT INTO juntas (iglesia_id, periodo_id, tipo, activa) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("iis", $iglesia_id, $periodo_id, $tipo);
    $stmt->execute();
    $junta_id = $conexion->insert_id;
    $stmt->close();
    
    $conexion->commit();
    
    header("Location: asignar.php?junta_id=$junta_id&success=creado");
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    error_log("Error al crear junta: " . $e->getMessage());
    header("Location: crear.php?iglesia_id=$iglesia_id&error=Error al guardar");
    exit;
}