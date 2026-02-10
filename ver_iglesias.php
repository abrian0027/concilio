<?php
require_once 'config/config.php';

$sql = "SELECT i.id, i.codigo, i.nombre, i.activo, d.nombre as distrito 
        FROM iglesias i 
        LEFT JOIN distritos d ON i.distrito_id = d.id 
        ORDER BY i.nombre";
$result = $conexion->query($sql);

echo "=== LISTADO DE IGLESIAS ===\n\n";
echo sprintf("%-5s %-15s %-50s %-25s %s\n", "ID", "CODIGO", "NOMBRE", "DISTRITO", "ACTIVO");
echo str_repeat("-", 110) . "\n";

while ($row = $result->fetch_assoc()) {
    echo sprintf("%-5s %-15s %-50s %-25s %s\n", 
        $row['id'], 
        $row['codigo'], 
        mb_substr($row['nombre'], 0, 48),
        mb_substr($row['distrito'] ?? 'Sin distrito', 0, 23),
        $row['activo'] ? 'Sí' : 'No'
    );
}

echo "\nTotal: " . $result->num_rows . " iglesias\n";
