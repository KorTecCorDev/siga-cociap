-- =============================================================================
-- Seed 003: Escenarios completos de boleta para presentación al comité directivo
-- Colegio de Aplicación "Víctor Valenzuela Guardia" — UNASAM, mayo 2026
-- =============================================================================
-- Escenarios cubiertos:
--   E1  1° Primaria A  (sec 1)  — unidocente, escala solo literal (AD/A/B/C)
--   E2  1° Secundaria A (sec 13) — con Taller Raz. Mat., escala numérica+literal
--   E3  4° Secundaria A (sec 20) — Arte=Raz.Mat., Ed.Rel.=Ética, EPT=Hab.Ped.
--
-- Alumno de demo por sección (verlos en el panel de padre):
--   E1 → matricula_id = 1   (1°P A)
--   E2 → matricula_id = 78  (1°S A)
--   E3 → matricula_id = 106 (4°S A)
--
-- Notas del alumno de demo — variedad para mostrar todos los literales:
--   Primeras áreas: AD (17-20)  sin conclusión
--   Áreas medias:   A  (14-16)  sin conclusión
--   Penúltimas:     B  (11-13)  con conclusión (obligatoria en primaria, opcional en sec)
--   Última área:    C  (0-10)   con conclusión (obligatoria en ambos niveles)
--
-- Ejecutar DESPUÉS de tener cargado el backup_13_05_2026.sql
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- PASO 0: Tutor de la sección 1 (1°P A, unidocente)
-- ─────────────────────────────────────────────────────────────────────────────
-- El user_id 4 es el primer docente disponible sin tutoría asignada
UPDATE secciones SET tutor_id = 4 WHERE id = 1 AND tutor_id IS NULL;


-- =============================================================================
-- E1: 1° PRIMARIA A — unidocente, escala solo literal
-- Sección: 1 | Matriculas: 1-5 | Docente único: user 4
-- =============================================================================

-- Cargas: un solo docente cubre todas las áreas (unidocente)
INSERT IGNORE INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
VALUES
    (4, 1, 1, NULL,  1, 4, 'activa'),  -- Personal Social       → comp 4,5,6,7,8
    (4, 1, 1, NULL,  2, 3, 'activa'),  -- Educación Física       → comp 11,12,13
    (4, 1, 1, NULL,  3, 2, 'activa'),  -- Arte y Cultura         → comp 17,18
    (4, 1, 1, NULL,  4, 2, 'activa'),  -- Inglés                 → comp 1,2,3
    (4, 1, 1, NULL,  5, 2, 'activa'),  -- Ed. Religiosa          → comp 9,10
    (4, 1, 1,  1, NULL, 3, 'activa'),  -- Comunicación           → comp 14
    (4, 1, 1,  2, NULL, 3, 'activa'),  -- Plan Lector            → comp 15
    (4, 1, 1,  3, NULL, 3, 'activa'),  -- Razonamiento Verbal    → comp 16
    (4, 1, 1,  4, NULL, 4, 'activa'),  -- Aritmética             → comp 19
    (4, 1, 1,  5, NULL, 3, 'activa'),  -- Álgebra                → comp 20
    (4, 1, 1,  6, NULL, 3, 'activa'),  -- Geometría              → comp 21
    (4, 1, 1,  7, NULL, 3, 'activa'),  -- Razonamiento Matemático→ comp 22
    (4, 1, 1,  8, NULL, 3, 'activa'),  -- Química                → comp 23
    (4, 1, 1,  9, NULL, 3, 'activa'),  -- Biología               → comp 24
    (4, 1, 1, 10, NULL, 3, 'activa'),  -- Física                 → comp 25
    (4, 1, 1, NULL,  9, 0, 'activa');  -- Comp. Transversales    → comp 26,27

-- Capturar IDs de cargas de sec 1
SET @c1_ps    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=1   AND subarea_id IS NULL);
SET @c1_ef    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=2   AND subarea_id IS NULL);
SET @c1_arte  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=3   AND subarea_id IS NULL);
SET @c1_ing   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=4   AND subarea_id IS NULL);
SET @c1_rel   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=5   AND subarea_id IS NULL);
SET @c1_com   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=1);
SET @c1_pl    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=2);
SET @c1_rv    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=3);
SET @c1_arit  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=4);
SET @c1_alg   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=5);
SET @c1_geom  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=6);
SET @c1_rm    = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=7);
SET @c1_quim  = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=8);
SET @c1_bio   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=9);
SET @c1_fis   = (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND subarea_id=10);
SET @c1_transv= (SELECT id FROM cargas_academicas WHERE seccion_id=1 AND anio_id=1 AND area_id=9   AND subarea_id IS NULL);

-- Criterios: 2 por competencia (Práctica + Examen bimestral)
INSERT IGNORE INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    -- Personal Social (comp 4-8)
    (@c1_ps, 4, 1,'Práctica calificada',1), (@c1_ps, 4, 1,'Examen bimestral',2),
    (@c1_ps, 5, 1,'Práctica calificada',1), (@c1_ps, 5, 1,'Examen bimestral',2),
    (@c1_ps, 6, 1,'Práctica calificada',1), (@c1_ps, 6, 1,'Examen bimestral',2),
    (@c1_ps, 7, 1,'Práctica calificada',1), (@c1_ps, 7, 1,'Examen bimestral',2),
    (@c1_ps, 8, 1,'Práctica calificada',1), (@c1_ps, 8, 1,'Examen bimestral',2),
    -- Educación Física (comp 11-13)
    (@c1_ef, 11, 1,'Práctica calificada',1), (@c1_ef, 11, 1,'Examen bimestral',2),
    (@c1_ef, 12, 1,'Práctica calificada',1), (@c1_ef, 12, 1,'Examen bimestral',2),
    (@c1_ef, 13, 1,'Práctica calificada',1), (@c1_ef, 13, 1,'Examen bimestral',2),
    -- Arte y Cultura (comp 17-18)
    (@c1_arte, 17, 1,'Práctica calificada',1), (@c1_arte, 17, 1,'Examen bimestral',2),
    (@c1_arte, 18, 1,'Práctica calificada',1), (@c1_arte, 18, 1,'Examen bimestral',2),
    -- Inglés (comp 1-3)
    (@c1_ing, 1, 1,'Práctica calificada',1), (@c1_ing, 1, 1,'Examen bimestral',2),
    (@c1_ing, 2, 1,'Práctica calificada',1), (@c1_ing, 2, 1,'Examen bimestral',2),
    (@c1_ing, 3, 1,'Práctica calificada',1), (@c1_ing, 3, 1,'Examen bimestral',2),
    -- Ed. Religiosa (comp 9-10)
    (@c1_rel, 9,  1,'Práctica calificada',1), (@c1_rel, 9,  1,'Examen bimestral',2),
    (@c1_rel, 10, 1,'Práctica calificada',1), (@c1_rel, 10, 1,'Examen bimestral',2),
    -- Comunicación (comp 14-16 por subárea)
    (@c1_com, 14, 1,'Práctica calificada',1), (@c1_com, 14, 1,'Examen bimestral',2),
    (@c1_pl,  15, 1,'Práctica calificada',1), (@c1_pl,  15, 1,'Examen bimestral',2),
    (@c1_rv,  16, 1,'Práctica calificada',1), (@c1_rv,  16, 1,'Examen bimestral',2),
    -- Matemática (comp 19-22 por subárea)
    (@c1_arit, 19, 1,'Práctica calificada',1), (@c1_arit, 19, 1,'Examen bimestral',2),
    (@c1_alg,  20, 1,'Práctica calificada',1), (@c1_alg,  20, 1,'Examen bimestral',2),
    (@c1_geom, 21, 1,'Práctica calificada',1), (@c1_geom, 21, 1,'Examen bimestral',2),
    (@c1_rm,   22, 1,'Práctica calificada',1), (@c1_rm,   22, 1,'Examen bimestral',2),
    -- Ciencia y Tecnología (comp 23-25 por subárea)
    (@c1_quim, 23, 1,'Práctica calificada',1), (@c1_quim, 23, 1,'Examen bimestral',2),
    (@c1_bio,  24, 1,'Práctica calificada',1), (@c1_bio,  24, 1,'Examen bimestral',2),
    (@c1_fis,  25, 1,'Práctica calificada',1), (@c1_fis,  25, 1,'Examen bimestral',2),
    -- Comp. Transversales (comp 26-27)
    (@c1_transv, 26, 1,'I Bimestre',1),
    (@c1_transv, 27, 1,'I Bimestre',1);

