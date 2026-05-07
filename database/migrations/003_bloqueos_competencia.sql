-- ============================================================
-- SIGA-COCIAP — Migración 003
-- Tabla: bloqueos_competencia
-- ============================================================

CREATE TABLE IF NOT EXISTS bloqueos_competencia (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    carga_id        INT UNSIGNED NOT NULL,
    competencia_id  SMALLINT UNSIGNED NOT NULL,
    periodo_id      SMALLINT UNSIGNED NOT NULL,
    bloqueado_por   INT UNSIGNED NOT NULL,
    bloqueado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bloqueo (carga_id, competencia_id, periodo_id),
    FOREIGN KEY (carga_id)       REFERENCES cargas_academicas(id),
    FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    FOREIGN KEY (periodo_id)     REFERENCES periodos(id),
    FOREIGN KEY (bloqueado_por)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;