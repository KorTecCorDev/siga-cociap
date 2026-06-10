-- ============================================================
-- 019 — Competencias transversales por docente + cierre del tutor
-- ============================================================
-- A partir del II Bimestre las transversales (TIC/GAMA) las registra
-- CADA docente en su propia carga (criterios + notas, igual que sus
-- competencias). El tutor de la sección agrega conclusiones y CIERRA
-- el bimestre transversal; solo con cierre vigente aparecen TIC/GAMA
-- en las boletas (promedio de promedios por carga bloqueada).
--
-- Este script además:
--   1. Sella retroactivamente el B1 (cierre por sección cuya carga
--      transversal del tutor quedó totalmente bloqueada).
--   2. Migra las conclusiones B1 del tutor a la tabla nueva.
--   3. Desactiva las cargas transversales del tutor (los datos B1
--      siguen legibles vía la agregación de boleta).
--
-- Idempotente: CREATE IF NOT EXISTS, INSERT con guardas, UPDATE estable.
-- ============================================================

-- ── 1. Conclusiones descriptivas de competencias transversales ──
-- Una conclusión por alumno + competencia + bimestre, registrada por
-- el tutor desde la vista de Tutoría (independiente de las cargas).
CREATE TABLE IF NOT EXISTS conclusiones_transversales (
    id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id   INT UNSIGNED      NOT NULL,
    competencia_id SMALLINT UNSIGNED NOT NULL,
    periodo_id     SMALLINT UNSIGNED NOT NULL,
    conclusion     TEXT              NOT NULL,
    registrado_por INT UNSIGNED      NOT NULL,
    registrado_en  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificado_en  DATETIME          NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mat_comp_periodo (matricula_id, competencia_id, periodo_id),
    KEY idx_periodo (periodo_id),
    CONSTRAINT fk_ctrans_matricula   FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    CONSTRAINT fk_ctrans_competencia FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    CONSTRAINT fk_ctrans_periodo     FOREIGN KEY (periodo_id)     REFERENCES periodos(id),
    CONSTRAINT fk_ctrans_usuario     FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Cierres transversales por sección + bimestre ─────────────
-- El registro VIGENTE es el que tiene anulado_en IS NULL. Reabrir
-- (desbloquear) cualquier carga de la sección anula el cierre con traza.
CREATE TABLE IF NOT EXISTS cierres_transversales (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    seccion_id       SMALLINT UNSIGNED NOT NULL,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    cerrado_por      INT UNSIGNED      NOT NULL,
    cerrado_en       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anulado_en       DATETIME          NULL,
    anulado_por      INT UNSIGNED      NULL,
    motivo_anulacion VARCHAR(500)      NULL,
    KEY idx_seccion_periodo (seccion_id, periodo_id),
    CONSTRAINT fk_cierre_seccion  FOREIGN KEY (seccion_id)  REFERENCES secciones(id),
    CONSTRAINT fk_cierre_periodo  FOREIGN KEY (periodo_id)  REFERENCES periodos(id),
    CONSTRAINT fk_cierre_usuario  FOREIGN KEY (cerrado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_cierre_anulador FOREIGN KEY (anulado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Sellado retroactivo del I Bimestre ───────────────────────
-- Cierre para cada sección cuya carga transversal del tutor tiene
-- TODAS las competencias del área transversal bloqueadas en el B1
-- del año activo. Así las boletas B1 no pierden TIC/GAMA.
INSERT INTO cierres_transversales (seccion_id, periodo_id, cerrado_por)
SELECT s.id, p1.id, ca.docente_id
FROM secciones s
INNER JOIN cargas_academicas ca ON ca.seccion_id = s.id
INNER JOIN areas a              ON a.id = ca.area_id AND a.tipo = 'transversal'
INNER JOIN periodos p1          ON p1.numero = 1
                                AND p1.anio_id = (
                                    SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' LIMIT 1
                                )
WHERE (
        SELECT COUNT(DISTINCT bc.competencia_id)
        FROM bloqueos_competencia bc
        WHERE bc.carga_id = ca.id AND bc.periodo_id = p1.id
      ) >= (
        SELECT COUNT(*) FROM competencias c WHERE c.area_id = a.id
      )
  AND NOT EXISTS (
        SELECT 1 FROM cierres_transversales ct
        WHERE ct.seccion_id = s.id
          AND ct.periodo_id = p1.id
      );

-- ── 4. Migrar conclusiones B1 del tutor a la tabla nueva ────────
-- Las boletas leen las conclusiones transversales SOLO de
-- conclusiones_transversales; las del B1 vivían en calificaciones.
INSERT IGNORE INTO conclusiones_transversales
    (matricula_id, competencia_id, periodo_id, conclusion, registrado_por)
SELECT cal.matricula_id, cal.competencia_id, cal.periodo_id,
       cal.conclusion_descriptiva, cal.registrado_por
FROM calificaciones cal
INNER JOIN cargas_academicas ca ON ca.id = cal.carga_id
INNER JOIN areas a              ON a.id = ca.area_id AND a.tipo = 'transversal'
WHERE cal.conclusion_descriptiva IS NOT NULL
  AND cal.conclusion_descriptiva != '';

-- ── 5. Desactivar las cargas transversales del tutor ────────────
-- Desde B2 el registro es por docente; la carga del tutor ya no se usa
-- para ingresar notas. Sus datos B1 permanecen en BD y son legibles.
UPDATE cargas_academicas ca
INNER JOIN areas a ON a.id = ca.area_id AND a.tipo = 'transversal'
SET ca.estado = 'inactiva'
WHERE ca.estado = 'activa';