-- Variables para criterios de sec 1 (pares por competencia: A=práctica, B=examen)
SET @cr1_ps4a =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=4 AND orden=1);
SET @cr1_ps4b =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=4 AND orden=2);
SET @cr1_ps5a =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=5 AND orden=1);
SET @cr1_ps5b =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=5 AND orden=2);
SET @cr1_ps6a =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=6 AND orden=1);
SET @cr1_ps6b =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=6 AND orden=2);
SET @cr1_ps7a =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=7 AND orden=1);
SET @cr1_ps7b =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=7 AND orden=2);
SET @cr1_ps8a =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=8 AND orden=1);
SET @cr1_ps8b =(SELECT id FROM criterios WHERE carga_id=@c1_ps AND competencia_id=8 AND orden=2);
SET @cr1_ef11a=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=11 AND orden=1);
SET @cr1_ef11b=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=11 AND orden=2);
SET @cr1_ef12a=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=12 AND orden=1);
SET @cr1_ef12b=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=12 AND orden=2);
SET @cr1_ef13a=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=13 AND orden=1);
SET @cr1_ef13b=(SELECT id FROM criterios WHERE carga_id=@c1_ef  AND competencia_id=13 AND orden=2);
SET @cr1_ar17a=(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=17 AND orden=1);
SET @cr1_ar17b=(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=17 AND orden=2);
SET @cr1_ar18a=(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=18 AND orden=1);
SET @cr1_ar18b=(SELECT id FROM criterios WHERE carga_id=@c1_arte AND competencia_id=18 AND orden=2);
SET @cr1_in1a =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=1  AND orden=1);
SET @cr1_in1b =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=1  AND orden=2);
SET @cr1_in2a =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=2  AND orden=1);
SET @cr1_in2b =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=2  AND orden=2);
SET @cr1_in3a =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=3  AND orden=1);
SET @cr1_in3b =(SELECT id FROM criterios WHERE carga_id=@c1_ing  AND competencia_id=3  AND orden=2);
SET @cr1_re9a =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=9  AND orden=1);
SET @cr1_re9b =(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=9  AND orden=2);
SET @cr1_re10a=(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=10 AND orden=1);
SET @cr1_re10b=(SELECT id FROM criterios WHERE carga_id=@c1_rel  AND competencia_id=10 AND orden=2);
SET @cr1_co14a=(SELECT id FROM criterios WHERE carga_id=@c1_com  AND competencia_id=14 AND orden=1);
SET @cr1_co14b=(SELECT id FROM criterios WHERE carga_id=@c1_com  AND competencia_id=14 AND orden=2);
SET @cr1_pl15a=(SELECT id FROM criterios WHERE carga_id=@c1_pl   AND competencia_id=15 AND orden=1);
SET @cr1_pl15b=(SELECT id FROM criterios WHERE carga_id=@c1_pl   AND competencia_id=15 AND orden=2);
SET @cr1_rv16a=(SELECT id FROM criterios WHERE carga_id=@c1_rv   AND competencia_id=16 AND orden=1);
SET @cr1_rv16b=(SELECT id FROM criterios WHERE carga_id=@c1_rv   AND competencia_id=16 AND orden=2);
SET @cr1_ar19a=(SELECT id FROM criterios WHERE carga_id=@c1_arit AND competencia_id=19 AND orden=1);
SET @cr1_ar19b=(SELECT id FROM criterios WHERE carga_id=@c1_arit AND competencia_id=19 AND orden=2);
SET @cr1_al20a=(SELECT id FROM criterios WHERE carga_id=@c1_alg  AND competencia_id=20 AND orden=1);
SET @cr1_al20b=(SELECT id FROM criterios WHERE carga_id=@c1_alg  AND competencia_id=20 AND orden=2);
SET @cr1_ge21a=(SELECT id FROM criterios WHERE carga_id=@c1_geom AND competencia_id=21 AND orden=1);
SET @cr1_ge21b=(SELECT id FROM criterios WHERE carga_id=@c1_geom AND competencia_id=21 AND orden=2);
SET @cr1_rm22a=(SELECT id FROM criterios WHERE carga_id=@c1_rm   AND competencia_id=22 AND orden=1);
SET @cr1_rm22b=(SELECT id FROM criterios WHERE carga_id=@c1_rm   AND competencia_id=22 AND orden=2);
SET @cr1_qu23a=(SELECT id FROM criterios WHERE carga_id=@c1_quim AND competencia_id=23 AND orden=1);
SET @cr1_qu23b=(SELECT id FROM criterios WHERE carga_id=@c1_quim AND competencia_id=23 AND orden=2);
SET @cr1_bi24a=(SELECT id FROM criterios WHERE carga_id=@c1_bio  AND competencia_id=24 AND orden=1);
SET @cr1_bi24b=(SELECT id FROM criterios WHERE carga_id=@c1_bio  AND competencia_id=24 AND orden=2);
SET @cr1_fi25a=(SELECT id FROM criterios WHERE carga_id=@c1_fis  AND competencia_id=25 AND orden=1);
SET @cr1_fi25b=(SELECT id FROM criterios WHERE carga_id=@c1_fis  AND competencia_id=25 AND orden=2);
SET @cr1_tr26 =(SELECT id FROM criterios WHERE carga_id=@c1_transv AND competencia_id=26);
SET @cr1_tr27 =(SELECT id FROM criterios WHERE carga_id=@c1_transv AND competencia_id=27);

-- ── Notas por criterio (5 alumnos, mat 1-5) ──────────────────────────────────
-- mat 1 = alumno demo: AD→A→B(con conclusión)→C(con conclusión) a lo largo de la boleta
-- mat 2-5 = resto de alumnos: notas uniformes A (15)
-- Formato: (criterio_id, matricula_id, nota)

