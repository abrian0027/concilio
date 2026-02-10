<?php
/**
 * Página de Error - Formulario de Solicitud
 */

// Determinar tipo de error
$tipo = $_GET['tipo'] ?? 'general';
$codigo = $_GET['codigo'] ?? '';
$iglesia_nombre = $_GET['iglesia'] ?? '';

switch ($tipo) {
    case 'sin_codigo':
        $error_titulo = 'Código de Iglesia Requerido';
        $error_mensaje = 'No se ha proporcionado un código de iglesia válido. Por favor, utiliza el enlace que te proporcionó tu iglesia.';
        $error_icon = 'bi-qr-code';
        $error_color = '#f59e0b';
        break;
        
    case 'iglesia_no_encontrada':
        $error_titulo = 'Iglesia No Encontrada';
        $error_mensaje = 'No se encontró ninguna iglesia con el código "' . htmlspecialchars($codigo) . '". Verifica que el enlace sea correcto o contacta a tu pastor.';
        $error_icon = 'bi-search';
        $error_color = '#ef4444';
        break;
        
    case 'solicitudes_deshabilitadas':
        $error_titulo = 'Formulario No Disponible';
        $error_mensaje = 'El formulario de solicitud de membresía para "' . htmlspecialchars($iglesia_nombre) . '" no está disponible en este momento. Por favor, contacta directamente a tu pastor.';
        $error_icon = 'bi-pause-circle';
        $error_color = '#6b7280';
        break;
        
    default:
        $error_titulo = 'Error';
        $error_mensaje = 'Ha ocurrido un error inesperado. Por favor, intenta nuevamente.';
        $error_icon = 'bi-exclamation-triangle';
        $error_color = '#ef4444';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Error - Solicitud de Membresía</title>
    <link rel="icon" type="image/png" href="/concilio/assets/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0dcaf0;
            --primary-dark: #0891b2;
            --error-color: <?php echo $error_color; ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        
        .error-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(0,0,0,0.03) 0%, rgba(0,0,0,0.06) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: var(--error-color);
        }
        
        .error-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: max(16px, 1rem);
            min-height: 48px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(13, 202, 240, 0.35);
            color: white;
        }
        
        .btn:active {
            transform: scale(0.98);
        }
        
        @media (max-width: 480px) {
            .error-card {
                padding: 2rem 1.5rem;
            }
            
            .error-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="bi <?php echo $error_icon; ?>"></i>
        </div>
        <h1 class="error-title"><?php echo $error_titulo; ?></h1>
        <p class="error-message"><?php echo $error_mensaje; ?></p>
        <a href="/concilio/" class="btn">
            <i class="bi bi-house"></i> Ir al inicio
        </a>
    </div>
</body>
</html>
