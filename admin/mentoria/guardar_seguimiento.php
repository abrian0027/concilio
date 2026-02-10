<?php
/**
 * Guardar Seguimiento - Sistema Concilio
 * Procesa el guardado de un nuevo seguimiento de mentoría
 */

require_once __DIR__ . '/../../config/config.php';

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
$puede_editar = in_array($rol_nombre, ['pastor', 'secretaria']);

if (!$puede_editar) {
    header("Location: index.php?error=No tienes permiso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$mentoria_id = isset($_POST['mentoria_id']) ? (int)$_POST['mentoria_id'] : 0;
$fecha_reunion = isset($_POST['fecha_reunion']) ? trim($_POST['fecha_reunion']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$proximos_pasos = isset($_POST['proximos_pasos']) ? trim($_POST['proximos_pasos']) : '';

// Validaciones
if ($mentoria_id === 0 || empty($fecha_reunion) || empty($descripcion)) {
    header("Location: ver.php?id=$mentoria_id&error=Datos incompletos");
    exit;
}

// Verificar que la mentoría existe y está activa
$stmt = $conexion->prepare("SELECT id FROM mentorias WHERE id = ? AND estado = 'activa'");
$stmt->bind_param("i", $mentoria_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header("Location: index.php?error=Mentoría no válida o no activa");
    exit;
}

// Guardar seguimiento
$stmt = $conexion->prepare("INSERT INTO mentoria_seguimientos (mentoria_id, fecha_reunion, descripcion, proximos_pasos, registrado_por) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $mentoria_id, $fecha_reunion, $descripcion, $proximos_pasos, $_SESSION['usuario_id']);

if ($stmt->execute()) {
    header("Location: ver.php?id=$mentoria_id&exito=" . urlencode("Seguimiento registrado"));
} else {
    header("Location: ver.php?id=$mentoria_id&error=" . urlencode("Error al guardar"));
}
exit;