INSERT IGNORE INTO calificaciones_criterio (criterio_id, matricula_id, nota) VALUES
-- Personal Social — comp 4 → mat1: 19(AD), resto: 15
(@cr1_ps4a,1,19),(@cr1_ps4a,2,15),(@cr1_ps4a,3,16),(@cr1_ps4a,4,14),(@cr1_ps4a,5,15),
(@cr1_ps4b,1,18),(@cr1_ps4b,2,15),(@cr1_ps4b,3,16),(@cr1_ps4b,4,15),(@cr1_ps4b,5,14),
-- Personal Social — comp 5 → mat1: 18(AD)
(@cr1_ps5a,1,18),(@cr1_ps5a,2,14),(@cr1_ps5a,3,15),(@cr1_ps5a,4,16),(@cr1_ps5a,5,15),
(@cr1_ps5b,1,18),(@cr1_ps5b,2,15),(@cr1_ps5b,3,14),(@cr1_ps5b,4,15),(@cr1_ps5b,5,16),
-- Personal Social — comp 6 → mat1: 17(AD)
(@cr1_ps6a,1,17),(@cr1_ps6a,2,15),(@cr1_ps6a,3,14),(@cr1_ps6a,4,15),(@cr1_ps6a,5,14),
(@cr1_ps6b,1,17),(@cr1_ps6b,2,14),(@cr1_ps6b,3,15),(@cr1_ps6b,4,14),(@cr1_ps6b,5,15),
-- Personal Social — comp 7 → mat1: 15(A)
(@cr1_ps7a,1,15),(@cr1_ps7a,2,15),(@cr1_ps7a,3,14),(@cr1_ps7a,4,13),(@cr1_ps7a,5,15),
(@cr1_ps7b,1,15),(@cr1_ps7b,2,14),(@cr1_ps7b,3,15),(@cr1_ps7b,4,14),(@cr1_ps7b,5,14),
-- Personal Social — comp 8 → mat1: 16(A)
(@cr1_ps8a,1,16),(@cr1_ps8a,2,14),(@cr1_ps8a,3,15),(@cr1_ps8a,4,15),(@cr1_ps8a,5,15),
(@cr1_ps8b,1,16),(@cr1_ps8b,2,15),(@cr1_ps8b,3,14),(@cr1_ps8b,4,14),(@cr1_ps8b,5,15),
-- Ed. Física — comp 11 → mat1: 17(AD)
(@cr1_ef11a,1,17),(@cr1_ef11a,2,15),(@cr1_ef11a,3,14),(@cr1_ef11a,4,15),(@cr1_ef11a,5,15),
(@cr1_ef11b,1,17),(@cr1_ef11b,2,14),(@cr1_ef11b,3,15),(@cr1_ef11b,4,15),(@cr1_ef11b,5,14),
-- Ed. Física — comp 12 → mat1: 15(A)
(@cr1_ef12a,1,15),(@cr1_ef12a,2,15),(@cr1_ef12a,3,14),(@cr1_ef12a,4,15),(@cr1_ef12a,5,14),
(@cr1_ef12b,1,15),(@cr1_ef12b,2,14),(@cr1_ef12b,3,15),(@cr1_ef12b,4,14),(@cr1_ef12b,5,15),
-- Ed. Física — comp 13 → mat1: 14(A)
(@cr1_ef13a,1,14),(@cr1_ef13a,2,14),(@cr1_ef13a,3,15),(@cr1_ef13a,4,15),(@cr1_ef13a,5,13),
(@cr1_ef13b,1,14),(@cr1_ef13b,2,15),(@cr1_ef13b,3,14),(@cr1_ef13b,4,14),(@cr1_ef13b,5,14),
-- Arte y Cultura — comp 17 → mat1: 15(A)
(@cr1_ar17a,1,15),(@cr1_ar17a,2,15),(@cr1_ar17a,3,14),(@cr1_ar17a,4,15),(@cr1_ar17a,5,14),
(@cr1_ar17b,1,15),(@cr1_ar17b,2,14),(@cr1_ar17b,3,15),(@cr1_ar17b,4,14),(@cr1_ar17b,5,15),
-- Arte y Cultura — comp 18 → mat1: 16(A)
(@cr1_ar18a,1,16),(@cr1_ar18a,2,15),(@cr1_ar18a,3,14),(@cr1_ar18a,4,15),(@cr1_ar18a,5,15),
(@cr1_ar18b,1,16),(@cr1_ar18b,2,14),(@cr1_ar18b,3,15),(@cr1_ar18b,4,14),(@cr1_ar18b,5,14),
-- Inglés — comp 1 → mat1: 14(A)
(@cr1_in1a,1,14),(@cr1_in1a,2,15),(@cr1_in1a,3,13),(@cr1_in1a,4,14),(@cr1_in1a,5,15),
(@cr1_in1b,1,14),(@cr1_in1b,2,14),(@cr1_in1b,3,14),(@cr1_in1b,4,13),(@cr1_in1b,5,14),
-- Inglés — comp 2 → mat1: 15(A)
(@cr1_in2a,1,15),(@cr1_in2a,2,14),(@cr1_in2a,3,15),(@cr1_in2a,4,15),(@cr1_in2a,5,14),
(@cr1_in2b,1,15),(@cr1_in2b,2,15),(@cr1_in2b,3,14),(@cr1_in2b,4,14),(@cr1_in2b,5,15),
-- Inglés — comp 3 → mat1: 13(B, con conclusión)
(@cr1_in3a,1,13),(@cr1_in3a,2,14),(@cr1_in3a,3,15),(@cr1_in3a,4,14),(@cr1_in3a,5,15),
(@cr1_in3b,1,13),(@cr1_in3b,2,15),(@cr1_in3b,3,14),(@cr1_in3b,4,15),(@cr1_in3b,5,14),
-- Ed. Religiosa — comp 9 → mat1: 15(A)
(@cr1_re9a,1,15),(@cr1_re9a,2,15),(@cr1_re9a,3,14),(@cr1_re9a,4,15),(@cr1_re9a,5,14),
(@cr1_re9b,1,15),(@cr1_re9b,2,14),(@cr1_re9b,3,15),(@cr1_re9b,4,14),(@cr1_re9b,5,15),
-- Ed. Religiosa — comp 10 → mat1: 14(A)
(@cr1_re10a,1,14),(@cr1_re10a,2,14),(@cr1_re10a,3,15),(@cr1_re10a,4,14),(@cr1_re10a,5,15),
(@cr1_re10b,1,14),(@cr1_re10b,2,15),(@cr1_re10b,3,14),(@cr1_re10b,4,15),(@cr1_re10b,5,14),
-- Comunicación — comp 14 → mat1: 12(B, con conclusión)
(@cr1_co14a,1,12),(@cr1_co14a,2,15),(@cr1_co14a,3,14),(@cr1_co14a,4,15),(@cr1_co14a,5,14),
(@cr1_co14b,1,12),(@cr1_co14b,2,14),(@cr1_co14b,3,15),(@cr1_co14b,4,14),(@cr1_co14b,5,15),
-- Plan Lector — comp 15 → mat1: 14(A)
(@cr1_pl15a,1,14),(@cr1_pl15a,2,15),(@cr1_pl15a,3,14),(@cr1_pl15a,4,15),(@cr1_pl15a,5,14),
(@cr1_pl15b,1,14),(@cr1_pl15b,2,14),(@cr1_pl15b,3,15),(@cr1_pl15b,4,14),(@cr1_pl15b,5,15),
-- Razonamiento Verbal — comp 16 → mat1: 11(B, con conclusión)
(@cr1_rv16a,1,11),(@cr1_rv16a,2,14),(@cr1_rv16a,3,15),(@cr1_rv16a,4,14),(@cr1_rv16a,5,15),
(@cr1_rv16b,1,11),(@cr1_rv16b,2,15),(@cr1_rv16b,3,14),(@cr1_rv16b,4,15),(@cr1_rv16b,5,14),
-- Aritmética — comp 19 → mat1: 14(A)
(@cr1_ar19a,1,14),(@cr1_ar19a,2,15),(@cr1_ar19a,3,14),(@cr1_ar19a,4,15),(@cr1_ar19a,5,14),
(@cr1_ar19b,1,14),(@cr1_ar19b,2,14),(@cr1_ar19b,3,15),(@cr1_ar19b,4,14),(@cr1_ar19b,5,15),
-- Álgebra — comp 20 → mat1: 15(A)
(@cr1_al20a,1,15),(@cr1_al20a,2,14),(@cr1_al20a,3,15),(@cr1_al20a,4,14),(@cr1_al20a,5,15),
(@cr1_al20b,1,15),(@cr1_al20b,2,15),(@cr1_al20b,3,14),(@cr1_al20b,4,15),(@cr1_al20b,5,14),
-- Geometría — comp 21 → mat1: 11(B, con conclusión)
(@cr1_ge21a,1,11),(@cr1_ge21a,2,14),(@cr1_ge21a,3,15),(@cr1_ge21a,4,14),(@cr1_ge21a,5,15),
(@cr1_ge21b,1,11),(@cr1_ge21b,2,15),(@cr1_ge21b,3,14),(@cr1_ge21b,4,15),(@cr1_ge21b,5,14),
-- Razonamiento Matemático — comp 22 → mat1: 08(C, con conclusión)
(@cr1_rm22a,1, 8),(@cr1_rm22a,2,14),(@cr1_rm22a,3,15),(@cr1_rm22a,4,13),(@cr1_rm22a,5,14),
(@cr1_rm22b,1, 8),(@cr1_rm22b,2,15),(@cr1_rm22b,3,14),(@cr1_rm22b,4,14),(@cr1_rm22b,5,15),
-- Química — comp 23 → mat1: 14(A)
(@cr1_qu23a,1,14),(@cr1_qu23a,2,15),(@cr1_qu23a,3,14),(@cr1_qu23a,4,15),(@cr1_qu23a,5,14),
(@cr1_qu23b,1,14),(@cr1_qu23b,2,14),(@cr1_qu23b,3,15),(@cr1_qu23b,4,14),(@cr1_qu23b,5,15),
-- Biología — comp 24 → mat1: 15(A)
(@cr1_bi24a,1,15),(@cr1_bi24a,2,15),(@cr1_bi24a,3,14),(@cr1_bi24a,4,15),(@cr1_bi24a,5,14),
(@cr1_bi24b,1,15),(@cr1_bi24b,2,14),(@cr1_bi24b,3,15),(@cr1_bi24b,4,14),(@cr1_bi24b,5,15),
-- Física — comp 25 → mat1: 14(A)
(@cr1_fi25a,1,14),(@cr1_fi25a,2,14),(@cr1_fi25a,3,15),(@cr1_fi25a,4,14),(@cr1_fi25a,5,15),
(@cr1_fi25b,1,14),(@cr1_fi25b,2,15),(@cr1_fi25b,3,14),(@cr1_fi25b,4,15),(@cr1_fi25b,5,14),
-- Comp. Transversales — comp 26-27 → mat1: 15/14
(@cr1_tr26,1,15),(@cr1_tr26,2,15),(@cr1_tr26,3,14),(@cr1_tr26,4,15),(@cr1_tr26,5,14),
(@cr1_tr27,1,14),(@cr1_tr27,2,14),(@cr1_tr27,3,15),(@cr1_tr27,4,14),(@cr1_tr27,5,15);

