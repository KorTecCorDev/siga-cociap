-- ============================================================
-- 009_inasistencias.sql
-- Registro agregado de incidencias de asistencia por bimestre:
-- faltas, faltas justificadas, tardanzas, tardanzas justificadas.
-- Una fila por (matricula_id, periodo_id). Si no hay fila, todos
-- los contadores se consideran cero (no registro pendiente).
-- ============================================================

CREATE TABLE IF NOT EXISTS inasistencias (
    id                       INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    matricula_id             INT UNSIGNED      NOT NULL,
    periodo_id               SMALLINT UNSIGNED NOT NULL,
    faltas                   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    faltas_justificadas      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    tardanzas                SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    tardanzas_justificadas   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    registrado_por           INT UNSIGNED      NOT NULL,
    registrado_en            DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificado_en            DATETIME          NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_matricula_periodo (matricula_id, periodo_id),
    KEY idx_periodo        (periodo_id),
    KEY idx_registrado_por (registrado_por),
    CONSTRAINT inasistencias_ibfk_1 FOREIGN KEY (matricula_id)   REFERENCES matriculas (id),
    CONSTRAINT inasistencias_ibfk_2 FOREIGN KEY (periodo_id)     REFERENCES periodos   (id),
    CONSTRAINT inasistencias_ibfk_3 FOREIGN KEY (registrado_por) REFERENCES usuarios   (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
