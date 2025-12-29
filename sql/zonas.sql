-- =============================================
-- MÓDULO ZONAS/GRUPOS - Sistema Concilio
-- Permite a cada iglesia organizar miembros por zonas geográficas o grupos
-- =============================================

-- Tabla de Zonas
CREATE TABLE IF NOT EXISTS zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    iglesia_id INT NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (iglesia_id) REFERENCES iglesias(id) ON DELETE CASCADE,
    
    INDEX idx_iglesia (iglesia_id),
    INDEX idx_codigo (codigo),
    INDEX idx_activo (activo),
    UNIQUE KEY uk_iglesia_codigo (iglesia_id, codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campo zona_id a miembros
ALTER TABLE miembros ADD COLUMN zona_id INT NULL AFTER familia_id;
ALTER TABLE miembros ADD CONSTRAINT fk_miembros_zona FOREIGN KEY (zona_id) REFERENCES zonas(id) ON DELETE SET NULL;
ALTER TABLE miembros ADD INDEX idx_zona (zona_id);
