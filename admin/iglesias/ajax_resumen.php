<?php
/**
 * AJAX - Resumen de Iglesia
 * Devuelve datos completos de una iglesia para mostrar en modal
 */

session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado (sesión)']);
    exit;
}

// Verificar permiso
$rol = $_SESSION['rol_nombre'] ?? '';
if (!in_array($rol, ['super_admin', 'super_conferencia'])) {
    echo json_encode(['success' => false, 'error' => 'Sin permisos (rol: ' . $rol . ')']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';

$iglesia_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($iglesia_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de iglesia inválido (id: ' . $iglesia_id . ')']);
    exit;
}

try {
    // 1. Datos básicos de la iglesia (sin JOIN a pastores)
    $stmt = $conexion->prepare("
        SELECT i.*, 
               d.nombre AS distrito_nombre, d.codigo AS distrito_codigo,
               c.nombre AS conferencia_nombre, c.codigo AS conferencia_codigo
        FROM iglesias i
        INNER JOIN distritos d ON i.distrito_id = d.id
        INNER JOIN conferencias c ON d.conferencia_id = c.id
        WHERE i.id = ?
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare datos básicos: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute datos básicos: ' . $stmt->error]);
        exit;
    }
    $iglesia = $stmt->get_result()->fetch_assoc();
    if (!$iglesia) {
        echo json_encode(['success' => false, 'error' => 'Iglesia no encontrada (id: ' . $iglesia_id . ')']);
        exit;
    }
    // Validar que super_conferencia solo vea iglesias de su conferencia
    if ($rol === 'super_conferencia') {
        $conf_id = $_SESSION['conferencia_id'] ?? 0;
        $stmt_conf = $conexion->prepare("
            SELECT c.id FROM iglesias i
            INNER JOIN distritos d ON i.distrito_id = d.id
            INNER JOIN conferencias c ON d.conferencia_id = c.id
            WHERE i.id = ?
        ");
        if (!$stmt_conf) {
            echo json_encode(['success' => false, 'error' => 'Error en prepare validación conferencia: ' . $conexion->error]);
            exit;
        }
        $stmt_conf->bind_param("i", $iglesia_id);
        if (!$stmt_conf->execute()) {
            echo json_encode(['success' => false, 'error' => 'Error en execute validación conferencia: ' . $stmt_conf->error]);
            exit;
        }
        $row = $stmt_conf->get_result()->fetch_assoc();
        if (!$row || $row['id'] != $conf_id) {
            echo json_encode(['success' => false, 'error' => 'Sin acceso a esta iglesia (conf_id: ' . $conf_id . ', iglesia_conf: ' . ($row['id'] ?? 'null') . ')']);
            exit;
        }
    }
    // 2. Total de miembros activos
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as total FROM miembros WHERE iglesia_id = ? AND estado = 'activo'
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare total miembros: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute total miembros: ' . $stmt->error]);
        exit;
    }
    $total_miembros = $stmt->get_result()->fetch_assoc()['total'];
    // 3. Miembros por estado de membresía
    $stmt = $conexion->prepare("
        SELECT 
            SUM(CASE WHEN estado_miembro = 'en_plena' THEN 1 ELSE 0 END) AS en_plena,
            SUM(CASE WHEN estado_miembro = 'en_preparacion' THEN 1 ELSE 0 END) AS en_preparacion,
            SUM(CASE WHEN estado_miembro = 'miembro_menor' THEN 1 ELSE 0 END) AS menores
        FROM miembros 
        WHERE iglesia_id = ? AND estado = 'activo'
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare estados membresía: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute estados membresía: ' . $stmt->error]);
        exit;
    }
    $estados = $stmt->get_result()->fetch_assoc();
    // 4. Miembros por ministerio
    $stmt = $conexion->prepare("
        SELECT min.nombre, COUNT(m.id) AS cantidad
        FROM ministerios min
        LEFT JOIN miembros m ON m.ministerio_id = min.id 
            AND m.iglesia_id = ? AND m.estado = 'activo'
        WHERE min.activo = 1
        GROUP BY min.id, min.nombre
        ORDER BY min.id
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare ministerios: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute ministerios: ' . $stmt->error]);
        exit;
    }
    $result = $stmt->get_result();
    $ministerios = [];
    while ($row = $result->fetch_assoc()) {
        $ministerios[] = [
            'nombre' => $row['nombre'],
            'cantidad' => (int)$row['cantidad']
        ];
    }
    // 5. Conteo por género
    $stmt = $conexion->prepare("
        SELECT 
            SUM(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) AS hombres,
            SUM(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) AS mujeres
        FROM miembros 
        WHERE iglesia_id = ? AND estado = 'activo'
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare géneros: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute géneros: ' . $stmt->error]);
        exit;
    }
    $generos = $stmt->get_result()->fetch_assoc();
    // 6. Junta Administrativa Local (activa)
    $stmt = $conexion->prepare("
        SELECT 
            jm.es_pastor,
            cj.nombre AS cargo,
            m.nombre, m.apellido,
            m.telefono,
            m.foto
        FROM juntas j
        INNER JOIN junta_miembros jm ON jm.junta_id = j.id
        INNER JOIN miembros m ON jm.miembro_id = m.id
        INNER JOIN cargos_junta cj ON jm.cargo_id = cj.id
        WHERE j.iglesia_id = ? AND j.activa = 1 AND jm.activo = 1
        ORDER BY cj.orden
    ");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Error en prepare junta: ' . $conexion->error]);
        exit;
    }
    $stmt->bind_param("i", $iglesia_id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Error en execute junta: ' . $stmt->error]);
        exit;
    }
    $result = $stmt->get_result();
    $junta = [];
    $pastor_junta = null;
    while ($row = $result->fetch_assoc()) {
        $junta[] = [
            'cargo' => $row['cargo'],
            'nombre' => $row['nombre'] . ' ' . $row['apellido'],
            'telefono' => $row['telefono'] ?? '-',
            'foto' => $row['foto'] ?? null,
            'es_pastor' => (bool)$row['es_pastor']
        ];
        // Buscar el presidente/pastor
        if (strtolower($row['cargo']) === 'pastor (presidente)') {
            $foto_path = null;
            if (!empty($row['foto'])) {
                $ruta_foto = __DIR__ . '/../../uploads/miembros/' . $row['foto'];
                if (file_exists($ruta_foto)) {
                    $foto_path = '../uploads/miembros/' . $row['foto'];
                }
            }
            $pastor_junta = [
                'nombre' => $row['nombre'] . ' ' . $row['apellido'],
                'telefono' => $row['telefono'] ?? '-',
                'foto' => $foto_path
            ];
        }
    }
    // Preparar respuesta
    $response = [
        'success' => true,
        'iglesia' => [
            'codigo' => $iglesia['codigo'],
            'nombre' => $iglesia['nombre'],
            'direccion' => $iglesia['direccion'] ?? '-',
            'telefono' => $iglesia['telefono'] ?? '-',
            'conferencia' => $iglesia['conferencia_nombre'],
            'distrito' => $iglesia['distrito_nombre'],
            'activo' => (bool)$iglesia['activo']
        ],
        'pastor' => $pastor_junta ?? [
            'nombre' => 'Sin asignar',
            'telefono' => '-',
            'foto' => null
        ],
        'estadisticas' => [
            'total_miembros' => (int)$total_miembros,
            'en_plena' => (int)($estados['en_plena'] ?? 0),
            'en_preparacion' => (int)($estados['en_preparacion'] ?? 0),
            'menores' => (int)($estados['menores'] ?? 0),
            'hombres' => (int)($generos['hombres'] ?? 0),
            'mujeres' => (int)($generos['mujeres'] ?? 0)
        ],
        'ministerios' => $ministerios,
        'junta' => $junta
    ];
    echo json_encode($response);
} catch (Exception $e) {
    error_log("Error ajax_resumen.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Excepción: ' . $e->getMessage()]);
}