-- Calificaciones computadas (promedio de criterios)
-- Formato: (matricula_id, carga_id, periodo_id, competencia_id, nota, conclusion, registrado_por)
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
-- Personal Social (AD en todas para mat1)
(1,@c1_ps,1,4, 19,'',NOW(),4),(2,@c1_ps,1,4,15,'',NOW(),4),(3,@c1_ps,1,4,16,'',NOW(),4),(4,@c1_ps,1,4,15,'',NOW(),4),(5,@c1_ps,1,4,15,'',NOW(),4),
(1,@c1_ps,1,5, 18,'',NOW(),4),(2,@c1_ps,1,5,15,'',NOW(),4),(3,@c1_ps,1,5,15,'',NOW(),4),(4,@c1_ps,1,5,16,'',NOW(),4),(5,@c1_ps,1,5,16,'',NOW(),4),
(1,@c1_ps,1,6, 17,'',NOW(),4),(2,@c1_ps,1,6,15,'',NOW(),4),(3,@c1_ps,1,6,15,'',NOW(),4),(4,@c1_ps,1,6,15,'',NOW(),4),(5,@c1_ps,1,6,15,'',NOW(),4),
(1,@c1_ps,1,7, 15,'',NOW(),4),(2,@c1_ps,1,7,15,'',NOW(),4),(3,@c1_ps,1,7,15,'',NOW(),4),(4,@c1_ps,1,7,14,'',NOW(),4),(5,@c1_ps,1,7,15,'',NOW(),4),
(1,@c1_ps,1,8, 16,'',NOW(),4),(2,@c1_ps,1,8,15,'',NOW(),4),(3,@c1_ps,1,8,15,'',NOW(),4),(4,@c1_ps,1,8,15,'',NOW(),4),(5,@c1_ps,1,8,15,'',NOW(),4),
-- Educación Física
(1,@c1_ef,1,11,17,'',NOW(),4),(2,@c1_ef,1,11,15,'',NOW(),4),(3,@c1_ef,1,11,15,'',NOW(),4),(4,@c1_ef,1,11,15,'',NOW(),4),(5,@c1_ef,1,11,15,'',NOW(),4),
(1,@c1_ef,1,12,15,'',NOW(),4),(2,@c1_ef,1,12,15,'',NOW(),4),(3,@c1_ef,1,12,15,'',NOW(),4),(4,@c1_ef,1,12,15,'',NOW(),4),(5,@c1_ef,1,12,15,'',NOW(),4),
(1,@c1_ef,1,13,14,'',NOW(),4),(2,@c1_ef,1,13,15,'',NOW(),4),(3,@c1_ef,1,13,15,'',NOW(),4),(4,@c1_ef,1,13,15,'',NOW(),4),(5,@c1_ef,1,13,14,'',NOW(),4),
-- Arte y Cultura
(1,@c1_arte,1,17,15,'',NOW(),4),(2,@c1_arte,1,17,15,'',NOW(),4),(3,@c1_arte,1,17,15,'',NOW(),4),(4,@c1_arte,1,17,15,'',NOW(),4),(5,@c1_arte,1,17,15,'',NOW(),4),
(1,@c1_arte,1,18,16,'',NOW(),4),(2,@c1_arte,1,18,15,'',NOW(),4),(3,@c1_arte,1,18,15,'',NOW(),4),(4,@c1_arte,1,18,15,'',NOW(),4),(5,@c1_arte,1,18,15,'',NOW(),4),
-- Inglés
(1,@c1_ing,1,1,14,'',NOW(),4),(2,@c1_ing,1,1,15,'',NOW(),4),(3,@c1_ing,1,1,14,'',NOW(),4),(4,@c1_ing,1,1,14,'',NOW(),4),(5,@c1_ing,1,1,15,'',NOW(),4),
(1,@c1_ing,1,2,15,'',NOW(),4),(2,@c1_ing,1,2,15,'',NOW(),4),(3,@c1_ing,1,2,15,'',NOW(),4),(4,@c1_ing,1,2,15,'',NOW(),4),(5,@c1_ing,1,2,15,'',NOW(),4),
(1,@c1_ing,1,3,13,'Debe practicar más la redacción en inglés para alcanzar el nivel esperado.',NOW(),4),(2,@c1_ing,1,3,15,'',NOW(),4),(3,@c1_ing,1,3,15,'',NOW(),4),(4,@c1_ing,1,3,15,'',NOW(),4),(5,@c1_ing,1,3,15,'',NOW(),4),
-- Educación Religiosa
(1,@c1_rel,1,9, 15,'',NOW(),4),(2,@c1_rel,1,9, 15,'',NOW(),4),(3,@c1_rel,1,9, 15,'',NOW(),4),(4,@c1_rel,1,9, 15,'',NOW(),4),(5,@c1_rel,1,9, 15,'',NOW(),4),
(1,@c1_rel,1,10,14,'',NOW(),4),(2,@c1_rel,1,10,15,'',NOW(),4),(3,@c1_rel,1,10,15,'',NOW(),4),(4,@c1_rel,1,10,15,'',NOW(),4),(5,@c1_rel,1,10,15,'',NOW(),4),
-- Comunicación / Plan Lector / Razonamiento Verbal
(1,@c1_com,1,14,12,'Necesita reforzar su expresión oral. Se recomienda practicar en casa con lecturas en voz alta.',NOW(),4),(2,@c1_com,1,14,15,'',NOW(),4),(3,@c1_com,1,14,15,'',NOW(),4),(4,@c1_com,1,14,15,'',NOW(),4),(5,@c1_com,1,14,15,'',NOW(),4),
(1,@c1_pl, 1,15,14,'',NOW(),4),(2,@c1_pl, 1,15,15,'',NOW(),4),(3,@c1_pl, 1,15,14,'',NOW(),4),(4,@c1_pl, 1,15,15,'',NOW(),4),(5,@c1_pl, 1,15,14,'',NOW(),4),
(1,@c1_rv, 1,16,11,'Requiere acompañamiento en comprensión lectora. Se sugiere leer textos breves cada noche.',NOW(),4),(2,@c1_rv, 1,16,15,'',NOW(),4),(3,@c1_rv, 1,16,15,'',NOW(),4),(4,@c1_rv, 1,16,15,'',NOW(),4),(5,@c1_rv, 1,16,14,'',NOW(),4),
-- Matemática
(1,@c1_arit,1,19,14,'',NOW(),4),(2,@c1_arit,1,19,15,'',NOW(),4),(3,@c1_arit,1,19,15,'',NOW(),4),(4,@c1_arit,1,19,15,'',NOW(),4),(5,@c1_arit,1,19,15,'',NOW(),4),
(1,@c1_alg, 1,20,15,'',NOW(),4),(2,@c1_alg, 1,20,15,'',NOW(),4),(3,@c1_alg, 1,20,15,'',NOW(),4),(4,@c1_alg, 1,20,15,'',NOW(),4),(5,@c1_alg, 1,20,15,'',NOW(),4),
(1,@c1_geom,1,21,11,'Necesita practicar más la identificación de figuras geométricas. Recomiendo ejercicios complementarios.',NOW(),4),(2,@c1_geom,1,21,15,'',NOW(),4),(3,@c1_geom,1,21,14,'',NOW(),4),(4,@c1_geom,1,21,15,'',NOW(),4),(5,@c1_geom,1,21,15,'',NOW(),4),
(1,@c1_rm,  1,22, 8,'El/la estudiante presenta dificultades para resolver problemas de razonamiento matemático. Se recomienda apoyo adicional fuera del horario escolar y comunicación constante con la familia.',NOW(),4),(2,@c1_rm,1,22,15,'',NOW(),4),(3,@c1_rm,1,22,15,'',NOW(),4),(4,@c1_rm,1,22,14,'',NOW(),4),(5,@c1_rm,1,22,15,'',NOW(),4),
-- Ciencia y Tecnología
(1,@c1_quim,1,23,14,'',NOW(),4),(2,@c1_quim,1,23,15,'',NOW(),4),(3,@c1_quim,1,23,15,'',NOW(),4),(4,@c1_quim,1,23,14,'',NOW(),4),(5,@c1_quim,1,23,15,'',NOW(),4),
(1,@c1_bio, 1,24,15,'',NOW(),4),(2,@c1_bio, 1,24,15,'',NOW(),4),(3,@c1_bio, 1,24,14,'',NOW(),4),(4,@c1_bio, 1,24,15,'',NOW(),4),(5,@c1_bio, 1,24,15,'',NOW(),4),
(1,@c1_fis, 1,25,14,'',NOW(),4),(2,@c1_fis, 1,25,15,'',NOW(),4),(3,@c1_fis, 1,25,15,'',NOW(),4),(4,@c1_fis, 1,25,15,'',NOW(),4),(5,@c1_fis, 1,25,14,'',NOW(),4),
-- Competencias Transversales
(1,@c1_transv,1,26,15,'',NOW(),4),(2,@c1_transv,1,26,15,'',NOW(),4),(3,@c1_transv,1,26,14,'',NOW(),4),(4,@c1_transv,1,26,15,'',NOW(),4),(5,@c1_transv,1,26,14,'',NOW(),4),
(1,@c1_transv,1,27,14,'',NOW(),4),(2,@c1_transv,1,27,14,'',NOW(),4),(3,@c1_transv,1,27,15,'',NOW(),4),(4,@c1_transv,1,27,14,'',NOW(),4),(5,@c1_transv,1,27,15,'',NOW(),4);

