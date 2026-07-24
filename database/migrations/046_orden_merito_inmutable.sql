-- ════════════════════════════════════════════════════════════════════
-- Migración 046: Inmutabilidad del orden de mérito oficial + versión rectificada
-- ════════════════════════════════════════════════════════════════════
-- Regla de negocio (rediseño del orden de mérito, Fase B):
--   El snapshot OFICIAL (orden_merito_snapshot) es INMUTABLE una vez que el
--   periodo ESTUVO PUBLICADO (compuerta 044). Cierres/reaperturas/rectificaciones
--   posteriores a la publicación NO tocan el oficial: generan una versión
--   RECTIFICADA no oficial, visible solo en el Centro de control.
--
-- Dos cambios, ambos ADITIVOS (no alteran datos existentes):
--
-- 1) periodos_publicacion.primera_publicacion_en — marca MONOTÓNICA del primer
--    momento en que el bimestre se hizo público. Se sella una sola vez y nunca
--    se limpia (ver PublicacionBoletaModel::publicar, COALESCE), así el candado
--    de inmutabilidad no se reabre aunque luego se reprograme, suspenda o
--    despublique. El "ahora" lo pone PHP (America/Lima), no MySQL; el backfill
--    de abajo usa NOW() solo para datos históricos, todos con fecha pasada.
--
-- 2) orden_merito_rectificado — misma forma que orden_merito_snapshot + `motivo`.
--    Guarda la ÚLTIMA versión no oficial por periodo (se sobrescribe en cada
--    nueva rectificación / re-cierre de un bimestre ya publicado). La traza por
--    criterio vive aparte en rectificaciones_calificacion.
--
-- MariaDB 10.4: ADD COLUMN IF NOT EXISTS + CREATE TABLE IF NOT EXISTS → idempotente.
-- Ejecutar DESPUÉS de 044_periodos_publicacion.sql y 023_orden_merito_snapshot.sql.
-- ════════════════════════════════════════════════════════════════════

-- 1) Marca monotónica de primera publicación ─────────────────────────
ALTER TABLE periodos_publicacion
    ADD COLUMN IF NOT EXISTS primera_publicacion_en DATETIME NULL AFTER publica_en;

-- Backfill: todo lo que YA se publicó (publica_en en el pasado) queda sellado.
-- Las publicaciones PROGRAMADAS a futuro (publica_en > ahora) siguen NULL: aún
-- no fueron públicas. NOW() es válido aquí porque estos datos son históricos.
UPDATE periodos_publicacion
SET primera_publicacion_en = publica_en
WHERE primera_publicacion_en IS NULL
  AND publica_en <= NOW();

-- 2) Versión rectificada (no oficial) del orden de mérito ─────────────
CREATE TABLE IF NOT EXISTS orden_merito_rectificado (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    matricula_id     INT UNSIGNED NOT NULL,
    grado_id         INT UNSIGNED NOT NULL,
    seccion_id       INT UNSIGNED NULL,
    puesto_grado     SMALLINT UNSIGNED NOT NULL,
    puesto_seccion   SMALLINT UNSIGNED NULL,
    num_competencias SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_notas      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    promedio_general DECIMAL(5,2)  NULL,
    promedio_exacto  DECIMAL(12,8) NULL,
    num_c            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_b            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_ad           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_alto         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_16           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Diferencia con el snapshot oficial: motivo del recálculo no oficial.
    motivo           VARCHAR(500) NULL,
    generado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generado_por     INT UNSIGNED NULL,
    UNIQUE KEY uq_periodo_matricula (periodo_id, matricula_id),
    INDEX idx_periodo_grado   (periodo_id, grado_id, puesto_grado),
    INDEX idx_periodo_seccion (periodo_id, seccion_id, puesto_seccion),
    CONSTRAINT fk_omr_periodo   FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    CONSTRAINT fk_omr_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    CONSTRAINT fk_omr_generado  FOREIGN KEY (generado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
