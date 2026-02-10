-- =====================================================
-- MÓDULO: Solicitudes de Membresía
-- Sistema Concilio - Multi-tenant
-- Fecha: 2026-02-09
-- =====================================================

-- Tabla principal de solicitudes
CREATE TABLE IF NOT EXISTS solicitudes_membresia (
    id INT(11) NOT NULL AUTO_INCREMENT,
    iglesia_id INT(11) NOT NULL,
    
    -- Datos personales (igual que miembros)
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    sexo ENUM('M','F') NOT NULL,
    fecha_nacimiento DATE NULL,
    nacionalidad_id INT(11) NULL,
    tipo_documento ENUM('cedula','pasaporte') DEFAULT 'cedula',
    numero_documento VARCHAR(30) NOT NULL, -- OBLIGATORIO en solicitudes
    telefono VARCHAR(30) NULL,
    direccion VARCHAR(255) NULL,
    email VARCHAR(150) NULL, -- Para notificar al solicitante
    
    -- Estado civil
    estado_civil ENUM('soltero','casado','union_libre','divorciado','viudo') DEFAULT 'soltero',
    
    -- Nivel de estudios
    nivel_estudio_id INT(11) NULL,
    carrera_id INT(11) NULL,
    
    -- Datos eclesiásticos básicos
    es_bautizado TINYINT(1) NOT NULL DEFAULT 0,
    fecha_bautismo DATE NULL,
    iglesia_bautismo VARCHAR(150) NULL, -- ¿Dónde fue bautizado?
    
    -- Control de solicitud
    estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision TIMESTAMP NULL,
    revisado_por INT(11) NULL, -- usuario_id que revisó
    motivo_rechazo TEXT NULL,
    observaciones TEXT NULL,
    
    -- IP y seguridad
    ip_solicitud VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    
    PRIMARY KEY (id),
    KEY idx_iglesia (iglesia_id),
    KEY idx_estado (estado),
    KEY idx_cedula (numero_documento),
    KEY idx_fecha (fecha_solicitud),
    
    CONSTRAINT fk_solicitud_iglesia FOREIGN KEY (iglesia_id) REFERENCES iglesias(id),
    CONSTRAINT fk_solicitud_nacionalidad FOREIGN KEY (nacionalidad_id) REFERENCES nacionalidades(id),
    CONSTRAINT fk_solicitud_nivel FOREIGN KEY (nivel_estudio_id) REFERENCES niveles_estudio(id),
    CONSTRAINT fk_solicitud_carrera FOREIGN KEY (carrera_id) REFERENCES carreras(id),
    CONSTRAINT fk_solicitud_revisor FOREIGN KEY (revisado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista para contar solicitudes pendientes por iglesia
CREATE OR REPLACE VIEW v_solicitudes_pendientes AS
SELECT 
    iglesia_id,
    COUNT(*) AS total_pendientes
FROM solicitudes_membresia
WHERE estado = 'pendiente'
GROUP BY iglesia_id;

-- Índice para búsqueda rápida de cédula duplicada
CREATE INDEX idx_solicitud_documento ON solicitudes_membresia(numero_documento, iglesia_id);
