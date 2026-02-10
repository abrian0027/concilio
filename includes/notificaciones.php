<?php
/**
 * Sistema de Notificaciones por Correo
 * Concilio IML
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Envía un correo electrónico usando PHPMailer
 */
function enviarCorreo($destinatario, $asunto, $cuerpoHtml, $cuerpoTexto = '') {
    if (!defined('MAIL_ENABLED') || !MAIL_ENABLED) {
        error_log("Correo deshabilitado. Destinatario: $destinatario, Asunto: $asunto");
        return ['success' => false, 'message' => 'Envío de correo deshabilitado'];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        
        // Destinatario
        if (is_array($destinatario)) {
            foreach ($destinatario as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($destinatario);
        }
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $cuerpoTexto ?: strip_tags($cuerpoHtml);
        
        $mail->send();
        return ['success' => true, 'message' => 'Correo enviado correctamente'];
        
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * Obtiene el email del pastor de una iglesia
 */
function obtenerEmailPastor($conexion, $iglesia_id) {
    $sql = "SELECT u.email, u.nombre, u.apellido 
            FROM usuarios u 
            INNER JOIN roles r ON r.id = u.rol_id 
            WHERE u.iglesia_id = ? 
            AND LOWER(r.nombre) = 'pastor' 
            AND u.activo = 1 
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $iglesia_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Notifica al pastor sobre una nueva solicitud de membresía
 */
function notificarNuevaSolicitud($conexion, $solicitud_id, $iglesia_id) {
    // Obtener datos del pastor
    $pastor = obtenerEmailPastor($conexion, $iglesia_id);
    
    if (!$pastor || empty($pastor['email'])) {
        error_log("No se encontró pastor con email para iglesia ID: $iglesia_id");
        return ['success' => false, 'message' => 'Pastor sin email configurado'];
    }
    
    // Obtener datos de la solicitud
    $stmt = $conexion->prepare("
        SELECT s.*, i.nombre AS iglesia_nombre, i.codigo AS iglesia_codigo
        FROM solicitudes_membresia s
        INNER JOIN iglesias i ON i.id = s.iglesia_id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$solicitud) {
        return ['success' => false, 'message' => 'Solicitud no encontrada'];
    }
    
    // Construir el correo
    $nombreCompleto = $solicitud['nombre'] . ' ' . $solicitud['apellido'];
    $urlPanel = SITE_URL . '/admin/recepcion/ver.php?id=' . $solicitud_id;
    
    $asunto = "📋 Nueva Solicitud de Membresía - " . $nombreCompleto;
    
    $cuerpoHtml = generarPlantillaCorreo([
        'titulo' => 'Nueva Solicitud de Membresía',
        'pastor_nombre' => $pastor['nombre'],
        'contenido' => "
            <p>Se ha recibido una nueva solicitud de membresía en <strong>{$solicitud['iglesia_nombre']}</strong>.</p>
            
            <div style='background: #f8fafc; border-radius: 10px; padding: 20px; margin: 20px 0;'>
                <h3 style='margin: 0 0 15px 0; color: #1f2937;'>Datos del Solicitante</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280;'>Nombre:</td>
                        <td style='padding: 8px 0; font-weight: 600;'>{$nombreCompleto}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280;'>Documento:</td>
                        <td style='padding: 8px 0;'>{$solicitud['numero_documento']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280;'>Teléfono:</td>
                        <td style='padding: 8px 0;'>{$solicitud['telefono']}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #6b7280;'>Fecha:</td>
                        <td style='padding: 8px 0;'>" . date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) . "</td>
                    </tr>
                </table>
            </div>
        ",
        'boton_texto' => 'Ver Solicitud Completa',
        'boton_url' => $urlPanel,
        'pie' => 'Puedes aprobar o rechazar esta solicitud desde el panel de administración.'
    ]);
    
    return enviarCorreo($pastor['email'], $asunto, $cuerpoHtml);
}

/**
 * Notifica al solicitante que su solicitud fue aprobada
 */
function notificarSolicitudAprobada($conexion, $solicitud_id) {
    $stmt = $conexion->prepare("
        SELECT s.*, i.nombre AS iglesia_nombre
        FROM solicitudes_membresia s
        INNER JOIN iglesias i ON i.id = s.iglesia_id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$solicitud || empty($solicitud['email'])) {
        return ['success' => false, 'message' => 'Solicitante sin email'];
    }
    
    $asunto = "✅ ¡Bienvenido! Tu solicitud de membresía fue aprobada";
    
    $cuerpoHtml = generarPlantillaCorreo([
        'titulo' => '¡Solicitud Aprobada!',
        'pastor_nombre' => $solicitud['nombre'],
        'contenido' => "
            <div style='text-align: center; margin-bottom: 25px;'>
                <div style='width: 80px; height: 80px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;'>
                    <span style='font-size: 40px;'>✓</span>
                </div>
            </div>
            
            <p>Nos complace informarte que tu solicitud de membresía en <strong>{$solicitud['iglesia_nombre']}</strong> ha sido <strong style='color: #059669;'>APROBADA</strong>.</p>
            
            <p>Ahora eres parte oficial de nuestra familia eclesiástica. Te invitamos a participar activamente en las actividades de la iglesia.</p>
            
            <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0; color: #166534;'>
                    <strong>¡Bienvenido(a) a la familia!</strong><br>
                    Esperamos verte pronto en nuestros servicios.
                </p>
            </div>
        ",
        'pie' => 'Si tienes alguna pregunta, no dudes en contactar a tu pastor.'
    ]);
    
    return enviarCorreo($solicitud['email'], $asunto, $cuerpoHtml);
}

/**
 * Notifica al solicitante que su solicitud fue rechazada
 */
function notificarSolicitudRechazada($conexion, $solicitud_id, $motivo = '') {
    $stmt = $conexion->prepare("
        SELECT s.*, i.nombre AS iglesia_nombre
        FROM solicitudes_membresia s
        INNER JOIN iglesias i ON i.id = s.iglesia_id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $solicitud = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$solicitud || empty($solicitud['email'])) {
        return ['success' => false, 'message' => 'Solicitante sin email'];
    }
    
    $motivoHtml = $motivo 
        ? "<div style='background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 15px; margin: 20px 0;'>
               <p style='margin: 0; color: #991b1b;'><strong>Motivo:</strong> " . htmlspecialchars($motivo) . "</p>
           </div>"
        : "";
    
    $asunto = "Información sobre tu solicitud de membresía";
    
    $cuerpoHtml = generarPlantillaCorreo([
        'titulo' => 'Solicitud No Aprobada',
        'pastor_nombre' => $solicitud['nombre'],
        'contenido' => "
            <p>Lamentamos informarte que tu solicitud de membresía en <strong>{$solicitud['iglesia_nombre']}</strong> no ha sido aprobada en este momento.</p>
            
            {$motivoHtml}
            
            <p>Te invitamos a comunicarte con el pastor de la iglesia para más información o para aclarar cualquier situación.</p>
        ",
        'pie' => 'Gracias por tu comprensión.'
    ]);
    
    return enviarCorreo($solicitud['email'], $asunto, $cuerpoHtml);
}

/**
 * Genera la plantilla HTML del correo
 */
function generarPlantillaCorreo($datos) {
    $titulo = $datos['titulo'] ?? 'Notificación';
    $nombre = $datos['pastor_nombre'] ?? '';
    $contenido = $datos['contenido'] ?? '';
    $botonTexto = $datos['boton_texto'] ?? '';
    $botonUrl = $datos['boton_url'] ?? '';
    $pie = $datos['pie'] ?? '';
    
    $botonHtml = $botonUrl ? "
        <div style='text-align: center; margin: 25px 0;'>
            <a href='{$botonUrl}' style='display: inline-block; background: linear-gradient(135deg, #0891b2, #0dcaf0); color: white; text-decoration: none; padding: 14px 30px; border-radius: 10px; font-weight: 600; font-size: 16px;'>
                {$botonTexto}
            </a>
        </div>
    " : "";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; background-color: #f3f4f6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; border-radius: 16px 16px 0 0; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>Sistema Concilio IML</h1>
            </div>
            
            <!-- Content -->
            <div style='background: white; padding: 30px; border-radius: 0 0 16px 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>
                <h2 style='color: #1f2937; margin: 0 0 20px 0; font-size: 22px;'>{$titulo}</h2>
                
                " . ($nombre ? "<p style='color: #374151;'>Hola <strong>{$nombre}</strong>,</p>" : "") . "
                
                <div style='color: #374151; line-height: 1.6;'>
                    {$contenido}
                </div>
                
                {$botonHtml}
                
                " . ($pie ? "<p style='color: #6b7280; font-size: 14px; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>{$pie}</p>" : "") . "
            </div>
            
            <!-- Footer -->
            <div style='text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;'>
                <p style='margin: 0;'>Iglesia Metodista Libre de República Dominicana</p>
                <p style='margin: 5px 0 0 0;'>Este es un mensaje automático, por favor no responda a este correo.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
