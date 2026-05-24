-- 011_exoneraciones.sql
-- Exoneraciones anuales de alumnos de áreas o subáreas específicas.
-- area_id XOR subarea_id: exactamente uno debe estar presente (validado en aplicación).
-- La unicidad (matricula + año + area/subarea) se controla a nivel de aplicación
-- porque MySQL trata cada NULL como distinto en índices UNIQUE.

CREATE TABLE IF NOT EXISTS exoneraciones (
    id              INT UNSIGNED      PRIMARY KEY AUTO_INCREMENT,
    matricula_id    INT UNSIGNED      NOT NULL,
    anio_id         SMALLINT UNSIGNED NOT NULL,
    area_id         SMALLINT UNSIGNED NULL,
    subarea_id      SMALLINT UNSIGNED NULL,
    motivo          VARCHAR(255)      NOT NULL DEFAULT '',
    registrado_en   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    registrado_por  INT UNSIGNED      NOT NULL,
    revocado_en     DATETIME          NULL,
    revocado_por    INT UNSIGNED      NULL,
    KEY idx_mat_anio  (matricula_id, anio_id),
    KEY idx_area      (area_id),
    KEY idx_subarea   (subarea_id),
    FOREIGN KEY (matricula_id)  REFERENCES matriculas(id),
    FOREIGN KEY (anio_id)       REFERENCES anios_academicos(id),
    FOREIGN KEY (area_id)       REFERENCES areas(id),
    FOREIGN KEY (subarea_id)    REFERENCES subareas(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    FOREIGN KEY (revocado_por)   REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eliminar 'exonerado' del ENUM de omisiones_criterio.
-- Las exoneraciones se gestionan ahora en la tabla exoneraciones.
ALTER TABLE omisiones_criterio
    MODIFY COLUMN motivo ENUM(
        'ausencia_injustificada',
        'ausencia_justificada',
        'abandono',
        'no_aplico'
    ) NOT NULL;
