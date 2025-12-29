<?php
/**
 * Imprimir Acta de Presentación de Niño
 * Sistema Concilio - Módulo Registros
 * Formato oficial para impresión - Solo iglesia local
 */

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../../auth/login.php');
    exit;
}

// Verificar permisos - Solo roles de iglesia local
$rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
$roles_permitidos = array('pastor', 'secretaria');
if (!in_array($rol_nombre, $roles_permitidos)) {
    die('No tiene permisos para acceder a esta sección. Solo Pastor o Secretaria.');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;

if ($id <= 0) {
    die('ID no válido');
}

if ($iglesia_id <= 0) {
    die('No tiene una iglesia asignada');
}

// Obtener registro - Solo de la iglesia del usuario
$stmt = $conexion->prepare("SELECT p.*, i.nombre AS iglesia_nombre, i.codigo AS iglesia_codigo
                            FROM presentacion_ninos p
                            LEFT JOIN iglesias i ON p.iglesia_id = i.id
                            WHERE p.id = ? AND p.iglesia_id = ?");
$stmt->bind_param("ii", $id, $iglesia_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
    die('Registro no encontrado');
}

// Obtener pastor de la iglesia (via pastor_iglesias)
$stmt = $conexion->prepare("SELECT CONCAT(p.nombre, ' ', p.apellido) AS pastor_nombre 
                            FROM pastores p 
                            INNER JOIN pastor_iglesias pi ON pi.pastor_id = p.id 
                            WHERE pi.iglesia_id = ? AND pi.activo = 1 AND pi.es_principal = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$pastor_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$pastor_nombre = !empty($pastor_info['pastor_nombre']) ? mb_strtoupper($pastor_info['pastor_nombre'], 'UTF-8') : '';

// Obtener secretaria
$stmt = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) AS secretaria_nombre 
                            FROM usuarios 
                            WHERE iglesia_id = ? AND rol_id IN (SELECT id FROM roles WHERE nombre = 'secretaria') AND activo = 1
                            LIMIT 1");
$stmt->bind_param("i", $iglesia_id);
$stmt->execute();
$secretaria_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$secretaria_nombre = !empty($secretaria_info['secretaria_nombre']) ? mb_strtoupper($secretaria_info['secretaria_nombre'], 'UTF-8') : '';

// Meses en español
$meses = array(
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
);

// Formatear datos - Convertir nombres a MAYÚSCULAS
$nombre_completo = mb_strtoupper(trim($registro['nombres'] . ' ' . $registro['apellidos']), 'UTF-8');
$sexo = $registro['sexo'] ?? 'M';
$hijo_a = ($sexo == 'F') ? 'hija' : 'hijo';
$presentado_a = ($sexo == 'F') ? 'presentada' : 'presentado';
$iglesia_local = $registro['iglesia_nombre'] ?? '';

// Calcular edad al momento de presentación
$fecha_nac = new DateTime($registro['fecha_nacimiento']);
$fecha_pres = new DateTime($registro['fecha_presentacion']);
$edad = $fecha_nac->diff($fecha_pres);
$edad_texto = '';
if ($edad->y > 0) {
    $edad_texto = $edad->y . ' año' . ($edad->y > 1 ? 's' : '');
    if ($edad->m > 0) {
        $edad_texto .= ' y ' . $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
    }
} elseif ($edad->m > 0) {
    $edad_texto = $edad->m . ' mes' . ($edad->m > 1 ? 'es' : '');
    if ($edad->d > 0) {
        $edad_texto .= ' y ' . $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
    }
} else {
    $edad_texto = $edad->d . ' día' . ($edad->d > 1 ? 's' : '');
}

// Fecha presentación formateada
$dia_pres = date('j', strtotime($registro['fecha_presentacion']));
$mes_pres = $meses[(int)date('n', strtotime($registro['fecha_presentacion']))];
$anio_pres = date('Y', strtotime($registro['fecha_presentacion']));

// Datos opcionales con líneas si vacío - Convertir nombres a MAYÚSCULAS
$nombre_padre = !empty($registro['nombre_padre']) ? mb_strtoupper($registro['nombre_padre'], 'UTF-8') : '_____________________________';
$nombre_madre = !empty($registro['nombre_madre']) ? mb_strtoupper($registro['nombre_madre'], 'UTF-8') : '_____________________________';
$nacionalidad = !empty($registro['nacionalidad']) ? $registro['nacionalidad'] : '_____________________________';
$ministro = !empty($registro['ministro']) ? mb_strtoupper($registro['ministro'], 'UTF-8') : '_____________________________';
$testigo1 = !empty($registro['testigo1']) ? mb_strtoupper($registro['testigo1'], 'UTF-8') : '_____________________________';
$testigo2 = !empty($registro['testigo2']) ? mb_strtoupper($registro['testigo2'], 'UTF-8') : '_____________________________';
$lugar = !empty($registro['lugar']) ? $registro['lugar'] : ($iglesia_local ?? '_____________________________');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Presentación - <?php echo htmlspecialchars($nombre_completo); ?></title>
    <link rel="icon" type="image/png" href="/concilio/assets/img/favicon.png">
    <style>
        @page {
            size: 8.5in 11in;
            margin: 0.8cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
            padding: 10px;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 70px;
            height: auto;
            margin-bottom: 5px;
        }
        
        .registry-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0;
            text-decoration: underline;
            letter-spacing: 1px;
        }
        
        .acta-number {
            font-size: 11pt;
            margin-bottom: 12px;
        }
        
        .intro {
            text-align: center;
            font-style: italic;
            margin-bottom: 15px;
            font-size: 11pt;
        }
        
        .content {
            text-align: justify;
            margin-bottom: 20px;
        }
        
        .content p {
            margin-bottom: 10px;
            text-indent: 30px;
        }
        
        .underline {
            border-bottom: 1px solid #000;
            display: inline;
            padding: 0 3px;
            font-weight: bold;
        }
        
        .scripture {
            font-style: italic;
            display: block;
            margin: 10px 25px;
            text-align: center;
            font-size: 11pt;
        }
        
        .civil-data {
            margin: 15px 0;
            padding: 8px;
            border: 1px solid #ccc;
            background: #fafafa;
            font-size: 9pt;
        }
        
        .civil-data-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 10pt;
        }
        
        .signatures {
            margin-top: 25px;
            page-break-inside: avoid;
        }
        
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 25px;
            font-size: 9pt;
        }
        
        .signature-title {
            font-size: 8pt;
            color: #555;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        @media print {
            body {
                padding: 0;
                -webkit-print-color-adjust: exact;
            }
            .container {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .civil-data {
                background: #fff;
            }
        }
        
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 25px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
        
        .btn-back {
            position: fixed;
            top: 20px;
            right: 130px;
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-back:hover {
            background: #545b62;
            color: white;
        }
    </style>
</head>
<body>
    <a href="ver.php?id=<?php echo $id; ?>" class="btn-back no-print">← Volver</a>
    <button onclick="window.print()" class="btn-print no-print">🖨️ Imprimir</button>
    
    <div class="container">
        <!-- Encabezado Oficial -->
        <div class="header">
            <img src="/concilio/assets/img/logo-concilio.png" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div class="registry-name">REGISTRO BAUTISMAL Y DOCUMENTOS CRISTIANOS</div>
            <div class="registry-name">IGLESIA METODISTA LIBRE-INC.</div>
            <div class="title">ACTA DE PRESENTACIÓN DE NIÑOS AL SEÑOR</div>
            <div class="acta-number">Acta No. <strong><?php echo htmlspecialchars($registro['numero_acta']); ?></strong></div>
        </div>
        
        <!-- Introducción -->
        <div class="intro">
            Sirva la presente para certificar que:
        </div>
        
        <!-- Contenido del Acta -->
        <div class="content">
            <p>
                <span class="underline"><?php echo htmlspecialchars($nombre_completo); ?></span>, 
                de <span class="underline"><?php echo $edad_texto; ?></span> de edad, 
                <?php echo $hijo_a; ?> de <span class="underline"><?php echo htmlspecialchars($nombre_padre); ?></span> 
                y <span class="underline"><?php echo htmlspecialchars($nombre_madre); ?></span>, 
                de nacionalidad <span class="underline"><?php echo htmlspecialchars($nacionalidad); ?></span>,
            </p>
            
            <p>
                fue solemnemente <?php echo $presentado_a; ?> al Señor; en cumplimiento al mandato que nos establece Dios en las Sagradas Escrituras, donde nos dice:
            </p>
            
            <span class="scripture">
                "Dejad venir los niños a mí y no se lo impidáis, porque de los tales es el Reino de Dios."
            </span>
            
            <p>
                Dicho acto fue oficializado por el Ministro(a) de Dios, <span class="underline"><?php echo htmlspecialchars($ministro); ?></span>, 
                comparecieron como testigos los señores <span class="underline"><?php echo htmlspecialchars($testigo1); ?></span> 
                y <span class="underline"><?php echo htmlspecialchars($testigo2); ?></span>,
            </p>
            
            <p>
                el día <span class="underline"><?php echo $dia_pres; ?></span> 
                del mes de <span class="underline"><?php echo $mes_pres; ?></span> 
                del año <span class="underline"><?php echo $anio_pres; ?></span>, 
                en <span class="underline"><?php echo htmlspecialchars($lugar); ?></span>.
            </p>
        </div>
        
        <?php if (!empty($registro['libro_no']) || !empty($registro['folio']) || !empty($registro['acta_civil_no'])): ?>
        <!-- Datos de Registro Civil -->
        <div class="civil-data">
            <div class="civil-data-title">Datos de Registro Civil:</div>
            <?php if (!empty($registro['libro_no'])): ?>
                Libro No. <?php echo htmlspecialchars($registro['libro_no']); ?> |
            <?php endif; ?>
            <?php if (!empty($registro['folio'])): ?>
                Folio: <?php echo htmlspecialchars($registro['folio']); ?> |
            <?php endif; ?>
            <?php if (!empty($registro['acta_civil_no'])): ?>
                Acta No. <?php echo htmlspecialchars($registro['acta_civil_no']); ?>
                <?php if (!empty($registro['acta_civil_anio'])): ?>
                    del año <?php echo $registro['acta_civil_anio']; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($registro['oficilia_civil'])): ?>
                <br>Registrado en: <?php echo htmlspecialchars($registro['oficilia_civil']); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Firmas -->
        <div class="signatures">
            <!-- Fila 1: Padres -->
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo !empty($registro['nombre_padre']) ? htmlspecialchars($registro['nombre_padre']) : ''; ?>
                    </div>
                    <div class="signature-title">Padre</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo !empty($registro['nombre_madre']) ? htmlspecialchars($registro['nombre_madre']) : ''; ?>
                    </div>
                    <div class="signature-title">Madre</div>
                </div>
            </div>
            
            <!-- Fila 2: Testigos -->
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo !empty($registro['testigo1']) ? htmlspecialchars($registro['testigo1']) : ''; ?>
                    </div>
                    <div class="signature-title">Testigo 1</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo !empty($registro['testigo2']) ? htmlspecialchars($registro['testigo2']) : ''; ?>
                    </div>
                    <div class="signature-title">Testigo 2</div>
                </div>
            </div>
            
            <!-- Fila 3: Pastor y Secretario -->
            <div class="signature-row">
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo htmlspecialchars($pastor_nombre); ?>
                    </div>
                    <div class="signature-title">Pastor</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        <?php echo htmlspecialchars($secretaria_nombre); ?>
                    </div>
                    <div class="signature-title">Secretario(a)</div>
                </div>
            </div>
        </div>
        
        <!-- Pie de página con nombre de iglesia local -->
        <div class="footer">
            <?php echo htmlspecialchars($iglesia_local); ?> - Documento generado el <?php echo date('d/m/Y'); ?>
        </div>
    </div>
</body>
</html>
