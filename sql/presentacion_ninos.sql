-- =====================================================
-- TABLA: PRESENTACIÓN DE NIÑOS AL SEÑOR
-- Sistema Concilio - Módulo de Registros
-- Fecha: 29 de diciembre de 2025
-- =====================================================

CREATE TABLE IF NOT EXISTS `presentacion_ninos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `iglesia_id` INT(11) NOT NULL,
    `numero_acta` VARCHAR(20) NOT NULL COMMENT 'Formato: 001-2025',
    
    -- Datos del niño (solo nombre y fecha_nacimiento obligatorios)
    `nombres` VARCHAR(100) NOT NULL,
    `apellidos` VARCHAR(100) DEFAULT NULL,
    `fecha_nacimiento` DATE NOT NULL,
    `sexo` ENUM('M', 'F') DEFAULT 'M',
    `nacionalidad` VARCHAR(50) DEFAULT 'Dominicana',
    
    -- Padres (opcionales - si vacío, línea para firma en acta)
    `nombre_padre` VARCHAR(100) DEFAULT NULL,
    `nombre_madre` VARCHAR(100) DEFAULT NULL,
    
    -- Datos del acto
    `fecha_presentacion` DATE NOT NULL,
    `lugar` VARCHAR(200) DEFAULT NULL COMMENT 'Por defecto: nombre de la iglesia',
    `ministro` VARCHAR(100) DEFAULT NULL COMMENT 'Quien ofició - texto libre',
    `testigo1` VARCHAR(100) DEFAULT NULL,
    `testigo2` VARCHAR(100) DEFAULT NULL,
    
    -- Datos registro civil (todos opcionales)
    `libro_no` VARCHAR(20) DEFAULT NULL,
    `folio` VARCHAR(20) DEFAULT NULL,
    `acta_civil_no` VARCHAR(20) DEFAULT NULL,
    `acta_civil_anio` YEAR DEFAULT NULL,
    `oficilia_civil` VARCHAR(100) DEFAULT NULL,
    
    -- Observaciones
    `observaciones` TEXT DEFAULT NULL,
    
    -- Control
    `estado` ENUM('activo', 'anulado') DEFAULT 'activo',
    `creado_por` INT(11) DEFAULT NULL,
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_acta_iglesia` (`iglesia_id`, `numero_acta`),
    KEY `idx_iglesia` (`iglesia_id`),
    KEY `idx_fecha_presentacion` (`fecha_presentacion`),
    KEY `idx_estado` (`estado`),
    KEY `idx_nombres` (`nombres`, `apellidos`),
    
    CONSTRAINT `fk_presentacion_iglesia` FOREIGN KEY (`iglesia_id`) 
        REFERENCES `iglesias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_presentacion_usuario` FOREIGN KEY (`creado_por`) 
        REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ÍNDICE PARA BÚSQUEDAS POR AÑO
-- =====================================================
-- Ya incluido en idx_fecha_presentacion
