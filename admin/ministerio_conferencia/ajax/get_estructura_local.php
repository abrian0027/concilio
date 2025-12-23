<?php
/**
 * AJAX: Obtener Estructura Local del Ministerio
 * Devuelve información detallada de un ministerio específico en una iglesia
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Obtener parámetros
$iglesia_id = isset($_GET['iglesia_id']) ? intval($_GET['iglesia_id']) : 0;
$area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;

if ($iglesia_id <= 0 || $area_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

try {
    // MAPEO: areas_ministeriales → ministerios
    $area_to_ministerio = [
        1 => 1,  // Damas
        2 => 2,  // Caballeros
        3 => 3,  // Jóvenes
        4 => 4,  // Niños
        5 => 5   // Adolescentes
    ];
    
    $ministerio_id = $area_to_ministerio[$area_id] ?? 0;
    
    if ($ministerio_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Área ministerial no válida']);
        exit;
    }
    
    // 1. OBTENER LÍDER PRINCIPAL
    $sql = "SELECT al.*, 
            CONCAT(m.nombre, ' ', m.apellido) AS nombre_completo,
            m.telefono
            FROM area_lideres al
            INNER JOIN miembros m ON al.miembro_id = m.id
            WHERE al.iglesia_id = ? AND al.area_id = ? AND al.tipo = 'lider' AND al.activo = 1
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $iglesia_id, $area_id);
    $stmt->execute();
    $lider = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // 2. OBTENER CO-LÍDER
    $sql = "SELECT al.*, 
            CONCAT(m.nombre, ' ', m.apellido) AS nombre_completo,
            m.telefono
            FROM area_lideres al
            INNER JOIN miembros m ON al.miembro_id = m.id
            WHERE al.iglesia_id = ? AND al.area_id = ? AND al.tipo = 'colider' AND al.activo = 1
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $iglesia_id, $area_id);
    $stmt->execute();
    $colider = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // 3. OBTENER ESTADÍSTICAS
    $sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) AS mujeres,
            SUM(CASE WHEN es_bautizado = 1 THEN 1 ELSE 0 END) AS bautizados
            FROM miembros
            WHERE iglesia_id = ? AND ministerio_id = ? AND estado = 'activo'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $iglesia_id, $ministerio_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // 4. OBTENER LISTA DE MIEMBROS
    $sql = "SELECT 
            m.id,
            CONCAT(m.nombre, ' ', m.apellido) AS nombre,
            m.sexo,
            m.es_bautizado,
            TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) AS edad,
            (SELECT COUNT(*) FROM area_lideres al WHERE al.miembro_id = m.id AND al.area_id = ? AND al.tipo = 'lider' AND al.activo = 1) AS es_lider,
            (SELECT COUNT(*) FROM area_lideres al WHERE al.miembro_id = m.id AND al.area_id = ? AND al.tipo = 'colider' AND al.activo = 1) AS es_colider
            FROM miembros m
            WHERE m.iglesia_id = ? AND m.ministerio_id = ? AND m.estado = 'activo'
            ORDER BY m.nombre, m.apellido";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiii", $area_id, $area_id, $iglesia_id, $ministerio_id);
    $stmt->execute();
    $miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'data' => [
            'lider' => [
                'nombre' => $lider ? $lider['nombre_completo'] : null,
                'telefono' => $lider['telefono'] ?? null
            ],
            'colider' => [
                'nombre' => $colider ? $colider['nombre_completo'] : null,
                'telefono' => $colider['telefono'] ?? null
            ],
            'stats' => [
                'total' => intval($stats['total'] ?? 0),
                'hombres' => intval($stats['hombres'] ?? 0),
                'mujeres' => intval($stats['mujeres'] ?? 0),
                'bautizados' => intval($stats['bautizados'] ?? 0)
            ],
            'miembros' => array_map(function($m) {
                return [
                    'id' => intval($m['id']),
                    'nombre' => $m['nombre'],
                    'edad' => intval($m['edad']),
                    'sexo' => $m['sexo'],
                    'es_bautizado' => intval($m['es_bautizado']) === 1,
                    'es_lider' => intval($m['es_lider']) > 0,
                    'es_colider' => intval($m['es_colider']) > 0
                ];
            }, $miembros)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}
?>
