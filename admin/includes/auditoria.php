<?php
/**
 * SISTEMA DE AUDITORÍA
 * Sistema Concilio - Registro de actividades del sistema
 * 
 * Uso:
 *   require_once 'includes/auditoria.php';
 *   registrar_auditoria('crear', 'miembros', 'miembros', $id, 'Nuevo miembro creado', null, $datos);
 */

/**
 * Registra una acción en el log de auditoría
 * 
 * @param string $accion Tipo de acción: login, logout, login_fallido, crear, editar, eliminar, asignar, quitar
 * @param string $modulo Módulo del sistema: autenticacion, usuarios, miembros, finanzas, ministerios, iglesias, etc.
 * @param string $tabla Nombre de la tabla afectada (opcional)
 * @param int $registro_id ID del registro afectado (opcional)
 * @param string $descripcion Descripción legible de la acción
 * @param array|null $datos_antes Estado anterior del registro (para editar/eliminar)
 * @param array|null $datos_despues Estado nuevo del registro (para crear/editar)
 * @return bool True si se registró correctamente
 */
function registrar_auditoria($accion, $modulo, $tabla = null, $registro_id = null, $descripcion = '', $datos_antes = null, $datos_despues = null) {
    global $conexion;
    
    // Obtener datos del usuario de la sesión
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    $usuario_nombre = '';
    
    if (isset($_SESSION['usuario_nombre']) && isset($_SESSION['usuario_apellido'])) {
        $usuario_nombre = $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'];
    } elseif (isset($_SESSION['usuario_nombre'])) {
        $usuario_nombre = $_SESSION['usuario_nombre'];
    }
    
    // Obtener IP del cliente
    $ip_address = obtener_ip_cliente();
    
    // Obtener User Agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    
    // Convertir arrays a JSON
    $datos_antes_json = $datos_antes ? json_encode($datos_antes, JSON_UNESCAPED_UNICODE) : null;
    $datos_despues_json = $datos_despues ? json_encode($datos_despues, JSON_UNESCAPED_UNICODE) : null;
    
    try {
        $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, tabla_afectada, registro_id, descripcion, datos_antes, datos_despues, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issssssssss", 
            $usuario_id, 
            $usuario_nombre, 
            $accion, 
            $modulo, 
            $tabla, 
            $registro_id, 
            $descripcion, 
            $datos_antes_json, 
            $datos_despues_json, 
            $ip_address, 
            $user_agent
        );
        
        $resultado = $stmt->execute();
        $stmt->close();
        
        return $resultado;
        
    } catch (Exception $e) {
        // Loguear error pero no interrumpir la operación principal
        error_log("Error al registrar auditoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la IP real del cliente (considerando proxies)
 */
function obtener_ip_cliente() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Puede contener múltiples IPs separadas por coma
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Registra un login exitoso
 */
function auditoria_login($usuario_id, $usuario_nombre) {
    global $conexion;
    
    $ip = obtener_ip_cliente();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    
    $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address, user_agent) 
            VALUES (?, ?, 'login', 'autenticacion', 'Inicio de sesión exitoso', ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isss", $usuario_id, $usuario_nombre, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Registra un intento de login fallido
 */
function auditoria_login_fallido($usuario_intento) {
    global $conexion;
    
    $ip = obtener_ip_cliente();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    $descripcion = "Intento de login fallido con usuario: " . $usuario_intento;
    
    $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address, user_agent) 
            VALUES (NULL, ?, 'login_fallido', 'autenticacion', ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", $usuario_intento, $descripcion, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Registra un logout
 */
function auditoria_logout() {
    if (!isset($_SESSION['usuario_id'])) return;
    
    global $conexion;
    
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_nombre = ($_SESSION['usuario_nombre'] ?? '') . ' ' . ($_SESSION['usuario_apellido'] ?? '');
    $ip = obtener_ip_cliente();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
    
    $sql = "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address, user_agent) 
            VALUES (?, ?, 'logout', 'autenticacion', 'Cierre de sesión', ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isss", $usuario_id, $usuario_nombre, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

/**
 * Funciones de conveniencia para cada tipo de acción
 */
function auditoria_crear($modulo, $tabla, $registro_id, $descripcion, $datos_nuevos = null) {
    return registrar_auditoria('crear', $modulo, $tabla, $registro_id, $descripcion, null, $datos_nuevos);
}

function auditoria_editar($modulo, $tabla, $registro_id, $descripcion, $datos_antes = null, $datos_despues = null) {
    return registrar_auditoria('editar', $modulo, $tabla, $registro_id, $descripcion, $datos_antes, $datos_despues);
}

function auditoria_eliminar($modulo, $tabla, $registro_id, $descripcion, $datos_antes = null) {
    return registrar_auditoria('eliminar', $modulo, $tabla, $registro_id, $descripcion, $datos_antes, null);
}

function auditoria_asignar($modulo, $tabla, $registro_id, $descripcion, $datos = null) {
    return registrar_auditoria('asignar', $modulo, $tabla, $registro_id, $descripcion, null, $datos);
}

function auditoria_quitar($modulo, $tabla, $registro_id, $descripcion, $datos = null) {
    return registrar_auditoria('quitar', $modulo, $tabla, $registro_id, $descripcion, $datos, null);
}
