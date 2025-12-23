<?php
/**
 * Widget: Tarjeta de Estadística
 * Muestra una estadística en formato de small-box
 */

function renderStatCard($config, $conexion, $session_data) {
    // Ejecutar query
    $value = 0;
    if (isset($config['query'])) {
        $query = $config['query'];
        
        // Preparar parámetros si existen
        if (isset($config['params']) && is_array($config['params'])) {
            $stmt = $conexion->prepare($query);
            
            // Construir tipos y valores
            $types = '';
            $values = [];
            foreach ($config['params'] as $param) {
                $types .= 'i'; // Asumimos integer, ajustar si es necesario
                $values[] = $session_data[$param] ?? 0;
            }
            
            // Bind parameters dinámicamente
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $value = $row['total'] ?? 0;
            }
            $stmt->close();
        } else {
            // Query sin parámetros
            $result = $conexion->query($query);
            if ($result && $row = $result->fetch_assoc()) {
                $value = $row['total'] ?? 0;
            }
        }
    }
    
    // Formatear valor si es necesario
    if (isset($config['format']) && $config['format'] === 'currency') {
        $value_display = '$' . number_format($value, 2);
    } else {
        $value_display = number_format($value);
    }
    
    $title = $config['title'] ?? 'Estadística';
    $icon = $config['icon'] ?? 'fa-chart-bar';
    $color = $config['color'] ?? 'info';
    $col = $config['col'] ?? 'col-md-3';
    ?>
    
    <div class="<?php echo $col; ?>">
        <div class="small-box bg-<?php echo $color; ?>">
            <div class="inner">
                <h3><?php echo $value_display; ?></h3>
                <p><?php echo htmlspecialchars($title); ?></p>
            </div>
            <div class="icon">
                <i class="fas <?php echo $icon; ?>"></i>
            </div>
        </div>
    </div>
    
    <?php
}
