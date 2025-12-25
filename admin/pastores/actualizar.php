<?php
/**
 * Actualizar pastor existente
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/auditoria.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || !in_array($_SESSION['rol_nombre'], ['super_admin', 'obispo', 'super_conferencia'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener ID del pastor
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?error=' . urlencode('ID de pastor inválido'));
    exit;
}

// Verificar que el pastor existe y obtener datos antes de actualizar
$check = $conexion->prepare("SELECT * FROM pastores WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php?error=' . urlencode('Pastor no encontrado'));
    exit;
}

$pastor_antes = $result->fetch_assoc();
$check->close();

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

// Formación académica
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

// Verificar que la cédula no exista en otro pastor
if (!empty($cedula)) {
    $check = $conexion->prepare("SELECT id FROM pastores WHERE cedula = ? AND id != ?");
    $check->bind_param("si", $cedula, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $errores[] = "Ya existe otro pastor con esta cédula";
    }
    $check->close();
}

if (!empty($errores)) {
    header('Location: editar.php?id=' . $id . '&error=' . urlencode(implode('. ', $errores)));
    exit;
}

// Procesar foto
$foto = $pastor_antes['foto']; // Mantener la foto actual por defecto
$foto_anterior = $pastor_antes['foto'];

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $extensiones_permitidas)) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Formato de imagen no permitido. Use JPG, PNG o GIF'));
        exit;
    }
    
    if ($archivo['size'] > 2 * 1024 * 1024) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('La imagen no debe superar 2MB'));
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
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error al subir la imagen'));
        exit;
    }
    
    // Eliminar foto anterior si existe
    if ($foto_anterior && file_exists($directorio . $foto_anterior)) {
        unlink($directorio . $foto_anterior);
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

// Actualizar pastor
try {
    // Usar prepared statement para evitar inyección SQL
    $sql = "UPDATE pastores SET
                nombre = ?,
                apellido = ?,
                sexo = ?,
                tipo_documento = ?,
                cedula = ?,
                pasaporte = ?,
                telefono = ?,
                fecha_nacimiento = ?,
                edad = ?,
                nacionalidad_id = ?,
                estado_civil = ?,
                conferencia_id = ?,
                fecha_ingreso_ministerio = ?,
                anos_servicio = ?,
                orden_ministerial = ?,
                nivel_estudio_id = ?,
                carrera_id = ?,
                formacion_continuada = ?,
                foto = ?,
                actualizado_en = NOW()
            WHERE id = ?";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception($conexion->error);
    }

    // Ajustar tipos para bind_param
    $edad_param = $edad !== null ? $edad : null;
    $nacionalidad_id_param = $nacionalidad_id !== null ? $nacionalidad_id : null;
    $conferencia_id_param = $conferencia_id !== null ? $conferencia_id : null;
    $anos_servicio_param = $anos_servicio !== null ? $anos_servicio : null;
    $nivel_estudio_id_param = $nivel_estudio_id !== null ? $nivel_estudio_id : null;
    $carrera_id_param = $carrera_id !== null ? $carrera_id : null;

    $stmt->bind_param(
        "ssssssssississsiissi",
        $nombre,
        $apellido,
        $sexo,
        $tipo_documento,
        $cedula,
        $pasaporte,
        $telefono,
        $fecha_nacimiento,
        $edad_param,
        $nacionalidad_id_param,
        $estado_civil,
        $conferencia_id_param,
        $fecha_ingreso_ministerio,
        $anos_servicio_param,
        $orden_ministerial,
        $nivel_estudio_id_param,
        $carrera_id_param,
        $formacion_continuada,
        $foto,
        $id
    );

    if ($stmt->execute()) {
        // Obtener datos después de actualizar
        $check = $conexion->prepare("SELECT * FROM pastores WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $pastor_despues = $check->get_result()->fetch_assoc();
        $check->close();
        
        // ============================================
        // SINCRONIZAR NOMBRE EN MIEMBROS Y USUARIOS
        // ============================================
        $cedula_sync = $pastor_despues['cedula'];
        
        // Sincronizar con tabla MIEMBROS (por número de documento)
        if (!empty($cedula_sync)) {
            $stmt_sync = $conexion->prepare("UPDATE miembros SET nombre = ?, apellido = ? WHERE numero_documento = ?");
            $stmt_sync->bind_param("sss", $nombre, $apellido, $cedula_sync);
            $stmt_sync->execute();
            $stmt_sync->close();
        }
        
        // Sincronizar con tabla USUARIOS (por usuario = cédula)
        if (!empty($cedula_sync)) {
            $stmt_sync = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido = ? WHERE usuario = ?");
            $stmt_sync->bind_param("sss", $nombre, $apellido, $cedula_sync);
            $stmt_sync->execute();
            $stmt_sync->close();
        }
        
        // Registrar en auditoría
        auditoria_editar(
            'pastores',
            'pastores',
            $id,
            "Pastor actualizado: $nombre $apellido (sincronizado con miembros/usuarios)",
            $pastor_antes,
            $pastor_despues
        );
        
        header('Location: ver.php?id=' . $id . '&msg=' . urlencode('Pastor actualizado exitosamente'));
        exit;
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error al actualizar pastor: " . $e->getMessage());
    header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error al guardar: ' . $e->getMessage()));
    exit;
}
