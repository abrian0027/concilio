<?php
/**
 * Guardar nuevo pastor
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['super_admin', 'obispo', 'super_conferencia'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crear.php');
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$sexo = $_POST['sexo'] ?? 'M';
$tipo_documento = $_POST['tipo_documento'] ?? 'Cédula';
$cedula = trim($_POST['cedula'] ?? '');
$pasaporte = trim($_POST['pasaporte'] ?? '') ?: null;
$telefono = trim($_POST['telefono'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$nacionalidad_id = !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null;
$estado_civil = $_POST['estado_civil'] ?? 'Soltero';
$conferencia_id = !empty($_POST['conferencia_id']) ? (int)$_POST['conferencia_id'] : null;

// Datos ministeriales
$fecha_ingreso_ministerio = $_POST['fecha_ingreso_ministerio'] ?? '';
$orden_ministerial = $_POST['orden_ministerial'] ?? 'Candidato Ministerial';

// Formación académica (nuevos campos)
$nivel_estudio_id = !empty($_POST['nivel_estudio_id']) ? (int)$_POST['nivel_estudio_id'] : null;
$carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;
$formacion_continuada = trim($_POST['formacion_continuada'] ?? '') ?: null;

// Validaciones
$errores = [];

if (empty($nombre)) $errores[] = "El nombre es obligatorio";
if (empty($apellido)) $errores[] = "El apellido es obligatorio";
if ($tipo_documento === 'Cédula' && empty($cedula)) $errores[] = "La cédula es obligatoria";
if (empty($telefono)) $errores[] = "El teléfono es obligatorio";
if (empty($fecha_nacimiento)) $errores[] = "La fecha de nacimiento es obligatoria";
if (empty($fecha_ingreso_ministerio)) $errores[] = "La fecha de ingreso al ministerio es obligatoria";
if (empty($conferencia_id)) $errores[] = "La conferencia es obligatoria";

// Validar formato cédula
if ($tipo_documento === 'Cédula' && !empty($cedula)) {
    if (!preg_match('/^\d{3}-\d{7}-\d{1}$/', $cedula)) {
        $errores[] = "La cédula debe tener el formato: 000-0000000-0";
    }
}

// Verificar que la cédula no exista
if (!empty($cedula)) {
    $check = $conexion->prepare("SELECT id FROM pastores WHERE cedula = ?");
    $check->bind_param("s", $cedula);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errores[] = "Ya existe un pastor con esta cédula";
    }
    $check->close();
}

if (!empty($errores)) {
    header('Location: crear.php?error=' . urlencode(implode('. ', $errores)));
    exit;
}

// Procesar foto
$foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $extensiones_permitidas)) {
        header('Location: crear.php?error=' . urlencode('Formato de imagen no permitido. Use JPG, PNG o GIF'));
        exit;
    }
    
    if ($archivo['size'] > 2 * 1024 * 1024) {
        header('Location: crear.php?error=' . urlencode('La imagen no debe superar 2MB'));
        exit;
    }
    
    // Crear directorio si no existe
    $directorio = __DIR__ . '/../../uploads/pastores/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    // Generar nombre único
    $foto = 'pastor_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_destino = $directorio . $foto;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        header('Location: crear.php?error=' . urlencode('Error al subir la imagen'));
        exit;
    }
}

// Calcular edad y años de servicio
$edad = null;
if (!empty($fecha_nacimiento)) {
    $fecha_nac = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y;
}

$anos_servicio = null;
if (!empty($fecha_ingreso_ministerio)) {
    $fecha_ing = new DateTime($fecha_ingreso_ministerio);
    $hoy = new DateTime();
    $anos_servicio = $hoy->diff($fecha_ing)->y;
}

// Insertar pastor usando consulta directa para manejar NULLs
try {
    // Escapar valores para seguridad
    $nombre_esc = $conexion->real_escape_string($nombre);
    $apellido_esc = $conexion->real_escape_string($apellido);
    $sexo_esc = $conexion->real_escape_string($sexo);
    $tipo_documento_esc = $conexion->real_escape_string($tipo_documento);
    $cedula_esc = $conexion->real_escape_string($cedula);
    $pasaporte_esc = $pasaporte ? "'" . $conexion->real_escape_string($pasaporte) . "'" : "NULL";
    $telefono_esc = $conexion->real_escape_string($telefono);
    $fecha_nacimiento_esc = $conexion->real_escape_string($fecha_nacimiento);
    $edad_esc = $edad !== null ? (int)$edad : "NULL";
    $nacionalidad_id_esc = $nacionalidad_id !== null ? (int)$nacionalidad_id : "NULL";
    $estado_civil_esc = $conexion->real_escape_string($estado_civil);
    $conferencia_id_esc = $conferencia_id !== null ? (int)$conferencia_id : "NULL";
    $fecha_ingreso_esc = $conexion->real_escape_string($fecha_ingreso_ministerio);
    $anos_servicio_esc = $anos_servicio !== null ? (int)$anos_servicio : "NULL";
    $orden_ministerial_esc = $conexion->real_escape_string($orden_ministerial);
    $nivel_estudio_id_esc = $nivel_estudio_id !== null ? (int)$nivel_estudio_id : "NULL";
    $carrera_id_esc = $carrera_id !== null ? (int)$carrera_id : "NULL";
    $formacion_continuada_esc = $formacion_continuada ? "'" . $conexion->real_escape_string($formacion_continuada) . "'" : "NULL";
    $foto_esc = $foto ? "'" . $conexion->real_escape_string($foto) . "'" : "NULL";
    
    $sql = "INSERT INTO pastores (
                nombre, apellido, sexo, tipo_documento, cedula, pasaporte,
                telefono, fecha_nacimiento, edad, nacionalidad_id, estado_civil,
                conferencia_id, fecha_ingreso_ministerio, anos_servicio, orden_ministerial,
                nivel_estudio_id, carrera_id, formacion_continuada, foto, activo
            ) VALUES (
                '$nombre_esc', '$apellido_esc', '$sexo_esc', '$tipo_documento_esc', '$cedula_esc', $pasaporte_esc,
                '$telefono_esc', '$fecha_nacimiento_esc', $edad_esc, $nacionalidad_id_esc, '$estado_civil_esc',
                $conferencia_id_esc, '$fecha_ingreso_esc', $anos_servicio_esc, '$orden_ministerial_esc',
                $nivel_estudio_id_esc, $carrera_id_esc, $formacion_continuada_esc, $foto_esc, 1
            )";
    
    if ($conexion->query($sql)) {
        $pastor_id = $conexion->insert_id;
        header('Location: index.php?msg=' . urlencode('Pastor registrado exitosamente. Ahora puede asignarle una iglesia.'));
        exit;
    } else {
        throw new Exception($conexion->error);
    }
    
} catch (Exception $e) {
    error_log("Error al crear pastor: " . $e->getMessage());
    header('Location: crear.php?error=' . urlencode('Error al guardar: ' . $e->getMessage()));
    exit;
}
