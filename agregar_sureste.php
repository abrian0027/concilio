<?php
/**
 * Script para agregar Distritos e Iglesias de la Conferencia Sureste
 * Códigos: IML-301 en adelante
 */

require_once 'config/config.php';

echo "=== AGREGAR CONFERENCIA SURESTE ===\n\n";

// Verificar que existe la Conferencia Sureste (ID: 3)
$conf = $conexion->query("SELECT id, nombre FROM conferencias WHERE id = 3")->fetch_assoc();
if (!$conf) {
    die("Error: No existe la Conferencia Sureste (ID: 3)\n");
}
echo "✓ Conferencia encontrada: {$conf['nombre']} (ID: {$conf['id']})\n\n";

// ============================================
// DISTRITOS DE LA CONFERENCIA SURESTE
// ============================================
$distritos = [
    ['codigo' => 'SE-101', 'nombre' => 'Distrito Santo Domingo Central'],
    ['codigo' => 'SE-102', 'nombre' => 'Distrito Santo Domingo Oriental'],
    ['codigo' => 'SE-103', 'nombre' => 'Distrito Este'],
    ['codigo' => 'SE-104', 'nombre' => 'Distrito Sur'],
];

// ============================================
// IGLESIAS POR DISTRITO
// ============================================
$iglesias = [
    // Distrito Santo Domingo Central (SE-101)
    'SE-101' => [
        'IML – Casa del Padre – Hotel Golden House',
        'IML – Cristo Rey',
        'IML – Ensanche La Fe',
        'IML – Ensanche Luperón',
        'IML – Ensanche Quisqueya',
        'IML – Arroyo Bonito – Capilla Quisqueya',
        'IML – Haina Étnico',
        'IML – Boca Nigua – Capilla Haina Boca Étnico',
        'IML – Haina Shalom',
        'IML – Herrera – Barrio Enriquillo',
        'IML – Palmarejo – Capilla Herrera',
        'IML – Jardines del Norte',
        'IML – Jesús el Mesías (La 15) – Barrio 27 de Febrero',
        'IML – Juan de Morfa (Central)',
        'IML – Km 24 – Barrio Eduardo Brito, Autopista Duarte',
        'IML – Km 24 Étnico – Capilla',
        'IML – Manoguayabo – Hato Nuevo',
        'IML – Nación Santa – Enriquillo',
        'IML – Haina Balsequillo – Capilla Nación Santa',
        'IML – Majagual, Sabana Perdida – Capilla Nación Santa',
        'IML – Pantoja',
        'IML – Roca Mar – En Su Presencia',
        'IML – Constanza – Capilla En Su Presencia',
        'IML – Simón Bolívar',
        'IML – Villa Linda – Ciudad Satélite – Capilla',
    ],
    // Distrito Santo Domingo Oriental (SE-102)
    'SE-102' => [
        'IML – Alma Rosa Primera',
        'IML – Carretera Mella (Luz en las Tinieblas)',
        'IML – Ensanche Isabelita',
        'IML – Ensanche Cancela (Étnico)',
        'IML – Ensanche Ozama',
        'IML – Mendoza – Capilla Ozama',
        'IML – Invivienda',
        'IML – Villa Esfuerzo – Capilla Invivienda',
        'IML – Los Frailes I',
        'IML – Los Mina',
        'IML – Los Tres Brazos',
        'IML – Los Tres Ojos',
        'IML – Urbanización Ciudad Juan Bosch',
        'IML – Urbanización Lomisa',
        'IML – Valiente (Étnico)',
        'IML – Villa Faro',
        'IML – Villa Mella – Buena Vista II',
        'IML – Villa Mella – El Edén',
        'IML – Villa Mella – Guaricano Étnico',
        'IML – Villa Mella – Vista Bella III',
    ],
    // Distrito Este (SE-103)
    'SE-103' => [
        'IML – El Valle',
        'IML – Higüey I',
        'IML – Higüey II',
        'IML – Magua',
        'IML – Romana I (Central)',
        'IML – Romana II – Quisqueya',
        'IML – Romana III – Casa de Alabanzas',
        'IML – Romana IV – Villa Progreso',
        'IML – Romana V – La Lechoza',
        'IML – Romana VI – Barrio George',
        'IML – Romana VII – Piedra Linda',
        'IML – Romana VIII',
        'IML – Romana IX',
        'IML – Romana X',
        'IML – Romana XI (Benjamín)',
        'IML – Sabana de la Mar',
        'IML – San Pedro I – Central',
        'IML – San Pedro II – Villa Olímpica',
        'IML – San Pedro IV (Canaán) – Capilla San Pedro II',
        'IML – San Pedro III – Barrio Miramar',
    ],
    // Distrito Sur (SE-104)
    'SE-104' => [
        'IML – Azua Central',
        'IML – Azua – Finca 6',
        'IML – Azua – Finca Étnico',
        'IML – Azua – Las Charcas (Étnico)',
        'IML – Azua – Sector El Hoyo',
        'IML – Baní',
        'IML – Baní – El Fundo – Capilla Baní',
        'IML – Barahona',
        'IML – Elías Piña',
        'IML – Ocoa Étnico',
        'IML – San Cristóbal',
        'IML – San Cristóbal (Étnico)',
        'IML – San Cristóbal Étnico II',
        'IML – San José de Ocoa',
        'IML – San Juan I (Central)',
        'IML – San Juan II – Casa de Adoración',
        'IML – San Juan III (El Renuevo)',
    ],
];

