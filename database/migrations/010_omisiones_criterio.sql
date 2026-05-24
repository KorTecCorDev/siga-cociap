-- 010_omisiones_criterio.sql
-- Registra el motivo por el que un alumno no fue evaluado en un criterio.
-- La omision persiste aunque el criterio sea soft-deleted (para auditoría).

CREATE TABLE IF NOT EXISTS omisiones_criterio (
    id               INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
    criterio_id      INT UNSIGNED  NOT NULL,
    matricula_id     INT UNSIGNED  NOT NULL,
    motivo           ENUM(
                         'ausencia_injustificada',
                         'ausencia_justificada',
                         'abandono',
                         'no_aplico',
                         'exonerado'
                     )             NOT NULL,
    registrado_en    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registrado_por   INT UNSIGNED  NOT NULL,
    UNIQUE KEY uq_criterio_matricula (criterio_id, matricula_id),
    KEY idx_matricula (matricula_id),
    FOREIGN KEY (criterio_id)    REFERENCES criterios(id),
    FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
