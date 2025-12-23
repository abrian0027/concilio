<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar sesión y permisos
$puede_crear = in_array($_SESSION['rol_nombre'] ?? '', ['super_admin', 'pastor', 'secretaria']);

if (!isset($_SESSION['usuario_id']) || !$puede_crear) {
    header("Location: index.php?error=Sin permisos");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Recibir datos y convertir nombres a mayúsculas
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

// Iglesia: si es super_admin puede elegir, sino usa la de sesión
if ($_SESSION['rol_nombre'] === 'super_admin') {
    $iglesia_id = (int)($_POST['iglesia_id'] ?? 0);
} else {
    $iglesia_id = (int)($_SESSION['iglesia_id'] ?? 0);
}

// Validaciones básicas
if ($nombre === '' || $apellido === '' || $sexo === '' || $iglesia_id === 0) {
    header("Location: crear.php?error=Faltan campos obligatorios (nombre, apellido, sexo, iglesia)");
    exit;
}

// Validar documento único (si se ingresó)
if ($numero_documento !== '') {
    $stmt = $conexion->prepare("SELECT id FROM miembros WHERE numero_documento = ?");
    $stmt->bind_param("s", $numero_documento);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $tipo_doc_texto = $tipo_documento === 'cedula' ? 'cédula' : 'pasaporte';
        header("Location: crear.php?error=El número de $tipo_doc_texto ya está registrado para otro miembro");
        exit;
    }
    $stmt->close();
}

// Si no es bautizado, limpiar fecha de bautismo
if ($es_bautizado === 0) {
    $fecha_bautismo = null;
}

// Manejar foto ANTES de la transacción
$foto_nombre = null;
$carpeta_destino = __DIR__ . '/../../uploads/miembros/';

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['foto'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $extensiones_validas = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $extensiones_validas)) {
        header("Location: crear.php?error=Formato de imagen no válido. Use JPG, PNG o GIF");
        exit;
    }
    
    if ($archivo['size'] > 2 * 1024 * 1024) {
        header("Location: crear.php?error=La imagen es muy grande. Máximo 2MB");
        exit;
    }
    
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);
    }
    
    $foto_nombre = 'miembro_' . time() . '_' . uniqid() . '.' . $extension;
    $ruta_destino = $carpeta_destino . $foto_nombre;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        header("Location: crear.php?error=Error al subir la imagen");
        exit;
    }
}

// Preparar valores para inserción
$numero_documento_db = $numero_documento !== '' ? $numero_documento : null;
$telefono_db = $telefono !== '' ? $telefono : null;
$direccion_db = $direccion !== '' ? $direccion : null;

// INICIAR TRANSACCIÓN - La familia solo se crea si todo sale bien
$conexion->begin_transaction();

try {
    $familia_id = null;
    
    // Crear familia solo dentro de la transacción
    if ($familia_opcion === 'nueva') {
        // Obtener el último código de familia para esta iglesia
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
        
        // Insertar nueva familia
        $stmt = $conexion->prepare("INSERT INTO familias (iglesia_id, codigo, apellido_familia) VALUES (?, ?, ?)");
        $apellido_fam = $apellido_familia !== '' ? $apellido_familia : null;
        $stmt->bind_param("iss", $iglesia_id, $nuevo_codigo, $apellido_fam);
        $stmt->execute();
        $familia_id = $conexion->insert_id;
        $stmt->close();
        
    } elseif ($familia_opcion !== '' && is_numeric($familia_opcion)) {
        $familia_id = (int)$familia_opcion;
    }
    
    // Insertar miembro
    $sql = "INSERT INTO miembros (
                iglesia_id, familia_id, nombre, apellido, sexo, fecha_nacimiento,
                nacionalidad_id, tipo_documento, numero_documento, telefono, direccion, foto,
                estado_civil, nivel_estudio_id, carrera_id,
                estado_miembro, ministerio_id, es_bautizado, fecha_bautismo, es_lider, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    
    $stmt->bind_param(
        "iissssissssssiisissis",
        $iglesia_id,
        $familia_id,
        $nombre,
        $apellido,
        $sexo,
        $fecha_nacimiento,
        $nacionalidad_id,
        $tipo_documento,
        $numero_documento_db,
        $telefono_db,
        $direccion_db,
        $foto_nombre,
        $estado_civil,
        $nivel_estudio_id,
        $carrera_id,
        $estado_miembro,
        $ministerio_id,
        $es_bautizado,
        $fecha_bautismo,
        $es_lider,
        $estado
    );
    
    $stmt->execute();
    $stmt->close();
    
    // Si todo salió bien, confirmar transacción
    $conexion->commit();
    
    header("Location: index.php?success=creado");
    exit;
    
} catch (Exception $e) {
    // Si hubo error, revertir transacción (la familia NO se guarda)
    $conexion->rollback();
    
    error_log("Error al crear miembro: " . $e->getMessage());
    
    // Eliminar foto si se subió
    if ($foto_nombre !== null && file_exists($carpeta_destino . $foto_nombre)) {
        unlink($carpeta_destino . $foto_nombre);
    }
    
    header("Location: crear.php?error=Error al guardar: " . urlencode($e->getMessage()));
    exit;
}