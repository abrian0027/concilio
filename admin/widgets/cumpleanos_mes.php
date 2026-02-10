<?php
/**
 * Widget: Cumpleaños del Mes
 * Muestra los cumpleaños según el rol del usuario:
 * - Pastor/Secretaria: Miembros de su iglesia local
 * - Pastor que es Supervisor de Distrito: Miembros de su iglesia + Pastores de su distrito
 * - Superintendente Conferencia: Pastores de su conferencia
 * - Super Admin: Todos los pastores
 * - Lider Ministerio (Presidente): Líderes de iglesias locales de su conferencia
 */

function renderCumpleanosMes($config, $conexion, $session_data) {
    $rol_nombre = strtolower($session_data['rol_nombre'] ?? '');
    $iglesia_id = $session_data['iglesia_id'] ?? 0;
    $usuario_id = $session_data['usuario_id'] ?? 0;
    
    $mes_actual = (int)date('n');
    $nombre_mes = getNombreMes($mes_actual);
    
    $cumpleanos = [];
    $titulo = 'Cumpleaños del Mes';
    $tipo = 'miembros';
    
    // Verificar si el usuario (aunque sea pastor) es supervisor de algún distrito
    $distrito_supervisor_id = getDistritoSupervisor($conexion, $usuario_id);
    $es_supervisor_distrito = ($distrito_supervisor_id !== null && $distrito_supervisor_id > 0);
    
    // Determinar qué cumpleaños mostrar según el rol
    if (in_array($rol_nombre, ['pastor', 'secretaria'])) {
        // Iglesia local: Cumpleaños de miembros
        $cumpleanos = getCumpleanosMiembros($conexion, $iglesia_id, $mes_actual);
        $titulo = '🎂 Cumpleaños de Miembros - ' . $nombre_mes;
        $tipo = 'miembros';
        // Renderizar widget de miembros
        renderWidgetCumpleanos($cumpleanos, $titulo, $tipo, $config);
        
        // Si el pastor también es supervisor de distrito, mostrar widget adicional
        if ($es_supervisor_distrito) {
            $cumpleanos_pastores = getCumpleanosPastoresDistrito($conexion, $distrito_supervisor_id, $mes_actual);
            $titulo_pastores = '🎂 Cumpleaños Pastores del Distrito - ' . $nombre_mes;
            renderWidgetCumpleanos($cumpleanos_pastores, $titulo_pastores, 'pastores', $config);
        }
        
    } elseif ($rol_nombre == 'super_admin') {
        // Super Admin: Todos los pastores de todas las conferencias
        $cumpleanos = getCumpleanosPastoresGeneral($conexion, $mes_actual);
        $titulo = '🎂 Cumpleaños de Pastores - ' . $nombre_mes;
        $tipo = 'pastores';
        // Renderizar widget
        renderWidgetCumpleanos($cumpleanos, $titulo, $tipo, $config);
        
    } elseif ($rol_nombre == 'super_conferencia') {
        // Superintendente de Conferencia: Pastores de su conferencia
        $conferencia_id = getConferenciaUsuario($conexion, $usuario_id);
        if ($conferencia_id) {
            $cumpleanos = getCumpleanosPastoresConferencia($conexion, $conferencia_id, $mes_actual);
            $titulo = '🎂 Cumpleaños de Pastores - ' . $nombre_mes;
            $tipo = 'pastores';
            // Renderizar widget
            renderWidgetCumpleanos($cumpleanos, $titulo, $tipo, $config);
        }
        
    } elseif ($rol_nombre == 'super_distrito') {
        // Rol específico super_distrito (por si se usa directamente)
        // Widget 1: Cumpleaños de miembros de su iglesia local
        if ($iglesia_id > 0) {
            $cumpleanos_miembros = getCumpleanosMiembros($conexion, $iglesia_id, $mes_actual);
            $titulo_miembros = '🎂 Cumpleaños de Miembros - ' . $nombre_mes;
            renderWidgetCumpleanos($cumpleanos_miembros, $titulo_miembros, 'miembros', $config);
        }
        
        // Widget 2: Cumpleaños de pastores de su distrito
        if ($es_supervisor_distrito) {
            $cumpleanos_pastores = getCumpleanosPastoresDistrito($conexion, $distrito_supervisor_id, $mes_actual);
            $titulo_pastores = '🎂 Cumpleaños Pastores del Distrito - ' . $nombre_mes;
            renderWidgetCumpleanos($cumpleanos_pastores, $titulo_pastores, 'pastores', $config);
        }
        
    } elseif ($rol_nombre == 'lider_ministerio') {
        // Presidente de Ministerio: Líderes de iglesias locales de su conferencia
        $conferencia_id = getConferenciaLiderMinisterio($conexion, $usuario_id);
        if ($conferencia_id) {
            $cumpleanos = getCumpleanosLideresConferencia($conexion, $conferencia_id, $mes_actual);
            $titulo = '🎂 Cumpleaños de Líderes - ' . $nombre_mes;
            $tipo = 'lideres';
            // Renderizar widget
            renderWidgetCumpleanos($cumpleanos, $titulo, $tipo, $config);
        }
    }
}

