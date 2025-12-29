<?php
/**
 * Actualizar Visita - Sistema Concilio
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../config/config.php';

// Solo pastor, secretaria o super_admin pueden actualizar
$puede_editar = in_array($ROL_NOMBRE, ['super_admin', 'pastor', 'secretaria']);

if (!$puede_editar) {
    header("Location: index.php?error=No tienes permiso para esta acción");
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Obtener datos
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nombre = isset($_POST['nombre']) ? trim(strtoupper($_POST['nombre'])) : '';
$apellido = isset($_POST['apellido']) ? trim(strtoupper($_POST['apellido'])) : '';
$sexo = isset($_POST['sexo']) ? $_POST['sexo'] : '';
$nacionalidad_id = !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null;
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
$fecha_visita = isset($_POST['fecha_visita']) ? $_POST['fecha_visita'] : '';
$invitado_por = !empty($_POST['invitado_por']) ? (int)$_POST['invitado_por'] : null;
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$estado = isset($_POST['estado']) && in_array($_POST['estado'], ['activo', 'inactivo']) ? $_POST['estado'] : 'activo';

// Validaciones
$errores = [];

if ($id === 0) {
    header("Location: index.php?error=ID no válido");
    exit;
}

if (empty($nombre)) {
    $errores[] = "El nombre es requerido";
}
if (empty($apellido)) {
    $errores[] = "El apellido es requerido";
}
if (!in_array($sexo, ['M', 'F'])) {
    $errores[] = "Seleccione un sexo válido";
}
if (empty($categoria)) {
    $errores[] = "Seleccione una categoría";
}
if (empty($fecha_visita)) {
    $errores[] = "La fecha de visita es requerida";
}

// Validar categoría
$categorias_validas = ['damas', 'caballeros', 'jovenes', 'jovencitos', 'ninos'];
if (!in_array($categoria, $categorias_validas)) {
    $errores[] = "Categoría no válida";
}

if (!empty($errores)) {
    $error_msg = implode(", ", $errores);
    header("Location: editar.php?id=" . $id . "&error=" . urlencode($error_msg));
    exit;
}

// Verificar que la visita existe y pertenece a la iglesia
$sql_check = "SELECT id, iglesia_id, convertido_miembro FROM visitas WHERE id = ?";
if ($ROL_NOMBRE !== 'super_admin') {
    $sql_check .= " AND iglesia_id = " . (int)$IGLESIA_ID;
}
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    header("Location: index.php?error=Visita no encontrada o no tienes permiso");
    exit;
}

$visita = $result_check->fetch_assoc();

// No permitir editar si ya fue convertida
if ($visita['convertido_miembro']) {
    header("Location: index.php?error=Esta visita ya fue convertida a miembro y no puede editarse");
    exit;
}

// Actualizar la visita
$sql = "UPDATE visitas SET 
        nombre = ?, 
        apellido = ?, 
        sexo = ?, 
        nacionalidad_id = ?, 
        telefono = ?, 
        categoria = ?, 
        fecha_visita = ?, 
        invitado_por = ?, 
        observaciones = ?,
        estado = ?
        WHERE id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "sssissssssi",
    $nombre,
    $apellido,
    $sexo,
    $nacionalidad_id,
    $telefono,
    $categoria,
    $fecha_visita,
    $invitado_por,
    $observaciones,
    $estado,
    $id
);

if ($stmt->execute()) {
    header("Location: index.php?success=Visita actualizada correctamente");
    exit;
} else {
    header("Location: editar.php?id=" . $id . "&error=" . urlencode("Error al actualizar: " . $conexion->error));
    exit;
}
