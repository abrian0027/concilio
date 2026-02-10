<?php
require_once 'config/config.php';

echo "=== FORMATO DE CÉDULAS EN BD ===\n\n";

$r = $conexion->query("SELECT numero_documento FROM miembros WHERE numero_documento IS NOT NULL AND numero_documento != '' LIMIT 10");

if ($r && $r->num_rows > 0) {
    while($row = $r->fetch_assoc()) {
        echo $row['numero_documento'] . "\n";
    }
} else {
    echo "No hay miembros con cédula registrada.\n";
}
