-- =============================================================================
-- Seed 003 — Escenarios de boleta para presentación al comité directivo
-- Colegio de Aplicación "Víctor Valenzuela Guardia" — UNASAM, mayo 2026
-- =============================================================================
-- Reestructurado para backup_18_05_2026.sql
-- EJECUTAR UNA SOLA VEZ sobre la BD restaurada desde ese backup.
--
-- Escenarios cubiertos:
--   E1  1° Primaria A  (sec 1,  unidocente user 21)
--   E2  1° Secundaria A (sec 13, cargas reales ya en backup + Economía/Ed.Rel.)
--   E3  4° Secundaria A (sec 20, Arte=Raz.Mat., alias Ed.Rel./EPT)
--
-- Alumno de demo por escenario:
--   E1 → matricula_id = 1   (notas curadas: AD→A→B→C)
--   E2 → matricula_id = 78  (notas REALES ingresadas por docentes el 16/17-may)
--   E3 → matricula_id = 106 (notas curadas: AD→A→B→C)
--
-- URLs de boleta (periodo_id = 1):
--   /boleta/{mat_id}/1          ← imprimible A4
--   /boleta/digital/{mat_id}/1  ← digital mobile
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFICAR estado de la BD antes de continuar (diagnóstico rápido)
-- ─────────────────────────────────────────────────────────────────────────────
-- SELECT 'Sec 1 tutor=' AS info, tutor_id FROM secciones WHERE id=1;
-- SELECT 'Carga 44 docente=' AS info, docente_id FROM cargas_academicas WHERE id=44;
-- SELECT 'Cargas sec 1=' AS info, COUNT(*) FROM cargas_academicas WHERE seccion_id=1;


-- =============================================================================
-- E1: 1° PRIMARIA A — unidocente user 21, escala solo literal (AD/A/B/C)
-- Sección: 1 | Matriculas: 1–5
-- Estado backup: tutor_id=21 ✓, carga 44 (transversal area 9) ✓
--                Faltan: todas las demás cargas de sec 1
-- =============================================================================

-- La sección 1 ya tiene tutor_id=21 desde el backup_18_05_2026.sql.
-- No se necesita UPDATE secciones.

-- Carga transversal ya existe como carga 44 (docente 21, sec 1, area 9).
SET @c1_transv = 44;

-- Insertar cargas faltantes (docente=21, unidocente).
-- WHERE NOT EXISTS previene duplicados porque cargas_academicas no tiene UNIQUE KEY.

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, NULL, 1, 4, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=1  AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, NULL, 2, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=2  AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, NULL, 3, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=3  AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, NULL, 4, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=4  AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, NULL, 5, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=5  AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 1, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=1);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 2, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=2);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 3, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=3);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 4, NULL, 4, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=4);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 5, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=5);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 6, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=6);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 7, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=7);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 8, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=8);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 9, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=9);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 21, 1, 1, 10, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=10);

-- Resolver IDs de cargas sec 1
SET @c1_ps    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=1  AND subarea_id IS NULL LIMIT 1);
SET @c1_ef    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=2  AND subarea_id IS NULL LIMIT 1);
SET @c1_arte  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=3  AND subarea_id IS NULL LIMIT 1);
SET @c1_ing   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=4  AND subarea_id IS NULL LIMIT 1);
SET @c1_rel   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=5  AND subarea_id IS NULL LIMIT 1);
SET @c1_com   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=1  LIMIT 1);
SET @c1_pl    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=2  LIMIT 1);
SET @c1_rv    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=3  LIMIT 1);
SET @c1_arit  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=4  LIMIT 1);
SET @c1_alg   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=5  LIMIT 1);
SET @c1_geom  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=6  LIMIT 1);
SET @c1_rm    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=7  LIMIT 1);
SET @c1_quim  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=8  LIMIT 1);
SET @c1_bio   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=9  LIMIT 1);
SET @c1_fis   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=10 LIMIT 1);

-- Criterios E1 — 2 por competencia, 1 para transversales
-- (sec 1 no tiene datos previos → INSERT directo es seguro)
INSERT INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    (@c1_ps, 4,1,'Práctica calificada',1),(@c1_ps, 4,1,'Examen bimestral',2),
    (@c1_ps, 5,1,'Práctica calificada',1),(@c1_ps, 5,1,'Examen bimestral',2),
    (@c1_ps, 6,1,'Práctica calificada',1),(@c1_ps, 6,1,'Examen bimestral',2),
    (@c1_ps, 7,1,'Práctica calificada',1),(@c1_ps, 7,1,'Examen bimestral',2),
    (@c1_ps, 8,1,'Práctica calificada',1),(@c1_ps, 8,1,'Examen bimestral',2),
    (@c1_ef,11,1,'Práctica calificada',1),(@c1_ef,11,1,'Examen bimestral',2),
    (@c1_ef,12,1,'Práctica calificada',1),(@c1_ef,12,1,'Examen bimestral',2),
    (@c1_ef,13,1,'Práctica calificada',1),(@c1_ef,13,1,'Examen bimestral',2),
    (@c1_arte,17,1,'Práctica calificada',1),(@c1_arte,17,1,'Examen bimestral',2),
    (@c1_arte,18,1,'Práctica calificada',1),(@c1_arte,18,1,'Examen bimestral',2),
    (@c1_ing, 1,1,'Práctica calificada',1),(@c1_ing, 1,1,'Examen bimestral',2),
    (@c1_ing, 2,1,'Práctica calificada',1),(@c1_ing, 2,1,'Examen bimestral',2),
    (@c1_ing, 3,1,'Práctica calificada',1),(@c1_ing, 3,1,'Examen bimestral',2),
    (@c1_rel, 9,1,'Práctica calificada',1),(@c1_rel, 9,1,'Examen bimestral',2),
    (@c1_rel,10,1,'Práctica calificada',1),(@c1_rel,10,1,'Examen bimestral',2),
    (@c1_com,14,1,'Práctica calificada',1),(@c1_com,14,1,'Examen bimestral',2),
    (@c1_pl, 15,1,'Práctica calificada',1),(@c1_pl, 15,1,'Examen bimestral',2),
    (@c1_rv, 16,1,'Práctica calificada',1),(@c1_rv, 16,1,'Examen bimestral',2),
    (@c1_arit,19,1,'Práctica calificada',1),(@c1_arit,19,1,'Examen bimestral',2),
    (@c1_alg, 20,1,'Práctica calificada',1),(@c1_alg, 20,1,'Examen bimestral',2),
    (@c1_geom,21,1,'Práctica calificada',1),(@c1_geom,21,1,'Examen bimestral',2),
    (@c1_rm,  22,1,'Práctica calificada',1),(@c1_rm,  22,1,'Examen bimestral',2),
    (@c1_quim,23,1,'Práctica calificada',1),(@c1_quim,23,1,'Examen bimestral',2),
    (@c1_bio, 24,1,'Práctica calificada',1),(@c1_bio, 24,1,'Examen bimestral',2),
    (@c1_fis, 25,1,'Práctica calificada',1),(@c1_fis, 25,1,'Examen bimestral',2),
    (@c1_transv,26,1,'I Bimestre',1),
    (@c1_transv,27,1,'I Bimestre',1);

