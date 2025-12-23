<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../config/config.php';

$conferencia_id = filter_input(INPUT_GET, 'conferencia_id', FILTER_VALIDATE_INT);

if (!$conferencia_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conexion->prepare("
        SELECT id, codigo, nombre 
        FROM distritos 
        WHERE conferencia_id = ? AND activo = 1 
        ORDER BY nombre
    ");
    $stmt->bind_param("i", $conferencia_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $distritos = [];
    while ($row = $resultado->fetch_assoc()) {
        $distritos[] = $row;
    }

    echo json_encode($distritos, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error en distritos_ajax: " . $e->getMessage());
    echo json_encode([]);
}
?>