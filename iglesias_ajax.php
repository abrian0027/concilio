<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';

$distrito_id = $_GET['distrito_id'] ?? null;
if (!$distrito_id) {
    echo json_encode([]);
    exit;
}

$distrito_id = (int)$distrito_id;
$stmt = $conexion->prepare("SELECT id, codigo, nombre FROM iglesias WHERE distrito_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$result = $stmt->get_result();

$iglesias = [];
while ($row = $result->fetch_assoc()) {
    $iglesias[] = $row;
}

$stmt->close();
echo json_encode($iglesias, JSON_UNESCAPED_UNICODE);