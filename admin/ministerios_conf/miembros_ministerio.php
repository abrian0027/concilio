<?php
/**
 * Estadísticas de Miembros por Ministerio
 * Muestra la cantidad de miembros del ministerio organizados por iglesia
 * Para Líderes de Ministerio (ejemplo: Presidente de Jóvenes)
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$ROL_NOMBRE = $_SESSION['rol_nombre'] ?? '';
$usuario_cedula = $_SESSION['cedula'] ?? $_SESSION['usuario'] ?? '';

// Obtener ministerios que lidera este usuario
$ministerios_lidera = [];
$sql_ministerios = "SELECT mlc.*, 
                           m.nombre AS ministerio_nombre,
                           m.id AS ministerio_id,
                           c.id AS conferencia_id,
                           c.nombre AS conferencia_nombre,
                           c.codigo AS conferencia_codigo
                    FROM ministerio_lideres_conferencia mlc
                    INNER JOIN ministerios m ON mlc.ministerio_id = m.id
                    INNER JOIN conferencias c ON mlc.conferencia_id = c.id
                    INNER JOIN miembros mb ON mlc.miembro_id = mb.id
                    WHERE mb.numero_documento = ? AND mlc.activo = 1";

$stmt = $conexion->prepare($sql_ministerios);
$stmt->bind_param("s", $usuario_cedula);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $ministerios_lidera[] = $row;
}
$stmt->close();

if (count($ministerios_lidera) == 0) {
    header('Location: ../panel_ministerio.php');
    exit;
}

// Tomar el primer ministerio como principal
$ministerio_principal = $ministerios_lidera[0];
$conferencia_id = $ministerio_principal['conferencia_id'];
$ministerio_id = $ministerio_principal['ministerio_id'];

// Obtener estadísticas generales
$stats = [
    'total_miembros' => 0,
    'total_distritos' => 0,
    'total_iglesias' => 0,
    'iglesias_con_miembros' => 0
];

// Total de miembros del ministerio en la conferencia
// NOTA: Todos los miembros registrados son contados, sin importar su estado_miembro
$sql = "SELECT COUNT(DISTINCT m.id) as total 
        FROM miembros m
        INNER JOIN iglesias i ON m.iglesia_id = i.id
        INNER JOIN distritos d ON i.distrito_id = d.id
        WHERE d.conferencia_id = ? 
        AND m.ministerio_id = ? 
        AND m.estado = 'activo'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $conferencia_id, $ministerio_id);
$stmt->execute();
$stats['total_miembros'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de distritos en la conferencia
$sql = "SELECT COUNT(DISTINCT d.id) as total 
        FROM distritos d
        WHERE d.conferencia_id = ? AND d.activo = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$stats['total_distritos'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de iglesias con miembros del ministerio
$sql = "SELECT COUNT(DISTINCT i.id) as total 
        FROM iglesias i
        INNER JOIN distritos d ON i.distrito_id = d.id
        INNER JOIN miembros m ON m.iglesia_id = i.id
        WHERE d.conferencia_id = ? 
        AND m.ministerio_id = ? 
        AND m.estado = 'activo'
        AND i.activo = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $conferencia_id, $ministerio_id);
$stmt->execute();
$stats['iglesias_con_miembros'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total de iglesias en la conferencia
$sql = "SELECT COUNT(DISTINCT i.id) as total 
        FROM iglesias i
        INNER JOIN distritos d ON i.distrito_id = d.id
        WHERE d.conferencia_id = ? AND i.activo = 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $conferencia_id);
$stmt->execute();
$stats['total_iglesias'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener miembros por distrito e iglesia
$miembros_por_distrito = [];
$sql_miembros = "SELECT 
                    d.id AS distrito_id,
                    d.nombre AS distrito_nombre,
                    i.id AS iglesia_id,
                    i.codigo AS iglesia_codigo,
                    i.nombre AS iglesia_nombre,
                    COUNT(m.id) AS total_miembros,
                    GROUP_CONCAT(
                        CONCAT(
                            m.nombre, ' ', m.apellido, 
                            ' (', m.numero_documento, ') - ',
                            CASE m.estado_miembro
                                WHEN 'en_plena' THEN 'En Plena Relación'
                                WHEN 'en_preparacion' THEN 'En Preparación'
                                WHEN 'miembro_menor' THEN 'Miembro Menor'
                                ELSE m.estado_miembro
                            END
                        )
                        ORDER BY m.apellido, m.nombre
                        SEPARATOR '|'
                    ) AS lista_miembros
                FROM distritos d
                LEFT JOIN iglesias i ON i.distrito_id = d.id AND i.activo = 1
                LEFT JOIN miembros m ON m.iglesia_id = i.id 
                    AND m.ministerio_id = ? 
                    AND m.estado = 'activo'
                WHERE d.conferencia_id = ? AND d.activo = 1
                GROUP BY d.id, i.id
                ORDER BY d.nombre, i.nombre";

// Obtener estadísticas por estado de miembro
$sql_desglose = "SELECT 
                    m.estado_miembro,
                    COUNT(m.id) as total
                FROM miembros m
                INNER JOIN iglesias i ON m.iglesia_id = i.id
                INNER JOIN distritos d ON i.distrito_id = d.id
                WHERE d.conferencia_id = ? 
                AND m.ministerio_id = ? 
                AND m.estado = 'activo'
                GROUP BY m.estado_miembro";
$stmt_desglose = $conexion->prepare($sql_desglose);
$stmt_desglose->bind_param("ii", $conferencia_id, $ministerio_id);
$stmt_desglose->execute();
$result_desglose = $stmt_desglose->get_result();

$desglose_estados = [
    'en_plena' => 0,
    'en_preparacion' => 0,
    'miembro_menor' => 0
];

while ($row = $result_desglose->fetch_assoc()) {
    $desglose_estados[$row['estado_miembro']] = $row['total'];
}
$stmt_desglose->close();

$stmt = $conexion->prepare($sql_miembros);
$stmt->bind_param("ii", $ministerio_id, $conferencia_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $distrito_id = $row['distrito_id'];
    
    if (!isset($miembros_por_distrito[$distrito_id])) {
        $miembros_por_distrito[$distrito_id] = [
            'nombre' => $row['distrito_nombre'],
            'total_miembros' => 0,
            'iglesias' => []
        ];
    }
    
    if ($row['iglesia_id']) {
        $miembros = [];
        if ($row['lista_miembros']) {
            $miembros = explode('|', $row['lista_miembros']);
        }
        
        $miembros_por_distrito[$distrito_id]['iglesias'][] = [
            'id' => $row['iglesia_id'],
            'codigo' => $row['iglesia_codigo'],
            'nombre' => $row['iglesia_nombre'],
            'total_miembros' => $row['total_miembros'],
            'miembros' => $miembros
        ];
        
        $miembros_por_distrito[$distrito_id]['total_miembros'] += $row['total_miembros'];
    }
}
$stmt->close();

$page_title = "Miembros del " . $ministerio_principal['ministerio_nombre'];
include __DIR__ . '/../includes/header.php';
?>

<style>
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border-radius: 15px;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.9;
        }
        
        .distrito-card {
            border-left: 4px solid #667eea;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .distrito-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .distrito-header {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 1rem;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
        }
        
        .iglesia-item {
            border-left: 3px solid #17a2b8;
            padding: 1rem;
            margin: 0.5rem 0;
            background: white;
            transition: all 0.2s;
        }
        
        .iglesia-item:hover {
            background: #f8f9fa;
            border-left-color: #667eea;
        }
        
        .badge-count {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        
        .member-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .member-list.show {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .member-item {
            padding: 0.3rem 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .member-item:last-child {
            border-bottom: none;
        }
        
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background: #667eea;
            color: white;
        }
        
        .collapse-icon {
            transition: transform 0.3s;
        }
        
        .collapsed .collapse-icon {
            transform: rotate(-90deg);
        }
        
        /* ========== RESPONSIVE MÓVIL ========== */
        @media (max-width: 767.98px) {
            .header-section {
                padding: 1.5rem 0;
            }
            
            .header-section h2 {
                font-size: 1.3rem;
            }
            
            .header-section p {
                font-size: 0.85rem;
            }
            
            .btn-back {
                padding: 0.4rem 1rem;
                font-size: 0.9rem;
                margin-top: 1rem;
                width: 100%;
            }
            
            .stat-card {
                margin-bottom: 0.75rem;
            }
            
            .stat-card h2 {
                font-size: 1.5rem;
            }
            
            .stat-card p {
                font-size: 0.85rem;
            }
            
            .stat-icon {
                font-size: 1.8rem;
            }
            
            .distrito-header h5 {
                font-size: 1rem;
            }
            
            .badge-count {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
                display: block;
                margin-top: 0.5rem;
            }
            
            .iglesia-item h6 {
                font-size: 0.9rem;
            }
            
            .member-item {
                font-size: 0.85rem;
                padding: 0.4rem 0.5rem;
            }
            
            .btn-sm {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .header-section {
                padding: 1rem 0;
            }
            
            .header-section h2 {
                font-size: 1.1rem;
            }
            
            .stat-card h2 {
                font-size: 1.3rem;
            }
            
            .distrito-header {
                padding: 0.75rem;
            }
            
            .iglesia-item {
                padding: 0.75rem;
            }
        }
    </style>

<!-- Content Wrapper -->
<div class="content-wrapper" style="margin-left: 0; padding: 0;">
    
    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-users me-2"></i>
                        Miembros del <?php echo htmlspecialchars($ministerio_principal['ministerio_nombre']); ?>
                    </h2>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-map-marked-alt me-1"></i>
                        <?php echo htmlspecialchars($ministerio_principal['conferencia_codigo'] . ' - ' . $ministerio_principal['conferencia_nombre']); ?>
                    </p>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-user-tie me-1"></i>
                        <?php echo htmlspecialchars(ucfirst($ministerio_principal['cargo'])); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../panel_ministerio.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Panel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        
        <!-- Estadísticas Generales -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0"><?php echo $stats['total_miembros']; ?></h2>
                                <p class="mb-0">Total Miembros</p>
                            </div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0"><?php echo $stats['total_distritos']; ?></h2>
                                <p class="mb-0">Distritos</p>
                            </div>
                            <i class="fas fa-map-marked-alt stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0"><?php echo $stats['iglesias_con_miembros']; ?></h2>
                                <p class="mb-0">Iglesias con Miembros</p>
                            </div>
                            <i class="fas fa-church stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-0"><?php echo $stats['total_iglesias']; ?></h2>
                                <p class="mb-0">Total Iglesias</p>
                            </div>
                            <i class="fas fa-building stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Desglose por Estado de Miembro -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Desglose por Estado de Miembro
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border rounded p-3" style="border-left: 4px solid #28a745 !important;">
                                    <h3 class="text-success mb-1">
                                        <i class="fas fa-user-check me-2"></i>
                                        <?php echo $desglose_estados['en_plena']; ?>
                                    </h3>
                                    <p class="mb-0 text-muted">En Plena Relación</p>
                                    <small class="text-muted">
                                        <?php echo $stats['total_miembros'] > 0 ? round(($desglose_estados['en_plena'] / $stats['total_miembros']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3" style="border-left: 4px solid #ffc107 !important;">
                                    <h3 class="text-warning mb-1">
                                        <i class="fas fa-user-clock me-2"></i>
                                        <?php echo $desglose_estados['en_preparacion']; ?>
                                    </h3>
                                    <p class="mb-0 text-muted">En Preparación</p>
                                    <small class="text-muted">
                                        <?php echo $stats['total_miembros'] > 0 ? round(($desglose_estados['en_preparacion'] / $stats['total_miembros']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3" style="border-left: 4px solid #17a2b8 !important;">
                                    <h3 class="text-info mb-1">
                                        <i class="fas fa-child me-2"></i>
                                        <?php echo $desglose_estados['miembro_menor']; ?>
                                    </h3>
                                    <p class="mb-0 text-muted">Miembros Menores</p>
                                    <small class="text-muted">
                                        <?php echo $stats['total_miembros'] > 0 ? round(($desglose_estados['miembro_menor'] / $stats['total_miembros']) * 100, 1) : 0; ?>%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista por Distritos -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-list-ul me-2"></i>
                    Distribución por Distritos e Iglesias
                </h4>
            </div>
            <div class="card-body">
                
                <?php if (empty($miembros_por_distrito)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay miembros registrados en este ministerio para la conferencia actual.
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($miembros_por_distrito as $distrito_id => $distrito): ?>
                        <div class="card distrito-card">
                            <div class="distrito-header" data-bs-toggle="collapse" data-bs-target="#distrito-<?php echo $distrito_id; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chevron-down collapse-icon me-2"></i>
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($distrito['nombre']); ?>
                                        </h5>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-primary badge-count">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $distrito['total_miembros']; ?> miembros
                                        </span>
                                        <span class="badge bg-secondary badge-count ms-2">
                                            <i class="fas fa-church me-1"></i>
                                            <?php echo count($distrito['iglesias']); ?> iglesias
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="collapse show" id="distrito-<?php echo $distrito_id; ?>">
                                <div class="card-body">
                                    
                                    <?php if (empty($distrito['iglesias'])): ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No hay iglesias registradas en este distrito.
                                        </p>
                                    <?php else: ?>
                                        
                                        <?php foreach ($distrito['iglesias'] as $iglesia): ?>
                                            <div class="iglesia-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <h6 class="mb-0">
                                                            <i class="fas fa-church me-2 text-info"></i>
                                                            <?php echo htmlspecialchars($iglesia['codigo'] . ' - ' . $iglesia['nombre']); ?>
                                                        </h6>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <span class="badge bg-info me-2">
                                                            <?php echo $iglesia['total_miembros']; ?> 
                                                            <?php echo $iglesia['total_miembros'] == 1 ? 'miembro' : 'miembros'; ?>
                                                        </span>
                                                        <?php if ($iglesia['total_miembros'] > 0): ?>
                                                            <button class="btn btn-sm btn-outline-primary toggle-members" 
                                                                    data-target="members-<?php echo $iglesia['id']; ?>">
                                                                <i class="fas fa-eye me-1"></i>
                                                                Ver Miembros
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($iglesia['total_miembros'] > 0): ?>
                                                    <div class="member-list" id="members-<?php echo $iglesia['id']; ?>">
                                                        <h6 class="mb-2 text-primary">
                                                            <i class="fas fa-users me-2"></i>
                                                            Lista de Miembros:
                                                        </h6>
                                                        <?php foreach ($iglesia['miembros'] as $miembro): ?>
                                                            <div class="member-item">
                                                                <i class="fas fa-user-circle me-2 text-muted"></i>
                                                                <?php echo htmlspecialchars($miembro); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                    <?php endif; ?>
                                    
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                <?php endif; ?>
                
            </div>
        </div>

    </div>

</div><!-- /.content-wrapper -->

<script>
    // Toggle para mostrar/ocultar miembros
    document.querySelectorAll('.toggle-members').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const targetId = this.getAttribute('data-target');
            const memberList = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (memberList.classList.contains('show')) {
                memberList.classList.remove('show');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.innerHTML = '<i class="fas fa-eye me-1"></i> Ver Miembros';
            } else {
                memberList.classList.add('show');
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.innerHTML = '<i class="fas fa-eye-slash me-1"></i> Ocultar';
            }
        });
    });
    
    // Rotar icono al colapsar/expandir distrito
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(el => {
        el.addEventListener('click', function() {
            this.classList.toggle('collapsed');
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
