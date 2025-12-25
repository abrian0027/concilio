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

// Obtener foto anterior para eliminarla
$stmt = $conexion->prepare("SELECT foto FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$foto_anterior = $usuario['foto'] ?? null;

// Mover archivo subido
if (!move_uploaded_file($file['tmp_name'], $ruta_destino)) {
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al guardar la imagen.";
    $_SESSION['perfil_tipo'] = 'danger';
    header('Location: index.php');
    exit;
}

// Actualizar base de datos
$stmt = $conexion->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
$stmt->bind_param("si", $nuevo_nombre, $usuario_id);

if ($stmt->execute()) {
    // Eliminar foto anterior si existe
    if ($foto_anterior && file_exists($upload_dir . $foto_anterior)) {
        unlink($upload_dir . $foto_anterior);
    }
    
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-check-circle me-1'></i> Foto de perfil actualizada correctamente.";
    $_SESSION['perfil_tipo'] = 'success';
} else {
    // Si falla la BD, eliminar el archivo subido
    if (file_exists($ruta_destino)) {
        unlink($ruta_destino);
    }
    $_SESSION['perfil_mensaje'] = "<i class='fas fa-times-circle me-1'></i> Error al actualizar la foto en la base de datos.";
    $_SESSION['perfil_tipo'] = 'danger';
}
$stmt->close();

header('Location: index.php');
exit;
