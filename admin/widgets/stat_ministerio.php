<?php
/**
 * Widget: Estadísticas de Ministerio
 * Widget especial para líderes de ministerio
 */

function renderStatMinisterio($config, $conexion, $session_data) {
    $col = $config['col'] ?? 'col-md-12';
    $usuario_cedula = $session_data['cedula'] ?? '';
    
    // Obtener información del ministerio
    $sql_ministerios = "SELECT mlc.*, 
                               m.nombre AS ministerio_nombre,
                               c.id AS conferencia_id,
                               c.nombre AS conferencia_nombre,
                               c.codigo AS conferencia_codigo
                        FROM ministerio_lideres_conferencia mlc
                        INNER JOIN ministerios m ON mlc.ministerio_id = m.id
                        INNER JOIN conferencias c ON mlc.conferencia_id = c.id
                        INNER JOIN miembros mb ON mlc.miembro_id = mb.id
                        WHERE mb.numero_documento = ? AND mlc.activo = 1
                        LIMIT 1";
    
    $stmt = $conexion->prepare($sql_ministerios);
    $stmt->bind_param("s", $usuario_cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    $ministerio_info = $result->fetch_assoc();
    $stmt->close();
    
    if (!$ministerio_info) {
        ?>
        <div class="<?php echo $col; ?>">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No se encontró información del ministerio asignado.
            </div>
        </div>
        <?php
        return;
    }
    
    $conferencia_id = $ministerio_info['conferencia_id'];
    $ministerio_id = $ministerio_info['ministerio_id'];
    
    // Obtener estadísticas
    $stats = ['total_iglesias' => 0, 'iglesias_con_lider' => 0, 'total_miembros' => 0];
    
    // Total iglesias
    $sql = "SELECT COUNT(*) as total FROM iglesias i 
            INNER JOIN distritos d ON i.distrito_id = d.id 
            WHERE d.conferencia_id = ? AND i.activo = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $conferencia_id);
    $stmt->execute();
    $stats['total_iglesias'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Total miembros del ministerio
    $sql = "SELECT COUNT(DISTINCT m.id) as total 
            FROM miembros m
            INNER JOIN iglesias i ON m.iglesia_id = i.id
            INNER JOIN distritos d ON i.distrito_id = d.id
            WHERE d.conferencia_id = ? AND m.ministerio_id = ? AND m.estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $conferencia_id, $ministerio_id);
    $stmt->execute();
    $stats['total_miembros'] = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Buscar área ministerial
    $sql = "SELECT id FROM areas_ministeriales WHERE nombre LIKE ? AND activo = 1 LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $nombre_like = '%' . $ministerio_info['ministerio_nombre'] . '%';
    $stmt->bind_param("s", $nombre_like);
    $stmt->execute();
    $area = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($area) {
        $sql = "SELECT COUNT(DISTINCT al.iglesia_id) as total 
                FROM area_lideres al
                INNER JOIN iglesias i ON al.iglesia_id = i.id
                INNER JOIN distritos d ON i.distrito_id = d.id
                WHERE d.conferencia_id = ? AND al.area_id = ? AND al.activo = 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $conferencia_id, $area['id']);
        $stmt->execute();
        $stats['iglesias_con_lider'] = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
    
    $porcentaje = $stats['total_iglesias'] > 0 
        ? round(($stats['iglesias_con_lider'] / $stats['total_iglesias']) * 100) 
        : 0;
    ?>
    
    <div class="<?php echo $col; ?>">
        <!-- Header del Ministerio -->
        <div class="card card-primary card-outline mb-3">
            <div class="card-body">
                <h4 class="mb-2">
                    <i class="fas fa-hands-praying me-2"></i>
                    Líder de <?php echo htmlspecialchars($ministerio_info['ministerio_nombre']); ?>
                </h4>
                <p class="text-muted mb-1">
                    <i class="fas fa-globe-americas me-1"></i>
                    <?php echo htmlspecialchars($ministerio_info['conferencia_codigo'] . ' - ' . $ministerio_info['conferencia_nombre']); ?>
                </p>
                <p class="text-muted mb-0">
                    <i class="fas fa-id-badge me-1"></i>
                    <?php echo htmlspecialchars(ucfirst($ministerio_info['cargo'])); ?>
                </p>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?php echo $stats['total_iglesias']; ?></h3>
                        <p>Iglesias en Conferencia</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-church"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['iglesias_con_lider']; ?></h3>
                        <p>Iglesias con Líder</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-12">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['total_miembros']; ?></h3>
                        <p>Miembros del Ministerio</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Cobertura -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i>Cobertura del Ministerio
                </h3>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: <?php echo $porcentaje; ?>%"
                         aria-valuenow="<?php echo $porcentaje; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?php echo $porcentaje; ?>%
                    </div>
                </div>
                <p class="text-muted mb-0">
                    <?php echo $stats['iglesias_con_lider']; ?> de <?php echo $stats['total_iglesias']; ?> iglesias 
                    tienen líder de <?php echo htmlspecialchars($ministerio_info['ministerio_nombre']); ?> asignado.
                </p>
            </div>
        </div>
    </div>
    
    <?php
}