-- Resolver IDs de criterios sec 1
SET @cr1_ps4a  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=4  AND orden=1 LIMIT 1);
SET @cr1_ps4b  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=4  AND orden=2 LIMIT 1);
SET @cr1_ps5a  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=5  AND orden=1 LIMIT 1);
SET @cr1_ps5b  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=5  AND orden=2 LIMIT 1);
SET @cr1_ps6a  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=6  AND orden=1 LIMIT 1);
SET @cr1_ps6b  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=6  AND orden=2 LIMIT 1);
SET @cr1_ps7a  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=7  AND orden=1 LIMIT 1);
SET @cr1_ps7b  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=7  AND orden=2 LIMIT 1);
SET @cr1_ps8a  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=8  AND orden=1 LIMIT 1);
SET @cr1_ps8b  =(SELECT id FROM criterios WHERE carga_id=@c1_ps   AND competencia_id=8  AND orden=2 LIMIT 1);
SET @cr1_ef11a =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=11 AND orden=1 LIMIT 1);
SET @cr1_ef11b =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=11 AND orden=2 LIMIT 1);
SET @cr1_ef12a =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=12 AND orden=1 LIMIT 1);
SET @cr1_ef12b =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=12 AND orden=2 LIMIT 1);
SET @cr1_ef13a =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=13 AND orden=1 LIMIT 1);
SET @cr1_ef13b =(SELECT id FROM criterios WHERE carga_id=@c1_ef   AND competencia_id=13 AND orden=2 LIMIT 1);
SET @cr1_ar17a =(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=17 AND orden=1 LIMIT 1);
SET @cr1_ar17b =(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=17 AND orden=2 LIMIT 1);
SET @cr1_ar18a =(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=18 AND orden=1 LIMIT 1);
SET @cr1_ar18b =(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=18 AND orden=2 LIMIT 1);
SET @cr1_in1a  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=1  AND orden=1 LIMIT 1);
SET @cr1_in1b  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=1  AND orden=2 LIMIT 1);
SET @cr1_in2a  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=2  AND orden=1 LIMIT 1);
SET @cr1_in2b  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=2  AND orden=2 LIMIT 1);
SET @cr1_in3a  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=3  AND orden=1 LIMIT 1);
SET @cr1_in3b  =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=3  AND orden=2 LIMIT 1);
SET @cr1_re9a  =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=9  AND orden=1 LIMIT 1);
SET @cr1_re9b  =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=9  AND orden=2 LIMIT 1);
SET @cr1_re10a =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=10 AND orden=1 LIMIT 1);
SET @cr1_re10b =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=10 AND orden=2 LIMIT 1);
SET @cr1_co14a =(SELECT id FROM criterios WHERE carga_id=@c1_com  AND competencia_id=14 AND orden=1 LIMIT 1);
SET @cr1_co14b =(SELECT id FROM criterios WHERE carga_id=@c1_com  AND competencia_id=14 AND orden=2 LIMIT 1);
SET @cr1_pl15a =(SELECT id FROM criterios WHERE carga_id=@c1_pl   AND competencia_id=15 AND orden=1 LIMIT 1);
SET @cr1_pl15b =(SELECT id FROM criterios WHERE carga_id=@c1_pl   AND competencia_id=15 AND orden=2 LIMIT 1);
SET @cr1_rv16a =(SELECT id FROM criterios WHERE carga_id=@c1_rv   AND competencia_id=16 AND orden=1 LIMIT 1);
SET @cr1_rv16b =(SELECT id FROM criterios WHERE carga_id=@c1_rv   AND competencia_id=16 AND orden=2 LIMIT 1);
SET @cr1_ar19a =(SELECT id FROM criterios WHERE carga_id=@c1_arit AND competencia_id=19 AND orden=1 LIMIT 1);
SET @cr1_ar19b =(SELECT id FROM criterios WHERE carga_id=@c1_arit AND competencia_id=19 AND orden=2 LIMIT 1);
SET @cr1_al20a =(SELECT id FROM criterios WHERE carga_id=@c1_alg  AND competencia_id=20 AND orden=1 LIMIT 1);
SET @cr1_al20b =(SELECT id FROM criterios WHERE carga_id=@c1_alg  AND competencia_id=20 AND orden=2 LIMIT 1);
SET @cr1_ge21a =(SELECT id FROM criterios WHERE carga_id=@c1_geom AND competencia_id=21 AND orden=1 LIMIT 1);
SET @cr1_ge21b =(SELECT id FROM criterios WHERE carga_id=@c1_geom AND competencia_id=21 AND orden=2 LIMIT 1);
SET @cr1_rm22a =(SELECT id FROM criterios WHERE carga_id=@c1_rm   AND competencia_id=22 AND orden=1 LIMIT 1);
SET @cr1_rm22b =(SELECT id FROM criterios WHERE carga_id=@c1_rm   AND competencia_id=22 AND orden=2 LIMIT 1);
SET @cr1_qu23a =(SELECT id FROM criterios WHERE carga_id=@c1_quim AND competencia_id=23 AND orden=1 LIMIT 1);
SET @cr1_qu23b =(SELECT id FROM criterios WHERE carga_id=@c1_quim AND competencia_id=23 AND orden=2 LIMIT 1);
SET @cr1_bi24a =(SELECT id FROM criterios WHERE carga_id=@c1_bio  AND competencia_id=24 AND orden=1 LIMIT 1);
SET @cr1_bi24b =(SELECT id FROM criterios WHERE carga_id=@c1_bio  AND competencia_id=24 AND orden=2 LIMIT 1);
SET @cr1_fi25a =(SELECT id FROM criterios WHERE carga_id=@c1_fis  AND competencia_id=25 AND orden=1 LIMIT 1);
SET @cr1_fi25b =(SELECT id FROM criterios WHERE carga_id=@c1_fis  AND competencia_id=25 AND orden=2 LIMIT 1);
SET @cr1_tr26  =(SELECT id FROM criterios WHERE carga_id=@c1_transv AND competencia_id=26 LIMIT 1);
SET @cr1_tr27  =(SELECT id FROM criterios WHERE carga_id=@c1_transv AND competencia_id=27 LIMIT 1);

-- Notas por criterio E1 (mat 1-5)
-- mat 1 = alumno demo: AD→A→B(con conclusión)→C(con conclusión)
-- mat 2-5 = resto de la sección: A (14-15)
INSERT IGNORE INTO calificaciones_criterio (criterio_id, matricula_id, nota) VALUES
(@cr1_ps4a,1,19),(@cr1_ps4a,2,15),(@cr1_ps4a,3,16),(@cr1_ps4a,4,14),(@cr1_ps4a,5,15),
(@cr1_ps4b,1,18),(@cr1_ps4b,2,15),(@cr1_ps4b,3,16),(@cr1_ps4b,4,15),(@cr1_ps4b,5,14),
(@cr1_ps5a,1,18),(@cr1_ps5a,2,14),(@cr1_ps5a,3,15),(@cr1_ps5a,4,16),(@cr1_ps5a,5,15),
(@cr1_ps5b,1,18),(@cr1_ps5b,2,15),(@cr1_ps5b,3,14),(@cr1_ps5b,4,15),(@cr1_ps5b,5,16),
(@cr1_ps6a,1,17),(@cr1_ps6a,2,15),(@cr1_ps6a,3,14),(@cr1_ps6a,4,15),(@cr1_ps6a,5,14),
(@cr1_ps6b,1,17),(@cr1_ps6b,2,14),(@cr1_ps6b,3,15),(@cr1_ps6b,4,14),(@cr1_ps6b,5,15),
(@cr1_ps7a,1,15),(@cr1_ps7a,2,15),(@cr1_ps7a,3,14),(@cr1_ps7a,4,13),(@cr1_ps7a,5,15),
(@cr1_ps7b,1,15),(@cr1_ps7b,2,14),(@cr1_ps7b,3,15),(@cr1_ps7b,4,14),(@cr1_ps7b,5,14),
(@cr1_ps8a,1,16),(@cr1_ps8a,2,14),(@cr1_ps8a,3,15),(@cr1_ps8a,4,15),(@cr1_ps8a,5,15),
(@cr1_ps8b,1,16),(@cr1_ps8b,2,15),(@cr1_ps8b,3,14),(@cr1_ps8b,4,14),(@cr1_ps8b,5,15),
(@cr1_ef11a,1,17),(@cr1_ef11a,2,15),(@cr1_ef11a,3,14),(@cr1_ef11a,4,15),(@cr1_ef11a,5,15),
(@cr1_ef11b,1,17),(@cr1_ef11b,2,14),(@cr1_ef11b,3,15),(@cr1_ef11b,4,15),(@cr1_ef11b,5,14),
(@cr1_ef12a,1,15),(@cr1_ef12a,2,15),(@cr1_ef12a,3,14),(@cr1_ef12a,4,15),(@cr1_ef12a,5,14),
(@cr1_ef12b,1,15),(@cr1_ef12b,2,14),(@cr1_ef12b,3,15),(@cr1_ef12b,4,14),(@cr1_ef12b,5,15),
(@cr1_ef13a,1,14),(@cr1_ef13a,2,14),(@cr1_ef13a,3,15),(@cr1_ef13a,4,15),(@cr1_ef13a,5,13),
(@cr1_ef13b,1,14),(@cr1_ef13b,2,15),(@cr1_ef13b,3,14),(@cr1_ef13b,4,14),(@cr1_ef13b,5,14),
(@cr1_ar17a,1,15),(@cr1_ar17a,2,15),(@cr1_ar17a,3,14),(@cr1_ar17a,4,15),(@cr1_ar17a,5,14),
(@cr1_ar17b,1,15),(@cr1_ar17b,2,14),(@cr1_ar17b,3,15),(@cr1_ar17b,4,14),(@cr1_ar17b,5,15),
(@cr1_ar18a,1,16),(@cr1_ar18a,2,15),(@cr1_ar18a,3,14),(@cr1_ar18a,4,15),(@cr1_ar18a,5,15),
(@cr1_ar18b,1,16),(@cr1_ar18b,2,14),(@cr1_ar18b,3,15),(@cr1_ar18b,4,14),(@cr1_ar18b,5,14),
(@cr1_in1a,1,14),(@cr1_in1a,2,15),(@cr1_in1a,3,13),(@cr1_in1a,4,14),(@cr1_in1a,5,15),
(@cr1_in1b,1,14),(@cr1_in1b,2,14),(@cr1_in1b,3,14),(@cr1_in1b,4,13),(@cr1_in1b,5,14),
(@cr1_in2a,1,15),(@cr1_in2a,2,14),(@cr1_in2a,3,15),(@cr1_in2a,4,15),(@cr1_in2a,5,14),
(@cr1_in2b,1,15),(@cr1_in2b,2,15),(@cr1_in2b,3,14),(@cr1_in2b,4,14),(@cr1_in2b,5,15),
(@cr1_in3a,1,13),(@cr1_in3a,2,14),(@cr1_in3a,3,15),(@cr1_in3a,4,14),(@cr1_in3a,5,15),
(@cr1_in3b,1,13),(@cr1_in3b,2,15),(@cr1_in3b,3,14),(@cr1_in3b,4,15),(@cr1_in3b,5,14),
(@cr1_re9a,1,15),(@cr1_re9a,2,15),(@cr1_re9a,3,14),(@cr1_re9a,4,15),(@cr1_re9a,5,14),
(@cr1_re9b,1,15),(@cr1_re9b,2,14),(@cr1_re9b,3,15),(@cr1_re9b,4,14),(@cr1_re9b,5,15),
(@cr1_re10a,1,14),(@cr1_re10a,2,14),(@cr1_re10a,3,15),(@cr1_re10a,4,14),(@cr1_re10a,5,15),
(@cr1_re10b,1,14),(@cr1_re10b,2,15),(@cr1_re10b,3,14),(@cr1_re10b,4,15),(@cr1_re10b,5,14),
(@cr1_co14a,1,12),(@cr1_co14a,2,15),(@cr1_co14a,3,14),(@cr1_co14a,4,15),(@cr1_co14a,5,14),
(@cr1_co14b,1,12),(@cr1_co14b,2,14),(@cr1_co14b,3,15),(@cr1_co14b,4,14),(@cr1_co14b,5,15),
(@cr1_pl15a,1,14),(@cr1_pl15a,2,15),(@cr1_pl15a,3,14),(@cr1_pl15a,4,15),(@cr1_pl15a,5,14),
(@cr1_pl15b,1,14),(@cr1_pl15b,2,14),(@cr1_pl15b,3,15),(@cr1_pl15b,4,14),(@cr1_pl15b,5,15),
(@cr1_rv16a,1,11),(@cr1_rv16a,2,14),(@cr1_rv16a,3,15),(@cr1_rv16a,4,14),(@cr1_rv16a,5,15),
(@cr1_rv16b,1,11),(@cr1_rv16b,2,15),(@cr1_rv16b,3,14),(@cr1_rv16b,4,15),(@cr1_rv16b,5,14),
(@cr1_ar19a,1,14),(@cr1_ar19a,2,15),(@cr1_ar19a,3,14),(@cr1_ar19a,4,15),(@cr1_ar19a,5,14),
(@cr1_ar19b,1,14),(@cr1_ar19b,2,14),(@cr1_ar19b,3,15),(@cr1_ar19b,4,14),(@cr1_ar19b,5,15),
(@cr1_al20a,1,15),(@cr1_al20a,2,14),(@cr1_al20a,3,15),(@cr1_al20a,4,14),(@cr1_al20a,5,15),
(@cr1_al20b,1,15),(@cr1_al20b,2,15),(@cr1_al20b,3,14),(@cr1_al20b,4,15),(@cr1_al20b,5,14),
(@cr1_ge21a,1,11),(@cr1_ge21a,2,14),(@cr1_ge21a,3,15),(@cr1_ge21a,4,14),(@cr1_ge21a,5,15),
(@cr1_ge21b,1,11),(@cr1_ge21b,2,15),(@cr1_ge21b,3,14),(@cr1_ge21b,4,15),(@cr1_ge21b,5,14),
(@cr1_rm22a,1, 8),(@cr1_rm22a,2,14),(@cr1_rm22a,3,15),(@cr1_rm22a,4,13),(@cr1_rm22a,5,14),
(@cr1_rm22b,1, 8),(@cr1_rm22b,2,15),(@cr1_rm22b,3,14),(@cr1_rm22b,4,14),(@cr1_rm22b,5,15),
(@cr1_qu23a,1,14),(@cr1_qu23a,2,15),(@cr1_qu23a,3,14),(@cr1_qu23a,4,15),(@cr1_qu23a,5,14),
(@cr1_qu23b,1,14),(@cr1_qu23b,2,14),(@cr1_qu23b,3,15),(@cr1_qu23b,4,14),(@cr1_qu23b,5,15),
(@cr1_bi24a,1,15),(@cr1_bi24a,2,15),(@cr1_bi24a,3,14),(@cr1_bi24a,4,15),(@cr1_bi24a,5,14),
(@cr1_bi24b,1,15),(@cr1_bi24b,2,14),(@cr1_bi24b,3,15),(@cr1_bi24b,4,14),(@cr1_bi24b,5,15),
(@cr1_fi25a,1,14),(@cr1_fi25a,2,14),(@cr1_fi25a,3,15),(@cr1_fi25a,4,14),(@cr1_fi25a,5,15),
(@cr1_fi25b,1,14),(@cr1_fi25b,2,15),(@cr1_fi25b,3,14),(@cr1_fi25b,4,15),(@cr1_fi25b,5,14),
(@cr1_tr26,1,15),(@cr1_tr26,2,15),(@cr1_tr26,3,14),(@cr1_tr26,4,15),(@cr1_tr26,5,14),
(@cr1_tr27,1,14),(@cr1_tr27,2,14),(@cr1_tr27,3,15),(@cr1_tr27,4,14),(@cr1_tr27,5,15);

