<?php 
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../admin/includes/auditoria.php';

$mensaje_error = "";
$mostrar_modal_cedula = false;
$codigo_iglesia_temp = "";
$usuarios_disponibles = [];
$nombre_iglesia_temp = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si es el formulario del modal (cedula)
    if (isset($_POST['modal_cedula']) && $_POST['modal_cedula'] == '1') {
        $codigo_iglesia_temp = $_POST['codigo_iglesia_temp'] ?? '';
        $cedula = trim($_POST['cedula'] ?? '');
        $clave = $_POST['clave_temp'] ?? '';
        
        if ($cedula === "") {
            $mensaje_error = "Debe ingresar su número de cédula.";
            $mostrar_modal_cedula = true;
        } else {
            // Buscar el usuario específico por cédula e iglesia
            $sqlUsuarioCedula = "
                SELECT u.id, u.nombre, u.apellido, u.usuario, u.clave,
                       u.rol_id, u.conferencia_id, u.distrito_id, u.iglesia_id,
                       r.nombre AS rol_nombre,
                       m.numero_documento, m.id AS miembro_id,
                       i.nombre AS nombre_iglesia
                FROM iglesias i
                INNER JOIN miembros m ON i.id = m.iglesia_id AND m.numero_documento = ? AND m.estado = 'activo'
                INNER JOIN usuarios u ON i.id = u.iglesia_id 
                    AND u.activo = 1
                    AND (
                        LOWER(SUBSTRING_INDEX(u.nombre, ' ', 1)) = LOWER(SUBSTRING_INDEX(m.nombre, ' ', 1))
                        OR u.usuario LIKE CONCAT('%', LOWER(SUBSTRING_INDEX(m.nombre, ' ', 1)), '%')
                    )
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE i.codigo = ?
                LIMIT 1
            ";
            
            $stmt = $conexion->prepare($sqlUsuarioCedula);
            $stmt->bind_param("ss", $cedula, $codigo_iglesia_temp);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                if (password_verify($clave, $row['clave'])) {
                    // Login exitoso
                    // Regenerar id de sesión para prevenir fijación de sesión
                    session_regenerate_id(true);
                    $_SESSION['usuario_id']      = $row['id'];
                    $_SESSION['usuario_nombre']  = $row['nombre'] . " " . $row['apellido'];
                    $_SESSION['usuario_login']   = $row['usuario'];
                    $_SESSION['rol_id']          = $row['rol_id'];
                    $_SESSION['rol_nombre']      = $row['rol_nombre'];
                    $_SESSION['conferencia_id']  = $row['conferencia_id'];
                    $_SESSION['distrito_id']     = $row['distrito_id'];
                    $_SESSION['iglesia_id']      = $row['iglesia_id'];
                    $_SESSION['miembro_id']      = $row['miembro_id'];
                    $_SESSION['cedula']          = $row['numero_documento'];
                    $_SESSION['login_por_cedula'] = true;

                    // Registrar auditoría de login exitoso
                    auditoria_login($row['id'], $row['nombre'] . ' ' . $row['apellido']);

                    // Redirección al dashboard unificado
                    header("Location: ../admin/dashboard.php");
                    exit;
                } else {
                    // Registrar intento fallido
                    auditoria_login_fallido($cedula);
                    $mensaje_error = "Contraseña incorrecta para esta cédula.";
                    $mostrar_modal_cedula = true;
                }
            } else {
                $mensaje_error = "Cédula no encontrada en esta iglesia.";
                $mostrar_modal_cedula = true;
            }
            $stmt->close();
        }
    } 
    // Formulario principal de login
    else {
        $entrada = trim($_POST['usuario'] ?? '');
        $clave   = trim($_POST['clave'] ?? '');

        if ($entrada === "" || $clave === "") {
            $mensaje_error = "Debe escribir usuario/código y contraseña.";
        } else {
            $usuarioEncontrado = null;
            $esCodigoIglesia = false;

            // Verificar si es código de iglesia
            $sqlIglesia = "SELECT id, codigo, nombre FROM iglesias WHERE codigo = ? AND activo = 1 LIMIT 1";
            $stmtIgl = $conexion->prepare($sqlIglesia);
            $stmtIgl->bind_param("s", $entrada);
            $stmtIgl->execute();
            $resIgl = $stmtIgl->get_result();

            if ($resIgl && $resIgl->num_rows === 1) {
                $esCodigoIglesia = true;
                $iglesia = $resIgl->fetch_assoc();
                $nombre_iglesia_temp = $iglesia['nombre'];
                
                // Verificar si hay usuarios en esta iglesia
                $sqlUsuariosCount = "SELECT COUNT(*) as total FROM usuarios WHERE iglesia_id = ? AND activo = 1";
                $stmtCount = $conexion->prepare($sqlUsuariosCount);
                $stmtCount->bind_param("i", $iglesia['id']);
                $stmtCount->execute();
                $resCount = $stmtCount->get_result();
                $countRow = $resCount->fetch_assoc();
                
                if ($countRow['total'] > 0) {
                    // Mostrar modal para pedir cédula
                    $mostrar_modal_cedula = true;
                    $codigo_iglesia_temp = $entrada;
                    
                    // Buscar usuarios disponibles para mostrar en el modal
                    $sqlUsuarios = "
                        SELECT u.id, u.nombre, u.apellido, u.rol_id, r.nombre as rol_nombre
                        FROM usuarios u
                        INNER JOIN roles r ON r.id = u.rol_id
                        WHERE u.iglesia_id = ? AND u.activo = 1
                        ORDER BY 
                            CASE r.nombre
                                WHEN 'pastor' THEN 1
                                WHEN 'tesorero' THEN 2
                                WHEN 'secretaria' THEN 3
                                ELSE 4
                            END
                    ";
                    $stmtUsers = $conexion->prepare($sqlUsuarios);
                    $stmtUsers->bind_param("i", $iglesia['id']);
                    $stmtUsers->execute();
                    $resUsers = $stmtUsers->get_result();
                    
                    while ($user = $resUsers->fetch_assoc()) {
                        $usuarios_disponibles[] = $user;
                    }
                    $stmtUsers->close();
                } else {
                    $mensaje_error = "No hay usuarios registrados en esta iglesia.";
                }
                $stmtCount->close();
            } else {
                // No es código de iglesia, intentar como usuario normal
                $sqlUsuario = "
                    SELECT u.id, u.nombre, u.apellido, u.usuario, u.clave,
                           u.rol_id, u.conferencia_id, u.distrito_id, u.iglesia_id,
                           r.nombre AS rol_nombre
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    WHERE u.usuario = ?
                      AND u.activo = 1
                    LIMIT 1
                ";
                $stmtUsr = $conexion->prepare($sqlUsuario);
                $stmtUsr->bind_param("s", $entrada);
                $stmtUsr->execute();
                $resUsr = $stmtUsr->get_result();

                if ($resUsr && $resUsr->num_rows === 1) {
                    $rowU = $resUsr->fetch_assoc();
                    if (password_verify($clave, $rowU['clave'])) {
                        $usuarioEncontrado = $rowU;
                    } else {
                        // Registrar intento fallido
                        auditoria_login_fallido($entrada);
                        $mensaje_error = "Contraseña incorrecta.";
                    }
                } else {
                    // Registrar intento con usuario no existente
                    auditoria_login_fallido($entrada);
                    $mensaje_error = "Usuario no encontrado.";
                }
                $stmtUsr->close();
            }
            $stmtIgl->close();

            // Si encontramos usuario por método tradicional
            if ($usuarioEncontrado && !$esCodigoIglesia) {
                // Regenerar id de sesión tras autenticación exitosa
                session_regenerate_id(true);
                $_SESSION['usuario_id']      = $usuarioEncontrado['id'];
                $_SESSION['usuario_nombre']  = $usuarioEncontrado['nombre'] . " " . $usuarioEncontrado['apellido'];
                $_SESSION['usuario_login']   = $usuarioEncontrado['usuario'];
                $_SESSION['rol_id']          = $usuarioEncontrado['rol_id'];
                $_SESSION['rol_nombre']      = $usuarioEncontrado['rol_nombre'];
                $_SESSION['conferencia_id']  = $usuarioEncontrado['conferencia_id'];
                $_SESSION['distrito_id']     = $usuarioEncontrado['distrito_id'];
                $_SESSION['iglesia_id']      = $usuarioEncontrado['iglesia_id'];

                // Registrar auditoría de login exitoso
                auditoria_login($usuarioEncontrado['id'], $usuarioEncontrado['nombre'] . ' ' . $usuarioEncontrado['apellido']);

                // Redirección al dashboard unificado
                header("Location: ../admin/dashboard.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema Concilio - Inicio de sesión</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
    <link rel="shortcut icon" href="../assets/img/favicon.ico">
    
    <link rel="stylesheet" href="../css/theme.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ======= ESTILOS GENERALES DEL LOGIN ======= */
        :root {
            --primary-color: #1a56db;
            --secondary-color: #0e3fa9;
            --light-blue: #e8f1ff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 10px 25px rgba(0, 0, 0, 0.12);
            --gradient-blue: linear-gradient(135deg, #1a56db 0%, #0e3fa9 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body.login-body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            line-height: 1.5;
        }

        .login-page {
            width: 100%;
            padding: 1.5rem;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            display: flex;
            min-height: 700px;
        }

        /* COLUMNA IZQUIERDA: IMAGEN / PRESENTACIÓN (solo en pantallas grandes) */
        .login-image-side {
            position: relative;
            flex: 1.2;
            display: none;
            background-image: url("../assets/img/login-fondo.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* OVERLAY MUCHO MÁS TRANSPARENTE */
        .login-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to bottom right,
                rgba(26, 86, 219, 0.65),
                rgba(14, 63, 169, 0.55)
            );
            color: #ffffff;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            backdrop-filter: blur(1px);
        }

        .login-image-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .login-logo {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-logo img {
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
        }

        .login-image-title {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-image-subtitle {
            font-size: 0.95rem;
            opacity: 0.95;
            margin-top: 0.25rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .login-image-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 450px;
        }

        .login-image-body h2 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-image-body p {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .login-image-footer {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* COLUMNA DERECHA: FORMULARIO */
        .login-form-side {
            flex: 1;
            padding: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        /* Logo en la parte superior del formulario - SIN CÍRCULO */
        .login-form-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        /* LOGO MÁS GRANDE Y SIN FORMA CIRCULAR */
        .login-form-logo img {
            width: 120px;
            height: auto;
            max-height: 120px;
            object-fit: contain;
            border: none;
            box-shadow: none;
            border-radius: 8px;
        }

        .login-card-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-card-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .login-card-header p {
            font-size: 0.95rem;
            color: var(--text-light);
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            padding: 0.9rem 1.2rem;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.15);
        }

        .btn-primary {
            width: 100%;
            background: var(--gradient-blue);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(26, 86, 219, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Mensaje de error */
        .alert-login {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            border-left: 4px solid var(--danger-color);
            background-color: #fef2f2;
            color: #991b1b;
        }

        .alert-login i {
            margin-right: 8px;
        }

        /* Área de ayuda/información */
        .login-help-area {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .help-section-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            text-align: center;
        }

        .help-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .help-btn {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--text-dark);
            flex: 1;
            min-width: 140px;
            justify-content: center;
        }

        .help-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
            text-decoration: none;
        }

        .help-btn.whatsapp {
            background: #25d366;
            color: white;
            border-color: #25d366;
        }

        .help-btn.info {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .help-btn i {
            font-size: 1.1rem;
        }

        .help-text {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-light);
            text-align: center;
            line-height: 1.5;
        }

        .info-cedula {
            display: inline-block;
            margin-top: 5px;
            padding: 3px 8px;
            background-color: #e8f1ff;
            border-radius: 4px;
            font-size: 0.75rem;
            color: var(--primary-color);
            border: 1px solid #d0dffd;
        }

        /* MODAL DE INFORMACIÓN */
        .info-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
            backdrop-filter: blur(4px);
        }

        .info-modal-backdrop.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .info-modal {
            background: #ffffff;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            overflow-y: auto;
        }

        .info-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .info-modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .info-modal-close {
            border: none;
            background: transparent;
            font-size: 1.8rem;
            cursor: pointer;
            line-height: 1;
            color: var(--text-light);
            transition: color 0.2s ease;
        }

        .info-modal-close:hover {
            color: var(--text-dark);
        }

        .info-modal-body {
            font-size: 0.95rem;
            color: var(--text-dark);
        }

        .info-modal-body p {
            margin-bottom: 1rem;
        }

        .info-modal-body ul {
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-modal-body li {
            margin-bottom: 0.5rem;
        }

        /* MODAL DE CÉDULA */
        .cedula-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            padding: 1rem;
            backdrop-filter: blur(6px);
        }

        .cedula-modal-backdrop.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .cedula-modal {
            background: #ffffff;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .cedula-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, #1a56db 0%, #0e3fa9 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .cedula-modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .cedula-modal-close {
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1.8rem;
            cursor: pointer;
            line-height: 1;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .cedula-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .cedula-modal-body {
            padding: 2rem;
        }

        .cedula-instructions {
            margin-bottom: 2rem;
            padding: 1.25rem;
            background: #f0f7ff;
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .cedula-instructions p {
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .alert-cedula {
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            border-left: 4px solid var(--danger-color);
            background-color: #fef2f2;
            color: #991b1b;
        }

        .alert-cedula i {
            margin-right: 8px;
        }

        .usuarios-disponibles {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .usuarios-lista {
            margin-top: 0.75rem;
        }

        .usuario-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .usuario-item:last-child {
            border-bottom: none;
        }

        .usuario-item i {
            color: var(--primary-color);
            width: 20px;
        }

        .usuario-nombre {
            font-weight: 500;
            color: var(--text-dark);
        }

        .usuario-rol {
            font-size: 0.85rem;
            color: var(--text-light);
            background: #f0f0f0;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            margin-left: auto;
        }

        .cedula-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .cedula-actions .btn {
            flex: 1;
            padding: 0.9rem;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-secondary {
            background: #6b7280;
            border: none;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        /* Animación de bienvenida */
        .welcome-message {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(26, 86, 219, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            color: white;
            flex-direction: column;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        .welcome-message.show {
            display: flex;
        }

        .welcome-content {
            max-width: 500px;
            padding: 2rem;
        }

        .welcome-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: bounce 1s ease infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .welcome-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .welcome-name {
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            margin: 1rem 0;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-role {
            font-size: 1.1rem;
            color: #a5b4fc;
            margin-bottom: 2rem;
        }

        .welcome-loading {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
            opacity: 0.8;
        }

        .welcome-loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* RESPONSIVE */
        @media (min-width: 992px) {
            .login-image-side {
                display: flex;
            }
            
            .login-page {
                padding: 2rem;
            }
            
            .login-help-area {
                flex-direction: row;
                align-items: flex-start;
                justify-content: space-between;
            }
            
            .help-buttons {
                flex-direction: row;
                justify-content: center;
                width: 100%;
            }
        }

        @media (max-width: 991.98px) {
            .login-container {
                max-width: 600px;
                min-height: auto;
            }
            
            .login-form-side {
                padding: 2.5rem 2rem;
            }
            
            .login-card-header h1 {
                font-size: 1.6rem;
            }
            
            .login-form-logo img {
                width: 100px;
                max-height: 100px;
            }
        }

        @media (max-width: 767.98px) {
            .login-page {
                padding: 1rem;
            }
            
            .login-container {
                border-radius: 16px;
            }
            
            .login-form-side {
                padding: 2rem 1.5rem;
            }
            
            .login-form-logo img {
                width: 90px;
                max-height: 90px;
            }
            
            .login-card-header h1 {
                font-size: 1.5rem;
            }
            
            .form-control {
                padding: 0.8rem 1rem;
            }
            
            .btn-primary {
                padding: 0.9rem;
            }
            
            .cedula-modal {
                margin: 1rem;
            }
            
            .cedula-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 575.98px) {
            body.login-body {
                background: #ffffff;
            }
            
            .login-page {
                padding: 0;
            }
            
            .login-container {
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
            }
            
            .login-form-side {
                padding: 2rem 1.25rem 3rem;
            }
            
            .login-form-logo {
                margin-bottom: 1.2rem;
            }
            
            .login-form-logo img {
                width: 80px;
                max-height: 80px;
            }
            
            .login-card-header {
                margin-bottom: 1.5rem;
            }
            
            .login-card-header h1 {
                font-size: 1.4rem;
            }
            
            .form-group {
                margin-bottom: 1.5rem;
            }
            
            .help-buttons {
                flex-direction: column;
            }
            
            .help-btn {
                min-width: 100%;
            }
            
            .info-modal, .cedula-modal {
                padding: 1.5rem;
                margin: 0.5rem;
            }
            
            .cedula-modal-header {
                padding: 1.25rem 1.5rem;
            }
            
            .cedula-modal-body {
                padding: 1.5rem;
            }
        }

        @media (min-width: 1600px) {
            .login-container {
                max-width: 1400px;
            }
            
            .login-form-side {
                padding: 4rem;
            }
            
            .login-card {
                max-width: 500px;
            }
            
            .login-form-logo img {
                width: 140px;
                max-height: 140px;
            }
            
            .login-card-header h1 {
                font-size: 2.2rem;
            }
            
            .login-card-header p {
                font-size: 1.1rem;
            }
            
            .form-control {
                padding: 1.1rem 1.4rem;
                font-size: 1.1rem;
            }
            
            .btn-primary {
                padding: 1.2rem;
                font-size: 1.1rem;
            }
            
            .help-btn {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="login-body">

<div class="login-page">
    <div class="login-container">

        <!-- COLUMNA IZQUIERDA: IMAGEN (solo en pantallas grandes) -->
        <div class="login-image-side">
            <div class="login-image-overlay">
                <div class="login-image-header">
                    <div class="login-logo">
                        <!-- Logo del Concilio 
                        <img src="../assets/img/logo-concilio.png" alt="Logo Concilio">
                        -->
                    </div>
                    <div>
                        <div class="login-image-title">
                            Sistema de Gestión del Concilio
                        </div>
                        <div class="login-image-subtitle">
                            Iglesia Metodista Libre República Dominicana
                        </div>
                    </div>
                </div>

                <div class="login-image-body">
                    <h2>Bienvenido(a)</h2>
                    <p>
                        Acceda con las credenciales asignadas por la administración
                        del Concilio para gestionar su iglesia, distrito o conferencia.
                        Este sistema le permitirá administrar miembros, finanzas,
                        eventos y reportes de manera eficiente.
                    </p>
                    <p style="margin-top: 15px; background: rgba(255,255,255,0.1); padding: 10px; border-radius: 8px;">
                        <strong>Protección de identidad:</strong> Al usar el código de su iglesia,
                        deberá identificarse con su cédula para garantizar que acceda con su rol correcto.
                    </p>
                </div>

                <div class="login-image-footer">
                    © <?php echo date('Y'); ?> Concilio Iglesia Metodista Libre. Rep.Dom. Todos los derechos reservados.
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: FORMULARIO -->
        <div class="login-form-side">
            <div class="login-card">

                <!-- Logo encima del formulario - SIN CÍRCULO -->
                <div class="login-form-logo">
                    <img src="../assets/img/logo-concilio.png" alt="Logo Iglesia">
                </div>

                <div class="login-card-header">
                    <h1>Inicio de sesión</h1>
                    <p>Sistema de Gestión del Concilio</p>
                </div>

                <?php if ($mensaje_error && !$mostrar_modal_cedula): ?>
                    <div class="alert alert-danger alert-login">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" autocomplete="off" id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="usuario">
                            <i class="fas fa-user"></i> Usuario / Código de Iglesia
                        </label>
                        <input
                            type="text"
                            name="usuario"
                            id="usuario"
                            class="form-control"
                            placeholder="Usuario o código de iglesia"
                            required
                            autofocus
                            value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                        >
                        <small style="display: block; margin-top: 5px; color: #6b7280; font-size: 0.8rem;">
                            Puede usar: 
                            <span class="info-cedula">Su nombre de usuario</span> • 
                            <span class="info-cedula">Código de iglesia</span>
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="clave">
                            <i class="fas fa-lock"></i> Contraseña
                        </label>
                        <input
                            type="password"
                            name="clave"
                            id="clave"
                            class="form-control"
                            placeholder="Ingrese su contraseña"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Entrar al sistema
                    </button>
                </form>

                <!-- Área de ayuda e información -->
                <div class="login-help-area">
                    <div class="help-section-title">
                        ¿Necesitas ayuda?
                    </div>
                    <div class="help-buttons">
                        <a 
                            href="https://wa.me/18093919509?text=Hola,%20necesito%20ayuda%20con%20el%20sistema%20del%20Concilio"
                            target="_blank"
                            rel="noopener"
                            class="help-btn whatsapp"
                        >
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        
                        <button 
                            type="button" 
                            id="btnInfo" 
                            class="help-btn info"
                        >
                            <i class="fas fa-info-circle"></i> Información
                        </button>
                    </div>
                    
                    <div class="help-text">
                        <p>
                            Si tienes problemas para iniciar sesión, contacta al administrador del sistema.
                            Usa navegadores actualizados como Chrome, Edge o Firefox.
                        </p>
                        <p>
                            <strong>Nota:</strong> Al usar el código de iglesia, se le pedirá su cédula
                            para verificar su identidad.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PARA INGRESAR CÉDULA (CUANDO SE USA CÓDIGO DE IGLESIA) -->
<div class="cedula-modal-backdrop <?php echo $mostrar_modal_cedula ? 'show' : ''; ?>" id="cedulaModal">
    <div class="cedula-modal">
        <div class="cedula-modal-header">
            <div class="cedula-modal-title">
                <i class="fas fa-id-card"></i> Verificación de identidad
            </div>
            <button type="button" class="cedula-modal-close" data-close-cedula>&times;</button>
        </div>
        <div class="cedula-modal-body">
            <?php if ($mensaje_error && $mostrar_modal_cedula): ?>
                <div class="alert alert-danger alert-cedula">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php" id="formCedula">
                <input type="hidden" name="modal_cedula" value="1">
                <input type="hidden" name="codigo_iglesia_temp" value="<?php echo htmlspecialchars($codigo_iglesia_temp); ?>">
                <input type="hidden" name="clave_temp" id="clave_temp" value="<?php echo isset($_POST['clave']) ? htmlspecialchars($_POST['clave']) : ''; ?>">
                
                <div class="cedula-instructions">
                    <p>Para ingresar a <strong><?php echo htmlspecialchars($nombre_iglesia_temp ?: $codigo_iglesia_temp); ?></strong>, 
                    debe identificarse con su número de cédula.</p>
                    
                    <?php if (!empty($usuarios_disponibles)): ?>
                    <div class="usuarios-disponibles">
                        <p><strong>Usuarios registrados en esta iglesia:</strong></p>
                        <div class="usuarios-lista">
                            <?php foreach ($usuarios_disponibles as $usuario): ?>
                            <div class="usuario-item">
                                <i class="fas fa-user"></i>
                                <span class="usuario-nombre"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></span>
                                <span class="usuario-rol">(<?php echo htmlspecialchars($usuario['rol_nombre']); ?>)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="cedula">
                        <i class="fas fa-address-card"></i> Número de Cédula
                    </label>
                    <input
                        type="text"
                        name="cedula"
                        id="cedula"
                        class="form-control"
                        placeholder="Ej: 001-0046778-5"
                        required
                        autofocus
                        pattern="[0-9-]+"
                        title="Ingrese su número de cédula con guiones"
                    >
                    <small style="display: block; margin-top: 5px; color: #6b7280; font-size: 0.8rem;">
                        Ingrese su cédula tal como aparece en el sistema
                    </small>
                </div>
                
                <div class="cedula-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle"></i> Verificar y continuar
                    </button>
                    <button type="button" class="btn btn-secondary" data-close-cedula>
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DE INFORMACIÓN / INSTRUCCIONES -->
<div class="info-modal-backdrop" id="infoModal">
    <div class="info-modal">
        <div class="info-modal-header">
            <div class="info-modal-title">Información para usuarios</div>
            <button type="button" class="info-modal-close" data-close>&times;</button>
        </div>
        <div class="info-modal-body">
            <p>
                Bienvenido al Sistema de Gestión del Concilio Iglesia Metodista Libre.
                Sigue estas instrucciones para el uso correcto de la plataforma:
            </p>
            <ul>
                <li><strong>Formas de acceso:</strong>
                    <ol>
                        <li><strong>Con su nombre de usuario:</strong> Método tradicional</li>
                        <li><strong>Con el código de su iglesia:</strong> Luego deberá ingresar su cédula</li>
                    </ol>
                </li>
                <li><strong>¿Por qué pedimos la cédula?</strong> Para garantizar que cada persona 
                    acceda con su rol correcto, evitando confusiones cuando hay múltiples usuarios 
                    en la misma iglesia.</li>
                <li>Si olvidó sus credenciales, contacte al administrador de su conferencia o distrito.</li>
                <li>Cada usuario tiene permisos específicos según su rol (pastor, secretaria, tesorero, etc.).</li>
                <li>Use un navegador actualizado (Chrome, Edge, Firefox, Safari) para mejor experiencia.</li>
                <li>Mantenga su contraseña segura y no la comparta con otras personas.</li>
                <li>Para reportar problemas técnicos, use el botón de WhatsApp o contacte al soporte.</li>
            </ul>
            <p>
                <strong>Nota:</strong> Este sistema está diseñado para administrar iglesias, 
                distritos y conferencias de manera eficiente. Asegúrese de actualizar 
                la información regularmente.
            </p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elementos del modal de información
        var btnInfo = document.getElementById('btnInfo');
        var infoModal = document.getElementById('infoModal');
        var closeButtons = infoModal.querySelectorAll('[data-close]');
        
        // Elementos del modal de cédula
        var cedulaModal = document.getElementById('cedulaModal');
        var closeCedulaButtons = document.querySelectorAll('[data-close-cedula]');
        var claveInput = document.getElementById('clave');
        var claveTempInput = document.getElementById('clave_temp');
        
        // ========== MODAL DE INFORMACIÓN ==========
        if (btnInfo && infoModal) {
            // Abrir modal
            btnInfo.addEventListener('click', function () {
                infoModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });

            // Cerrar modal con botones de cerrar
            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    infoModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                });
            });

            // Cerrar modal haciendo clic fuera
            infoModal.addEventListener('click', function (e) {
                if (e.target === infoModal) {
                    infoModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
            });

            // Cerrar modal con tecla ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && infoModal.classList.contains('show')) {
                    infoModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
            });
        }
        
        // ========== MODAL DE CÉDULA ==========
        if (cedulaModal) {
            // Cerrar modal de cédula
            closeCedulaButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    cedulaModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    // Volver al formulario principal
                    document.getElementById('loginForm').reset();
                });
            });
            
            // Cerrar modal haciendo clic fuera
            cedulaModal.addEventListener('click', function (e) {
                if (e.target === cedulaModal) {
                    cedulaModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    document.getElementById('loginForm').reset();
                }
            });
            
            // Cerrar con ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && cedulaModal.classList.contains('show')) {
                    cedulaModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                    document.getElementById('loginForm').reset();
                }
            });
            
            // Enfocar campo de cédula cuando se abre
            cedulaModal.addEventListener('transitionend', function() {
                if (cedulaModal.classList.contains('show')) {
                    var cedulaInput = document.getElementById('cedula');
                    if (cedulaInput) {
                        setTimeout(function() {
                            cedulaInput.focus();
                        }, 100);
                    }
                }
            });
        }
        
        // ========== VALIDACIÓN DE CÉDULA EN TIEMPO REAL ==========
        var cedulaInput = document.getElementById('cedula');
        if (cedulaInput) {
            cedulaInput.addEventListener('input', function() {
                var valor = this.value.replace(/[^\d-]/g, '');
                this.value = valor;
                
                // Resaltar si parece ser una cédula válida
                if (valor.length >= 9 && valor.includes('-')) {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.15)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
        }
        
        // ========== GUARDAR CONTRASEÑA PARA MODAL ==========
        if (claveInput && claveTempInput) {
            // Guardar contraseña cuando se envía el formulario principal
            document.getElementById('loginForm').addEventListener('submit', function() {
                claveTempInput.value = claveInput.value;
            });
        }
        
        // ========== ENFOCAR CAMPO DE USUARIO AL CARGAR ==========
        var usuarioInput = document.getElementById('usuario');
        if (usuarioInput && !<?php echo $mostrar_modal_cedula ? 'true' : 'false'; ?>) {
            usuarioInput.focus();
        }
        
        // ========== DETECTAR SI ES CÓDIGO DE IGLESIA ==========
        if (usuarioInput) {
            usuarioInput.addEventListener('blur', function() {
                var valor = this.value.trim().toUpperCase();
                // Patrón para códigos de iglesia (ej: IMLC-101, IML-201)
                var esCodigoIglesia = /^[A-Z]{2,4}-?\d{3,}$/.test(valor);
                
                if (esCodigoIglesia) {
                    this.style.borderColor = '#1a56db';
                    this.style.boxShadow = '0 0 0 3px rgba(26, 86, 219, 0.15)';
                }
            });
        }
        
        // ========== MOSTRAR MODAL DE CÉDULA SI PHP LO INDICA ==========
        <?php if ($mostrar_modal_cedula): ?>
            setTimeout(function() {
                if (cedulaModal) {
                    cedulaModal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                }
            }, 300);
        <?php endif; ?>
    });
    
    // Función para mostrar mensaje de bienvenida
    function mostrarBienvenida(nombre, rol, iglesia) {
        var welcomeHTML = `
            <div class="welcome-message show">
                <div class="welcome-content">
                    <div class="welcome-icon">
                        <i class="fas fa-church"></i>
                    </div>
                    <h1 class="welcome-title">¡Bienvenido!</h1>
                    <p class="welcome-subtitle">${iglesia}</p>
                    <div class="welcome-name">${nombre}</div>
                    <p class="welcome-role">Rol: ${rol}</p>
                    <div class="welcome-loading">
                        <i class="fas fa-spinner"></i>
                        <span>Ingresando al sistema...</span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', welcomeHTML);
    }
</script>

</body>
</html>