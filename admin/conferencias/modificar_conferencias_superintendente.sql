-- ============================================
-- MODIFICACIÓN TABLA CONFERENCIAS
-- Sistema Concilio - Superintendentes
-- ============================================

-- Agregar campo superintendente_id a conferencias
ALTER TABLE `conferencias` 
ADD COLUMN `superintendente_id` INT(11) NULL DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_conferencias_superintendente` 
    FOREIGN KEY (`superintendente_id`) REFERENCES `pastores`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Crear índice para búsquedas
ALTER TABLE `conferencias` ADD INDEX `idx_superintendente` (`superintendente_id`);

-- ============================================
-- VISTA PARA CONFERENCIAS CON SUPERINTENDENTE
-- ============================================
DROP VIEW IF EXISTS v_conferencias_completo;

CREATE VIEW v_conferencias_completo AS
SELECT 
    c.id,
    c.codigo,
    c.nombre,
    c.superintendente_id,
    c.telefono,
    c.correo,
    c.activo,
    c.creado_en,
    -- Datos del superintendente
    p.nombre AS superintendente_nombre,
    p.apellido AS superintendente_apellido,
    CONCAT(p.nombre, ' ', p.apellido) AS superintendente_completo,
    p.cedula AS superintendente_cedula,
    p.telefono AS superintendente_telefono,
    p.orden_ministerial,
    -- Estadísticas
    (SELECT COUNT(*) FROM distritos d WHERE d.conferencia_id = c.id AND d.activo = 1) AS total_distritos,
    (SELECT COUNT(*) FROM iglesias i 
     INNER JOIN distritos d ON i.distrito_id = d.id 
     WHERE d.conferencia_id = c.id AND i.activo = 1) AS total_iglesias,
    (SELECT COUNT(*) FROM pastores pas WHERE pas.conferencia_id = c.id) AS total_pastores
FROM conferencias c
LEFT JOIN pastores p ON c.superintendente_id = p.id;

-- ============================================
-- TABLA HISTORIAL DE SUPERINTENDENTES
-- ============================================
CREATE TABLE IF NOT EXISTS `conferencia_superintendentes_historial` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `conferencia_id` INT(11) NOT NULL,
    `pastor_id` INT(11) NOT NULL,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NULL DEFAULT NULL,
    `motivo_fin` VARCHAR(255) NULL DEFAULT NULL,
    `observaciones` TEXT NULL DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_conferencia` (`conferencia_id`),
    KEY `idx_pastor` (`pastor_id`),
    CONSTRAINT `fk_hist_super_conferencia` FOREIGN KEY (`conferencia_id`) 
        REFERENCES `conferencias`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_super_pastor` FOREIGN KEY (`pastor_id`) 
        REFERENCES `pastores`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
