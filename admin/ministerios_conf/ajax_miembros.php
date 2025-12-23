<?php
/**
 * AJAX - Obtener miembros de una iglesia
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

$iglesia_id = filter_input(INPUT_GET, 'iglesia_id', FILTER_VALIDATE_INT);

if (!$iglesia_id) {
    echo json_encode([]);
    exit;
}

// Obtener miembros activos de la iglesia
$sql = "SELECT m.id, 
               CONCAT(m.nombre, ' ', m.apellido) AS nombre,
               m.numero_documento AS cedula,
               m.telefono,
               i.nombre AS iglesia
        FROM miembros m
        LEFT JOIN iglesias i ON m.iglesia_id = i.id
        WHERE m.iglesia_id = ? 
          AND m.estado_miembro IN ('activo', 'en_plena')
        ORDER BY m.nombre, m.apellido";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$result = $stmt->get_result();

$miembros = [];
while ($row = $result->fetch_assoc()) {
    $miembros[] = $row;
}

$stmt->close();
echo json_encode($miembros);
