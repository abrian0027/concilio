<?php
/**
 * Página de Prueba de Correo
 * Solo accesible para super_admin
 */

$page_title = "Prueba de Correo";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/notificaciones.php';

// Solo super_admin puede acceder
if ($ROL_NOMBRE !== 'super_admin') {
    echo "<div class='alert alert-danger'>No tienes permiso para acceder a esta página.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$debug_info = '';

// Procesar envío de prueba
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_destino'])) {
    $email_destino = trim($_POST['email_destino']);
    
    if (empty($email_destino) || !filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Por favor ingresa un correo válido';
        $tipo_mensaje = 'danger';
    } else {
        // Intentar enviar correo de prueba
        $asunto = "🧪 Prueba de Correo - Sistema Concilio IML";
        
        $cuerpoHtml = generarPlantillaCorreo([
            'titulo' => '¡Correo de Prueba Exitoso!',
            'pastor_nombre' => 'Administrador',
            'contenido' => "
                <div style='text-align: center; margin-bottom: 25px;'>
                    <div style='width: 80px; height: 80px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;'>
                        <span style='font-size: 40px;'>✓</span>
                    </div>
                </div>
                
                <p>Este es un correo de prueba enviado desde el <strong>Sistema Concilio IML</strong>.</p>
                
                <p>Si estás leyendo este mensaje, significa que la configuración de correo está funcionando correctamente.</p>
                
                <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #166534;'>
                        <strong>Detalles técnicos:</strong><br>
                        Servidor: " . MAIL_HOST . "<br>
                        Puerto: " . MAIL_PORT . "<br>
                        Fecha: " . date('d/m/Y H:i:s') . "
                    </p>
                </div>
            ",
            'pie' => 'Este correo fue enviado como prueba de configuración.'
        ]);
        
        $resultado = enviarCorreo($email_destino, $asunto, $cuerpoHtml);
        
        if ($resultado['success']) {
            $mensaje = "✅ Correo enviado exitosamente a <strong>$email_destino</strong>";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "❌ Error al enviar: " . htmlspecialchars($resultado['message']);
            $tipo_mensaje = 'danger';
        }
    }
}

// Información de configuración actual
$config_info = [
    'MAIL_ENABLED' => defined('MAIL_ENABLED') ? (MAIL_ENABLED ? 'Sí' : 'No') : 'No definido',
    'MAIL_HOST' => defined('MAIL_HOST') ? MAIL_HOST : 'No definido',
    'MAIL_PORT' => defined('MAIL_PORT') ? MAIL_PORT : 'No definido',
    'MAIL_USERNAME' => defined('MAIL_USERNAME') ? MAIL_USERNAME : 'No definido',
    'MAIL_ENCRYPTION' => defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'No definido',
    'MAIL_FROM_EMAIL' => defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'No definido',
];
?>

<div class="content-header">
    <h1><i class="fas fa-envelope"></i> Prueba de Correo</h1>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
        <?php echo $mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Configuración actual -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-cog"></i> Configuración Actual</span>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <?php foreach ($config_info as $key => $value): ?>
                    <tr>
                        <td><strong><?php echo $key; ?></strong></td>
                        <td>
                            <?php if ($key === 'MAIL_ENABLED'): ?>
                                <?php if ($value === 'Sí'): ?>
                                    <span class="badge bg-success">Habilitado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Deshabilitado</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <code><?php echo htmlspecialchars($value); ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php if (!MAIL_ENABLED): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-exclamation-triangle"></i> 
                    El envío de correos está <strong>deshabilitado</strong> en este entorno.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Formulario de prueba -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <span class="card-title"><i class="fas fa-paper-plane"></i> Enviar Correo de Prueba</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Correo destino:</label>
                        <input type="email" name="email_destino" class="form-control" 
                               placeholder="tucorreo@ejemplo.com" required
                               value="<?php echo htmlspecialchars($_POST['email_destino'] ?? ''); ?>">
                        <small class="text-muted">El correo de prueba se enviará a esta dirección</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" <?php echo !MAIL_ENABLED ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane"></i> Enviar Correo de Prueba
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Ayuda -->
        <div class="card mt-3">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-question-circle"></i> ¿Problemas?</span>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Verifica que el correo <code>notificaciones@...</code> exista en cPanel</li>
                    <li>Confirma que la contraseña sea correcta</li>
                    <li>Prueba con puerto <code>587</code> (TLS) si <code>465</code> (SSL) no funciona</li>
                    <li>Revisa los logs en <code>/logs/php_errors.log</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
