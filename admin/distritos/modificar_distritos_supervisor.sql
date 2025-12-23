-- ============================================
-- MODIFICACIÓN TABLA DISTRITOS
-- Sistema Concilio - Supervisores de Distrito
-- ============================================

-- Agregar campo supervisor_id a distritos
ALTER TABLE `distritos` 
ADD COLUMN `supervisor_id` INT(11) NULL DEFAULT NULL AFTER `conferencia_id`,
ADD CONSTRAINT `fk_distritos_supervisor` 
    FOREIGN KEY (`supervisor_id`) REFERENCES `pastores`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Crear índice para búsquedas
ALTER TABLE `distritos` ADD INDEX `idx_supervisor` (`supervisor_id`);

-- ============================================
-- VISTA PARA DISTRITOS CON SUPERVISOR
-- ============================================
DROP VIEW IF EXISTS v_distritos_completo;

CREATE VIEW v_distritos_completo AS
SELECT 
    d.id,
    d.conferencia_id,
    d.codigo,
    d.nombre,
    d.supervisor_id,
    d.telefono,
    d.correo,
    d.activo,
    d.creado_en,
    -- Datos de la conferencia
    c.codigo AS conferencia_codigo,
    c.nombre AS conferencia_nombre,
    -- Datos del supervisor
    p.nombre AS supervisor_nombre,
    p.apellido AS supervisor_apellido,
    CONCAT(p.nombre, ' ', p.apellido) AS supervisor_completo,
    p.cedula AS supervisor_cedula,
    p.telefono AS supervisor_telefono,
    p.orden_ministerial,
    -- Estadísticas
    (SELECT COUNT(*) FROM iglesias i WHERE i.distrito_id = d.id AND i.activo = 1) AS total_iglesias,
    (SELECT COUNT(*) FROM pastores pas 
     INNER JOIN pastor_iglesias pi ON pas.id = pi.pastor_id 
     INNER JOIN iglesias ig ON pi.iglesia_id = ig.id 
     WHERE ig.distrito_id = d.id AND pi.activo = 1) AS total_pastores
FROM distritos d
INNER JOIN conferencias c ON d.conferencia_id = c.id
LEFT JOIN pastores p ON d.supervisor_id = p.id;

-- ============================================
-- TABLA HISTORIAL DE SUPERVISORES DE DISTRITO
-- ============================================
CREATE TABLE IF NOT EXISTS `distrito_supervisores_historial` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `distrito_id` INT(11) NOT NULL,
    `pastor_id` INT(11) NOT NULL,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NULL DEFAULT NULL,
    `motivo_fin` VARCHAR(255) NULL DEFAULT NULL,
    `observaciones` TEXT NULL DEFAULT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_distrito` (`distrito_id`),
    KEY `idx_pastor` (`pastor_id`),
    CONSTRAINT `fk_hist_sup_distrito` FOREIGN KEY (`distrito_id`) 
        REFERENCES `distritos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_hist_sup_pastor` FOREIGN KEY (`pastor_id`) 
        REFERENCES `pastores`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
