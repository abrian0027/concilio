<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$distrito_id = (int)($_GET['distrito_id'] ?? 0);

if ($distrito_id === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conexion->prepare("SELECT id, codigo, nombre FROM iglesias WHERE distrito_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $distrito_id);
$stmt->execute();
$resultado = $stmt->get_result();

$iglesias = [];
while ($row = $resultado->fetch_assoc()) {
    $iglesias[] = $row;
}

$stmt->close();
echo json_encode($iglesias);