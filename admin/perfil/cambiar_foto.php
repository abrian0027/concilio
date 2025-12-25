<?php
declare(strict_types=1);

/**
 * Cambiar foto de perfil del usuario
 * Sistema Concilio
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$upload_dir = __DIR__ . '/../../uploads/usuarios/';

// Verificar que se subió un archivo
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    $error_msgs = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido.',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo.',
        UPLOAD_ERR_EXTENSION => 'Extensión PHP detuvo la subida.'
    ];
    
    $error_code = $_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE;
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> " . ($error_msgs[$error_code] ?? 'Error al subir el archivo.');
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

$file = $_FILES['foto'];

// Validar tamaño (máximo 2MB)
$max_size = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $max_size) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> El archivo es demasiado grande. Máximo 2MB.";
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Validar tipo de archivo
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Tipo de archivo no permitido. Use JPG, PNG o WEBP.";
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Generar nombre único
$extension = match($mime_type) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    default => 'jpg'
};
$nuevo_nombre = 'user_' . $usuario_id . '_' . time() . '.' . $extension;
$ruta_destino = $upload_dir . $nuevo_nombre;

// Obtener datos del usuario para sincronización
$stmt = $conexion->prepare("SELECT u.foto, u.miembro_id, u.usuario as cedula FROM usuarios u WHERE u.id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$foto_anterior = $usuario['foto'] ?? null;
$miembro_id = $usuario['miembro_id'] ?? null;
$cedula = $usuario['cedula'] ?? null;

// Mover archivo subido
if (!move_uploaded_file($file['tmp_name'], $ruta_destino)) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al guardar la imagen.";
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// ============================================
// SINCRONIZAR FOTO EN TODAS LAS TABLAS
// ============================================

$errores_sync = [];

// 1. Actualizar tabla USUARIOS
$stmt = $conexion->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
$stmt->bind_param("si", $nuevo_nombre, $usuario_id);
$usuarios_ok = $stmt->execute();
$stmt->close();

// 2. Actualizar tabla MIEMBROS (si tiene miembro_id vinculado)
$miembros_ok = true;
if ($miembro_id) {
    // Obtener foto anterior de miembro para eliminar
    $stmt = $conexion->prepare("SELECT foto FROM miembros WHERE id = ?");
    $stmt->bind_param("i", $miembro_id);
    $stmt->execute();
    $miembro_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $foto_miembro_anterior = $miembro_data['foto'] ?? null;
    
    // Copiar la foto a la carpeta de miembros para compatibilidad
    $carpeta_miembros = __DIR__ . '/../../uploads/miembros/';
    if (!file_exists($carpeta_miembros)) {
        mkdir($carpeta_miembros, 0755, true);
    }
    copy($ruta_destino, $carpeta_miembros . $nuevo_nombre);
    
    // Actualizar foto en miembros
    $stmt = $conexion->prepare("UPDATE miembros SET foto = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_nombre, $miembro_id);
    $miembros_ok = $stmt->execute();
    $stmt->close();
    
    // Eliminar foto anterior de miembro si existe y es diferente
    if ($miembros_ok && $foto_miembro_anterior && $foto_miembro_anterior !== $nuevo_nombre) {
        $ruta_miembro = __DIR__ . '/../../uploads/miembros/' . $foto_miembro_anterior;
        if (file_exists($ruta_miembro)) {
            unlink($ruta_miembro);
        }
    }
}

// 3. Actualizar tabla PASTORES (si tiene cédula vinculada)
$pastores_ok = true;
if ($cedula) {
    // Verificar si existe en pastores
    $stmt = $conexion->prepare("SELECT id, foto FROM pastores WHERE cedula = ?");
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $pastor_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($pastor_data) {
        $foto_pastor_anterior = $pastor_data['foto'] ?? null;
        
        // Copiar la foto a la carpeta de pastores para compatibilidad
        $carpeta_pastores = __DIR__ . '/../../uploads/pastores/';
        if (!file_exists($carpeta_pastores)) {
            mkdir($carpeta_pastores, 0755, true);
        }
        copy($ruta_destino, $carpeta_pastores . $nuevo_nombre);
        
        // Actualizar foto en pastores
        $stmt = $conexion->prepare("UPDATE pastores SET foto = ? WHERE cedula = ?");
        $stmt->bind_param("ss", $nuevo_nombre, $cedula);
        $pastores_ok = $stmt->execute();
        $stmt->close();
        
        // Eliminar foto anterior de pastor si existe y es diferente
        if ($pastores_ok && $foto_pastor_anterior && $foto_pastor_anterior !== $nuevo_nombre) {
            $ruta_pastor = __DIR__ . '/../../uploads/pastores/' . $foto_pastor_anterior;
            if (file_exists($ruta_pastor)) {
                unlink($ruta_pastor);
            }
        }
    }
}

// Resultado final
if ($usuarios_ok) {
    // Eliminar foto anterior del usuario si existe
    if ($foto_anterior && file_exists($upload_dir . $foto_anterior)) {
        unlink($upload_dir . $foto_anterior);
    }
    
    // Mensaje de éxito con detalles de sincronización
    $sync_info = [];
    if ($miembro_id && $miembros_ok) $sync_info[] = 'Miembro';
    if ($cedula && $pastores_ok) $sync_info[] = 'Pastor';
    
    $sync_msg = !empty($sync_info) ? ' (sincronizado en: ' . implode(', ', $sync_info) . ')' : '';
    
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-check-circle me-1'></i> Foto de perfil actualizada correctamente" . $sync_msg;
    $_SESSION['perfil_tipo'] = 'success';
} else {
    // Si falla la BD, eliminar el archivo subido
    if (file_exists($ruta_destino)) {
        unlink($ruta_destino);
    }
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al actualizar la foto en la base de datos.";
    $_SESSION['perfil_tipo'] = 'danger';
}

header('Location: index.php');
exit;
