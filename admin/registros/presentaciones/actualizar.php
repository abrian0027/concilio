<?php
/**
 * Actualizar Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Solo accesible para la iglesia local
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
if (!in_array($rol_nombre, $roles_permitidos)) {
    header('Location: index.php?error=No tiene permisos. Solo Pastor o Secretaria.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($id <= 0 || $iglesia_id <= 0) {
    header('Location: index.php?error=Datos no válidos o sin iglesia asignada');
    exit;
}

// Verificar que el registro existe
$stmt = $conexion->prepare("SELECT id, estado FROM presentacion_ninos WHERE id = ? AND iglesia_id = ?");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    header('Location: index.php?error=Registro no encontrado');
    exit;
}

if ($registro['estado'] != 'activo') {
    header('Location: editar.php?id=' . $id . '&error=No se puede editar un acta anulada');
    exit;
}

// Recoger datos del formulario
$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$sexo = $_POST['sexo'] ?? 'M';
$nacionalidad = trim($_POST['nacionalidad'] ?? '');
$nombre_padre = trim($_POST['nombre_padre'] ?? '');
$nombre_madre = trim($_POST['nombre_madre'] ?? '');
$fecha_presentacion = $_POST['fecha_presentacion'] ?? '';
$lugar = trim($_POST['lugar'] ?? '');
$ministro = trim($_POST['ministro'] ?? '');
$testigo1 = trim($_POST['testigo1'] ?? '');
$testigo2 = trim($_POST['testigo2'] ?? '');
$libro_no = trim($_POST['libro_no'] ?? '');
$folio = trim($_POST['folio'] ?? '');
$acta_civil_no = trim($_POST['acta_civil_no'] ?? '');
$acta_civil_anio = !empty($_POST['acta_civil_anio']) ? (int)$_POST['acta_civil_anio'] : 0;
$oficilia_civil = trim($_POST['oficilia_civil'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// Validaciones
if (empty($nombres) || empty($fecha_nacimiento) || empty($fecha_presentacion)) {
    header('Location: editar.php?id=' . $id . '&error=Nombres, fecha de nacimiento y fecha de presentación son obligatorios');
    exit;
}

// Validar fechas
if (strtotime($fecha_presentacion) < strtotime($fecha_nacimiento)) {
    header('Location: editar.php?id=' . $id . '&error=La fecha de presentación no puede ser anterior al nacimiento');
    exit;
}

// Actualizar registro
$sql = "UPDATE presentacion_ninos SET 
    nombres = ?, 
    apellidos = ?, 
    fecha_nacimiento = ?, 
    sexo = ?, 
    nacionalidad = ?,
    nombre_padre = ?, 
    nombre_madre = ?, 
    fecha_presentacion = ?, 
    lugar = ?,
    ministro = ?, 
    testigo1 = ?, 
    testigo2 = ?, 
    libro_no = ?, 
    folio = ?,
    acta_civil_no = ?, 
    acta_civil_anio = ?, 
    oficilia_civil = ?, 
    observaciones = ?,
    actualizado_en = NOW(), 
    actualizado_por = ?
    WHERE id = ? AND iglesia_id = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    error_log("Error preparando consulta actualizar.php: " . $conexion->error);
    header('Location: editar.php?id=' . $id . '&error=Error preparando consulta: ' . urlencode($conexion->error));
    exit;
}

// Bind: 21 parámetros en total
// 15 strings + 1 int (año) + 2 strings + 3 ints = sssssssssssssssissiii (21 chars)
$stmt->bind_param(
    "sssssssssssssssissiii",
    $nombres,           // 1 s
    $apellidos,         // 2 s
    $fecha_nacimiento,  // 3 s
    $sexo,              // 4 s
    $nacionalidad,      // 5 s
    $nombre_padre,      // 6 s
    $nombre_madre,      // 7 s
    $fecha_presentacion,// 8 s
    $lugar,             // 9 s
    $ministro,          // 10 s
    $testigo1,          // 11 s
    $testigo2,          // 12 s
    $libro_no,          // 13 s
    $folio,             // 14 s
    $acta_civil_no,     // 15 s
    $acta_civil_anio,   // 16 i
    $oficilia_civil,    // 17 s
    $observaciones,     // 18 s
    $usuario_id,        // 19 i (actualizado_por)
    $id,                // 20 i (WHERE id)
    $iglesia_id         // 21 i (WHERE iglesia_id)
);

if ($stmt->execute()) {
    $stmt->close();
    
    // Registrar auditoría (estructura correcta de la tabla)
    $accion = 'editar';
    $modulo = 'presentaciones';
    $tabla = 'presentacion_ninos';
    $descripcion = "Actualizó presentación ID: $id, Niño/a: $nombres $apellidos";
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    $stmt_audit = $conexion->prepare("INSERT INTO auditoria (usuario_id, accion, modulo, tabla_afectada, registro_id, descripcion, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt_audit) {
        $stmt_audit->bind_param("isssisss", $usuario_id, $accion, $modulo, $tabla, $id, $descripcion, $ip, $user_agent);
        $stmt_audit->execute();
        $stmt_audit->close();
    }
    
    header('Location: ver.php?id=' . $id . '&success=Registro actualizado correctamente');
} else {
    $error = $stmt->error;
    error_log("Error ejecutando actualizar.php: " . $error);
    $stmt->close();
    header('Location: editar.php?id=' . $id . '&error=Error al actualizar: ' . urlencode($error));
}

$conexion->close();
?>
