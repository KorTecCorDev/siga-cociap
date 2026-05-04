-- ============================================================
-- SIGA-COCIAP — Migración 002
-- Agrega tablas: criterios y calificaciones_criterio
-- Fecha: 2026-05-04
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS criterios (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    carga_id        INT UNSIGNED NOT NULL,
    competencia_id  SMALLINT UNSIGNED NOT NULL,
    periodo_id      SMALLINT UNSIGNED NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    orden           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carga_id)       REFERENCES cargas_academicas(id) ON DELETE CASCADE,
    FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    FOREIGN KEY (periodo_id)     REFERENCES periodos(id),
    INDEX idx_carga_competencia_periodo (carga_id, competencia_id, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calificaciones_criterio (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    criterio_id     INT UNSIGNED NOT NULL,
    matricula_id    INT UNSIGNED NOT NULL,
    nota            TINYINT UNSIGNED NOT NULL,
    registrado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificado_en   DATETIME NULL,
    UNIQUE KEY uq_criterio_matricula (criterio_id, matricula_id),
    CONSTRAINT chk_nota_criterio CHECK (nota BETWEEN 0 AND 20),
    FOREIGN KEY (criterio_id)  REFERENCES criterios(id) ON DELETE CASCADE,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    INDEX idx_criterio  (criterio_id),
    INDEX idx_matricula (matricula_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;