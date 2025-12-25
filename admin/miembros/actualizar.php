<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
$puede_editar = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_editar) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos
$id = (int)($_POST['id'] ?? 0);
$nombre = mb_strtoupper(trim($_POST['nombre'] ?? ''), 'UTF-8');
$apellido = mb_strtoupper(trim($_POST['apellido'] ?? ''), 'UTF-8');
$sexo = $_POST['sexo'] ?? '';
$fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
$nacionalidad_id = !empty($_POST['nacionalidad_id']) ? (int)$_POST['nacionalidad_id'] : null;
$tipo_documento = $_POST['tipo_documento'] ?? 'cedula';
$numero_documento = trim($_POST['numero_documento'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$estado_civil = $_POST['estado_civil'] ?? 'soltero';
$nivel_estudio_id = !empty($_POST['nivel_estudio_id']) ? (int)$_POST['nivel_estudio_id'] : null;
$carrera_id = !empty($_POST['carrera_id']) ? (int)$_POST['carrera_id'] : null;
$estado_miembro = $_POST['estado_miembro'] ?? 'en_preparacion';
$ministerio_id = !empty($_POST['ministerio_id']) ? (int)$_POST['ministerio_id'] : null;
$es_bautizado = (int)($_POST['es_bautizado'] ?? 0);
$fecha_bautismo = !empty($_POST['fecha_bautismo']) ? $_POST['fecha_bautismo'] : null;
$es_lider = (int)($_POST['es_lider'] ?? 0);
$estado = $_POST['estado'] ?? 'activo';
$familia_opcion = $_POST['familia_opcion'] ?? '';
$apellido_familia = mb_strtoupper(trim($_POST['apellido_familia'] ?? ''), 'UTF-8');
$foto_actual = $_POST['foto_actual'] ?? '';

// Iglesia
if ($_SESSION['rol_nombre'] === 'super_admin') {
    $iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)($_SESSION['iglesia_id'] ?? 0);
}

// Validaciones básicas
if ($id === 0 || $nombre === '' || $apellido === '' || $sexo === '' || $iglesia_id === 0) {
    header("Location: editar.php?id=$id&error=Faltan campos obligatorios");
    exit;
}

// Verificar que el miembro existe
$stmt = $conexion->prepare("SELECT iglesia_id, foto, numero_documento FROM miembros WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$miembro_actual = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$miembro_actual) {
    header("Location: index.php?error=Miembro no encontrado");
    exit;
}

// Verificar acceso
if ($_SESSION['rol_nombre'] !== 'super_admin') {
    if ($miembro_actual['iglesia_id'] != $_SESSION['iglesia_id']) {
        header("Location: index.php?error=No tienes permiso para editar este miembro");
        exit;
    }
}

// Validar documento único
if ($numero_documento !== '' && $numero_documento !== $miembro_actual['numero_documento']) {
    $stmt = $conexion->prepare("SELECT id FROM miembros WHERE numero_documento = ? AND id != ?");
    $stmt->bind_param("si", $numero_documento, $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $tipo_doc_texto = $tipo_documento === 'cedula' ? 'cédula' : 'pasaporte';
        header("Location: editar.php?id=$id&error=El número de $tipo_doc_texto ya está registrado");
        exit;
    }
    $stmt->close();
}

// Si no es bautizado, limpiar fecha
if ($es_bautizado === 0) {
    $fecha_bautismo = null;
}

// Manejar foto
$foto_nombre = $foto_actual !== '' ? $foto_actual : null;
$foto_nueva = false;
$carpeta_destino = __DIR__ . '/../../uploads/miembros/';

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_validas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $extensiones_validas)) {
        header("Location: editar.php?id=$id&error=Formato de imagen no válido");
        exit;
    }
    
    if ($archivo['size'] > 2 * 1024 * 1024) {
        header("Location: editar.php?id=$id&error=La imagen es muy grande. Máximo 2MB");
        exit;
    }
    
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);
    }
    
    $foto_nombre = 'miembro_' . time() . '_' . uniqid() . '.' . $extension;
    
    if (!move_uploaded_file($archivo['tmp_name'], $carpeta_destino . $foto_nombre)) {
        header("Location: editar.php?id=$id&error=Error al subir la imagen");
        exit;
    }
    
    $foto_nueva = true;
}

// INICIAR TRANSACCIÓN
$conexion->begin_transaction();

