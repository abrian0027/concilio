<?php
/**
 * Guardar Nueva Visita - Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
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
$nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
$apellido = mb_strtoupper(trim($_POST['apellido'] ?? ''), 'UTF-8');
$sexo = $_POST['sexo'] ?? '';
$nacionalidad_id = !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null;
$telefono = trim($_POST['telefono'] ?? '');
$categoria = $_POST['categoria'] ?? '';
$fecha_visita = $_POST['fecha_visita'] ?? date('Y-m-d');
$invitado_por = !empty($_POST['invitado_por']) ? (int)$_POST['invitado_por'] : null;
$observaciones = trim($_POST['observaciones'] ?? '');

// Iglesia: si es super_admin puede elegir, sino usa la de sesión
if ($_SESSION['rol_nombre'] === 'super_admin') {
    $iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)($_SESSION['iglesia_id'] ?? 0);
}

$usuario_registro = (int)$_SESSION['usuario_id'];

// Validaciones básicas
if ($nombre === '' || $apellido === '' || $sexo === '' || $categoria === '' || $iglesia_id === 0) {
    header("Location: crear.php?error=Faltan campos obligatorios (nombre, apellido, sexo, categoría)");
    exit;
}

// Validar categoría
$categorias_validas = ['damas', 'caballeros', 'jovenes', 'jovencitos', 'ninos'];
if (!in_array($categoria, $categorias_validas)) {
    header("Location: crear.php?error=Categoría no válida");
    exit;
}

// Validar fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_visita)) {
    $fecha_visita = date('Y-m-d');
}

try {
    // Insertar visita
    $sql = "INSERT INTO visitas (
                iglesia_id, nombre, apellido, sexo, nacionalidad_id, telefono,
                categoria, invitado_por, fecha_visita, observaciones, usuario_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conexion->error);
    }
    
    $stmt->bind_param(
        "isssississi",
        $iglesia_id,
        $nombre,
        $apellido,
        $sexo,
        $nacionalidad_id,
        $telefono,
        $categoria,
        $invitado_por,
        $fecha_visita,
        $observaciones,
        $usuario_registro
    );
    
    if ($stmt->execute()) {
        header("Location: index.php?exito=Visita registrada correctamente");
        exit;
    } else {
        throw new Exception("Error al guardar: " . $stmt->error);
    }
    
} catch (Exception $e) {
    header("Location: crear.php?error=" . urlencode($e->getMessage()));
    exit;
}
