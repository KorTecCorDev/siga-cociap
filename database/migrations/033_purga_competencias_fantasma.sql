-- ════════════════════════════════════════════════════════════════════
-- Migración 033: Purga de competencias "fantasma" en boletas
-- ════════════════════════════════════════════════════════════════════
-- SÍNTOMA (detectado en 2°B Primaria, área Arte y Cultura, B1):
--   La competencia C19 "Aprecia de manera crítica..." aparecía en TODAS las
--   boletas de la sección pese a que la tutora solo evaluó C20 "Crea proyectos".
--
-- CAUSA: al abandonar una competencia (borrar sus criterios tras haber
--   evaluado), el sistema NO limpió su promedio (`calificaciones`) ni su
--   `bloqueo`. Quedó una competencia BLOQUEADA + CALIFICADA pero con CERO
--   criterios vivos — un estado imposible bajo los invariantes. Como
--   `getBoletaAlumno` incluye una competencia por (calificaciones ∩ bloqueos)
--   sin mirar los criterios, el huérfano se mostraba en la boleta y en el
--   export SIAGIE.
--
-- DOBLE REMEDIACIÓN:
--   * Código: `getBoletaAlumno` ahora exige AL MENOS UN criterio vivo y
--     confirmado (blinda la boleta contra cualquier huérfano futuro).
--   * Datos (esta migración): purga el estado huérfano para restaurar la
--     competencia a "no evaluada".
--
-- DINÁMICA (sin IDs hardcodeados): el fantasma se detecta por el patrón
--   BLOQUEO + CALIFICACIONES + 0 criterios vivos. Se materializa el conjunto
--   afectado en una tabla temporal y se borra de las tres tablas.
-- IDEMPOTENTE: tras correr, ningún (carga,competencia,periodo) cumple el
--   patrón, así que una segunda corrida no borra nada.
--
-- ⚠️ ANTES DE APLICAR EN PRODUCCIÓN: correr el PREVIEW de más abajo para ver
--   exactamente qué competencias afectará. En local el escaneo dio 1 sola
--   (2°B Arte C19). Ejecutar con conexión utf8mb4.
-- Ejecutar DESPUÉS de 032_area_tutoria.sql.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto primero en prod: ──
--   SELECT b.carga_id, b.competencia_id, b.periodo_id,
--          (SELECT COUNT(*) FROM calificaciones c
--             WHERE c.carga_id=b.carga_id AND c.competencia_id=b.competencia_id
--               AND c.periodo_id=b.periodo_id) AS calificaciones,
--          (SELECT COUNT(*) FROM bloqueos_competencia bb
--             WHERE bb.carga_id=b.carga_id AND bb.competencia_id=b.competencia_id
--               AND bb.periodo_id=b.periodo_id) AS bloqueos
--   FROM (SELECT DISTINCT carga_id, competencia_id, periodo_id
--         FROM bloqueos_competencia) b
--   WHERE EXISTS (SELECT 1 FROM calificaciones c
--                 WHERE c.carga_id=b.carga_id AND c.competencia_id=b.competencia_id
--                   AND c.periodo_id=b.periodo_id)
--     AND NOT EXISTS (SELECT 1 FROM criterios cr
--                     WHERE cr.carga_id=b.carga_id AND cr.competencia_id=b.competencia_id
--                       AND cr.periodo_id=b.periodo_id AND cr.eliminado_en IS NULL);

-- 1. Materializar el conjunto de competencias fantasma (patrón, no IDs).
DROP TEMPORARY TABLE IF EXISTS tmp_competencias_fantasma;
CREATE TEMPORARY TABLE tmp_competencias_fantasma AS
SELECT DISTINCT b.carga_id, b.competencia_id, b.periodo_id
FROM bloqueos_competencia b
WHERE EXISTS (
        SELECT 1 FROM calificaciones c
        WHERE c.carga_id       = b.carga_id
          AND c.competencia_id = b.competencia_id
          AND c.periodo_id     = b.periodo_id
      )
  AND NOT EXISTS (
        SELECT 1 FROM criterios cr
        WHERE cr.carga_id       = b.carga_id
          AND cr.competencia_id = b.competencia_id
          AND cr.periodo_id     = b.periodo_id
          AND cr.eliminado_en   IS NULL
      );

-- 2. Borrar las notas por criterio huérfanas (de los criterios ya borrados
--    de esas competencias).
DELETE cc FROM calificaciones_criterio cc
INNER JOIN criterios cr ON cr.id = cc.criterio_id
INNER JOIN tmp_competencias_fantasma g
        ON g.carga_id       = cr.carga_id
       AND g.competencia_id = cr.competencia_id
       AND g.periodo_id     = cr.periodo_id;

-- 3. Borrar los promedios huérfanos (restaura el invariante
--    "fila en calificaciones ⟺ nota viva").
DELETE cal FROM calificaciones cal
INNER JOIN tmp_competencias_fantasma g
        ON g.carga_id       = cal.carga_id
       AND g.competencia_id = cal.competencia_id
       AND g.periodo_id     = cal.periodo_id;

-- 4. Borrar el bloqueo huérfano.
DELETE b FROM bloqueos_competencia b
INNER JOIN tmp_competencias_fantasma g
        ON g.carga_id       = b.carga_id
       AND g.competencia_id = b.competencia_id
       AND g.periodo_id     = b.periodo_id;

DROP TEMPORARY TABLE IF EXISTS tmp_competencias_fantasma;

-- Verificación (debe devolver 0):
--   SELECT COUNT(*) FROM (SELECT DISTINCT carga_id, competencia_id, periodo_id
--     FROM bloqueos_competencia) b
--   WHERE EXISTS (SELECT 1 FROM calificaciones c WHERE c.carga_id=b.carga_id
--       AND c.competencia_id=b.competencia_id AND c.periodo_id=b.periodo_id)
--     AND NOT EXISTS (SELECT 1 FROM criterios cr WHERE cr.carga_id=b.carga_id
--       AND cr.competencia_id=b.competencia_id AND cr.periodo_id=b.periodo_id
--       AND cr.eliminado_en IS NULL);