-- Calificaciones computadas E1
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
(1,@c1_ps,1,4,19,'',NOW(),21),(2,@c1_ps,1,4,15,'',NOW(),21),(3,@c1_ps,1,4,16,'',NOW(),21),(4,@c1_ps,1,4,15,'',NOW(),21),(5,@c1_ps,1,4,15,'',NOW(),21),
(1,@c1_ps,1,5,18,'',NOW(),21),(2,@c1_ps,1,5,15,'',NOW(),21),(3,@c1_ps,1,5,15,'',NOW(),21),(4,@c1_ps,1,5,16,'',NOW(),21),(5,@c1_ps,1,5,16,'',NOW(),21),
(1,@c1_ps,1,6,17,'',NOW(),21),(2,@c1_ps,1,6,15,'',NOW(),21),(3,@c1_ps,1,6,15,'',NOW(),21),(4,@c1_ps,1,6,15,'',NOW(),21),(5,@c1_ps,1,6,15,'',NOW(),21),
(1,@c1_ps,1,7,15,'',NOW(),21),(2,@c1_ps,1,7,15,'',NOW(),21),(3,@c1_ps,1,7,15,'',NOW(),21),(4,@c1_ps,1,7,14,'',NOW(),21),(5,@c1_ps,1,7,15,'',NOW(),21),
(1,@c1_ps,1,8,16,'',NOW(),21),(2,@c1_ps,1,8,15,'',NOW(),21),(3,@c1_ps,1,8,15,'',NOW(),21),(4,@c1_ps,1,8,15,'',NOW(),21),(5,@c1_ps,1,8,15,'',NOW(),21),
(1,@c1_ef,1,11,17,'',NOW(),21),(2,@c1_ef,1,11,15,'',NOW(),21),(3,@c1_ef,1,11,15,'',NOW(),21),(4,@c1_ef,1,11,15,'',NOW(),21),(5,@c1_ef,1,11,15,'',NOW(),21),
(1,@c1_ef,1,12,15,'',NOW(),21),(2,@c1_ef,1,12,15,'',NOW(),21),(3,@c1_ef,1,12,15,'',NOW(),21),(4,@c1_ef,1,12,15,'',NOW(),21),(5,@c1_ef,1,12,15,'',NOW(),21),
(1,@c1_ef,1,13,14,'',NOW(),21),(2,@c1_ef,1,13,15,'',NOW(),21),(3,@c1_ef,1,13,15,'',NOW(),21),(4,@c1_ef,1,13,15,'',NOW(),21),(5,@c1_ef,1,13,14,'',NOW(),21),
(1,@c1_arte,1,17,15,'',NOW(),21),(2,@c1_arte,1,17,15,'',NOW(),21),(3,@c1_arte,1,17,15,'',NOW(),21),(4,@c1_arte,1,17,15,'',NOW(),21),(5,@c1_arte,1,17,15,'',NOW(),21),
(1,@c1_arte,1,18,16,'',NOW(),21),(2,@c1_arte,1,18,15,'',NOW(),21),(3,@c1_arte,1,18,15,'',NOW(),21),(4,@c1_arte,1,18,15,'',NOW(),21),(5,@c1_arte,1,18,15,'',NOW(),21),
(1,@c1_ing,1,1,14,'',NOW(),21),(2,@c1_ing,1,1,15,'',NOW(),21),(3,@c1_ing,1,1,14,'',NOW(),21),(4,@c1_ing,1,1,14,'',NOW(),21),(5,@c1_ing,1,1,15,'',NOW(),21),
(1,@c1_ing,1,2,15,'',NOW(),21),(2,@c1_ing,1,2,15,'',NOW(),21),(3,@c1_ing,1,2,15,'',NOW(),21),(4,@c1_ing,1,2,15,'',NOW(),21),(5,@c1_ing,1,2,15,'',NOW(),21),
(1,@c1_ing,1,3,13,'Debe practicar más la redacción en inglés para alcanzar el nivel esperado.',NOW(),21),(2,@c1_ing,1,3,15,'',NOW(),21),(3,@c1_ing,1,3,15,'',NOW(),21),(4,@c1_ing,1,3,15,'',NOW(),21),(5,@c1_ing,1,3,15,'',NOW(),21),
(1,@c1_rel,1,9, 15,'',NOW(),21),(2,@c1_rel,1,9, 15,'',NOW(),21),(3,@c1_rel,1,9, 15,'',NOW(),21),(4,@c1_rel,1,9, 15,'',NOW(),21),(5,@c1_rel,1,9, 15,'',NOW(),21),
(1,@c1_rel,1,10,14,'',NOW(),21),(2,@c1_rel,1,10,15,'',NOW(),21),(3,@c1_rel,1,10,15,'',NOW(),21),(4,@c1_rel,1,10,15,'',NOW(),21),(5,@c1_rel,1,10,15,'',NOW(),21),
(1,@c1_com,1,14,12,'Necesita reforzar su expresión oral. Se recomienda practicar en casa con lecturas en voz alta.',NOW(),21),(2,@c1_com,1,14,15,'',NOW(),21),(3,@c1_com,1,14,15,'',NOW(),21),(4,@c1_com,1,14,15,'',NOW(),21),(5,@c1_com,1,14,15,'',NOW(),21),
(1,@c1_pl,1,15,14,'',NOW(),21),(2,@c1_pl,1,15,15,'',NOW(),21),(3,@c1_pl,1,15,14,'',NOW(),21),(4,@c1_pl,1,15,15,'',NOW(),21),(5,@c1_pl,1,15,14,'',NOW(),21),
(1,@c1_rv,1,16,11,'Requiere acompañamiento en comprensión lectora. Se sugiere leer textos breves cada noche.',NOW(),21),(2,@c1_rv,1,16,15,'',NOW(),21),(3,@c1_rv,1,16,15,'',NOW(),21),(4,@c1_rv,1,16,15,'',NOW(),21),(5,@c1_rv,1,16,14,'',NOW(),21),
(1,@c1_arit,1,19,14,'',NOW(),21),(2,@c1_arit,1,19,15,'',NOW(),21),(3,@c1_arit,1,19,15,'',NOW(),21),(4,@c1_arit,1,19,15,'',NOW(),21),(5,@c1_arit,1,19,15,'',NOW(),21),
(1,@c1_alg,1,20,15,'',NOW(),21),(2,@c1_alg,1,20,15,'',NOW(),21),(3,@c1_alg,1,20,15,'',NOW(),21),(4,@c1_alg,1,20,15,'',NOW(),21),(5,@c1_alg,1,20,15,'',NOW(),21),
(1,@c1_geom,1,21,11,'Necesita practicar más la identificación de figuras geométricas. Recomiendo ejercicios complementarios.',NOW(),21),(2,@c1_geom,1,21,15,'',NOW(),21),(3,@c1_geom,1,21,14,'',NOW(),21),(4,@c1_geom,1,21,15,'',NOW(),21),(5,@c1_geom,1,21,15,'',NOW(),21),
(1,@c1_rm,1,22,8,'El/la estudiante presenta dificultades para resolver problemas de razonamiento matemático. Se recomienda apoyo adicional fuera del horario escolar y comunicación constante con la familia.',NOW(),21),(2,@c1_rm,1,22,15,'',NOW(),21),(3,@c1_rm,1,22,15,'',NOW(),21),(4,@c1_rm,1,22,14,'',NOW(),21),(5,@c1_rm,1,22,15,'',NOW(),21),
(1,@c1_quim,1,23,14,'',NOW(),21),(2,@c1_quim,1,23,15,'',NOW(),21),(3,@c1_quim,1,23,15,'',NOW(),21),(4,@c1_quim,1,23,14,'',NOW(),21),(5,@c1_quim,1,23,15,'',NOW(),21),
(1,@c1_bio,1,24,15,'',NOW(),21),(2,@c1_bio,1,24,15,'',NOW(),21),(3,@c1_bio,1,24,14,'',NOW(),21),(4,@c1_bio,1,24,15,'',NOW(),21),(5,@c1_bio,1,24,15,'',NOW(),21),
(1,@c1_fis,1,25,14,'',NOW(),21),(2,@c1_fis,1,25,15,'',NOW(),21),(3,@c1_fis,1,25,15,'',NOW(),21),(4,@c1_fis,1,25,15,'',NOW(),21),(5,@c1_fis,1,25,14,'',NOW(),21),
(1,@c1_transv,1,26,15,'',NOW(),21),(2,@c1_transv,1,26,15,'',NOW(),21),(3,@c1_transv,1,26,14,'',NOW(),21),(4,@c1_transv,1,26,15,'',NOW(),21),(5,@c1_transv,1,26,14,'',NOW(),21),
(1,@c1_transv,1,27,14,'',NOW(),21),(2,@c1_transv,1,27,14,'',NOW(),21),(3,@c1_transv,1,27,15,'',NOW(),21),(4,@c1_transv,1,27,14,'',NOW(),21),(5,@c1_transv,1,27,15,'',NOW(),21);

