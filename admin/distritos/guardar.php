<?php
/**
 * Guardar/Actualizar distrito
 * Sistema Concilio
 */

session_start();
require_once __DIR__ . '/../../config/config.php';

// Verificar permisos
if (!isset($_SESSION['rol_nombre']) || $_SESSION['rol_nombre'] !== 'super_admin') {
    header('Location: index.php?error=' . urlencode('Sin permisos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Obtener datos
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$conferencia_id = filter_input(INPUT_POST, 'conferencia_id', FILTER_VALIDATE_INT);
$codigo = strtoupper(trim($_POST['codigo'] ?? ''));
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$activo = (int)($_POST['activo'] ?? 1);

// Validaciones
if (empty($codigo) || empty($nombre) || !$conferencia_id) {
    $redirect = $id ? "editar.php?id=$id" : "crear.php";
    header("Location: $redirect&error=" . urlencode("Campos obligatorios vacíos"));
    exit;
}

try {
    if ($id) {
        // ACTUALIZAR
        // Verificar código duplicado en la misma conferencia
        $stmt = $conexion->prepare("SELECT id FROM distritos WHERE codigo = ? AND conferencia_id = ? AND id != ?");
        $stmt->bind_param("sii", $codigo, $conferencia_id, $id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            header("Location: editar.php?id=$id&error=" . urlencode("El código ya existe en esta conferencia"));
            exit;
        }
        $stmt->close();
        
        // Actualizar
        $telefono_db = !empty($telefono) ? $telefono : null;
        $correo_db = !empty($correo) ? $correo : null;
        
        $stmt = $conexion->prepare("UPDATE distritos SET conferencia_id = ?, codigo = ?, nombre = ?, telefono = ?, correo = ?, activo = ? WHERE id = ?");
        $stmt->bind_param("issssii", $conferencia_id, $codigo, $nombre, $telefono_db, $correo_db, $activo, $id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: index.php?success=" . urlencode("Distrito actualizado exitosamente"));
        exit;
        
    } else {
        // CREAR NUEVO
        // Verificar código duplicado en la misma conferencia
        $stmt = $conexion->prepare("SELECT id FROM distritos WHERE codigo = ? AND conferencia_id = ?");
        $stmt->bind_param("si", $codigo, $conferencia_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            header("Location: crear.php?error=" . urlencode("El código ya existe en esta conferencia") 
                   . "&conferencia_id=" . $conferencia_id);
            exit;
        }
        $stmt->close();
        
        // Insertar
        $telefono_db = !empty($telefono) ? $telefono : null;
        $correo_db = !empty($correo) ? $correo : null;
        
        $stmt = $conexion->prepare("INSERT INTO distritos (conferencia_id, codigo, nombre, telefono, correo, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $conferencia_id, $codigo, $nombre, $telefono_db, $correo_db, $activo);
        $stmt->execute();
        
        $distrito_id = $conexion->insert_id;
        $stmt->close();
        
        // Redirigir a asignar supervisor
        header("Location: asignar_supervisor.php?id=" . $distrito_id . "&nuevo=1");
        exit;
    }
} catch (Exception $e) {
    error_log("Error al guardar distrito: " . $e->getMessage());
    $redirect = $id ? "editar.php?id=$id" : "crear.php";
    header("Location: $redirect&error=" . urlencode("Error: " . $e->getMessage()));
    exit;
}