/**
 * Obtener cumpleaños de miembros de una iglesia
 */
function getCumpleanosMiembros($conexion, $iglesia_id, $mes) {
    $cumpleanos = [];
    
    $sql = "SELECT id, nombre, apellido, fecha_nacimiento, telefono, foto,
                   DAY(fecha_nacimiento) as dia,
                   TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad
            FROM miembros 
            WHERE iglesia_id = ? 
            AND MONTH(fecha_nacimiento) = ? 
            AND estado = 'activo'
            ORDER BY DAY(fecha_nacimiento) ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $iglesia_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cumpleanos[] = $row;
    }
    $stmt->close();
    
    return $cumpleanos;
}

/**
 * Obtener cumpleaños de todos los pastores (para superintendente)
 */
function getCumpleanosPastoresGeneral($conexion, $mes) {
    $cumpleanos = [];
    
    $sql = "SELECT p.id, p.nombre, p.apellido, p.fecha_nacimiento, p.telefono, p.foto,
                   DAY(p.fecha_nacimiento) as dia,
                   TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                   i.nombre as iglesia_nombre
            FROM pastores p
            LEFT JOIN pastor_iglesias pi ON pi.pastor_id = p.id AND pi.activo = 1 AND pi.es_principal = 1
            LEFT JOIN iglesias i ON i.id = pi.iglesia_id
            WHERE MONTH(p.fecha_nacimiento) = ? 
            AND p.activo = 1
            ORDER BY DAY(p.fecha_nacimiento) ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cumpleanos[] = $row;
    }
    $stmt->close();
    
    return $cumpleanos;
}

/**
 * Obtener cumpleaños de pastores de un distrito específico
 */
function getCumpleanosPastoresDistrito($conexion, $distrito_id, $mes) {
    $cumpleanos = [];
    
    $sql = "SELECT p.id, p.nombre, p.apellido, p.fecha_nacimiento, p.telefono, p.foto,
                   DAY(p.fecha_nacimiento) as dia,
                   TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                   i.nombre as iglesia_nombre
            FROM pastores p
            INNER JOIN pastor_iglesias pi ON pi.pastor_id = p.id AND pi.activo = 1 AND pi.es_principal = 1
            INNER JOIN iglesias i ON i.id = pi.iglesia_id
            WHERE i.distrito_id = ?
            AND MONTH(p.fecha_nacimiento) = ? 
            AND p.activo = 1
            ORDER BY DAY(p.fecha_nacimiento) ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $distrito_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cumpleanos[] = $row;
    }
    $stmt->close();
    
    return $cumpleanos;
}

/**
 * Obtener el distrito asignado a un supervisor
 */
function getDistritoSupervisor($conexion, $usuario_id) {
    // Primero obtener la cédula del usuario
    $stmt = $conexion->prepare("SELECT usuario FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$usuario_data) return null;
    
    $usuario_cedula = $usuario_data['usuario'];
    
    // Buscar si está asignado como supervisor de algún distrito
    $sql = "SELECT d.id 
            FROM distritos d
            INNER JOIN pastores p ON p.id = d.supervisor_id
            WHERE p.cedula = ? AND d.activo = 1
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario_cedula);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['id'];
    }
    $stmt->close();
    return null;
}

/**
 * Obtener la conferencia del usuario (para super_conferencia)
 */
function getConferenciaUsuario($conexion, $usuario_id) {
    // Buscar la conferencia asignada al usuario
    $stmt = $conexion->prepare("SELECT conferencia_id FROM usuarios WHERE id = ? AND conferencia_id IS NOT NULL");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['conferencia_id'];
    }
    $stmt->close();
    
    // Si no tiene conferencia directa, buscar por cédula en pastores
    $stmt = $conexion->prepare("SELECT p.conferencia_id FROM pastores p 
                                INNER JOIN usuarios u ON p.cedula = u.usuario 
                                WHERE u.id = ? AND p.conferencia_id IS NOT NULL");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['conferencia_id'];
    }
    $stmt->close();
    return null;
}

/**
 * Obtener cumpleaños de pastores de una conferencia específica
 */
function getCumpleanosPastoresConferencia($conexion, $conferencia_id, $mes) {
    $cumpleanos = [];
    
    $sql = "SELECT p.id, p.nombre, p.apellido, p.fecha_nacimiento, p.telefono,
                   DAY(p.fecha_nacimiento) as dia,
                   TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad,
                   i.nombre as iglesia_nombre
            FROM pastores p
            LEFT JOIN pastor_iglesias pi ON pi.pastor_id = p.id AND pi.activo = 1 AND pi.es_principal = 1
            LEFT JOIN iglesias i ON i.id = pi.iglesia_id
            WHERE p.conferencia_id = ?
            AND MONTH(p.fecha_nacimiento) = ? 
            AND p.activo = 1
            ORDER BY DAY(p.fecha_nacimiento) ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $conferencia_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cumpleanos[] = $row;
    }
    $stmt->close();
    
    return $cumpleanos;
}

