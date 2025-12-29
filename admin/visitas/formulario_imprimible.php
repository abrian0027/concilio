<?php
/**
 * Formulario Imprimible de Visitas - Sistema Concilio
 * Para recoger datos de visitantes en papel
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

// Obtener datos de la iglesia
$iglesia_id = $_SESSION['iglesia_id'] ?? 0;
$iglesia_nombre = "Iglesia";
$iglesia_direccion = "";

if ($iglesia_id > 0) {
    $stmt = $conexion->prepare("SELECT nombre, direccion FROM iglesias WHERE id = ?");
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $iglesia_nombre = $row['nombre'];
        $iglesia_direccion = $row['direccion'] ?? '';
    }
}

// Número de filas en blanco para el formulario
$num_filas = isset($_GET['filas']) ? (int)$_GET['filas'] : 10;
if ($num_filas < 5) $num_filas = 5;
if ($num_filas > 25) $num_filas = 25;

// Fecha actual
$fecha_hoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Visitas - <?php echo htmlspecialchars($iglesia_nombre); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/concilio/assets/img/favicon.ico">
    <link rel="shortcut icon" href="/concilio/assets/img/favicon.ico">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }
        
        /* Header con logo */
        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .header-info {
            flex: 1;
        }
        
        .header-info h1 {
            font-size: 18px;
            color: #0d6efd;
            margin-bottom: 3px;
        }
        
        .header-info h2 {
            font-size: 14px;
            font-weight: normal;
            color: #666;
            margin-bottom: 2px;
        }
        
        .header-info p {
            font-size: 10px;
            color: #888;
        }
        
        .header-date {
            text-align: right;
            font-size: 12px;
        }
        
        .header-date strong {
            display: block;
            font-size: 14px;
            color: #0d6efd;
        }
        
        /* Título del formulario */
        .form-title {
            background: #0d6efd;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        /* Instrucciones */
        .instrucciones {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .instrucciones strong {
            color: #0d6efd;
        }
        
        /* Tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        th {
            background: #e9ecef;
            padding: 6px 4px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #ccc;
            white-space: nowrap;
        }
        
        td {
            padding: 8px 4px;
            border: 1px solid #ccc;
            height: 28px;
            vertical-align: middle;
        }
        
        .col-num { width: 25px; text-align: center; background: #f8f9fa; }
        .col-nombre { width: 18%; }
        .col-apellido { width: 18%; }
        .col-sexo { width: 40px; text-align: center; }
        .col-telefono { width: 13%; }
        .col-categoria { width: 12%; }
        .col-invitado { width: 18%; }
        .col-obs { width: auto; }
        
        /* Leyenda de categorías */
        .leyenda {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            font-size: 10px;
        }
        
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .leyenda-code {
            display: inline-block;
            width: 22px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            background: #0d6efd;
            color: white;
            font-weight: bold;
            font-size: 9px;
            border-radius: 3px;
        }
        
        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #666;
        }
        
        .firma-section {
            display: flex;
            gap: 40px;
            margin-top: 30px;
        }
        
        .firma-box {
            flex: 1;
            text-align: center;
        }
        
        .firma-linea {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
        }
        
        /* Botones de impresión (no se imprimen) */
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        
        .btn-print {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print-primary {
            background: #0d6efd;
            color: white;
        }
        
        .btn-print-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-print:hover {
            opacity: 0.9;
        }
        
        /* Estilos de impresión */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .container {
                padding: 5mm;
            }
            
            @page {
                size: A4 landscape;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>
    <!-- Botones de acción (no se imprimen) -->
    <div class="no-print">
        <button class="btn-print btn-print-primary" onclick="window.print()">
            🖨️ Imprimir
        </button>
        <button class="btn-print btn-print-secondary" onclick="window.close()">
            ✕ Cerrar
        </button>
        <select onchange="cambiarFilas(this.value)" style="padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
            <option value="5" <?php echo $num_filas == 5 ? 'selected' : ''; ?>>5 filas</option>
            <option value="10" <?php echo $num_filas == 10 ? 'selected' : ''; ?>>10 filas</option>
            <option value="15" <?php echo $num_filas == 15 ? 'selected' : ''; ?>>15 filas</option>
            <option value="20" <?php echo $num_filas == 20 ? 'selected' : ''; ?>>20 filas</option>
        </select>
    </div>
    
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <img src="../../assets/img/logo-concilio.png" alt="Logo" class="logo" onerror="this.style.display='none'">
            <div class="header-info">
                <h1><?php echo htmlspecialchars($iglesia_nombre); ?></h1>
                <h2>Registro de Visitas</h2>
                <?php if ($iglesia_direccion): ?>
                <p><?php echo htmlspecialchars($iglesia_direccion); ?></p>
                <?php endif; ?>
            </div>
            <div class="header-date">
                Fecha del Servicio:<br>
                <strong>____/____/________</strong>
            </div>
        </div>
        
        <!-- Instrucciones -->
        <div class="instrucciones">
            <strong>Instrucciones:</strong> Complete los datos de cada visitante. 
            En <strong>Sexo</strong> escriba M (Masculino) o F (Femenino). 
            En <strong>Categoría</strong> use los códigos de la leyenda.
            En <strong>Invitado por</strong> escriba el nombre del miembro que trajo al visitante.
        </div>
        
        <!-- Leyenda de categorías -->
        <div class="leyenda">
            <strong>Categorías:</strong>
            <div class="leyenda-item">
                <span class="leyenda-code">D</span> Damas (mujeres adultas)
            </div>
            <div class="leyenda-item">
                <span class="leyenda-code">C</span> Caballeros (hombres adultos)
            </div>
            <div class="leyenda-item">
                <span class="leyenda-code">J</span> Jóvenes (18-30 años)
            </div>
            <div class="leyenda-item">
                <span class="leyenda-code">JT</span> Jovencitos (12-17 años)
            </div>
            <div class="leyenda-item">
                <span class="leyenda-code">N</span> Niños (menores de 12)
            </div>
        </div>
        
        <!-- Tabla de registro -->
        <table>
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-nombre">Nombre</th>
                    <th class="col-apellido">Apellido</th>
                    <th class="col-sexo">Sexo</th>
                    <th class="col-telefono">Teléfono</th>
                    <th class="col-categoria">Cat.</th>
                    <th class="col-invitado">Invitado Por</th>
                    <th class="col-obs">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 1; $i <= $num_filas; $i++): ?>
                <tr>
                    <td class="col-num"><?php echo $i; ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        
        <!-- Sección de firmas -->
        <div class="firma-section">
            <div class="firma-box">
                <div class="firma-linea">Recibido por (Secretaria)</div>
            </div>
            <div class="firma-box">
                <div class="firma-linea">Fecha de Entrega</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <span>Sistema Concilio - Registro de Visitas</span>
            <span>Impreso el: <?php echo $fecha_hoy; ?></span>
        </div>
    </div>
    
    <script>
        function cambiarFilas(num) {
            window.location.href = 'formulario_imprimible.php?filas=' + num;
        }
    </script>
</body>
</html>
