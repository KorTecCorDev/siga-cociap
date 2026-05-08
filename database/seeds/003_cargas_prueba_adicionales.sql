-- ============================================================
-- SIGA-COCIAP — Seed 003: Cargas adicionales para testing
-- Agrega al docente de prueba (DNI 12345678) cargas de distintos
-- tipos en 1° Secundaria A para cubrir todos los casos de boleta:
--
--   Áreas-curso  : DPCC, Educación Física
--   Con subáreas : Álgebra (Mat.), Historia (CC.SS.)
--
-- Idempotente: usa NOT EXISTS para no duplicar cargas.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ─── DPCC (área-curso) ───────────────────────────────────────
INSERT INTO cargas_academicas
    (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT u.id, s.id, aa.id, ar.id, 2, 'activa'
FROM usuarios u
INNER JOIN personas p         ON p.id  = u.persona_id  AND p.dni = '12345678'
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones s        ON s.nombre = 'A' AND s.anio_id = aa.id
INNER JOIN grados g           ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
INNER JOIN areas ar            ON ar.nivel_id = 2
                              AND ar.nombre = 'Desarrollo Personal, Ciudadanía y Cívica'
WHERE NOT EXISTS (
    SELECT 1 FROM cargas_academicas ca
    WHERE ca.docente_id = u.id AND ca.seccion_id = s.id
      AND ca.anio_id = aa.id  AND ca.area_id = ar.id
);

-- ─── Educación Física (área-curso) ───────────────────────────
INSERT INTO cargas_academicas
    (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT u.id, s.id, aa.id, ar.id, 2, 'activa'
FROM usuarios u
INNER JOIN personas p          ON p.id  = u.persona_id  AND p.dni = '12345678'
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones s         ON s.nombre = 'A' AND s.anio_id = aa.id
INNER JOIN grados g            ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
INNER JOIN areas ar             ON ar.nivel_id = 2 AND ar.nombre = 'Educación Física'
WHERE NOT EXISTS (
    SELECT 1 FROM cargas_academicas ca
    WHERE ca.docente_id = u.id AND ca.seccion_id = s.id
      AND ca.anio_id = aa.id  AND ca.area_id = ar.id
);

-- ─── Álgebra — subárea de Matemática (con_subareas) ──────────
INSERT INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT u.id, s.id, aa.id, sa.id, 3, 'activa'
FROM usuarios u
INNER JOIN personas p          ON p.id  = u.persona_id  AND p.dni = '12345678'
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones s         ON s.nombre = 'A' AND s.anio_id = aa.id
INNER JOIN grados g            ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
INNER JOIN areas ar             ON ar.nivel_id = 2 AND ar.nombre = 'Matemática'
INNER JOIN subareas sa          ON sa.area_id = ar.id AND sa.nombre = 'Álgebra'
WHERE NOT EXISTS (
    SELECT 1 FROM cargas_academicas ca
    WHERE ca.docente_id = u.id AND ca.seccion_id = s.id
      AND ca.anio_id = aa.id  AND ca.subarea_id = sa.id
);

-- ─── Historia — subárea de Ciencias Sociales (con_subareas) ──
INSERT INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT u.id, s.id, aa.id, sa.id, 2, 'activa'
FROM usuarios u
INNER JOIN personas p          ON p.id  = u.persona_id  AND p.dni = '12345678'
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones s         ON s.nombre = 'A' AND s.anio_id = aa.id
INNER JOIN grados g            ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
INNER JOIN areas ar             ON ar.nivel_id = 2 AND ar.nombre = 'Ciencias Sociales'
INNER JOIN subareas sa          ON sa.area_id = ar.id AND sa.nombre = 'Historia'
WHERE NOT EXISTS (
    SELECT 1 FROM cargas_academicas ca
    WHERE ca.docente_id = u.id AND ca.seccion_id = s.id
      AND ca.anio_id = aa.id  AND ca.subarea_id = sa.id
);

SET FOREIGN_KEY_CHECKS = 1;