-- Bloqueos E1 (tutor = user 4, bloquea también los transversales)
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(@c1_ps,4,1,4),(@c1_ps,5,1,4),(@c1_ps,6,1,4),(@c1_ps,7,1,4),(@c1_ps,8,1,4),
(@c1_ef,11,1,4),(@c1_ef,12,1,4),(@c1_ef,13,1,4),
(@c1_arte,17,1,4),(@c1_arte,18,1,4),
(@c1_ing,1,1,4),(@c1_ing,2,1,4),(@c1_ing,3,1,4),
(@c1_rel,9,1,4),(@c1_rel,10,1,4),
(@c1_com,14,1,4),(@c1_pl,15,1,4),(@c1_rv,16,1,4),
(@c1_arit,19,1,4),(@c1_alg,20,1,4),(@c1_geom,21,1,4),(@c1_rm,22,1,4),
(@c1_quim,23,1,4),(@c1_bio,24,1,4),(@c1_fis,25,1,4),
(@c1_transv,26,1,4),(@c1_transv,27,1,4);


-- =============================================================================
-- E2: 1° SECUNDARIA A — ampliar datos existentes (cargas 1-12, 29)
-- Sección: 13 | Matriculas: 78-82
-- Escala: ambas (numérica + literal) | Con Taller Raz. Mat.
-- =============================================================================

-- Cargas faltantes en sec 13 (1°S A):
-- DPCC, Historia, Economía, Aritmética, Geometría, Química, Ed. Religiosa, EPT
INSERT IGNORE INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
VALUES
    (10, 13, 1, NULL, 10, 3, 'activa'),  -- DPCC           → comp 28,29
    (13, 13, 1,   11, NULL, 2, 'activa'),-- Historia        → comp 30
    (20, 13, 1,   13, NULL, 2, 'activa'),-- Economía        → comp 32
    ( 7, 13, 1,   17, NULL, 4, 'activa'),-- Aritmética      → comp 44
    ( 8, 13, 1,   19, NULL, 3, 'activa'),-- Geometría       → comp 46
    ( 9, 13, 1,   21, NULL, 3, 'activa'),-- Química         → comp 48
    (11, 13, 1, NULL, 14, 2, 'activa'),  -- Ed. Religiosa   → comp 51,52
    (16, 13, 1, NULL, 15, 2, 'activa');  -- EPT             → comp 53

SET @c13_dpcc = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND area_id=10   AND subarea_id IS NULL);
SET @c13_hist = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=11);
SET @c13_econ = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=13);
SET @c13_arit = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=17);
SET @c13_geom = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=19);
SET @c13_quim = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND subarea_id=21);
SET @c13_rel  = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND area_id=14  AND subarea_id IS NULL);
SET @c13_ept  = (SELECT id FROM cargas_academicas WHERE seccion_id=13 AND anio_id=1 AND area_id=15  AND subarea_id IS NULL);

-- Criterios para cargas nuevas de sec 13
INSERT IGNORE INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    (@c13_dpcc,28,1,'Práctica calificada',1),(@c13_dpcc,28,1,'Examen bimestral',2),
    (@c13_dpcc,29,1,'Práctica calificada',1),(@c13_dpcc,29,1,'Examen bimestral',2),
    (@c13_hist,30,1,'Práctica calificada',1),(@c13_hist,30,1,'Examen bimestral',2),
    (@c13_econ,32,1,'Práctica calificada',1),(@c13_econ,32,1,'Examen bimestral',2),
    (@c13_arit,44,1,'Práctica calificada',1),(@c13_arit,44,1,'Examen bimestral',2),
    (@c13_geom,46,1,'Práctica calificada',1),(@c13_geom,46,1,'Examen bimestral',2),
    (@c13_quim,48,1,'Práctica calificada',1),(@c13_quim,48,1,'Examen bimestral',2),
    (@c13_rel, 51,1,'Práctica calificada',1),(@c13_rel, 51,1,'Examen bimestral',2),
    (@c13_rel, 52,1,'Práctica calificada',1),(@c13_rel, 52,1,'Examen bimestral',2),
    (@c13_ept, 53,1,'I Bimestre',1);