-- Bloqueos E1 (docente/tutor 21 bloquea todo)
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(@c1_ps,4,1,21),(@c1_ps,5,1,21),(@c1_ps,6,1,21),(@c1_ps,7,1,21),(@c1_ps,8,1,21),
(@c1_ef,11,1,21),(@c1_ef,12,1,21),(@c1_ef,13,1,21),
(@c1_arte,17,1,21),(@c1_arte,18,1,21),
(@c1_ing,1,1,21),(@c1_ing,2,1,21),(@c1_ing,3,1,21),
(@c1_rel,9,1,21),(@c1_rel,10,1,21),
(@c1_com,14,1,21),(@c1_pl,15,1,21),(@c1_rv,16,1,21),
(@c1_arit,19,1,21),(@c1_alg,20,1,21),(@c1_geom,21,1,21),(@c1_rm,22,1,21),
(@c1_quim,23,1,21),(@c1_bio,24,1,21),(@c1_fis,25,1,21),
(@c1_transv,26,1,21),(@c1_transv,27,1,21);


-- =============================================================================
-- E2: 1° SECUNDARIA A — cargas reales del backup + áreas faltantes
-- Sección: 13 | Matriculas: 78–82 | Tutor: user 2
-- =============================================================================
-- La mayoría de cargas, criterios, calificaciones y bloqueos YA EXISTEN
-- en el backup con datos reales de los docentes (16–17 may 2026).
-- Solo se agregan las áreas faltantes y el bloqueo ausente de EPT.
--
-- IDs de cargas reales verificados en backup_18_05_2026.sql:
--   carga  1: docente  7, subarea 15 — Literatura      (comp 39)
--   carga  2: docente 12, subarea 22 — Biología         (comp 49)
--   carga  3: docente  9, subarea 16 — Lenguaje         (comp 40)
--   carga  4: docente 16, subarea 12 — Geografía        (comp 31)
--   carga  5: docente 19, area   11 — Ed. Física        (comp 33,34,35)
--   carga  6: docente 11, area   12 — Arte y Cultura    (comp 36,37)
--   carga  7: docente 15, subarea 14 — Raz. Verbal      (comp 38)
--   carga  9: docente  8, subarea 20 — Trigonometría    (comp 47)
--   carga 10: docente  6, area   13 — Inglés            (comp 41,42,43)
--   carga 11: docente 17, subarea 23 — Física           (comp 50)
--   carga 12: docente 18, area   16 — Taller Raz.Mat.  (comp 54,55)
--   carga 17: docente 10, area   10 — DPCC              (comp 28,29)
--   carga 20: docente 13, subarea 19 — Geometría        (comp 46)
--   carga 21: docente 14, subarea 11 — Historia         (comp 30)
--   carga 26: docente 17, subarea 21 — Química          (comp 48)
--   carga 28: docente 20, area   15 — EPT               (comp 53) ← sin bloqueo
--   carga 29: docente  2, area   21 — Transversales     (comp 56,57)
--   carga 42: docente  4, subarea 18 — Álgebra          (comp 45)
--   carga 43: docente  5, subarea 17 — Aritmética       (comp 44)
--   FALTA: Economía  (subarea 13, comp 32)
--   FALTA: Ed. Religiosa (area 14, comp 51, 52)

SET @c13_lit   = 1;
SET @c13_bio   = 2;
SET @c13_len   = 3;
SET @c13_geo   = 4;
SET @c13_ef    = 5;
SET @c13_arte  = 6;
SET @c13_rv    = 7;
SET @c13_trig  = 9;
SET @c13_ing   = 10;
SET @c13_fis   = 11;
SET @c13_tall  = 12;
SET @c13_dpcc  = 17;
SET @c13_geom  = 20;
SET @c13_hist  = 21;
SET @c13_quim  = 26;
SET @c13_ept   = 28;
SET @c13_trans = 29;
SET @c13_alg   = 42;
SET @c13_arit  = 43;

-- Insertar ÚNICAMENTE las cargas faltantes
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 20, 13, 1, 13, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=13);

SET @c13_econ = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=13 LIMIT 1);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 11, 13, 1, NULL, 14, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND area_id=14 AND subarea_id IS NULL);

SET @c13_rel = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND area_id=14 AND subarea_id IS NULL LIMIT 1);

-- Criterios EPT (carga 28, comp 53) — no existe en el backup
INSERT INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden)
VALUES (28, 53, 1, 'I Bimestre', 1);

SET @cr13_ept = (SELECT id FROM criterios WHERE carga_id=28 AND competencia_id=53 AND periodo_id=1 LIMIT 1);

-- Criterios para las 2 nuevas cargas
INSERT INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    (@c13_econ,32,1,'Práctica calificada',1),(@c13_econ,32,1,'Examen bimestral',2),
    (@c13_rel, 51,1,'Práctica calificada',1),(@c13_rel, 51,1,'Examen bimestral',2),
    (@c13_rel, 52,1,'Práctica calificada',1),(@c13_rel, 52,1,'Examen bimestral',2);

SET @cr13_econ32a = (SELECT id FROM criterios WHERE carga_id=@c13_econ AND competencia_id=32 AND orden=1 LIMIT 1);
SET @cr13_econ32b = (SELECT id FROM criterios WHERE carga_id=@c13_econ AND competencia_id=32 AND orden=2 LIMIT 1);
SET @cr13_rel51a  = (SELECT id FROM criterios WHERE carga_id=@c13_rel  AND competencia_id=51 AND orden=1 LIMIT 1);
SET @cr13_rel51b  = (SELECT id FROM criterios WHERE carga_id=@c13_rel  AND competencia_id=51 AND orden=2 LIMIT 1);
SET @cr13_rel52a  = (SELECT id FROM criterios WHERE carga_id=@c13_rel  AND competencia_id=52 AND orden=1 LIMIT 1);
SET @cr13_rel52b  = (SELECT id FROM criterios WHERE carga_id=@c13_rel  AND competencia_id=52 AND orden=2 LIMIT 1);

-- calificaciones_criterio para las 3 áreas nuevas (mat 78-82)
INSERT IGNORE INTO calificaciones_criterio (criterio_id, matricula_id, nota) VALUES
(@cr13_ept,   78,16),(@cr13_ept,   79,15),(@cr13_ept,   80,14),(@cr13_ept,   81,15),(@cr13_ept,   82,15),
(@cr13_econ32a,78,15),(@cr13_econ32a,79,14),(@cr13_econ32a,80,15),(@cr13_econ32a,81,15),(@cr13_econ32a,82,14),
(@cr13_econ32b,78,15),(@cr13_econ32b,79,15),(@cr13_econ32b,80,14),(@cr13_econ32b,81,14),(@cr13_econ32b,82,15),
(@cr13_rel51a, 78,15),(@cr13_rel51a, 79,14),(@cr13_rel51a, 80,15),(@cr13_rel51a, 81,15),(@cr13_rel51a, 82,14),
(@cr13_rel51b, 78,15),(@cr13_rel51b, 79,15),(@cr13_rel51b, 80,14),(@cr13_rel51b, 81,14),(@cr13_rel51b, 82,15),
(@cr13_rel52a, 78,14),(@cr13_rel52a, 79,15),(@cr13_rel52a, 80,14),(@cr13_rel52a, 81,15),(@cr13_rel52a, 82,14),
(@cr13_rel52b, 78,14),(@cr13_rel52b, 79,14),(@cr13_rel52b, 80,15),(@cr13_rel52b, 81,14),(@cr13_rel52b, 82,15);

