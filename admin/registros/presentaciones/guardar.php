<?php
/**
 * Guardar Nueva Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Solo accesible para la iglesia local
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../auth/login.php');
    exit;
}

// Verificar permisos - Solo roles de iglesia local
$roles_permitidos = array('pastor', 'secretaria');
$rol = strtolower($_SESSION['rol_nombre'] ?? '');
if (!in_array($rol, $roles_permitidos)) {
    header('Location: index.php?error=' . urlencode('Sin permisos. Solo Pastor o Secretaria.'));
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?error=' . urlencode('Método no permitido'));
    exit;
}

$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($iglesia_id <= 0) {
    header('Location: index.php?error=' . urlencode('No tiene una iglesia asignada'));
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

if ($iglesia_id <= 0) {
    header('Location: index.php?error=' . urlencode('No tiene una iglesia asignada'));
    exit;
}

// Obtener y sanitizar datos
$numero_acta = trim($_POST['numero_acta'] ?? '');
$nombres = trim($_POST['nombres'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$sexo = $_POST['sexo'] ?? 'M';
$nacionalidad = trim($_POST['nacionalidad'] ?? 'Dominicana');

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
$acta_civil_anio = !empty($_POST['acta_civil_anio']) ? (int)$_POST['acta_civil_anio'] : null;
$oficilia_civil = trim($_POST['oficilia_civil'] ?? '');

$observaciones = trim($_POST['observaciones'] ?? '');

// Validaciones
$errores = array();

if (empty($nombres)) {
    $errores[] = 'El nombre del niño/a es obligatorio';
}

if (empty($fecha_nacimiento)) {
    $errores[] = 'La fecha de nacimiento es obligatoria';
}

if (empty($fecha_presentacion)) {
    $errores[] = 'La fecha de presentación es obligatoria';
}

if (!empty($fecha_nacimiento) && !empty($fecha_presentacion)) {
    if (strtotime($fecha_presentacion) < strtotime($fecha_nacimiento)) {
        $errores[] = 'La fecha de presentación no puede ser anterior a la fecha de nacimiento';
    }
}

if (!in_array($sexo, array('M', 'F'))) {
    $sexo = 'M';
}

if (!empty($errores)) {
    header('Location: crear.php?error=' . urlencode(implode('. ', $errores)));
    exit;
}

// Verificar que el número de acta no exista
$stmt = $conexion->prepare("SELECT id FROM presentacion_ninos WHERE iglesia_id = ? AND numero_acta = ?");
$stmt->bind_param("is", $iglesia_id, $numero_acta);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    // Generar nuevo número
    $anio_actual = date('Y');
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_acta, '-', 1) AS UNSIGNED)) as ultimo 
            FROM presentacion_ninos 
            WHERE iglesia_id = ? AND numero_acta LIKE ?";
    $stmt2 = $conexion->prepare($sql);
    $like_anio = "%-{$anio_actual}";
    $stmt2->bind_param("is", $iglesia_id, $like_anio);
    $stmt2->execute();
    $resultado = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    $siguiente = ($resultado['ultimo'] ?? 0) + 1;
    $numero_acta = str_pad($siguiente, 3, '0', STR_PAD_LEFT) . '-' . $anio_actual;
}
$stmt->close();

// Si el lugar está vacío, usar nombre de la iglesia
if (empty($lugar)) {
    $stmt = $conexion->prepare("SELECT nombre FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $igl = $stmt->get_result()->fetch_assoc();
    $lugar = $igl['nombre'] ?? '';
    $stmt->close();
}

// Insertar registro
$sql = "INSERT INTO presentacion_ninos (
            iglesia_id, numero_acta, nombres, apellidos, fecha_nacimiento, sexo, nacionalidad,
            nombre_padre, nombre_madre, fecha_presentacion, lugar, ministro, testigo1, testigo2,
            libro_no, folio, acta_civil_no, acta_civil_anio, oficilia_civil, observaciones,
            estado, creado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "isssssssssssssssssssi",
    $iglesia_id,
    $numero_acta,
    $nombres,
    $apellidos,
    $fecha_nacimiento,
    $sexo,
    $nacionalidad,
    $nombre_padre,
    $nombre_madre,
    $fecha_presentacion,
    $lugar,
    $ministro,
    $testigo1,
    $testigo2,
    $libro_no,
    $folio,
    $acta_civil_no,
    $acta_civil_anio,
    $oficilia_civil,
    $observaciones,
    $usuario_id
);

if ($stmt->execute()) {
    $nuevo_id = $stmt->insert_id;
    $stmt->close();
    
    // Redirigir a ver el acta creada
    header('Location: ver.php?id=' . $nuevo_id . '&success=' . urlencode('Presentación registrada correctamente. Acta No. ' . $numero_acta));
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    header('Location: crear.php?error=' . urlencode('Error al guardar: ' . $error));
    exit;
}
