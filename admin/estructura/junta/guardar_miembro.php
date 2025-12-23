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
$junta_id = (int)($_POST['junta_id'] ?? 0);
$iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
$miembro_id = (int)($_POST['miembro_id'] ?? 0);
$cargo_id = (int)($_POST['cargo_id'] ?? 0);
$es_pastor = (int)($_POST['es_pastor'] ?? 0);

// Validaciones
if ($junta_id === 0 || $miembro_id === 0 || $cargo_id === 0) {
    header("Location: asignar.php?junta_id=$junta_id&error=Faltan datos obligatorios");
    exit;
}

// Verificar que la junta exista y esté activa
$stmt = $conexion->prepare("SELECT tipo FROM juntas WHERE id = ? AND activa = 1");
$stmt->bind_param("i", $junta_id);
$stmt->execute();
$junta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$junta) {
    header("Location: index.php?iglesia_id=$iglesia_id&error=Junta no encontrada o no está activa");
    exit;
}

// Verificar que el miembro no esté ya en la junta
$stmt = $conexion->prepare("SELECT id FROM junta_miembros WHERE junta_id = ? AND miembro_id = ? AND activo = 1");
$stmt->bind_param("ii", $junta_id, $miembro_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: asignar.php?junta_id=$junta_id&error=Este miembro ya está en la junta");
    exit;
}
$stmt->close();

// Si no es pastor, verificar límite
if ($es_pastor === 0) {
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM junta_miembros WHERE junta_id = ? AND es_pastor = 0 AND activo = 1");
    $stmt->bind_param("i", $junta_id);
    $stmt->execute();
    $conteo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($conteo['total'] >= (int)$junta['tipo']) {
        header("Location: asignar.php?junta_id=$junta_id&error=Se alcanzó el límite de miembros. Solo puede agregar pastores.");
        exit;
    }
}

try {
    $stmt = $conexion->prepare("INSERT INTO junta_miembros (junta_id, miembro_id, cargo_id, es_pastor, activo) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("iiii", $junta_id, $miembro_id, $cargo_id, $es_pastor);
    $stmt->execute();
    $stmt->close();
    
    header("Location: index.php?iglesia_id=$iglesia_id&success=miembro_agregado");
    exit;
    
} catch (Exception $e) {
    error_log("Error al agregar miembro a junta: " . $e->getMessage());
    header("Location: asignar.php?junta_id=$junta_id&error=Error al guardar");
    exit;
}