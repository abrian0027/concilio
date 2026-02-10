<?php
require_once 'config/config.php';

echo "=== DISTRITOS POR CONFERENCIA ===\n\n";

$sql = "SELECT c.id as conf_id, c.nombre as conferencia, d.id as dist_id, d.nombre as distrito
        FROM conferencias c
        LEFT JOIN distritos d ON d.conferencia_id = c.id
        ORDER BY c.nombre, d.nombre";

$result = $conexion->query($sql);

$conferencias = [];
while ($row = $result->fetch_assoc()) {
    $conf_nombre = $row['conferencia'];
    if (!isset($conferencias[$conf_nombre])) {
        $conferencias[$conf_nombre] = [];
    }
    if ($row['distrito']) {
        $conferencias[$conf_nombre][] = [
            'id' => $row['dist_id'],
            'nombre' => $row['distrito']
        ];
    }
}

foreach ($conferencias as $conf => $distritos) {
    echo "📍 {$conf}\n";
    echo str_repeat("-", 50) . "\n";
    
    if (count($distritos) > 0) {
        foreach ($distritos as $d) {
            // Contar iglesias en este distrito
            $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM iglesias WHERE distrito_id = ?");
            $stmt->bind_param("i", $d['id']);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            
            echo sprintf("   ID: %-3d %-30s (%d iglesias)\n", $d['id'], $d['nombre'], $total);
        }
        echo "   Total: " . count($distritos) . " distritos\n";
    } else {
        echo "   (Sin distritos asignados)\n";
    }
    echo "\n";
}
