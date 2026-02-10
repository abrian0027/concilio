<?php
/**
 * Agregar Miembro a Zona - Sistema Concilio
 * Asigna un miembro a una zona específica
 */

require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../../auth/login.php");
    exit;
}

$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

// Solo pastor, secretaria o super_admin pueden hacer esto
$puede_editar = in_array($rol_nombre, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    header("Location: index.php?error=No tienes permiso para realizar esta acción");
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Obtener datos
$zona_id = isset($_POST['zona_id']) ? (int)$_POST['zona_id'] : 0;
$miembro_id = isset($_POST['miembro_id']) ? (int)$_POST['miembro_id'] : 0;

if ($zona_id === 0 || $miembro_id === 0) {
    header("Location: index.php?error=Datos incompletos");
    exit;
}

// Verificar que la zona pertenece a la iglesia del usuario (si no es super_admin)
if ($rol_nombre !== 'super_admin') {
    $stmt = $conexion->prepare("SELECT id FROM zonas WHERE id = ? AND iglesia_id = ?");
    $stmt->bind_param("ii", $zona_id, $iglesia_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        header("Location: index.php?error=Zona no válida");
        exit;
    }
}

// Verificar que el miembro existe y pertenece a la iglesia
$stmt = $conexion->prepare("SELECT id, nombre, apellido FROM miembros WHERE id = ? AND estado = 'activo'" . 
                           ($rol_nombre !== 'super_admin' ? " AND iglesia_id = $iglesia_id" : ""));
$stmt->bind_param("i", $miembro_id);
$stmt->execute();
$result = $stmt->get_result();
$miembro = $result->fetch_assoc();

if (!$miembro) {
    header("Location: ver.php?id=$zona_id&error=Miembro no válido");
    exit;
}

// Actualizar el miembro para asignarlo a la zona
$stmt = $conexion->prepare("UPDATE miembros SET zona_id = ? WHERE id = ?");
$stmt->bind_param("ii", $zona_id, $miembro_id);

if ($stmt->execute()) {
    $nombre_completo = $miembro['nombre'] . ' ' . $miembro['apellido'];
    header("Location: ver.php?id=$zona_id&exito=" . urlencode("Se agregó a \"$nombre_completo\" a la zona"));
} else {
    header("Location: ver.php?id=$zona_id&error=Error al agregar miembro");
}
exit;