-- Calificaciones para las 3 áreas nuevas
-- Las calificaciones reales de las demás cargas ya están en el backup (INSERT IGNORE las preserva)
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
(78,28,1,53,16,'',NOW(),20),(79,28,1,53,15,'',NOW(),20),(80,28,1,53,14,'',NOW(),20),(81,28,1,53,15,'',NOW(),20),(82,28,1,53,15,'',NOW(),20),
(78,@c13_econ,1,32,15,'',NOW(),20),(79,@c13_econ,1,32,14,'',NOW(),20),(80,@c13_econ,1,32,15,'',NOW(),20),(81,@c13_econ,1,32,15,'',NOW(),20),(82,@c13_econ,1,32,14,'',NOW(),20),
(78,@c13_rel,1,51,15,'',NOW(),11),(79,@c13_rel,1,51,14,'',NOW(),11),(80,@c13_rel,1,51,15,'',NOW(),11),(81,@c13_rel,1,51,15,'',NOW(),11),(82,@c13_rel,1,51,14,'',NOW(),11),
(78,@c13_rel,1,52,14,'',NOW(),11),(79,@c13_rel,1,52,15,'',NOW(),11),(80,@c13_rel,1,52,14,'',NOW(),11),(81,@c13_rel,1,52,14,'',NOW(),11),(82,@c13_rel,1,52,15,'',NOW(),11);

-- Bloqueos E2
-- Los bloqueos de cargas 1-12,17,20,21,26,29,42,43 ya están en el backup.
-- INSERT IGNORE los preserva sin duplicar.
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
-- cargas con bloqueo real en backup (INSERT IGNORE = sin efecto si ya existe)
(1,39,1,7),(2,49,1,12),(3,40,1,9),(4,31,1,16),
(5,33,1,19),(5,34,1,19),(5,35,1,19),
(6,36,1,11),(6,37,1,11),
(7,38,1,15),(9,47,1,8),(10,41,1,6),(10,42,1,6),(10,43,1,6),
(11,50,1,17),(12,54,1,18),(12,55,1,18),
(17,28,1,10),(17,29,1,10),
(20,46,1,13),(21,30,1,14),
(26,48,1,17),(29,56,1,2),(29,57,1,2),
(42,45,1,4),(43,44,1,5),
-- cargas nuevas y EPT (sin bloqueo previo)
(28,53,1,20),
(@c13_econ,32,1,20),
(@c13_rel,51,1,11),(@c13_rel,52,1,11);


-- =============================================================================
-- E3: 4° SECUNDARIA A — todas las cargas son nuevas (solo existe carga 36)
-- Sección: 20 | Matriculas: 106–110 | Tutor: user 16
-- Arte y Cultura = Razonamiento Matemático (4°–5°S)
-- Ed. Religiosa alias (Ética y Valores) | EPT alias (Habilidades Pedagógicas)
-- =============================================================================

-- Carga 36 (transversal sec 20, docente 16, area 21) ya existe en el backup.
SET @c20_trans = 36;

-- Insertar cargas de sec 20 con WHERE NOT EXISTS
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 10, 20, 1, NULL, 10, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=10 AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  7, 20, 1, 11, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=11);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 16, 20, 1, 12, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=12);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 13, 20, 1, 13, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=13);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 19, 20, 1, NULL, 11, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=11 AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 11, 20, 1, NULL, 12, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=12 AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 15, 20, 1, 14, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=14);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  7, 20, 1, 15, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=15);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  9, 20, 1, 16, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=16);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  6, 20, 1, NULL, 13, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=13 AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  7, 20, 1, 17, NULL, 4, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=17);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  2, 20, 1, 18, NULL, 4, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=18);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  8, 20, 1, 19, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=19);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  8, 20, 1, 20, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=20);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT  9, 20, 1, 21, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=21);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 12, 20, 1, 22, NULL, 3, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=22);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 17, 20, 1, 23, NULL, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=23);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 11, 20, 1, NULL, 14, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=14 AND subarea_id IS NULL);

INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
SELECT 16, 20, 1, NULL, 15, 2, 'activa'
WHERE NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=15 AND subarea_id IS NULL);

-- Resolver IDs de cargas sec 20
SET @c20_dpcc = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=10 AND subarea_id IS NULL LIMIT 1);
SET @c20_hist = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=11 LIMIT 1);
SET @c20_geo  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=12 LIMIT 1);
SET @c20_econ = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=13 LIMIT 1);
SET @c20_ef   = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=11 AND subarea_id IS NULL LIMIT 1);
SET @c20_arte = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=12 AND subarea_id IS NULL LIMIT 1);
SET @c20_rv   = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=14 LIMIT 1);
SET @c20_lit  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=15 LIMIT 1);
SET @c20_len  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=16 LIMIT 1);
SET @c20_ing  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=13 AND subarea_id IS NULL LIMIT 1);
SET @c20_arit = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=17 LIMIT 1);
SET @c20_alg  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=18 LIMIT 1);
SET @c20_geom = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=19 LIMIT 1);
SET @c20_trig = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=20 LIMIT 1);
SET @c20_quim = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=21 LIMIT 1);
SET @c20_bio  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=22 LIMIT 1);
SET @c20_fis  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=23 LIMIT 1);
SET @c20_rel  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=14 AND subarea_id IS NULL LIMIT 1);
SET @c20_ept  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=15 AND subarea_id IS NULL LIMIT 1);

-- Criterios E3 (sec 20 sin datos previos → INSERT directo)
INSERT INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    (@c20_dpcc,28,1,'Práctica calificada',1),(@c20_dpcc,28,1,'Examen bimestral',2),
    (@c20_dpcc,29,1,'Práctica calificada',1),(@c20_dpcc,29,1,'Examen bimestral',2),
    (@c20_hist,30,1,'Práctica calificada',1),(@c20_hist,30,1,'Examen bimestral',2),
    (@c20_geo, 31,1,'Práctica calificada',1),(@c20_geo, 31,1,'Examen bimestral',2),
    (@c20_econ,32,1,'Práctica calificada',1),(@c20_econ,32,1,'Examen bimestral',2),
    (@c20_ef,  33,1,'Práctica calificada',1),(@c20_ef,  33,1,'Examen bimestral',2),
    (@c20_ef,  34,1,'Práctica calificada',1),(@c20_ef,  34,1,'Examen bimestral',2),
    (@c20_ef,  35,1,'Práctica calificada',1),(@c20_ef,  35,1,'Examen bimestral',2),
    (@c20_arte,36,1,'Práctica calificada',1),(@c20_arte,36,1,'Examen bimestral',2),
    (@c20_arte,37,1,'Práctica calificada',1),(@c20_arte,37,1,'Examen bimestral',2),
    (@c20_rv,  38,1,'Práctica calificada',1),(@c20_rv,  38,1,'Examen bimestral',2),
    (@c20_lit, 39,1,'Práctica calificada',1),(@c20_lit, 39,1,'Examen bimestral',2),
    (@c20_len, 40,1,'Práctica calificada',1),(@c20_len, 40,1,'Examen bimestral',2),
    (@c20_ing, 41,1,'Práctica calificada',1),(@c20_ing, 41,1,'Examen bimestral',2),
    (@c20_ing, 42,1,'Práctica calificada',1),(@c20_ing, 42,1,'Examen bimestral',2),
    (@c20_ing, 43,1,'Práctica calificada',1),(@c20_ing, 43,1,'Examen bimestral',2),
    (@c20_arit,44,1,'Práctica calificada',1),(@c20_arit,44,1,'Examen bimestral',2),
    (@c20_alg, 45,1,'Práctica calificada',1),(@c20_alg, 45,1,'Examen bimestral',2),
    (@c20_geom,46,1,'Práctica calificada',1),(@c20_geom,46,1,'Examen bimestral',2),
    (@c20_trig,47,1,'Práctica calificada',1),(@c20_trig,47,1,'Examen bimestral',2),
    (@c20_quim,48,1,'Práctica calificada',1),(@c20_quim,48,1,'Examen bimestral',2),
    (@c20_bio, 49,1,'Práctica calificada',1),(@c20_bio, 49,1,'Examen bimestral',2),
    (@c20_fis, 50,1,'Práctica calificada',1),(@c20_fis, 50,1,'Examen bimestral',2),
    (@c20_rel, 51,1,'Práctica calificada',1),(@c20_rel, 51,1,'Examen bimestral',2),
    (@c20_rel, 52,1,'Práctica calificada',1),(@c20_rel, 52,1,'Examen bimestral',2),
    (@c20_ept, 53,1,'I Bimestre',1),
    (@c20_trans,56,1,'I Bimestre',1),
    (@c20_trans,57,1,'I Bimestre',1);

