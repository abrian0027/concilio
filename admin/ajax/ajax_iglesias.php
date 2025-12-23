<?php
/**
 * AJAX - Obtener iglesias de un distrito
 * Sistema Concilio
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar que está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$distrito_id = filter_input(INPUT_GET, 'distrito_id', FILTER_VALIDATE_INT);

if (!$distrito_id) {
    echo json_encode([]);
    exit;
}

// Obtener iglesias activas del distrito
$sql = "SELECT id, codigo, nombre 
        FROM iglesias 
        WHERE distrito_id = ? AND activo = 1
        ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$result = $stmt->get_result();

$iglesias = [];
while ($row = $result->fetch_assoc()) {
    $iglesias[] = $row;
}

$stmt->close();
echo json_encode($iglesias);