try {
    $familia_id = null;
    
    // Crear familia si es nueva
    if ($familia_opcion === 'nueva') {
        $stmt = $conexion->prepare("SELECT codigo FROM familias WHERE iglesia_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $iglesia_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $ultima = $result->fetch_assoc();
            $num = (int)str_replace('FAM-', '', $ultima['codigo']);
            $nuevo_codigo = 'FAM-' . str_pad((string)($num + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $nuevo_codigo = 'FAM-001';
        }
        $stmt->close();
        
        $stmt = $conexion->prepare("INSERT INTO familias (iglesia_id, codigo, apellido_familia) VALUES (?, ?, ?)");
        $apellido_fam = $apellido_familia !== '' ? $apellido_familia : null;
        $stmt->bind_param("iss", $iglesia_id, $nuevo_codigo, $apellido_fam);
        $stmt->execute();
        $familia_id = $conexion->insert_id;
        $stmt->close();
        
    } elseif ($familia_opcion !== '' && is_numeric($familia_opcion)) {
        $familia_id = (int)$familia_opcion;
    }
    
    // Preparar valores para SQL - convertir NULL a 'NULL' string para la consulta
    $fecha_nac_sql = $fecha_nacimiento !== null ? "'$fecha_nacimiento'" : "NULL";
    $nacionalidad_sql = $nacionalidad_id !== null ? $nacionalidad_id : "NULL";
    $numero_doc_sql = $numero_documento !== '' ? "'" . $conexion->real_escape_string($numero_documento) . "'" : "NULL";
    $telefono_sql = $telefono !== '' ? "'" . $conexion->real_escape_string($telefono) . "'" : "NULL";
    $direccion_sql = $direccion !== '' ? "'" . $conexion->real_escape_string($direccion) . "'" : "NULL";
    $foto_sql = $foto_nombre !== null ? "'" . $conexion->real_escape_string($foto_nombre) . "'" : "NULL";
    $nivel_estudio_sql = $nivel_estudio_id !== null ? $nivel_estudio_id : "NULL";
    $carrera_sql = $carrera_id !== null ? $carrera_id : "NULL";
    $ministerio_sql = $ministerio_id !== null ? $ministerio_id : "NULL";
    $fecha_bautismo_sql = $fecha_bautismo !== null ? "'$fecha_bautismo'" : "NULL";
    $familia_sql = $familia_id !== null ? $familia_id : "NULL";
    
    // Actualizar miembro con consulta directa
    $sql = "UPDATE miembros SET 
                iglesia_id = $iglesia_id,
                familia_id = $familia_sql,
                nombre = '" . $conexion->real_escape_string($nombre) . "',
                apellido = '" . $conexion->real_escape_string($apellido) . "',
                sexo = '" . $conexion->real_escape_string($sexo) . "',
                fecha_nacimiento = $fecha_nac_sql,
                nacionalidad_id = $nacionalidad_sql,
                tipo_documento = '" . $conexion->real_escape_string($tipo_documento) . "',
                numero_documento = $numero_doc_sql,
                telefono = $telefono_sql,
                direccion = $direccion_sql,
                foto = $foto_sql,
                estado_civil = '" . $conexion->real_escape_string($estado_civil) . "',
                nivel_estudio_id = $nivel_estudio_sql,
                carrera_id = $carrera_sql,
                estado_miembro = '" . $conexion->real_escape_string($estado_miembro) . "',
                ministerio_id = $ministerio_sql,
                es_bautizado = $es_bautizado,
                fecha_bautismo = $fecha_bautismo_sql,
                es_lider = $es_lider,
                estado = '" . $conexion->real_escape_string($estado) . "'
            WHERE id = $id";
    
    $conexion->query($sql);
    
    // ============================================
    // SINCRONIZAR NOMBRE EN PASTORES Y USUARIOS
    // ============================================
    if (!empty($numero_documento)) {
        // Sincronizar con tabla PASTORES (por cédula)
        $stmt_sync = $conexion->prepare("UPDATE pastores SET nombre = ?, apellido = ? WHERE cedula = ?");
        $stmt_sync->bind_param("sss", $nombre, $apellido, $numero_documento);
        $stmt_sync->execute();
        $stmt_sync->close();
        
        // Sincronizar con tabla USUARIOS (por usuario = cédula)
        $stmt_sync = $conexion->prepare("UPDATE usuarios SET nombre = ?, apellido = ? WHERE usuario = ?");
        $stmt_sync->bind_param("sss", $nombre, $apellido, $numero_documento);
        $stmt_sync->execute();
        $stmt_sync->close();
    }
    
    $conexion->commit();
    
    // Eliminar foto anterior si se subió una nueva
    if ($foto_nueva && $foto_actual !== '' && file_exists($carpeta_destino . $foto_actual)) {
        unlink($carpeta_destino . $foto_actual);
    }
    
    header("Location: index.php?success=editado");
    exit;
    
} catch (Exception $e) {
    $conexion->rollback();
    
    error_log("Error al actualizar miembro: " . $e->getMessage());
    
    if ($foto_nueva && $foto_nombre !== null && file_exists($carpeta_destino . $foto_nombre)) {
        unlink($carpeta_destino . $foto_nombre);
    }
    
    header("Location: editar.php?id=$id&error=Error al actualizar: " . urlencode($e->getMessage()));
    exit;
}