-- Resolver IDs de criterios sec 20
SET @cr20_dp28a=(SELECT id FROM criterios WHERE carga_id=@c20_dpcc AND competencia_id=28 AND orden=1 LIMIT 1);
SET @cr20_dp28b=(SELECT id FROM criterios WHERE carga_id=@c20_dpcc AND competencia_id=28 AND orden=2 LIMIT 1);
SET @cr20_dp29a=(SELECT id FROM criterios WHERE carga_id=@c20_dpcc AND competencia_id=29 AND orden=1 LIMIT 1);
SET @cr20_dp29b=(SELECT id FROM criterios WHERE carga_id=@c20_dpcc AND competencia_id=29 AND orden=2 LIMIT 1);
SET @cr20_hi30a=(SELECT id FROM criterios WHERE carga_id=@c20_hist AND competencia_id=30 AND orden=1 LIMIT 1);
SET @cr20_hi30b=(SELECT id FROM criterios WHERE carga_id=@c20_hist AND competencia_id=30 AND orden=2 LIMIT 1);
SET @cr20_ge31a=(SELECT id FROM criterios WHERE carga_id=@c20_geo  AND competencia_id=31 AND orden=1 LIMIT 1);
SET @cr20_ge31b=(SELECT id FROM criterios WHERE carga_id=@c20_geo  AND competencia_id=31 AND orden=2 LIMIT 1);
SET @cr20_ec32a=(SELECT id FROM criterios WHERE carga_id=@c20_econ AND competencia_id=32 AND orden=1 LIMIT 1);
SET @cr20_ec32b=(SELECT id FROM criterios WHERE carga_id=@c20_econ AND competencia_id=32 AND orden=2 LIMIT 1);
SET @cr20_ef33a=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=33 AND orden=1 LIMIT 1);
SET @cr20_ef33b=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=33 AND orden=2 LIMIT 1);
SET @cr20_ef34a=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=34 AND orden=1 LIMIT 1);
SET @cr20_ef34b=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=34 AND orden=2 LIMIT 1);
SET @cr20_ef35a=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=35 AND orden=1 LIMIT 1);
SET @cr20_ef35b=(SELECT id FROM criterios WHERE carga_id=@c20_ef   AND competencia_id=35 AND orden=2 LIMIT 1);
SET @cr20_ar36a=(SELECT id FROM criterios WHERE carga_id=@c20_arte AND competencia_id=36 AND orden=1 LIMIT 1);
SET @cr20_ar36b=(SELECT id FROM criterios WHERE carga_id=@c20_arte AND competencia_id=36 AND orden=2 LIMIT 1);
SET @cr20_ar37a=(SELECT id FROM criterios WHERE carga_id=@c20_arte AND competencia_id=37 AND orden=1 LIMIT 1);
SET @cr20_ar37b=(SELECT id FROM criterios WHERE carga_id=@c20_arte AND competencia_id=37 AND orden=2 LIMIT 1);
SET @cr20_rv38a=(SELECT id FROM criterios WHERE carga_id=@c20_rv   AND competencia_id=38 AND orden=1 LIMIT 1);
SET @cr20_rv38b=(SELECT id FROM criterios WHERE carga_id=@c20_rv   AND competencia_id=38 AND orden=2 LIMIT 1);
SET @cr20_li39a=(SELECT id FROM criterios WHERE carga_id=@c20_lit  AND competencia_id=39 AND orden=1 LIMIT 1);
SET @cr20_li39b=(SELECT id FROM criterios WHERE carga_id=@c20_lit  AND competencia_id=39 AND orden=2 LIMIT 1);
SET @cr20_le40a=(SELECT id FROM criterios WHERE carga_id=@c20_len  AND competencia_id=40 AND orden=1 LIMIT 1);
SET @cr20_le40b=(SELECT id FROM criterios WHERE carga_id=@c20_len  AND competencia_id=40 AND orden=2 LIMIT 1);
SET @cr20_in41a=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=41 AND orden=1 LIMIT 1);
SET @cr20_in41b=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=41 AND orden=2 LIMIT 1);
SET @cr20_in42a=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=42 AND orden=1 LIMIT 1);
SET @cr20_in42b=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=42 AND orden=2 LIMIT 1);
SET @cr20_in43a=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=43 AND orden=1 LIMIT 1);
SET @cr20_in43b=(SELECT id FROM criterios WHERE carga_id=@c20_ing  AND competencia_id=43 AND orden=2 LIMIT 1);
SET @cr20_ar44a=(SELECT id FROM criterios WHERE carga_id=@c20_arit AND competencia_id=44 AND orden=1 LIMIT 1);
SET @cr20_ar44b=(SELECT id FROM criterios WHERE carga_id=@c20_arit AND competencia_id=44 AND orden=2 LIMIT 1);
SET @cr20_al45a=(SELECT id FROM criterios WHERE carga_id=@c20_alg  AND competencia_id=45 AND orden=1 LIMIT 1);
SET @cr20_al45b=(SELECT id FROM criterios WHERE carga_id=@c20_alg  AND competencia_id=45 AND orden=2 LIMIT 1);
SET @cr20_go46a=(SELECT id FROM criterios WHERE carga_id=@c20_geom AND competencia_id=46 AND orden=1 LIMIT 1);
SET @cr20_go46b=(SELECT id FROM criterios WHERE carga_id=@c20_geom AND competencia_id=46 AND orden=2 LIMIT 1);
SET @cr20_tr47a=(SELECT id FROM criterios WHERE carga_id=@c20_trig AND competencia_id=47 AND orden=1 LIMIT 1);
SET @cr20_tr47b=(SELECT id FROM criterios WHERE carga_id=@c20_trig AND competencia_id=47 AND orden=2 LIMIT 1);
SET @cr20_qu48a=(SELECT id FROM criterios WHERE carga_id=@c20_quim AND competencia_id=48 AND orden=1 LIMIT 1);
SET @cr20_qu48b=(SELECT id FROM criterios WHERE carga_id=@c20_quim AND competencia_id=48 AND orden=2 LIMIT 1);
SET @cr20_bi49a=(SELECT id FROM criterios WHERE carga_id=@c20_bio  AND competencia_id=49 AND orden=1 LIMIT 1);
SET @cr20_bi49b=(SELECT id FROM criterios WHERE carga_id=@c20_bio  AND competencia_id=49 AND orden=2 LIMIT 1);
SET @cr20_fi50a=(SELECT id FROM criterios WHERE carga_id=@c20_fis  AND competencia_id=50 AND orden=1 LIMIT 1);
SET @cr20_fi50b=(SELECT id FROM criterios WHERE carga_id=@c20_fis  AND competencia_id=50 AND orden=2 LIMIT 1);
SET @cr20_re51a=(SELECT id FROM criterios WHERE carga_id=@c20_rel  AND competencia_id=51 AND orden=1 LIMIT 1);
SET @cr20_re51b=(SELECT id FROM criterios WHERE carga_id=@c20_rel  AND competencia_id=51 AND orden=2 LIMIT 1);
SET @cr20_re52a=(SELECT id FROM criterios WHERE carga_id=@c20_rel  AND competencia_id=52 AND orden=1 LIMIT 1);
SET @cr20_re52b=(SELECT id FROM criterios WHERE carga_id=@c20_rel  AND competencia_id=52 AND orden=2 LIMIT 1);
SET @cr20_ep53 =(SELECT id FROM criterios WHERE carga_id=@c20_ept  AND competencia_id=53 LIMIT 1);
SET @cr20_ct56 =(SELECT id FROM criterios WHERE carga_id=@c20_trans AND competencia_id=56 LIMIT 1);
SET @cr20_ct57 =(SELECT id FROM criterios WHERE carga_id=@c20_trans AND competencia_id=57 LIMIT 1);

