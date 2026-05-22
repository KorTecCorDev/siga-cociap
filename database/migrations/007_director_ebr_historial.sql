-- ============================================================
-- Migración 007 — Historial de Director EBR
-- Registra quién ocupó el cargo de Director EBR en cada año
-- académico y en qué fechas, permitiendo cambios mid-year con
-- trazabilidad completa.
-- ============================================================

CREATE TABLE IF NOT EXISTS director_ebr_historial (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id   INT UNSIGNED    NOT NULL,
    anio_id      SMALLINT UNSIGNED NOT NULL,
    desde        DATE            NOT NULL,
    hasta        DATE            NULL COMMENT 'NULL = vigente actualmente',
    asignado_por INT UNSIGNED    NOT NULL,
    asignado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_anio_vigente (anio_id, hasta),
    INDEX idx_anio_desde   (anio_id, desde),
    CONSTRAINT fk_deh_usuario     FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
    CONSTRAINT fk_deh_anio        FOREIGN KEY (anio_id)      REFERENCES anios_academicos(id),
    CONSTRAINT fk_deh_asignado_por FOREIGN KEY (asignado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
