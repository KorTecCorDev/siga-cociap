-- ============================================================
-- 043_cierres_asistencia.sql
-- Cierre (aprobacion y bloqueo) del registro de asistencia por
-- seccion y bimestre. Espejo de cierres_conducta pero de UNA sola
-- etapa (Registro Academico); no hay etapa de tutor.
-- El cierre VIGENTE es el que tiene anulado_en IS NULL (sin UNIQUE:
-- la proteccion contra duplicados vive en getCierreVigente antes
-- de insertar, igual que en conducta).
-- Sin fila de inasistencias = 0 incidencias (estado valido), por lo
-- que el bloqueo NO exige completitud previa.
-- ============================================================

CREATE TABLE IF NOT EXISTS cierres_asistencia (
    id               INT      UNSIGNED NOT NULL AUTO_INCREMENT,
    seccion_id       SMALLINT UNSIGNED NOT NULL,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    ra_bloqueado_en  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ra_bloqueado_por INT      UNSIGNED NOT NULL,
    anulado_en       DATETIME          NULL,
    anulado_por      INT      UNSIGNED NULL,
    motivo_anulacion VARCHAR(500)      NULL,
    PRIMARY KEY (id),
    KEY idx_seccion_periodo (seccion_id, periodo_id),
    KEY idx_periodo (periodo_id),
    KEY idx_ra_por (ra_bloqueado_por),
    KEY idx_anulado_por (anulado_por),
    CONSTRAINT cierres_asistencia_ibfk_1 FOREIGN KEY (seccion_id)       REFERENCES secciones (id),
    CONSTRAINT cierres_asistencia_ibfk_2 FOREIGN KEY (periodo_id)       REFERENCES periodos  (id),
    CONSTRAINT cierres_asistencia_ibfk_3 FOREIGN KEY (ra_bloqueado_por) REFERENCES usuarios  (id),
    CONSTRAINT cierres_asistencia_ibfk_4 FOREIGN KEY (anulado_por)      REFERENCES usuarios  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
