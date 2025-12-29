-- =============================================
-- TABLA DE VISITAS - Sistema Concilio
-- Fecha: 2025-12-28
-- =============================================

-- Eliminar tabla si existe (solo para desarrollo)
-- DROP TABLE IF EXISTS visitas;

CREATE TABLE IF NOT EXISTS `visitas` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `iglesia_id` INT(11) NOT NULL COMMENT 'FK a iglesias',
    `nombre` VARCHAR(100) NOT NULL,
    `apellido` VARCHAR(100) NOT NULL,
    `sexo` ENUM('M', 'F') NOT NULL COMMENT 'M=Masculino, F=Femenino',
    `nacionalidad_id` INT(11) DEFAULT NULL COMMENT 'FK a nacionalidades',
    `telefono` VARCHAR(20) DEFAULT NULL,
    `categoria` ENUM('damas', 'caballeros', 'jovenes', 'jovencitos', 'ninos') NOT NULL COMMENT 'Grupo al que pertenece',
    `invitado_por` INT(11) DEFAULT NULL COMMENT 'FK a miembros - quién lo invitó',
    `fecha_visita` DATE NOT NULL COMMENT 'Fecha de primera visita',
    `observaciones` TEXT DEFAULT NULL COMMENT 'Notas adicionales',
    `convertido_miembro` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=No, 1=Ya es miembro',
    `miembro_id` INT(11) DEFAULT NULL COMMENT 'FK a miembros - si fue convertido',
    `fecha_conversion` DATE DEFAULT NULL COMMENT 'Fecha en que se convirtió a miembro',
    `estado` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    `usuario_registro` INT(11) DEFAULT NULL COMMENT 'Usuario que registró',
    `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_visitas_iglesia` (`iglesia_id`),
    KEY `idx_visitas_invitado` (`invitado_por`),
    KEY `idx_visitas_miembro` (`miembro_id`),
    KEY `idx_visitas_categoria` (`categoria`),
    KEY `idx_visitas_estado` (`estado`),
    KEY `idx_visitas_fecha` (`fecha_visita`),
    CONSTRAINT `fk_visitas_iglesia` FOREIGN KEY (`iglesia_id`) REFERENCES `iglesias` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_visitas_invitado` FOREIGN KEY (`invitado_por`) REFERENCES `miembros` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_visitas_miembro` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_visitas_nacionalidad` FOREIGN KEY (`nacionalidad_id`) REFERENCES `nacionalidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de visitas a las iglesias';

-- Índice para búsquedas por nombre
ALTER TABLE `visitas` ADD FULLTEXT KEY `idx_visitas_nombre` (`nombre`, `apellido`);