-- También criterios para cargas existentes que no los tienen (cargas 1-9, 11-12, 29)
INSERT IGNORE INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
    -- carga 1: Literatura (comp 39), carga 2: Biología (comp 49), carga 3: Lenguaje (comp 40)
    (1,39,1,'Práctica calificada',1),(1,39,1,'Examen bimestral',2),
    (2,49,1,'Práctica calificada',1),(2,49,1,'Examen bimestral',2),
    (3,40,1,'Práctica calificada',1),(3,40,1,'Examen bimestral',2),
    -- carga 4: Geografía (comp 31)
    (4,31,1,'Práctica calificada',1),(4,31,1,'Examen bimestral',2),
    -- carga 5: Ed. Física (comp 33,34,35)
    (5,33,1,'Práctica calificada',1),(5,33,1,'Examen bimestral',2),
    (5,34,1,'Práctica calificada',1),(5,34,1,'Examen bimestral',2),
    (5,35,1,'Práctica calificada',1),(5,35,1,'Examen bimestral',2),
    -- carga 6: Arte y Cultura (comp 36,37)
    (6,36,1,'Práctica calificada',1),(6,36,1,'Examen bimestral',2),
    (6,37,1,'Práctica calificada',1),(6,37,1,'Examen bimestral',2),
    -- carga 7: Razonamiento Verbal (comp 38)
    (7,38,1,'Práctica calificada',1),(7,38,1,'Examen bimestral',2),
    -- carga 8: Álgebra (comp 45)
    (8,45,1,'Práctica calificada',1),(8,45,1,'Examen bimestral',2),
    -- carga 9: Trigonometría (comp 47)
    (9,47,1,'Práctica calificada',1),(9,47,1,'Examen bimestral',2),
    -- carga 11: Física (comp 50)
    (11,50,1,'Práctica calificada',1),(11,50,1,'Examen bimestral',2),
    -- carga 12: Taller Raz. Mat. (comp 54,55) — ya tiene bloqueos; solo criterios
    (12,54,1,'I Bimestre',1),
    (12,55,1,'I Bimestre',1),
    -- carga 29: Transversales (comp 56,57) — tutor Sotelo
    (29,56,1,'I Bimestre',1),
    (29,57,1,'I Bimestre',1);

-- Calificaciones para cargas existentes en sec 13 (mat 78-82)
-- mat 78 = alumno demo: notas variadas; mat 79-82: uniformes A (15)
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
-- Carga 1: Literatura (comp 39) → mat78: 18(AD)
(78,1,1,39,18,'',NOW(),7),(79,1,1,39,15,'',NOW(),7),(80,1,1,39,16,'',NOW(),7),(81,1,1,39,14,'',NOW(),7),(82,1,1,39,15,'',NOW(),7),
-- Carga 2: Biología (comp 49) → mat78: 16(A)
(78,2,1,49,16,'',NOW(),12),(79,2,1,49,15,'',NOW(),12),(80,2,1,49,14,'',NOW(),12),(81,2,1,49,15,'',NOW(),12),(82,2,1,49,15,'',NOW(),12),
-- Carga 3: Lenguaje (comp 40) → mat78: 15(A)
(78,3,1,40,15,'',NOW(),9),(79,3,1,40,14,'',NOW(),9),(80,3,1,40,15,'',NOW(),9),(81,3,1,40,15,'',NOW(),9),(82,3,1,40,14,'',NOW(),9),
-- Carga 4: Geografía (comp 31) → mat78: 14(A)
(78,4,1,31,14,'',NOW(),16),(79,4,1,31,15,'',NOW(),16),(80,4,1,31,15,'',NOW(),16),(81,4,1,31,14,'',NOW(),16),(82,4,1,31,15,'',NOW(),16),
-- Carga 5: Ed. Física comp 33,34,35 → mat78: 15/14/17
(78,5,1,33,15,'',NOW(),19),(79,5,1,33,15,'',NOW(),19),(80,5,1,33,14,'',NOW(),19),(81,5,1,33,15,'',NOW(),19),(82,5,1,33,15,'',NOW(),19),
(78,5,1,34,14,'',NOW(),19),(79,5,1,34,15,'',NOW(),19),(80,5,1,34,15,'',NOW(),19),(81,5,1,34,14,'',NOW(),19),(82,5,1,34,15,'',NOW(),19),
(78,5,1,35,17,'',NOW(),19),(79,5,1,35,15,'',NOW(),19),(80,5,1,35,14,'',NOW(),19),(81,5,1,35,15,'',NOW(),19),(82,5,1,35,15,'',NOW(),19),
-- Carga 6: Arte y Cultura comp 36,37 → mat78: 12(B)/15
(78,6,1,36,12,'Debe desarrollar mayor sensibilidad artística. Se sugiere participar en actividades extracurriculares de arte.',NOW(),11),
(79,6,1,36,15,'',NOW(),11),(80,6,1,36,14,'',NOW(),11),(81,6,1,36,15,'',NOW(),11),(82,6,1,36,15,'',NOW(),11),
(78,6,1,37,15,'',NOW(),11),(79,6,1,37,14,'',NOW(),11),(80,6,1,37,15,'',NOW(),11),(81,6,1,37,15,'',NOW(),11),(82,6,1,37,14,'',NOW(),11),
-- Carga 7: Razonamiento Verbal (comp 38) → mat78: 15(A)
(78,7,1,38,15,'',NOW(),15),(79,7,1,38,15,'',NOW(),15),(80,7,1,38,14,'',NOW(),15),(81,7,1,38,15,'',NOW(),15),(82,7,1,38,14,'',NOW(),15),
-- Carga 8: Álgebra (comp 45) → mat78: 11(B)
(78,8,1,45,11,'Necesita reforzar los conceptos de álgebra. Se recomienda realizar ejercicios adicionales en casa.',NOW(),2),
(79,8,1,45,15,'',NOW(),2),(80,8,1,45,14,'',NOW(),2),(81,8,1,45,15,'',NOW(),2),(82,8,1,45,15,'',NOW(),2),
-- Carga 9: Trigonometría (comp 47) → mat78: 14(A)
(78,9,1,47,14,'',NOW(),8),(79,9,1,47,15,'',NOW(),8),(80,9,1,47,14,'',NOW(),8),(81,9,1,47,14,'',NOW(),8),(82,9,1,47,15,'',NOW(),8),
-- Carga 11: Física (comp 50) → mat78: 09(C)
(78,11,1,50, 9,'El/la estudiante necesita apoyo urgente en Física. Se recomienda clases de refuerzo y comunicación con los padres de familia para establecer un plan de mejora.',NOW(),17),
(79,11,1,50,15,'',NOW(),17),(80,11,1,50,14,'',NOW(),17),(81,11,1,50,15,'',NOW(),17),(82,11,1,50,15,'',NOW(),17),
-- Carga 12: Taller Raz. Mat. comp 54,55 → mat78: 15/16
(78,12,1,54,15,'',NOW(),18),(79,12,1,54,14,'',NOW(),18),(80,12,1,54,15,'',NOW(),18),(81,12,1,54,15,'',NOW(),18),(82,12,1,54,14,'',NOW(),18),
(78,12,1,55,16,'',NOW(),18),(79,12,1,55,15,'',NOW(),18),(80,12,1,55,15,'',NOW(),18),(81,12,1,55,16,'',NOW(),18),(82,12,1,55,15,'',NOW(),18),
-- Carga 29: Transversales comp 56,57 → mat78: 15/14
(78,29,1,56,15,'',NOW(),2),(79,29,1,56,15,'',NOW(),2),(80,29,1,56,14,'',NOW(),2),(81,29,1,56,15,'',NOW(),2),(82,29,1,56,15,'',NOW(),2),
(78,29,1,57,14,'',NOW(),2),(79,29,1,57,14,'',NOW(),2),(80,29,1,57,15,'',NOW(),2),(81,29,1,57,14,'',NOW(),2),(82,29,1,57,14,'',NOW(),2),
-- Cargas nuevas de sec 13
(78,@c13_dpcc,1,28,17,'',NOW(),10),(79,@c13_dpcc,1,28,15,'',NOW(),10),(80,@c13_dpcc,1,28,14,'',NOW(),10),(81,@c13_dpcc,1,28,15,'',NOW(),10),(82,@c13_dpcc,1,28,15,'',NOW(),10),
(78,@c13_dpcc,1,29,16,'',NOW(),10),(79,@c13_dpcc,1,29,15,'',NOW(),10),(80,@c13_dpcc,1,29,15,'',NOW(),10),(81,@c13_dpcc,1,29,14,'',NOW(),10),(82,@c13_dpcc,1,29,15,'',NOW(),10),
(78,@c13_hist,1,30,15,'',NOW(),13),(79,@c13_hist,1,30,15,'',NOW(),13),(80,@c13_hist,1,30,14,'',NOW(),13),(81,@c13_hist,1,30,15,'',NOW(),13),(82,@c13_hist,1,30,15,'',NOW(),13),
(78,@c13_econ,1,32,14,'',NOW(),20),(79,@c13_econ,1,32,15,'',NOW(),20),(80,@c13_econ,1,32,15,'',NOW(),20),(81,@c13_econ,1,32,14,'',NOW(),20),(82,@c13_econ,1,32,15,'',NOW(),20),
(78,@c13_arit,1,44,14,'',NOW(), 7),(79,@c13_arit,1,44,15,'',NOW(), 7),(80,@c13_arit,1,44,14,'',NOW(), 7),(81,@c13_arit,1,44,15,'',NOW(), 7),(82,@c13_arit,1,44,14,'',NOW(), 7),
(78,@c13_geom,1,46,14,'',NOW(), 8),(79,@c13_geom,1,46,15,'',NOW(), 8),(80,@c13_geom,1,46,14,'',NOW(), 8),(81,@c13_geom,1,46,14,'',NOW(), 8),(82,@c13_geom,1,46,15,'',NOW(), 8),
(78,@c13_quim,1,48,15,'',NOW(), 9),(79,@c13_quim,1,48,15,'',NOW(), 9),(80,@c13_quim,1,48,14,'',NOW(), 9),(81,@c13_quim,1,48,15,'',NOW(), 9),(82,@c13_quim,1,48,15,'',NOW(), 9),
(78,@c13_rel, 1,51,15,'',NOW(),11),(79,@c13_rel, 1,51,14,'',NOW(),11),(80,@c13_rel, 1,51,15,'',NOW(),11),(81,@c13_rel, 1,51,15,'',NOW(),11),(82,@c13_rel, 1,51,14,'',NOW(),11),
(78,@c13_rel, 1,52,14,'',NOW(),11),(79,@c13_rel, 1,52,15,'',NOW(),11),(80,@c13_rel, 1,52,14,'',NOW(),11),(81,@c13_rel, 1,52,14,'',NOW(),11),(82,@c13_rel, 1,52,15,'',NOW(),11),
(78,@c13_ept, 1,53,16,'',NOW(),16),(79,@c13_ept, 1,53,15,'',NOW(),16),(80,@c13_ept, 1,53,14,'',NOW(),16),(81,@c13_ept, 1,53,15,'',NOW(),16),(82,@c13_ept, 1,53,15,'',NOW(),16);

