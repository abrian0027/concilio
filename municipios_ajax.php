<?php
/**
 * AJAX: Obtener municipios por provincia
 * Sistema Concilio
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';

$provincia_id = $_GET['provincia_id'] ?? null;

if (!$provincia_id) {
    echo json_encode([]);
    exit;
}

$provincia_id = (int)$provincia_id;

$stmt = $conexion->prepare("SELECT id, nombre FROM municipios WHERE provincia_id = ? ORDER BY nombre");
$stmt->bind_param("i", $provincia_id);
$stmt->execute();
$result = $stmt->get_result();

$municipios = [];
while ($row = $result->fetch_assoc()) {
    $municipios[] = $row;
}

$stmt->close();
echo json_encode($municipios, JSON_UNESCAPED_UNICODE);