/**
 * Obtener la conferencia del líder de ministerio
 */
function getConferenciaLiderMinisterio($conexion, $usuario_id) {
    // Buscar el miembro asociado al usuario y su ministerio en la conferencia
    $stmt = $conexion->prepare("
        SELECT mlc.conferencia_id 
        FROM ministerio_lideres_conferencia mlc
        INNER JOIN miembros m ON mlc.miembro_id = m.id
        INNER JOIN usuarios u ON (u.usuario = m.numero_documento OR u.email = m.telefono)
        WHERE u.id = ? AND mlc.activo = 1 AND mlc.cargo = 'presidente'
        LIMIT 1
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['conferencia_id'];
    }
    $stmt->close();
    
    // Buscar conferencia directa del usuario
    $stmt = $conexion->prepare("SELECT conferencia_id FROM usuarios WHERE id = ? AND conferencia_id IS NOT NULL");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['conferencia_id'];
    }
    $stmt->close();
    return null;
}

/**
 * Obtener cumpleaños de líderes de iglesias locales de una conferencia
 */
function getCumpleanosLideresConferencia($conexion, $conferencia_id, $mes) {
    $cumpleanos = [];
    
    // Obtener miembros que son líderes de iglesias que pertenecen a la conferencia
    $sql = "SELECT m.id, m.nombre, m.apellido, m.fecha_nacimiento, m.telefono,
                   DAY(m.fecha_nacimiento) as dia,
                   TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) as edad,
                   i.nombre as iglesia_nombre
            FROM miembros m
            INNER JOIN iglesias i ON m.iglesia_id = i.id
            INNER JOIN distritos d ON i.distrito_id = d.id
            WHERE d.conferencia_id = ?
            AND MONTH(m.fecha_nacimiento) = ? 
            AND m.es_lider = 1
            AND m.estado = 'activo'
            ORDER BY DAY(m.fecha_nacimiento) ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $conferencia_id, $mes);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cumpleanos[] = $row;
    }
    $stmt->close();
    
    return $cumpleanos;
}

/**
 * Obtener nombre del mes en español
 */
function getNombreMes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? '';
}

/**
 * Renderizar el widget de cumpleaños
 */
function renderWidgetCumpleanos($cumpleanos, $titulo, $tipo, $config) {
    $col = $config['col'] ?? 'col-md-6';
    $max_mostrar = $config['max'] ?? 10;
    $hoy = (int)date('j');
    ?>
    
    <div class="<?php echo $col; ?>">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <?php echo htmlspecialchars($titulo); ?>
                    <span class="badge badge-primary ml-2"><?php echo count($cumpleanos); ?></span>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                <?php if (empty($cumpleanos)): ?>
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-birthday-cake fa-3x mb-3"></i>
                        <p>No hay cumpleaños este mes</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php 
                        $mostrados = 0;
                        foreach ($cumpleanos as $persona): 
                            if ($mostrados >= $max_mostrar) break;
                            $mostrados++;
                            
                            $es_hoy = ($persona['dia'] == $hoy);
                            $es_pasado = ($persona['dia'] < $hoy);
                            $edad_cumple = $persona['edad'] + ($es_pasado ? 0 : 1);
                        ?>
                        <li class="list-group-item <?php echo $es_hoy ? 'bg-warning-light' : ''; ?>" style="padding: 10px 15px;">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-<?php echo $es_hoy ? 'warning' : 'primary'; ?> text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px; min-width: 40px; font-size: 1.1rem;">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>
                                        <?php echo htmlspecialchars($persona['nombre'] . ' ' . $persona['apellido']); ?>
                                        <?php if ($es_hoy): ?>
                                            <span class="badge bg-warning text-dark ms-1">¡HOY!</span>
                                        <?php endif; ?>
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        <?php echo $persona['dia']; ?> de <?php echo getNombreMes((int)date('n')); ?>
                                        <span class="ms-2">
                                            <i class="fas fa-gift me-1"></i>
                                            Cumple <?php echo $edad_cumple; ?> años
                                        </span>
                                    </small>
                                    <?php if ($tipo == 'pastores' && !empty($persona['iglesia_nombre'])): ?>
                                        <br>
                                        <small class="text-info">
                                            <i class="fas fa-church me-1"></i>
                                            <?php echo htmlspecialchars($persona['iglesia_nombre']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <?php if (!empty($persona['telefono'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $persona['telefono']); ?>?text=<?php echo urlencode('¡Feliz Cumpleaños! 🎂🎉 Que Dios te bendiga en este día especial.'); ?>" 
                                           class="btn btn-success btn-sm" 
                                           target="_blank"
                                           title="Enviar felicitación por WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        
                        <?php if (count($cumpleanos) > $max_mostrar): ?>
                            <li class="list-group-item text-center">
                                <small class="text-muted">
                                    Y <?php echo count($cumpleanos) - $max_mostrar; ?> más...
                                </small>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
    .bg-warning-light {
        background-color: #fff3cd !important;
    }
    </style>
    
    <?php
}