-- Bloqueos E2 (todas las cargas de sec 13)
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(1,39,1,7),(2,49,1,12),(3,40,1,9),(4,31,1,16),
(5,33,1,19),(5,34,1,19),(5,35,1,19),
(6,36,1,11),(6,37,1,11),
(7,38,1,15),(8,45,1,2),(9,47,1,8),(11,50,1,17),
(12,54,1,18),(12,55,1,18),
(29,56,1,2),(29,57,1,2),
(@c13_dpcc,28,1,10),(@c13_dpcc,29,1,10),
(@c13_hist,30,1,13),(@c13_econ,32,1,20),
(@c13_arit,44,1,7),(@c13_geom,46,1,8),(@c13_quim,48,1,9),
(@c13_rel,51,1,11),(@c13_rel,52,1,11),(@c13_ept,53,1,16);


-- =============================================================================
-- E3: 4° SECUNDARIA A — Arte=Raz.Mat., Ed.Rel.=Ética, EPT=Hab.Ped.
-- Sección: 20 | Matriculas: 106-110 | Tutor: user 16
-- =============================================================================

-- Cargas académicas para sec 20 (solo existe la transversal, carga 36)
INSERT IGNORE INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, area_id, horas_semanales, estado)
VALUES
    (10, 20, 1, NULL, 10, 3, 'activa'),  -- DPCC              → comp 28,29
    ( 7, 20, 1,   11, NULL, 2, 'activa'),-- Historia           → comp 30
    (16, 20, 1,   12, NULL, 2, 'activa'),-- Geografía          → comp 31
    (13, 20, 1,   13, NULL, 2, 'activa'),-- Economía           → comp 32
    (19, 20, 1, NULL, 11, 3, 'activa'),  -- Educación Física   → comp 33,34,35
    (11, 20, 1, NULL, 12, 3, 'activa'),  -- Arte/Raz.Mat.      → comp 36,37
    (15, 20, 1,   14, NULL, 2, 'activa'),-- Raz. Verbal        → comp 38
    ( 7, 20, 1,   15, NULL, 2, 'activa'),-- Literatura         → comp 39
    ( 9, 20, 1,   16, NULL, 2, 'activa'),-- Lenguaje           → comp 40
    ( 6, 20, 1, NULL, 13, 3, 'activa'),  -- Inglés             → comp 41,42,43
    ( 7, 20, 1,   17, NULL, 4, 'activa'),-- Aritmética         → comp 44
    ( 2, 20, 1,   18, NULL, 4, 'activa'),-- Álgebra            → comp 45
    ( 8, 20, 1,   19, NULL, 3, 'activa'),-- Geometría          → comp 46
    ( 8, 20, 1,   20, NULL, 3, 'activa'),-- Trigonometría      → comp 47
    ( 9, 20, 1,   21, NULL, 3, 'activa'),-- Química            → comp 48
    (12, 20, 1,   22, NULL, 3, 'activa'),-- Biología           → comp 49
    (17, 20, 1,   23, NULL, 2, 'activa'),-- Física             → comp 50
    (11, 20, 1, NULL, 14, 2, 'activa'),  -- Ed. Religiosa/Ética→ comp 51,52
    (16, 20, 1, NULL, 15, 2, 'activa');  -- EPT/Hab.Ped.       → comp 53

SET @c20_dpcc= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=10 AND subarea_id IS NULL);
SET @c20_hist= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=11);
SET @c20_geo = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=12);
SET @c20_econ= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=13);
SET @c20_ef  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=11 AND subarea_id IS NULL);
SET @c20_arte= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=12 AND subarea_id IS NULL);
SET @c20_rv  = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=14);
SET @c20_lit = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=15);
SET @c20_len = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=16);
SET @c20_ing = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=13 AND subarea_id IS NULL);
SET @c20_arit= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=17);
SET @c20_alg = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=18);
SET @c20_geom= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=19);
SET @c20_trig= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=20);
SET @c20_quim= (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=21);
SET @c20_bio = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=22);
SET @c20_fis = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND subarea_id=23);
SET @c20_rel = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=14 AND subarea_id IS NULL);
SET @c20_ept = (SELECT id FROM cargas_academicas WHERE seccion_id=20 AND anio_id=1 AND area_id=15 AND subarea_id IS NULL);
-- Transversal ya existe como carga 36

INSERT IGNORE INTO criterios (carga_id, competencia_id, periodo_id, nombre, orden) VALUES
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
    -- Transversal (carga 36 existente) — criterios si no existen
    (36,56,1,'I Bimestre',1),(36,57,1,'I Bimestre',1);

