<?php
/**
 * Formulario Público de Solicitud de Membresía
 * URL: /solicitud/{codigo_iglesia}
 * Campos iguales al formulario de miembros del sistema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/notificaciones.php';

// Obtener código de iglesia desde URL amigable o parámetro GET
$codigo_iglesia = '';
if (isset($_GET['iglesia'])) {
    $codigo_iglesia = trim($_GET['iglesia']);
} elseif (isset($_SERVER['PATH_INFO'])) {
    $codigo_iglesia = trim($_SERVER['PATH_INFO'], '/');
}

if (empty($codigo_iglesia)) {
    header('Location: error.php?tipo=sin_codigo');
    exit;
}

// Buscar la iglesia por código
$stmt = $conexion->prepare("SELECT id, nombre, codigo, solicitudes_habilitadas FROM iglesias WHERE codigo = ? AND activo = 1");
$stmt->bind_param("s", $codigo_iglesia);
$stmt->execute();
$iglesia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$iglesia) {
    header('Location: error.php?tipo=iglesia_no_encontrada&codigo=' . urlencode($codigo_iglesia));
    exit;
}

if (!$iglesia['solicitudes_habilitadas']) {
    header('Location: error.php?tipo=solicitudes_deshabilitadas&iglesia=' . urlencode($iglesia['nombre']));
    exit;
}

$iglesia_id = $iglesia['id'];
$iglesia_nombre = $iglesia['nombre'];
$iglesia_codigo = $iglesia['codigo'];

// Obtener datos para selects (igual que en crear.php)
$nacionalidades = $conexion->query("SELECT * FROM nacionalidades WHERE activo = 1 ORDER BY id");
$niveles_estudio = $conexion->query("SELECT * FROM niveles_estudio WHERE activo = 1 ORDER BY id");
$carreras = $conexion->query("SELECT * FROM carreras WHERE activo = 1 ORDER BY nombre");
$ministerios = $conexion->query("SELECT * FROM ministerios WHERE activo = 1 AND id NOT IN (6, 7) ORDER BY nombre");

$mensaje = '';
$tipo_mensaje = '';
$datos = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Recopilar datos
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'sexo' => trim($_POST['sexo'] ?? ''),
        'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
        'nacionalidad_id' => !empty($_POST['nacionalidad_id']) ? intval($_POST['nacionalidad_id']) : null,
        'tipo_documento' => trim($_POST['tipo_documento'] ?? 'cedula'),
        'numero_documento' => trim($_POST['numero_documento'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'estado_civil' => trim($_POST['estado_civil'] ?? 'soltero'),
        'nivel_estudio_id' => !empty($_POST['nivel_estudio_id']) ? intval($_POST['nivel_estudio_id']) : null,
        'carrera_id' => !empty($_POST['carrera_id']) ? intval($_POST['carrera_id']) : null,
        'es_bautizado' => isset($_POST['es_bautizado']) && $_POST['es_bautizado'] == '1' ? 1 : 0,
        'fecha_bautismo' => trim($_POST['fecha_bautismo'] ?? ''),
        'ministerio_id' => !empty($_POST['ministerio_id']) ? intval($_POST['ministerio_id']) : null,
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];
    
    // Validaciones
    $errores = [];
    if (empty($datos['nombre'])) $errores[] = 'El nombre es requerido';
    if (empty($datos['apellido'])) $errores[] = 'El apellido es requerido';
    if (empty($datos['sexo'])) $errores[] = 'El sexo es requerido';
    if (empty($datos['numero_documento'])) $errores[] = 'El número de documento es requerido';
    if (empty($datos['telefono'])) $errores[] = 'El teléfono es requerido';
    
    // Normalizar documento
    $doc_limpio = str_replace(['-', ' '], '', $datos['numero_documento']);
    
    // Verificar duplicados
    $stmt = $conexion->prepare("SELECT id FROM solicitudes_membresia WHERE REPLACE(REPLACE(numero_documento, '-', ''), ' ', '') = ? AND iglesia_id = ? AND estado = 'pendiente'");
    $stmt->bind_param("si", $doc_limpio, $iglesia_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = 'Ya existe una solicitud pendiente con este documento';
    }
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT id FROM miembros WHERE REPLACE(REPLACE(numero_documento, '-', ''), ' ', '') = ? AND iglesia_id = ? AND estado = 'activo'");
    $stmt->bind_param("si", $doc_limpio, $iglesia_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errores[] = 'Ya existe un miembro activo con este documento';
    }
    $stmt->close();
    
    if (empty($errores)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $fecha_baut = !empty($datos['fecha_bautismo']) ? $datos['fecha_bautismo'] : null;
        $email = !empty($datos['email']) ? $datos['email'] : null;
        
        $sql = "INSERT INTO solicitudes_membresia (
            iglesia_id, nombre, apellido, sexo, fecha_nacimiento,
            nacionalidad_id, tipo_documento, numero_documento, telefono, email, direccion,
            estado_civil, nivel_estudio_id, carrera_id, es_bautizado, fecha_bautismo,
            ministerio_id, observaciones, ip_solicitud, user_agent, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            "issssississsiiisssss",
            $iglesia_id,
            $datos['nombre'],
            $datos['apellido'],
            $datos['sexo'],
            $datos['fecha_nacimiento'],
            $datos['nacionalidad_id'],
            $datos['tipo_documento'],
            $doc_limpio,
            $datos['telefono'],
            $email,
            $datos['direccion'],
            $datos['estado_civil'],
            $datos['nivel_estudio_id'],
            $datos['carrera_id'],
            $datos['es_bautizado'],
            $fecha_baut,
            $datos['ministerio_id'],
            $datos['observaciones'],
            $ip,
            $ua
        );
        
        if ($stmt->execute()) {
            $solicitud_id = $stmt->insert_id;
            
            // Enviar notificación al pastor
            notificarNuevaSolicitud($conexion, $solicitud_id, $iglesia_id);
            
            $mensaje = '¡Solicitud enviada exitosamente! El pastor revisará tu información y te contactará pronto.';
            $tipo_mensaje = 'success';
            $datos = [];
        } else {
            $mensaje = 'Error al enviar la solicitud: ' . $conexion->error;
            $tipo_mensaje = 'danger';
        }
        $stmt->close();
    } else {
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'danger';
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Solicitud de Membresía - <?php echo htmlspecialchars($iglesia_nombre); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0dcaf0;
            --primary-dark: #0891b2;
            --primary-light: #67e8f9;
            --primary-darker: #0e7490;
            --success: #10b981;
            --danger: #ef4444;
            --gradient-primary: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            --gradient-dark: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            margin: 0;
        }
        .form-header {
            background: var(--gradient-dark);
            color: white;
            padding: 1.5rem 1rem;
            text-align: center;
        }
        .form-header h1 { font-size: clamp(1.2rem, 4vw, 1.6rem); font-weight: 700; margin: 0 0 0.5rem; }
        .form-header .church-name { font-size: clamp(0.95rem, 3vw, 1.15rem); opacity: 0.9; }
        .form-header .church-code {
            font-size: 0.8rem;
            background: rgba(255,255,255,0.15);
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            display: inline-block;
            margin-top: 0.4rem;
        }
        .form-container { max-width: 750px; margin: 0 auto; padding: 1rem; }
        @media (min-width: 768px) { .form-container { padding: 2rem; } }
        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .form-card-body { padding: 1.25rem; }
        @media (min-width: 768px) { .form-card-body { padding: 2rem; } }
        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .section-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-label { font-weight: 500; font-size: 0.9rem; color: #374151; margin-bottom: 0.3rem; }
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            font-size: max(16px, 1rem);
            min-height: 48px;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13, 202, 240, 0.15);
            outline: none;
        }
        .input-group-text {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-right: none;
            color: var(--primary-dark);
            min-width: 46px;
            justify-content: center;
        }
        .input-group .form-control { border-left: none; }
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control { border-color: var(--primary); }
        .radio-group { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .radio-btn { flex: 1; min-width: 100px; }
        .radio-btn input { display: none; }
        .radio-btn label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.7rem;
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            font-size: max(16px, 1rem);
            min-height: 48px;
            transition: all 0.2s;
        }
        .radio-btn input:checked + label {
            background: var(--primary-light);
            border-color: var(--primary);
            color: var(--primary-darker);
            font-weight: 500;
        }
        .btn-submit {
            background: var(--gradient-primary);
            border: none;
            color: white;
            font-weight: 600;
            font-size: max(16px, 1.05rem);
            padding: 1rem 2rem;
            border-radius: 12px;
            width: 100%;
            min-height: 54px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(13,202,240,0.35); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        .alert { border: none; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; }
        .alert-success { background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #065f46; }
        .alert-danger { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #991b1b; }
        .required-text { font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; }
        .form-footer { text-align: center; padding: 1rem; color: #6b7280; font-size: 0.85rem; }
        .hidden { display: none; }
        textarea.form-control { min-height: 80px; resize: vertical; }
        @media (max-width: 575.98px) {
            .form-card-body { padding: 1rem; }
            .radio-btn { flex: 1 1 calc(50% - 0.25rem); }
        }
    </style>
</head>
<body>
    <header class="form-header">
        <h1><i class="bi bi-person-plus-fill"></i> Solicitud de Membresía</h1>
        <div class="church-name"><?php echo htmlspecialchars($iglesia_nombre); ?></div>
        <div class="church-code"><?php echo htmlspecialchars($iglesia_codigo); ?></div>
    </header>
    
    <div class="form-container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> fs-5"></i>
                    <div><?php echo $mensaje; ?></div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($tipo_mensaje !== 'success'): ?>
        <form method="POST" class="form-card" id="solicitudForm">
            <div class="form-card-body">
                <p class="required-text">Campos marcados con <span class="text-danger">*</span> son obligatorios</p>
                
                <!-- DATOS PERSONALES -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-person"></i> Datos Personales</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" required maxlength="100" 
                                   value="<?php echo htmlspecialchars($datos['nombre'] ?? ''); ?>" placeholder="Tu nombre">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" class="form-control" required maxlength="100"
                                   value="<?php echo htmlspecialchars($datos['apellido'] ?? ''); ?>" placeholder="Tu apellido">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Sexo <span class="text-danger">*</span></label>
                            <div class="radio-group">
                                <div class="radio-btn">
                                    <input type="radio" id="sexo_m" name="sexo" value="M" <?php echo ($datos['sexo'] ?? '') === 'M' ? 'checked' : ''; ?> required>
                                    <label for="sexo_m"><i class="bi bi-gender-male me-1"></i> Masculino</label>
                                </div>
                                <div class="radio-btn">
                                    <input type="radio" id="sexo_f" name="sexo" value="F" <?php echo ($datos['sexo'] ?? '') === 'F' ? 'checked' : ''; ?>>
                                    <label for="sexo_f"><i class="bi bi-gender-female me-1"></i> Femenino</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control"
                                   value="<?php echo htmlspecialchars($datos['fecha_nacimiento'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nacionalidad</label>
                            <select name="nacionalidad_id" class="form-select">
                                <option value="">-- Seleccione --</option>
                                <?php while ($nac = $nacionalidades->fetch_assoc()): ?>
                                    <option value="<?php echo $nac['id']; ?>" <?php echo ($datos['nacionalidad_id'] ?? '') == $nac['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($nac['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Tipo de Documento</label>
                            <select name="tipo_documento" id="tipo_documento" class="form-select">
                                <option value="cedula" <?php echo ($datos['tipo_documento'] ?? '') === 'cedula' ? 'selected' : ''; ?>>Cédula</option>
                                <option value="pasaporte" <?php echo ($datos['tipo_documento'] ?? '') === 'pasaporte' ? 'selected' : ''; ?>>Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Número de Documento <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <input type="text" name="numero_documento" id="numero_documento" class="form-control" required
                                       placeholder="000-0000000-0" maxlength="15" inputmode="numeric"
                                       value="<?php echo htmlspecialchars($datos['numero_documento'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="tel" name="telefono" id="telefono" class="form-control" required
                                       placeholder="809-000-0000" maxlength="12" inputmode="tel"
                                       value="<?php echo htmlspecialchars($datos['telefono'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control"
                                       placeholder="tucorreo@ejemplo.com" maxlength="150" inputmode="email"
                                       value="<?php echo htmlspecialchars($datos['email'] ?? ''); ?>">
                            </div>
                            <small class="text-muted">Opcional. Para recibir notificación de tu solicitud.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" maxlength="255"
                                   placeholder="Calle, número, sector, ciudad"
                                   value="<?php echo htmlspecialchars($datos['direccion'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- ESTADO CIVIL -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-heart"></i> Estado Civil</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Estado Civil</label>
                            <select name="estado_civil" class="form-select">
                                <option value="soltero" <?php echo ($datos['estado_civil'] ?? '') === 'soltero' ? 'selected' : ''; ?>>Soltero/a</option>
                                <option value="casado" <?php echo ($datos['estado_civil'] ?? '') === 'casado' ? 'selected' : ''; ?>>Casado/a</option>
                                <option value="union_libre" <?php echo ($datos['estado_civil'] ?? '') === 'union_libre' ? 'selected' : ''; ?>>Unión Libre</option>
                                <option value="divorciado" <?php echo ($datos['estado_civil'] ?? '') === 'divorciado' ? 'selected' : ''; ?>>Divorciado/a</option>
                                <option value="viudo" <?php echo ($datos['estado_civil'] ?? '') === 'viudo' ? 'selected' : ''; ?>>Viudo/a</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- NIVEL DE ESTUDIOS -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-book"></i> Nivel de Estudios</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nivel de Estudio</label>
                            <select name="nivel_estudio_id" id="nivel_estudio_id" class="form-select">
                                <option value="">-- Seleccione --</option>
                                <?php while ($ne = $niveles_estudio->fetch_assoc()): ?>
                                    <option value="<?php echo $ne['id']; ?>" 
                                            data-requiere-carrera="<?php echo $ne['requiere_carrera'] ?? 0; ?>"
                                            <?php echo ($datos['nivel_estudio_id'] ?? '') == $ne['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ne['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 <?php echo empty($datos['carrera_id']) ? 'hidden' : ''; ?>" id="div_carrera">
                            <label class="form-label">Carrera</label>
                            <select name="carrera_id" id="carrera_id" class="form-select">
                                <option value="">-- Seleccione --</option>
                                <?php while ($car = $carreras->fetch_assoc()): ?>
                                    <option value="<?php echo $car['id']; ?>" <?php echo ($datos['carrera_id'] ?? '') == $car['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($car['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- DATOS ECLESIÁSTICOS -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-water"></i> Datos Eclesiásticos</h2>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">¿Es Bautizado?</label>
                            <select name="es_bautizado" id="es_bautizado" class="form-select">
                                <option value="0" <?php echo ($datos['es_bautizado'] ?? 0) == 0 ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo ($datos['es_bautizado'] ?? 0) == 1 ? 'selected' : ''; ?>>Sí</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 <?php echo ($datos['es_bautizado'] ?? 0) != 1 ? 'hidden' : ''; ?>" id="div_fecha_bautismo">
                            <label class="form-label">Fecha de Bautismo</label>
                            <input type="date" name="fecha_bautismo" class="form-control"
                                   value="<?php echo htmlspecialchars($datos['fecha_bautismo'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ministerio de Interés</label>
                            <select name="ministerio_id" class="form-select">
                                <option value="">-- Seleccione --</option>
                                <?php while ($min = $ministerios->fetch_assoc()): ?>
                                    <option value="<?php echo $min['id']; ?>" <?php echo ($datos['ministerio_id'] ?? '') == $min['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($min['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- OBSERVACIONES -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-chat-text"></i> Información Adicional</h2>
                    <div class="mb-0">
                        <label class="form-label">Notas o Comentarios</label>
                        <textarea name="observaciones" class="form-control" rows="3" 
                                  placeholder="¿Hay algo que quieras compartir?"><?php echo htmlspecialchars($datos['observaciones'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <i class="bi bi-send-fill"></i> Enviar Solicitud
                </button>
            </div>
        </form>
        <?php else: ?>
        <div class="form-card">
            <div class="form-card-body text-center py-5">
                <div style="font-size: 5rem; color: var(--success); margin-bottom: 1.5rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 style="color: #065f46; margin-bottom: 1rem; font-weight: 700;">¡Solicitud Recibida!</h2>
                <p style="color: #374151; font-size: 1.1rem; max-width: 450px; margin: 0 auto 1.5rem;">
                    Tu solicitud de membresía ha sido enviada exitosamente a <strong><?php echo htmlspecialchars($iglesia_nombre); ?></strong>.
                </p>
                <div style="background: #f0fdf4; border-radius: 12px; padding: 1.25rem; max-width: 400px; margin: 0 auto 2rem; border: 1px solid #bbf7d0;">
                    <p style="margin: 0; color: #166534; font-size: 0.95rem;">
                        <i class="bi bi-info-circle me-2"></i>
                        El pastor revisará tu información y se comunicará contigo pronto.
                    </p>
                </div>
                <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="btn-submit" style="max-width: 280px; margin: 0 auto; text-decoration: none;">
                    <i class="bi bi-plus-circle"></i> Enviar Otra Solicitud
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <footer class="form-footer">
        <i class="bi bi-shield-check"></i> Tu información está segura
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar carrera según nivel de estudio
        document.getElementById('nivel_estudio_id').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const requiere = opt.getAttribute('data-requiere-carrera') === '1';
            document.getElementById('div_carrera').classList.toggle('hidden', !requiere);
            if (!requiere) document.getElementById('carrera_id').value = '';
        });
        
        // Mostrar/ocultar fecha bautismo
        document.getElementById('es_bautizado').addEventListener('change', function() {
            const show = this.value === '1';
            document.getElementById('div_fecha_bautismo').classList.toggle('hidden', !show);
            if (!show) document.querySelector('[name="fecha_bautismo"]').value = '';
        });
        
        // Formatear cédula
        document.getElementById('numero_documento').addEventListener('input', function(e) {
            if (document.getElementById('tipo_documento').value !== 'cedula') return;
            let v = e.target.value.replace(/\D/g, '').substring(0, 11);
            if (v.length > 3 && v.length <= 10) v = v.substring(0,3) + '-' + v.substring(3);
            else if (v.length > 10) v = v.substring(0,3) + '-' + v.substring(3,10) + '-' + v.substring(10);
            e.target.value = v;
        });
        
        // Formatear teléfono
        document.getElementById('telefono').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').substring(0, 10);
            if (v.length > 3 && v.length <= 6) v = v.substring(0,3) + '-' + v.substring(3);
            else if (v.length > 6) v = v.substring(0,3) + '-' + v.substring(3,6) + '-' + v.substring(6);
            e.target.value = v;
        });
        
        // Prevenir doble envío
        document.getElementById('solicitudForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';
        });
        
        // Capitalizar nombre/apellido mientras escribe
        ['nombre', 'apellido'].forEach(id => {
            const field = document.querySelector('[name="'+id+'"]');
            if (field) {
                field.style.textTransform = 'uppercase';
                field.addEventListener('input', function() {
                    const start = this.selectionStart;
                    const end = this.selectionEnd;
                    this.value = this.value.toUpperCase();
                    this.setSelectionRange(start, end);
                });
            }
        });
        
        // Inicializar visibilidad
        document.getElementById('nivel_estudio_id').dispatchEvent(new Event('change'));
    </script>
</body>
</html>
