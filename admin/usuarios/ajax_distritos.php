<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

$conferencia_id = (int)($_GET['conferencia_id'] ?? 0);

if ($conferencia_id === 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conexion->prepare("SELECT id, codigo, nombre FROM distritos WHERE conferencia_id = ? AND activo = 1 ORDER BY nombre");
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$resultado = $stmt->get_result();

$distritos = [];
while ($row = $resultado->fetch_assoc()) {
    $distritos[] = $row;
}

$stmt->close();
echo json_encode($distritos);