-- Calificaciones sec 20 (mat 106-110)
-- mat 106 = alumno demo: variedad; mat 107-110: uniformes A (15)
INSERT IGNORE INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_en, registrado_por)
VALUES
(106,@c20_dpcc,1,28,18,'',NOW(),10),(107,@c20_dpcc,1,28,15,'',NOW(),10),(108,@c20_dpcc,1,28,14,'',NOW(),10),(109,@c20_dpcc,1,28,15,'',NOW(),10),(110,@c20_dpcc,1,28,15,'',NOW(),10),
(106,@c20_dpcc,1,29,17,'',NOW(),10),(107,@c20_dpcc,1,29,15,'',NOW(),10),(108,@c20_dpcc,1,29,15,'',NOW(),10),(109,@c20_dpcc,1,29,14,'',NOW(),10),(110,@c20_dpcc,1,29,15,'',NOW(),10),
(106,@c20_hist,1,30,16,'',NOW(), 7),(107,@c20_hist,1,30,15,'',NOW(), 7),(108,@c20_hist,1,30,14,'',NOW(), 7),(109,@c20_hist,1,30,15,'',NOW(), 7),(110,@c20_hist,1,30,15,'',NOW(), 7),
(106,@c20_geo, 1,31,15,'',NOW(),16),(107,@c20_geo, 1,31,15,'',NOW(),16),(108,@c20_geo, 1,31,15,'',NOW(),16),(109,@c20_geo, 1,31,14,'',NOW(),16),(110,@c20_geo, 1,31,15,'',NOW(),16),
(106,@c20_econ,1,32,14,'',NOW(),13),(107,@c20_econ,1,32,15,'',NOW(),13),(108,@c20_econ,1,32,14,'',NOW(),13),(109,@c20_econ,1,32,15,'',NOW(),13),(110,@c20_econ,1,32,14,'',NOW(),13),
(106,@c20_ef,  1,33,17,'',NOW(),19),(107,@c20_ef,  1,33,15,'',NOW(),19),(108,@c20_ef,  1,33,16,'',NOW(),19),(109,@c20_ef,  1,33,15,'',NOW(),19),(110,@c20_ef,  1,33,14,'',NOW(),19),
(106,@c20_ef,  1,34,15,'',NOW(),19),(107,@c20_ef,  1,34,15,'',NOW(),19),(108,@c20_ef,  1,34,14,'',NOW(),19),(109,@c20_ef,  1,34,15,'',NOW(),19),(110,@c20_ef,  1,34,15,'',NOW(),19),
(106,@c20_ef,  1,35,14,'',NOW(),19),(107,@c20_ef,  1,35,14,'',NOW(),19),(108,@c20_ef,  1,35,15,'',NOW(),19),(109,@c20_ef,  1,35,14,'',NOW(),19),(110,@c20_ef,  1,35,15,'',NOW(),19),
(106,@c20_arte,1,36,15,'',NOW(),11),(107,@c20_arte,1,36,15,'',NOW(),11),(108,@c20_arte,1,36,14,'',NOW(),11),(109,@c20_arte,1,36,15,'',NOW(),11),(110,@c20_arte,1,36,15,'',NOW(),11),
(106,@c20_arte,1,37,16,'',NOW(),11),(107,@c20_arte,1,37,15,'',NOW(),11),(108,@c20_arte,1,37,15,'',NOW(),11),(109,@c20_arte,1,37,14,'',NOW(),11),(110,@c20_arte,1,37,15,'',NOW(),11),
(106,@c20_rv,  1,38,15,'',NOW(),15),(107,@c20_rv,  1,38,15,'',NOW(),15),(108,@c20_rv,  1,38,14,'',NOW(),15),(109,@c20_rv,  1,38,15,'',NOW(),15),(110,@c20_rv,  1,38,15,'',NOW(),15),
(106,@c20_lit, 1,39,14,'',NOW(), 7),(107,@c20_lit, 1,39,15,'',NOW(), 7),(108,@c20_lit, 1,39,15,'',NOW(), 7),(109,@c20_lit, 1,39,14,'',NOW(), 7),(110,@c20_lit, 1,39,15,'',NOW(), 7),
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
(106,@c20_bio, 1,49,14,'',NOW(),12),(107,@c20_bio, 1,49,15,'',NOW(),12),(108,@c20_bio, 1,49,15,'',NOW(),12),(109,@c20_bio, 1,49,14,'',NOW(),12),(110,@c20_bio, 1,49,15,'',NOW(),12),
(106,@c20_fis, 1,50,16,'',NOW(),17),(107,@c20_fis, 1,50,15,'',NOW(),17),(108,@c20_fis, 1,50,14,'',NOW(),17),(109,@c20_fis, 1,50,15,'',NOW(),17),(110,@c20_fis, 1,50,15,'',NOW(),17),
(106,@c20_rel, 1,51,15,'',NOW(),11),(107,@c20_rel, 1,51,14,'',NOW(),11),(108,@c20_rel, 1,51,15,'',NOW(),11),(109,@c20_rel, 1,51,15,'',NOW(),11),(110,@c20_rel, 1,51,14,'',NOW(),11),
(106,@c20_rel, 1,52,14,'',NOW(),11),(107,@c20_rel, 1,52,15,'',NOW(),11),(108,@c20_rel, 1,52,14,'',NOW(),11),(109,@c20_rel, 1,52,14,'',NOW(),11),(110,@c20_rel, 1,52,15,'',NOW(),11),
(106,@c20_ept, 1,53,16,'',NOW(),16),(107,@c20_ept, 1,53,15,'',NOW(),16),(108,@c20_ept, 1,53,14,'',NOW(),16),(109,@c20_ept, 1,53,15,'',NOW(),16),(110,@c20_ept, 1,53,15,'',NOW(),16),
-- Transversal (carga 36 ya existente, corregir comp a 56/57)
(106,36,1,56,16,'',NOW(),16),(107,36,1,56,15,'',NOW(),16),(108,36,1,56,14,'',NOW(),16),(109,36,1,56,15,'',NOW(),16),(110,36,1,56,15,'',NOW(),16),
(106,36,1,57,15,'',NOW(),16),(107,36,1,57,14,'',NOW(),16),(108,36,1,57,15,'',NOW(),16),(109,36,1,57,14,'',NOW(),16),(110,36,1,57,15,'',NOW(),16);

-- Bloqueos E3 (todas las cargas de sec 20)
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(@c20_dpcc,28,1,10),(@c20_dpcc,29,1,10),
(@c20_hist,30,1, 7),(@c20_geo,31,1,16),(@c20_econ,32,1,13),
(@c20_ef,33,1,19),(@c20_ef,34,1,19),(@c20_ef,35,1,19),
(@c20_arte,36,1,11),(@c20_arte,37,1,11),
(@c20_rv,38,1,15),(@c20_lit,39,1,7),(@c20_len,40,1,9),
(@c20_ing,41,1,6),(@c20_ing,42,1,6),(@c20_ing,43,1,6),
(@c20_arit,44,1,7),(@c20_alg,45,1,2),(@c20_geom,46,1,8),(@c20_trig,47,1,8),
(@c20_quim,48,1,9),(@c20_bio,49,1,12),(@c20_fis,50,1,17),
(@c20_rel,51,1,11),(@c20_rel,52,1,11),(@c20_ept,53,1,16),
(36,56,1,16),(36,57,1,16);


-- =============================================================================
-- CORRECCIÓN: bloqueos erróneos existentes en carga 38 (Transversal 5°S A)
-- Los bloqueos usan comp 54/55 (Taller Raz.Mat.) en lugar de 56/57 (Transversal)
-- =============================================================================
DELETE FROM bloqueos_competencia WHERE carga_id=38 AND competencia_id IN (54,55);
INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por) VALUES
(38,56,1,6),(38,57,1,6);

-- Corregir también las calificaciones erróneas de carga 38
UPDATE calificaciones SET competencia_id=56 WHERE carga_id=38 AND competencia_id=54;
UPDATE calificaciones SET competencia_id=57 WHERE carga_id=38 AND competencia_id=55;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- RESUMEN DE BOLETAS DISPONIBLES PARA DEMO
-- =============================================================================
-- Boleta 1°P A (escala solo literal):
--   URL imprimible: /boleta/{mat_id}/{periodo_id}
--   URL digital:    /boleta/digital/{mat_id}/{periodo_id}
--   Reemplazar {mat_id} por: 1, 2, 3, 4 o 5
--
-- Boleta 1°S A (escala numérica+literal, Taller Raz.Mat.):
--   mat_id: 78, 79, 80, 81 o 82
--
-- Boleta 4°S A (escala numérica+literal, Arte=Raz.Mat., aliases Ed.Rel. y EPT):
--   mat_id: 106, 107, 108, 109 o 110
--
-- En todos los casos: periodo_id = 1 (I Bimestre 2026)
-- =============================================================================
