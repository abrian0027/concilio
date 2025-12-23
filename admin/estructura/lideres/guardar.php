<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos
$puede_asignar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_asignar) {
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
$area_id = (int)($_POST['area_id'] ?? 0);
$miembro_id = (int)($_POST['miembro_id'] ?? 0);
$tipo = $_POST['tipo'] ?? 'lider';

// Validaciones
if ($iglesia_id === 0 || $periodo_id === 0 || $area_id === 0 || $miembro_id === 0) {
    header("Location: asignar.php?iglesia_id=$iglesia_id&error=Faltan datos obligatorios");
    exit;
}

if (!in_array($tipo, ['lider', 'colider'])) {
    $tipo = 'lider';
}

// Verificar si ya es líder de esa área en este período
$stmt = $conexion->prepare("SELECT id FROM area_lideres 
                            WHERE iglesia_id = ? AND area_id = ? AND periodo_id = ? AND miembro_id = ? AND activo = 1");
$stmt->bind_param("iiii", $iglesia_id, $area_id, $periodo_id, $miembro_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: asignar.php?iglesia_id=$iglesia_id&error=Este miembro ya es líder de esta área en el período actual");
    exit;
}
$stmt->close();

// Si es líder principal, verificar que no haya otro líder principal
if ($tipo === 'lider') {
    $stmt = $conexion->prepare("SELECT id FROM area_lideres 
                                WHERE iglesia_id = ? AND area_id = ? AND periodo_id = ? AND tipo = 'lider' AND activo = 1");
    $stmt->bind_param("iii", $iglesia_id, $area_id, $periodo_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: asignar.php?iglesia_id=$iglesia_id&error=Ya existe un líder principal para esta área. Puede asignar como co-líder.");
        exit;
    }
    $stmt->close();
}

try {
    $stmt = $conexion->prepare("INSERT INTO area_lideres (iglesia_id, area_id, periodo_id, miembro_id, tipo, activo) 
                                VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->bind_param("iiiis", $iglesia_id, $area_id, $periodo_id, $miembro_id, $tipo);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=asignado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al asignar líder: " . $e->getMessage());
    header("Location: asignar.php?iglesia_id=$iglesia_id&error=Error al guardar");
    exit;
}