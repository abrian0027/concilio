<?php
/**
 * DIAGNÓSTICO - Iglesia 40 (Matancitas)
 * Ejecutar este archivo para identificar el problema
 * URL: localhost/concilio/admin/distritos/diagnostico_iglesia.php?iglesia=40
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

$iglesia_id = isset($_GET['iglesia']) ? (int)$_GET['iglesia'] : 40;

echo "<h1>🔍 DIAGNÓSTICO - Iglesia ID: $iglesia_id</h1>";
echo "<hr>";

// ==================== 1. VERIFICAR IGLESIA ====================
echo "<h2>1️⃣ INFORMACIÓN DE LA IGLESIA</h2>";

$stmt = $conexion->prepare("SELECT i.*, d.nombre AS distrito_nombre, d.codigo AS distrito_codigo
                            FROM iglesias i
                            INNER JOIN distritos d ON i.distrito_id = d.id
                            WHERE i.id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($iglesia) {
    echo "<pre>";
    print_r($iglesia);
    echo "</pre>";
    echo "<p style='color: green;'>✅ Iglesia encontrada</p>";
} else {
    echo "<p style='color: red;'>❌ Iglesia NO encontrada</p>";
    exit;
}

// ==================== 2. VERIFICAR MIEMBROS ====================
echo "<h2>2️⃣ MIEMBROS DE LA IGLESIA</h2>";

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM miembros WHERE iglesia_id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

echo "<p><strong>Total de miembros:</strong> $total</p>";

if ($total == 0) {
    echo "<p style='color: orange;'>⚠️ No hay miembros registrados</p>";
}

// ==================== 3. VERIFICAR DATOS CRÍTICOS ====================
echo "<h2>3️⃣ VALIDACIÓN DE DATOS CRÍTICOS</h2>";

// Verificar campos NULL o vacíos
$problemas = [];

$stmt = $conexion->prepare("SELECT 
    SUM(CASE WHEN fecha_nacimiento IS NULL OR fecha_nacimiento = '0000-00-00' THEN 1 ELSE 0 END) AS sin_fecha,
    SUM(CASE WHEN sexo IS NULL OR sexo = '' OR sexo NOT IN ('M', 'F') THEN 1 ELSE 0 END) AS sin_sexo,
    SUM(CASE WHEN tipo_membresia IS NULL OR tipo_membresia = '' THEN 1 ELSE 0 END) AS sin_tipo
    FROM miembros 
    WHERE iglesia_id = ?");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$validacion = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campo</th><th>Registros con problema</th><th>Estado</th></tr>";

echo "<tr>";
echo "<td>fecha_nacimiento</td>";
echo "<td>{$validacion['sin_fecha']}</td>";
echo "<td>" . ($validacion['sin_fecha'] > 0 ? "❌ PROBLEMA" : "✅ OK") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>sexo</td>";
echo "<td>{$validacion['sin_sexo']}</td>";
echo "<td>" . ($validacion['sin_sexo'] > 0 ? "❌ PROBLEMA" : "✅ OK") . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>tipo_membresia</td>";
echo "<td>{$validacion['sin_tipo']}</td>";
echo "<td>" . ($validacion['sin_tipo'] > 0 ? "❌ PROBLEMA" : "✅ OK") . "</td>";
echo "</tr>";

echo "</table>";

// ==================== 4. LISTAR MIEMBROS CON PROBLEMAS ====================
if ($validacion['sin_fecha'] > 0 || $validacion['sin_sexo'] > 0) {
    echo "<h2>4️⃣ MIEMBROS CON DATOS INCOMPLETOS</h2>";
    
    $stmt = $conexion->prepare("SELECT id, nombre, apellido, fecha_nacimiento, sexo, tipo_membresia
                                FROM miembros 
                                WHERE iglesia_id = ?
                                AND (
                                    fecha_nacimiento IS NULL 
                                    OR fecha_nacimiento = '0000-00-00'
                                    OR sexo IS NULL 
                                    OR sexo = ''
                                    OR sexo NOT IN ('M', 'F')
                                )");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Fecha Nacimiento</th><th>Sexo</th><th>Tipo Membresía</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']} {$row['apellido']}</td>";
        echo "<td style='color: " . (empty($row['fecha_nacimiento']) || $row['fecha_nacimiento'] == '0000-00-00' ? "red" : "green") . "'>{$row['fecha_nacimiento']}</td>";
        echo "<td style='color: " . (empty($row['sexo']) || !in_array($row['sexo'], ['M', 'F']) ? "red" : "green") . "'>{$row['sexo']}</td>";
        echo "<td>{$row['tipo_membresia']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    $stmt->close();
}

// ==================== 5. PROBAR CONSULTAS CRÍTICAS ====================
echo "<h2>5️⃣ PRUEBA DE CONSULTAS CRÍTICAS</h2>";

echo "<h3>Consulta: Total por tipo de membresía</h3>";
try {
    $stmt = $conexion->prepare("SELECT 
        SUM(CASE WHEN tipo_membresia = 'preparacion' THEN 1 ELSE 0 END) AS preparacion,
        SUM(CASE WHEN tipo_membresia = 'plena_comunion' THEN 1 ELSE 0 END) AS plena_comunion,
        SUM(CASE WHEN tipo_membresia = 'menor' THEN 1 ELSE 0 END) AS menor
        FROM miembros 
        WHERE iglesia_id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $membresia = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo "<pre>";
    print_r($membresia);
    echo "</pre>";
    echo "<p style='color: green;'>✅ Consulta exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Consulta: Damas (mujeres >= 18 años)</h3>";
try {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total 
                                FROM miembros 
                                WHERE iglesia_id = ? 
                                AND sexo = 'F' 
                                AND TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 18");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $damas = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    echo "<p>Total Damas: $damas</p>";
    echo "<p style='color: green;'>✅ Consulta exitosa</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// ==================== 6. VERIFICAR PASTOR ====================
echo "<h2>6️⃣ PASTOR ASIGNADO</h2>";

$stmt = $conexion->prepare("SELECT CONCAT(p.nombre, ' ', p.apellido) AS nombre, p.telefono, p.email
                            FROM pastor_iglesias pi
                            INNER JOIN pastores p ON p.id = pi.pastor_id
                            WHERE pi.iglesia_id = ? AND pi.activo = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$pastor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($pastor) {
    echo "<p>✅ Pastor encontrado: <strong>{$pastor['nombre']}</strong></p>";
} else {
    echo "<p>⚠️ No hay pastor asignado</p>";
}

// ==================== 7. VERIFICAR JUNTA ADMINISTRATIVA ====================
echo "<h2>7️⃣ JUNTA ADMINISTRATIVA</h2>";

$stmt = $conexion->prepare("SELECT id FROM periodos_conferencia 
                            WHERE iglesia_id = ? AND activo = 1 
                            ORDER BY fecha_fin DESC LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$periodo_result = $stmt->get_result()->fetch_assoc();
$periodo_id = $periodo_result['id'] ?? 0;
$stmt->close();

if ($periodo_id > 0) {
    echo "<p>✅ Período activo encontrado: ID $periodo_id</p>";
    
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total
                                FROM junta_miembros jm
                                WHERE jm.junta_id IN (
                                    SELECT id FROM junta_administrativa 
                                    WHERE periodo_id = ?
                                )");
    $stmt->bind_param("i", $periodo_id);
    $stmt->execute();
    $miembros_junta = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    echo "<p>Total miembros de junta: $miembros_junta</p>";
} else {
    echo "<p>⚠️ No hay período activo</p>";
}

// ==================== 8. RESUMEN ====================
echo "<h2>8️⃣ RESUMEN DEL DIAGNÓSTICO</h2>";

$errores = [];

if (!$iglesia) {
    $errores[] = "Iglesia no encontrada";
}

if ($total == 0) {
    $errores[] = "No hay miembros registrados";
}

if ($validacion['sin_fecha'] > 0) {
    $errores[] = "{$validacion['sin_fecha']} miembros sin fecha de nacimiento";
}

if ($validacion['sin_sexo'] > 0) {
    $errores[] = "{$validacion['sin_sexo']} miembros sin sexo o con valor inválido";
}

if (count($errores) > 0) {
    echo "<div style='background: #fee; padding: 20px; border-left: 4px solid red;'>";
    echo "<h3 style='color: red;'>❌ PROBLEMAS ENCONTRADOS:</h3>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li><strong>$error</strong></li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🔧 SOLUCIÓN:</h3>";
    echo "<p>Ejecuta el siguiente script SQL para corregir los datos:</p>";
    
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
    echo "-- Actualizar fecha de nacimiento a una fecha por defecto (ejemplo: 1990-01-01)\n";
    echo "UPDATE miembros SET fecha_nacimiento = '1990-01-01' \n";
    echo "WHERE iglesia_id = $iglesia_id \n";
    echo "AND (fecha_nacimiento IS NULL OR fecha_nacimiento = '0000-00-00');\n\n";
    
    echo "-- Actualizar sexo a 'M' por defecto (o puedes poner 'F')\n";
    echo "UPDATE miembros SET sexo = 'M' \n";
    echo "WHERE iglesia_id = $iglesia_id \n";
    echo "AND (sexo IS NULL OR sexo = '' OR sexo NOT IN ('M', 'F'));\n\n";
    
    echo "-- Actualizar tipo de membresía a 'plena_comunion' por defecto\n";
    echo "UPDATE miembros SET tipo_membresia = 'plena_comunion' \n";
    echo "WHERE iglesia_id = $iglesia_id \n";
    echo "AND (tipo_membresia IS NULL OR tipo_membresia = '');";
    echo "</textarea>";
    
} else {
    echo "<div style='background: #efe; padding: 20px; border-left: 4px solid green;'>";
    echo "<h3 style='color: green;'>✅ TODO CORRECTO</h3>";
    echo "<p>No se encontraron problemas con los datos de esta iglesia.</p>";
    echo "<p>El error HTTP 500 puede deberse a otro problema. Revisa los logs de PHP.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='resumen_iglesia.php?iglesia=$iglesia_id' style='padding: 10px 20px; background: #0dcaf0; color: white; text-decoration: none; border-radius: 5px;'>🔄 Probar Resumen de Iglesia</a></p>";
?>
