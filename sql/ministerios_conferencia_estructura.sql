-- =====================================================
-- SISTEMA DE MINISTERIOS DE CONFERENCIA
-- Estructura para líderes de ministerio a nivel conferencia
-- Fecha: 22 de diciembre de 2025
-- =====================================================

-- =====================================================
-- Tabla 1: Cargos de Ministerio de Conferencia
-- =====================================================
CREATE TABLE IF NOT EXISTS `cargos_ministerio_conf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar cargos iniciales
INSERT INTO `cargos_ministerio_conf` (`id`, `nombre`, `descripcion`, `orden`, `activo`) VALUES
(1, 'Presidente', 'Presidente del ministerio de conferencia', 1, 1),
(2, 'Vicepresidente', 'Vicepresidente del ministerio de conferencia', 2, 1),
(3, 'Secretario', 'Secretario del ministerio de conferencia', 3, 1),
(4, 'Tesorero', 'Tesorero del ministerio de conferencia', 4, 1),
(5, 'Vocal 1', 'Primer vocal del ministerio', 5, 1),
(6, 'Vocal 2', 'Segundo vocal del ministerio', 6, 1);

-- =====================================================
-- Tabla 2: Líderes de Ministerio de Conferencia
-- =====================================================
CREATE TABLE IF NOT EXISTS `ministerios_conferencia_lideres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conferencia_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL COMMENT 'FK a areas_ministeriales (1=Damas, 2=Caballeros, 3=Jóvenes, etc.)',
  `miembro_id` int(11) NOT NULL COMMENT 'FK a miembros - El líder del ministerio',
  `cargo_id` int(11) NOT NULL COMMENT 'FK a cargos_ministerio_conf (1=Presidente, 2=Vice, etc.)',
  `periodo_id` int(11) DEFAULT NULL COMMENT 'FK a periodos_conferencia - Opcional',
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asignacion` (`conferencia_id`, `area_id`, `miembro_id`, `cargo_id`, `periodo_id`),
  KEY `idx_conferencia` (`conferencia_id`),
  KEY `idx_area` (`area_id`),
  KEY `idx_miembro` (`miembro_id`),
  KEY `idx_cargo` (`cargo_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_mcl_conferencia` FOREIGN KEY (`conferencia_id`) REFERENCES `conferencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mcl_area` FOREIGN KEY (`area_id`) REFERENCES `areas_ministeriales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mcl_miembro` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mcl_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos_ministerio_conf` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Tabla 3 (OPCIONAL - FUTURO): Líderes de Distrito
-- =====================================================
-- Esta tabla se puede crear después si necesitan nivel de distrito
/*
CREATE TABLE IF NOT EXISTS `ministerios_distrito_lideres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `distrito_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `miembro_id` int(11) NOT NULL,
  `cargo_id` int(11) NOT NULL,
  `periodo_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asignacion_distrito` (`distrito_id`, `area_id`, `miembro_id`, `cargo_id`, `periodo_id`),
  KEY `idx_distrito` (`distrito_id`),
  KEY `idx_area_distrito` (`area_id`),
  KEY `idx_miembro_distrito` (`miembro_id`),
  CONSTRAINT `fk_mdl_distrito` FOREIGN KEY (`distrito_id`) REFERENCES `distritos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mdl_area` FOREIGN KEY (`area_id`) REFERENCES `areas_ministeriales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mdl_miembro` FOREIGN KEY (`miembro_id`) REFERENCES `miembros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mdl_cargo` FOREIGN KEY (`cargo_id`) REFERENCES `cargos_ministerio_conf` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- =====================================================
-- DATOS DE PRUEBA (Ejemplo: Darling Kery como Presidente de Jóvenes)
-- =====================================================
-- Asumiendo:
-- - Conferencia ID = 1 (ajustar según tu BD)
-- - Área ID = 3 (Jóvenes)
-- - Miembro ID = 7 (Darling Javier Kery - ajustar según tu BD)
-- - Cargo ID = 1 (Presidente)

-- NOTA: Descomenta y ajusta los IDs según tu base de datos
/*
INSERT INTO `ministerios_conferencia_lideres` 
(`conferencia_id`, `area_id`, `miembro_id`, `cargo_id`, `periodo_id`, `activo`) 
VALUES 
(1, 3, 7, 1, NULL, 1);
*/

-- =====================================================
-- VISTAS ÚTILES (Opcional - Para consultas rápidas)
-- =====================================================

-- Vista 1: Líderes de conferencia con información completa
CREATE OR REPLACE VIEW `v_ministerios_conferencia_lideres` AS
SELECT 
    mcl.id,
    mcl.conferencia_id,
    c.nombre AS conferencia_nombre,
    c.codigo AS conferencia_codigo,
    mcl.area_id,
    am.nombre AS area_nombre,
    mcl.miembro_id,
    CONCAT(m.nombre, ' ', m.apellido) AS lider_nombre,
    m.telefono AS lider_telefono,
    i.nombre AS iglesia_nombre,
    i.codigo AS iglesia_codigo,
    mcl.cargo_id,
    cmc.nombre AS cargo_nombre,
    cmc.orden AS cargo_orden,
    mcl.activo,
    mcl.creado_en
FROM ministerios_conferencia_lideres mcl
INNER JOIN conferencias c ON mcl.conferencia_id = c.id
INNER JOIN areas_ministeriales am ON mcl.area_id = am.id
INNER JOIN miembros m ON mcl.miembro_id = m.id
INNER JOIN iglesias i ON m.iglesia_id = i.id
INNER JOIN cargos_ministerio_conf cmc ON mcl.cargo_id = cmc.id
WHERE mcl.activo = 1
ORDER BY c.nombre, am.nombre, cmc.orden;

-- Vista 2: Conteo de miembros por ministerio y conferencia
CREATE OR REPLACE VIEW `v_conteo_ministerios_conferencia` AS
SELECT 
    c.id AS conferencia_id,
    c.nombre AS conferencia_nombre,
    am.id AS area_id,
    am.nombre AS area_nombre,
    COUNT(DISTINCT m.id) AS total_miembros,
    COUNT(DISTINCT i.distrito_id) AS total_distritos,
    COUNT(DISTINCT m.iglesia_id) AS total_iglesias
FROM conferencias c
INNER JOIN distritos d ON d.conferencia_id = c.id
INNER JOIN iglesias i ON i.distrito_id = d.id
INNER JOIN miembros m ON m.iglesia_id = i.id
CROSS JOIN areas_ministeriales am
WHERE m.ministerio_id = am.id 
  AND m.estado = 'activo'
  AND i.activo = 1
  AND d.activo = 1
GROUP BY c.id, c.nombre, am.id, am.nombre;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================
-- Ya están incluidos en la definición de tablas arriba

-- =====================================================
-- PERMISOS Y COMENTARIOS
-- =====================================================
-- Esta estructura permite:
-- 1. Asignar múltiples líderes por ministerio (Presidente, Vice, Secretario, Tesorero)
-- 2. Un miembro puede ser líder de múltiples ministerios
-- 3. Un miembro puede tener múltiples cargos en diferentes ministerios
-- 4. Sistema de períodos opcional (NULL = permanente)
-- 5. Soft delete con campo 'activo'

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
