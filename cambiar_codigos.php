<?php
/**
 * Script para cambiar códigos de iglesias
 * De: IMLC-XXX  A: IML-XXX
 */

require_once 'config/config.php';

echo "=== CAMBIO DE CÓDIGOS DE IGLESIAS ===\n\n";

// Mostrar códigos actuales
echo "CÓDIGOS ACTUALES:\n";
echo str_repeat("-", 50) . "\n";

$sql = "SELECT id, codigo, nombre FROM iglesias ORDER BY codigo";
$result = $conexion->query($sql);
$iglesias = [];

while ($row = $result->fetch_assoc()) {
    $iglesias[] = $row;
    $nuevo_codigo = str_replace('IMLC-', 'IML-', $row['codigo']);
    echo sprintf("ID: %3d | %s -> %s | %s\n", 
        $row['id'], 
        $row['codigo'], 
        $nuevo_codigo,
        $row['nombre']
    );
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Total: " . count($iglesias) . " iglesias a actualizar\n\n";

// Preguntar confirmación
echo "¿Desea ejecutar el cambio? (escribe 'SI' para confirmar): ";
$handle = fopen("php://stdin", "r");
$confirmacion = trim(fgets($handle));

if (strtoupper($confirmacion) === 'SI') {
    echo "\nEjecutando cambios...\n\n";
    
    $conexion->begin_transaction();
    
    try {
        $actualizados = 0;
        
        foreach ($iglesias as $iglesia) {
            $nuevo_codigo = str_replace('IMLC-', 'IML-', $iglesia['codigo']);
            
            $stmt = $conexion->prepare("UPDATE iglesias SET codigo = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_codigo, $iglesia['id']);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                echo "✓ {$iglesia['codigo']} -> {$nuevo_codigo}\n";
                $actualizados++;
            }
            $stmt->close();
        }
        
        $conexion->commit();
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "¡COMPLETADO! Se actualizaron {$actualizados} códigos.\n";
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo "\n❌ ERROR: " . $e->getMessage() . "\n";
        echo "Se revirtieron todos los cambios.\n";
    }
    
} else {
    echo "\nOperación cancelada.\n";
}

fclose($handle);