// Preguntar confirmación
echo "Se agregarán:\n";
echo "- 4 distritos\n";
$total_iglesias = array_sum(array_map('count', $iglesias));
echo "- {$total_iglesias} iglesias\n";
echo "- Códigos: IML-301 a IML-" . (300 + $total_iglesias) . "\n\n";

echo "¿Desea continuar? (escribe 'SI' para confirmar): ";
$handle = fopen("php://stdin", "r");
$confirmacion = trim(fgets($handle));

if (strtoupper($confirmacion) !== 'SI') {
    echo "\nOperación cancelada.\n";
    exit;
}

echo "\n";

// ============================================
// INSERTAR DISTRITOS
// ============================================
$conexion->begin_transaction();

try {
    echo "=== INSERTANDO DISTRITOS ===\n";
    
    $distrito_ids = [];
    
    foreach ($distritos as $d) {
        // Verificar si ya existe
        $check = $conexion->prepare("SELECT id FROM distritos WHERE codigo = ? OR nombre = ?");
        $check->bind_param("ss", $d['codigo'], $d['nombre']);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();
        
        if ($existe) {
            echo "⚠ Distrito ya existe: {$d['nombre']} (ID: {$existe['id']})\n";
            $distrito_ids[$d['codigo']] = $existe['id'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO distritos (conferencia_id, codigo, nombre, activo) VALUES (3, ?, ?, 1)");
            $stmt->bind_param("ss", $d['codigo'], $d['nombre']);
            $stmt->execute();
            $distrito_ids[$d['codigo']] = $conexion->insert_id;
            echo "✓ Distrito agregado: {$d['nombre']} (ID: {$distrito_ids[$d['codigo']]})\n";
        }
    }
    
    // ============================================
    // INSERTAR IGLESIAS
    // ============================================
    echo "\n=== INSERTANDO IGLESIAS ===\n";
    
    $codigo_num = 301; // Empezar desde IML-301
    $iglesias_agregadas = 0;
    
    foreach ($iglesias as $distrito_codigo => $lista_iglesias) {
        $distrito_id = $distrito_ids[$distrito_codigo];
        echo "\n📍 {$distrito_codigo} (ID: {$distrito_id}):\n";
        
        foreach ($lista_iglesias as $nombre) {
            $codigo = 'IML-' . $codigo_num;
            
            // Verificar si ya existe
            $check = $conexion->prepare("SELECT id FROM iglesias WHERE nombre = ?");
            $check->bind_param("s", $nombre);
            $check->execute();
            $existe = $check->get_result()->fetch_assoc();
            
            if ($existe) {
                echo "   ⚠ Ya existe: {$nombre}\n";
            } else {
                $stmt = $conexion->prepare("INSERT INTO iglesias (distrito_id, codigo, nombre, activo) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("iss", $distrito_id, $codigo, $nombre);
                $stmt->execute();
                echo "   ✓ {$codigo} - {$nombre}\n";
                $iglesias_agregadas++;
            }
            
            $codigo_num++;
        }
    }
    
    $conexion->commit();
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "¡COMPLETADO!\n";
    echo "- Distritos: 4\n";
    echo "- Iglesias agregadas: {$iglesias_agregadas}\n";
    echo "- Códigos usados: IML-301 a IML-" . ($codigo_num - 1) . "\n";
    
} catch (Exception $e) {
    $conexion->rollback();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Se revirtieron todos los cambios.\n";
}

fclose($handle);