-- Notas por criterio E3 (mat 106-110)
-- mat 106 = alumno demo: AD→A→B(con conclusión)→C(con conclusión)
INSERT IGNORE INTO calificaciones_criterio (criterio_id, matricula_id, nota) VALUES
(@cr20_dp28a,106,18),(@cr20_dp28a,107,15),(@cr20_dp28a,108,14),(@cr20_dp28a,109,15),(@cr20_dp28a,110,15),
(@cr20_dp28b,106,18),(@cr20_dp28b,107,14),(@cr20_dp28b,108,15),(@cr20_dp28b,109,14),(@cr20_dp28b,110,15),
(@cr20_dp29a,106,17),(@cr20_dp29a,107,15),(@cr20_dp29a,108,15),(@cr20_dp29a,109,14),(@cr20_dp29a,110,15),
(@cr20_dp29b,106,17),(@cr20_dp29b,107,14),(@cr20_dp29b,108,14),(@cr20_dp29b,109,15),(@cr20_dp29b,110,14),
(@cr20_hi30a,106,16),(@cr20_hi30a,107,15),(@cr20_hi30a,108,14),(@cr20_hi30a,109,15),(@cr20_hi30a,110,15),
(@cr20_hi30b,106,16),(@cr20_hi30b,107,14),(@cr20_hi30b,108,15),(@cr20_hi30b,109,14),(@cr20_hi30b,110,14),
(@cr20_ge31a,106,15),(@cr20_ge31a,107,15),(@cr20_ge31a,108,15),(@cr20_ge31a,109,14),(@cr20_ge31a,110,15),
(@cr20_ge31b,106,15),(@cr20_ge31b,107,14),(@cr20_ge31b,108,14),(@cr20_ge31b,109,15),(@cr20_ge31b,110,14),
(@cr20_ec32a,106,14),(@cr20_ec32a,107,15),(@cr20_ec32a,108,14),(@cr20_ec32a,109,15),(@cr20_ec32a,110,14),
(@cr20_ec32b,106,14),(@cr20_ec32b,107,14),(@cr20_ec32b,108,15),(@cr20_ec32b,109,14),(@cr20_ec32b,110,15),
(@cr20_ef33a,106,17),(@cr20_ef33a,107,15),(@cr20_ef33a,108,16),(@cr20_ef33a,109,15),(@cr20_ef33a,110,14),
(@cr20_ef33b,106,17),(@cr20_ef33b,107,15),(@cr20_ef33b,108,15),(@cr20_ef33b,109,15),(@cr20_ef33b,110,15),
(@cr20_ef34a,106,15),(@cr20_ef34a,107,15),(@cr20_ef34a,108,14),(@cr20_ef34a,109,15),(@cr20_ef34a,110,15),
(@cr20_ef34b,106,15),(@cr20_ef34b,107,14),(@cr20_ef34b,108,15),(@cr20_ef34b,109,14),(@cr20_ef34b,110,14),
(@cr20_ef35a,106,14),(@cr20_ef35a,107,14),(@cr20_ef35a,108,15),(@cr20_ef35a,109,14),(@cr20_ef35a,110,15),
(@cr20_ef35b,106,14),(@cr20_ef35b,107,15),(@cr20_ef35b,108,14),(@cr20_ef35b,109,15),(@cr20_ef35b,110,14),
(@cr20_ar36a,106,15),(@cr20_ar36a,107,15),(@cr20_ar36a,108,14),(@cr20_ar36a,109,15),(@cr20_ar36a,110,15),
(@cr20_ar36b,106,15),(@cr20_ar36b,107,14),(@cr20_ar36b,108,15),(@cr20_ar36b,109,14),(@cr20_ar36b,110,14),
(@cr20_ar37a,106,16),(@cr20_ar37a,107,15),(@cr20_ar37a,108,15),(@cr20_ar37a,109,14),(@cr20_ar37a,110,15),
(@cr20_ar37b,106,16),(@cr20_ar37b,107,14),(@cr20_ar37b,108,14),(@cr20_ar37b,109,15),(@cr20_ar37b,110,14),
(@cr20_rv38a,106,15),(@cr20_rv38a,107,15),(@cr20_rv38a,108,14),(@cr20_rv38a,109,15),(@cr20_rv38a,110,15),
(@cr20_rv38b,106,15),(@cr20_rv38b,107,14),(@cr20_rv38b,108,15),(@cr20_rv38b,109,14),(@cr20_rv38b,110,14),
(@cr20_li39a,106,14),(@cr20_li39a,107,15),(@cr20_li39a,108,15),(@cr20_li39a,109,14),(@cr20_li39a,110,15),
(@cr20_li39b,106,14),(@cr20_li39b,107,14),(@cr20_li39b,108,14),(@cr20_li39b,109,15),(@cr20_li39b,110,14),
(@cr20_le40a,106,12),(@cr20_le40a,107,15),(@cr20_le40a,108,14),(@cr20_le40a,109,15),(@cr20_le40a,110,14),
(@cr20_le40b,106,12),(@cr20_le40b,107,14),(@cr20_le40b,108,15),(@cr20_le40b,109,14),(@cr20_le40b,110,15),
(@cr20_in41a,106,14),(@cr20_in41a,107,15),(@cr20_in41a,108,14),(@cr20_in41a,109,15),(@cr20_in41a,110,14),
(@cr20_in41b,106,14),(@cr20_in41b,107,14),(@cr20_in41b,108,15),(@cr20_in41b,109,14),(@cr20_in41b,110,15),
(@cr20_in42a,106,15),(@cr20_in42a,107,14),(@cr20_in42a,108,15),(@cr20_in42a,109,14),(@cr20_in42a,110,15),
(@cr20_in42b,106,15),(@cr20_in42b,107,15),(@cr20_in42b,108,14),(@cr20_in42b,109,15),(@cr20_in42b,110,14),
(@cr20_in43a,106,11),(@cr20_in43a,107,15),(@cr20_in43a,108,14),(@cr20_in43a,109,15),(@cr20_in43a,110,14),
(@cr20_in43b,106,11),(@cr20_in43b,107,14),(@cr20_in43b,108,15),(@cr20_in43b,109,14),(@cr20_in43b,110,15),
(@cr20_ar44a,106,14),(@cr20_ar44a,107,15),(@cr20_ar44a,108,14),(@cr20_ar44a,109,15),(@cr20_ar44a,110,14),
(@cr20_ar44b,106,14),(@cr20_ar44b,107,14),(@cr20_ar44b,108,15),(@cr20_ar44b,109,14),(@cr20_ar44b,110,15),
(@cr20_al45a,106,15),(@cr20_al45a,107,15),(@cr20_al45a,108,14),(@cr20_al45a,109,15),(@cr20_al45a,110,15),
(@cr20_al45b,106,15),(@cr20_al45b,107,14),(@cr20_al45b,108,15),(@cr20_al45b,109,14),(@cr20_al45b,110,14),
(@cr20_go46a,106,14),(@cr20_go46a,107,15),(@cr20_go46a,108,14),(@cr20_go46a,109,14),(@cr20_go46a,110,15),
(@cr20_go46b,106,14),(@cr20_go46b,107,14),(@cr20_go46b,108,15),(@cr20_go46b,109,15),(@cr20_go46b,110,14),
(@cr20_tr47a,106, 9),(@cr20_tr47a,107,15),(@cr20_tr47a,108,14),(@cr20_tr47a,109,15),(@cr20_tr47a,110,14),
(@cr20_tr47b,106, 9),(@cr20_tr47b,107,14),(@cr20_tr47b,108,15),(@cr20_tr47b,109,14),(@cr20_tr47b,110,15),
(@cr20_qu48a,106,15),(@cr20_qu48a,107,15),(@cr20_qu48a,108,14),(@cr20_qu48a,109,15),(@cr20_qu48a,110,14),
(@cr20_qu48b,106,15),(@cr20_qu48b,107,14),(@cr20_qu48b,108,15),(@cr20_qu48b,109,14),(@cr20_qu48b,110,15),
(@cr20_bi49a,106,14),(@cr20_bi49a,107,15),(@cr20_bi49a,108,15),(@cr20_bi49a,109,14),(@cr20_bi49a,110,15),
(@cr20_bi49b,106,14),(@cr20_bi49b,107,14),(@cr20_bi49b,108,14),(@cr20_bi49b,109,15),(@cr20_bi49b,110,14),
(@cr20_fi50a,106,16),(@cr20_fi50a,107,15),(@cr20_fi50a,108,14),(@cr20_fi50a,109,15),(@cr20_fi50a,110,15),
(@cr20_fi50b,106,16),(@cr20_fi50b,107,14),(@cr20_fi50b,108,15),(@cr20_fi50b,109,14),(@cr20_fi50b,110,14),
(@cr20_re51a,106,15),(@cr20_re51a,107,14),(@cr20_re51a,108,15),(@cr20_re51a,109,15),(@cr20_re51a,110,14),
(@cr20_re51b,106,15),(@cr20_re51b,107,15),(@cr20_re51b,108,14),(@cr20_re51b,109,14),(@cr20_re51b,110,15),
(@cr20_re52a,106,14),(@cr20_re52a,107,15),(@cr20_re52a,108,14),(@cr20_re52a,109,15),(@cr20_re52a,110,14),
(@cr20_re52b,106,14),(@cr20_re52b,107,14),(@cr20_re52b,108,15),(@cr20_re52b,109,14),(@cr20_re52b,110,15),
(@cr20_ep53, 106,16),(@cr20_ep53, 107,15),(@cr20_ep53, 108,14),(@cr20_ep53, 109,15),(@cr20_ep53, 110,15),
(@cr20_ct56, 106,16),(@cr20_ct56, 107,15),(@cr20_ct56, 108,14),(@cr20_ct56, 109,15),(@cr20_ct56, 110,15),
(@cr20_ct57, 106,15),(@cr20_ct57, 107,14),(@cr20_ct57, 108,15),(@cr20_ct57, 109,14),(@cr20_ct57, 110,15);

