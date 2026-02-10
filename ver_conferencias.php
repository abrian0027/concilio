<?php
require_once 'config/config.php';

// Primero ver las conferencias
echo "=== CONFERENCIAS DISPONIBLES ===\n\n";
$conf = $conexion->query("SELECT id, nombre FROM conferencias ORDER BY nombre");
while ($row = $conf->fetch_assoc()) {
    echo "ID: {$row['id']} - {$row['nombre']}\n";
}

echo "\n=== IGLESIAS EN CONFERENCIA SURESTE ===\n\n";

$sql = "SELECT i.id, i.codigo, i.nombre, d.nombre as distrito, c.nombre as conferencia
        FROM iglesias i 
        LEFT JOIN distritos d ON i.distrito_id = d.id 
        LEFT JOIN conferencias c ON d.conferencia_id = c.id 
        WHERE c.nombre LIKE '%Sureste%' OR c.nombre LIKE '%Sur%'
        ORDER BY d.nombre, i.nombre";
$result = $conexion->query($sql);

if ($result->num_rows > 0) {
    echo sprintf("%-12s %-40s %-25s\n", "CODIGO", "NOMBRE", "DISTRITO");
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-12s %-40s %-25s\n", 
            $row['codigo'], 
            mb_substr($row['nombre'], 0, 38),
            mb_substr($row['distrito'], 0, 23)
        );
    }
    echo "\nTotal: " . $result->num_rows . " iglesias\n";
} else {
    echo "No se encontraron iglesias en Conferencia Sureste\n";
    echo "\nBuscando en todas las conferencias...\n\n";
    
    $sql2 = "SELECT DISTINCT c.nombre as conferencia, COUNT(i.id) as total
             FROM conferencias c
             LEFT JOIN distritos d ON d.conferencia_id = c.id
             LEFT JOIN iglesias i ON i.distrito_id = d.id
             GROUP BY c.id, c.nombre
             ORDER BY c.nombre";
    $r2 = $conexion->query($sql2);
    while ($row = $r2->fetch_assoc()) {
        echo "{$row['conferencia']}: {$row['total']} iglesias\n";
    }
}