-- Calificaciones computadas E3
-- mat 106: AD en DPCC/Hist/EF, A en mayoría, B en Lenguaje/Inglés comp43, C en Trigonometría
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
(106,@c20_dpcc,1,28,18,'',NOW(),10),(107,@c20_dpcc,1,28,15,'',NOW(),10),(108,@c20_dpcc,1,28,14,'',NOW(),10),(109,@c20_dpcc,1,28,15,'',NOW(),10),(110,@c20_dpcc,1,28,15,'',NOW(),10),
(106,@c20_dpcc,1,29,17,'',NOW(),10),(107,@c20_dpcc,1,29,15,'',NOW(),10),(108,@c20_dpcc,1,29,14,'',NOW(),10),(109,@c20_dpcc,1,29,15,'',NOW(),10),(110,@c20_dpcc,1,29,15,'',NOW(),10),
(106,@c20_hist,1,30,16,'',NOW(), 7),(107,@c20_hist,1,30,15,'',NOW(), 7),(108,@c20_hist,1,30,14,'',NOW(), 7),(109,@c20_hist,1,30,15,'',NOW(), 7),(110,@c20_hist,1,30,15,'',NOW(), 7),
(106,@c20_geo, 1,31,15,'',NOW(),16),(107,@c20_geo, 1,31,15,'',NOW(),16),(108,@c20_geo, 1,31,14,'',NOW(),16),(109,@c20_geo, 1,31,15,'',NOW(),16),(110,@c20_geo, 1,31,14,'',NOW(),16),
(106,@c20_econ,1,32,14,'',NOW(),13),(107,@c20_econ,1,32,15,'',NOW(),13),(108,@c20_econ,1,32,14,'',NOW(),13),(109,@c20_econ,1,32,15,'',NOW(),13),(110,@c20_econ,1,32,14,'',NOW(),13),
(106,@c20_ef,  1,33,17,'',NOW(),19),(107,@c20_ef,  1,33,15,'',NOW(),19),(108,@c20_ef,  1,33,16,'',NOW(),19),(109,@c20_ef,  1,33,15,'',NOW(),19),(110,@c20_ef,  1,33,15,'',NOW(),19),
(106,@c20_ef,  1,34,15,'',NOW(),19),(107,@c20_ef,  1,34,15,'',NOW(),19),(108,@c20_ef,  1,34,14,'',NOW(),19),(109,@c20_ef,  1,34,14,'',NOW(),19),(110,@c20_ef,  1,34,14,'',NOW(),19),
(106,@c20_ef,  1,35,14,'',NOW(),19),(107,@c20_ef,  1,35,15,'',NOW(),19),(108,@c20_ef,  1,35,14,'',NOW(),19),(109,@c20_ef,  1,35,15,'',NOW(),19),(110,@c20_ef,  1,35,14,'',NOW(),19),
(106,@c20_arte,1,36,15,'',NOW(),11),(107,@c20_arte,1,36,15,'',NOW(),11),(108,@c20_arte,1,36,14,'',NOW(),11),(109,@c20_arte,1,36,14,'',NOW(),11),(110,@c20_arte,1,36,14,'',NOW(),11),
(106,@c20_arte,1,37,16,'',NOW(),11),(107,@c20_arte,1,37,14,'',NOW(),11),(108,@c20_arte,1,37,14,'',NOW(),11),(109,@c20_arte,1,37,15,'',NOW(),11),(110,@c20_arte,1,37,14,'',NOW(),11),
(106,@c20_rv,  1,38,15,'',NOW(),15),(107,@c20_rv,  1,38,15,'',NOW(),15),(108,@c20_rv,  1,38,14,'',NOW(),15),(109,@c20_rv,  1,38,15,'',NOW(),15),(110,@c20_rv,  1,38,15,'',NOW(),15),
(106,@c20_lit, 1,39,14,'',NOW(), 7),(107,@c20_lit, 1,39,15,'',NOW(), 7),(108,@c20_lit, 1,39,14,'',NOW(), 7),(109,@c20_lit, 1,39,15,'',NOW(), 7),(110,@c20_lit, 1,39,14,'',NOW(), 7),
(106,@c20_len, 1,40,12,'Requiere mayor atención en la redacción de textos. Se sugiere trabajar con guías de escritura en casa.',NOW(), 9),
(107,@c20_len,1,40,15,'',NOW(), 9),(108,@c20_len,1,40,14,'',NOW(), 9),(109,@c20_len,1,40,15,'',NOW(), 9),(110,@c20_len,1,40,14,'',NOW(), 9),
(106,@c20_ing, 1,41,14,'',NOW(), 6),(107,@c20_ing, 1,41,15,'',NOW(), 6),(108,@c20_ing, 1,41,14,'',NOW(), 6),(109,@c20_ing, 1,41,15,'',NOW(), 6),(110,@c20_ing, 1,41,14,'',NOW(), 6),
(106,@c20_ing, 1,42,15,'',NOW(), 6),(107,@c20_ing, 1,42,14,'',NOW(), 6),(108,@c20_ing, 1,42,15,'',NOW(), 6),(109,@c20_ing, 1,42,14,'',NOW(), 6),(110,@c20_ing, 1,42,15,'',NOW(), 6),
(106,@c20_ing, 1,43,11,'Debe practicar más la escritura en inglés. Recomiendo ejercicios de redacción guiada.',NOW(), 6),
(107,@c20_ing,1,43,15,'',NOW(), 6),(108,@c20_ing,1,43,14,'',NOW(), 6),(109,@c20_ing,1,43,15,'',NOW(), 6),(110,@c20_ing,1,43,14,'',NOW(), 6),
(106,@c20_arit,1,44,14,'',NOW(), 7),(107,@c20_arit,1,44,15,'',NOW(), 7),(108,@c20_arit,1,44,14,'',NOW(), 7),(109,@c20_arit,1,44,15,'',NOW(), 7),(110,@c20_arit,1,44,14,'',NOW(), 7),
(106,@c20_alg, 1,45,15,'',NOW(), 2),(107,@c20_alg, 1,45,15,'',NOW(), 2),(108,@c20_alg, 1,45,14,'',NOW(), 2),(109,@c20_alg, 1,45,15,'',NOW(), 2),(110,@c20_alg, 1,45,15,'',NOW(), 2),
(106,@c20_geom,1,46,14,'',NOW(), 8),(107,@c20_geom,1,46,15,'',NOW(), 8),(108,@c20_geom,1,46,14,'',NOW(), 8),(109,@c20_geom,1,46,14,'',NOW(), 8),(110,@c20_geom,1,46,15,'',NOW(), 8),
(106,@c20_trig,1,47, 9,'El/la estudiante presenta dificultades serias en Trigonometría. Se recomienda refuerzo inmediato y comunicación con el apoderado para establecer un plan de apoyo.',NOW(), 8),
(107,@c20_trig,1,47,15,'',NOW(), 8),(108,@c20_trig,1,47,14,'',NOW(), 8),(109,@c20_trig,1,47,15,'',NOW(), 8),(110,@c20_trig,1,47,14,'',NOW(), 8),
(106,@c20_quim,1,48,15,'',NOW(), 9),(107,@c20_quim,1,48,15,'',NOW(), 9),(108,@c20_quim,1,48,14,'',NOW(), 9),(109,@c20_quim,1,48,15,'',NOW(), 9),(110,@c20_quim,1,48,14,'',NOW(), 9),
(106,@c20_bio, 1,49,14,'',NOW(),12),(107,@c20_bio, 1,49,15,'',NOW(),12),(108,@c20_bio, 1,49,14,'',NOW(),12),(109,@c20_bio, 1,49,14,'',NOW(),12),(110,@c20_bio, 1,49,15,'',NOW(),12),
(106,@c20_fis, 1,50,16,'',NOW(),17),(107,@c20_fis, 1,50,15,'',NOW(),17),(108,@c20_fis, 1,50,14,'',NOW(),17),(109,@c20_fis, 1,50,15,'',NOW(),17),(110,@c20_fis, 1,50,15,'',NOW(),17),
(106,@c20_rel, 1,51,15,'',NOW(),11),(107,@c20_rel, 1,51,14,'',NOW(),11),(108,@c20_rel, 1,51,15,'',NOW(),11),(109,@c20_rel, 1,51,15,'',NOW(),11),(110,@c20_rel, 1,51,14,'',NOW(),11),
(106,@c20_rel, 1,52,14,'',NOW(),11),(107,@c20_rel, 1,52,15,'',NOW(),11),(108,@c20_rel, 1,52,14,'',NOW(),11),(109,@c20_rel, 1,52,14,'',NOW(),11),(110,@c20_rel, 1,52,15,'',NOW(),11),
(106,@c20_ept, 1,53,16,'',NOW(),16),(107,@c20_ept, 1,53,15,'',NOW(),16),(108,@c20_ept, 1,53,14,'',NOW(),16),(109,@c20_ept, 1,53,15,'',NOW(),16),(110,@c20_ept, 1,53,15,'',NOW(),16),
(106,@c20_trans,1,56,16,'',NOW(),16),(107,@c20_trans,1,56,15,'',NOW(),16),(108,@c20_trans,1,56,14,'',NOW(),16),(109,@c20_trans,1,56,15,'',NOW(),16),(110,@c20_trans,1,56,15,'',NOW(),16),
(106,@c20_trans,1,57,15,'',NOW(),16),(107,@c20_trans,1,57,14,'',NOW(),16),(108,@c20_trans,1,57,15,'',NOW(),16),(109,@c20_trans,1,57,14,'',NOW(),16),(110,@c20_trans,1,57,15,'',NOW(),16);

-- Bloqueos E3
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(@c20_dpcc,28,1,10),(@c20_dpcc,29,1,10),
(@c20_hist,30,1, 7),(@c20_geo, 31,1,16),(@c20_econ,32,1,13),
(@c20_ef,33,1,19),(@c20_ef,34,1,19),(@c20_ef,35,1,19),
(@c20_arte,36,1,11),(@c20_arte,37,1,11),
(@c20_rv,38,1,15),(@c20_lit,39,1,7),(@c20_len,40,1,9),
(@c20_ing,41,1,6),(@c20_ing,42,1,6),(@c20_ing,43,1,6),
(@c20_arit,44,1,7),(@c20_alg,45,1,2),(@c20_geom,46,1,8),(@c20_trig,47,1,8),
(@c20_quim,48,1,9),(@c20_bio,49,1,12),(@c20_fis,50,1,17),
(@c20_rel,51,1,11),(@c20_rel,52,1,11),
(@c20_ept,53,1,16),
(@c20_trans,56,1,16),(@c20_trans,57,1,16);


-- =============================================================================
-- CORRECCIÓN: bloqueos con competencia incorrecta en carga 38 (Transversal 5°S A)
-- Carga 38 = transversal sec 22 (5°S A), docente 6.
-- Tenía bloqueos en comp 54/55 (Taller Raz.Mat.) en lugar de comp 56/57 (Transversal sec).
-- =============================================================================
DELETE FROM bloqueos_competencia WHERE carga_id=38 AND competencia_id IN (54,55);
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(38,56,1,6),(38,57,1,6);

UPDATE calificaciones SET competencia_id=56 WHERE carga_id=38 AND competencia_id=54;
UPDATE calificaciones SET competencia_id=57 WHERE carga_id=38 AND competencia_id=55;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- RESUMEN — boletas disponibles para demo (periodo_id = 1)
-- =============================================================================
-- E1  1°P A  escala literal:         /boleta/1/1   /boleta/digital/1/1
--     Otros alumnos sec 1: mat 2,3,4,5
--
-- E2  1°S A  notas reales + aliases:  /boleta/78/1  /boleta/digital/78/1
--     Otros alumnos sec 13: mat 79,80,81,82
--     (mat 80 tiene B con conclusión real en Geografía — útil para demostrar literal B)
--
-- E3  4°S A  Arte=Raz.Mat. aliases:   /boleta/106/1 /boleta/digital/106/1
--     Otros alumnos sec 20: mat 107,108,109,110
-- =============================================================================
