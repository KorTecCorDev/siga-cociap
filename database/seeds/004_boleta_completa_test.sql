-- ============================================================
-- SIGA-COCIAP — Seed 004: Boleta completa para test de página A4
-- Rellena TODAS las competencias de 1° Secundaria A para
-- el alumno Xiara Angeles Fernandez (DNI 77752898).
-- Incluye 5 notas C con conclusiones largas para probar
-- el truncado y que todo quepa en una sola página A4.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ════════════════════════════════════════════════════════════
-- VARIABLES BASE
-- ════════════════════════════════════════════════════════════
SET @mat_id  = (
    SELECT m.id FROM matriculas m
    INNER JOIN estudiantes e ON e.id = m.estudiante_id
    INNER JOIN personas p    ON p.id = e.persona_id
    WHERE p.dni = '77752898' AND m.estado = 'aprobada'
    ORDER BY m.id DESC LIMIT 1
);
SET @per_id  = (SELECT id FROM periodos WHERE estado = 'activo' LIMIT 1);
SET @usr_id  = (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id = u.persona_id WHERE p.dni = '12345678' LIMIT 1);
SET @sec_id  = (SELECT seccion_id FROM matriculas WHERE id = @mat_id);
SET @anio_id = (SELECT anio_id  FROM matriculas WHERE id = @mat_id);

-- Conclusiones largas para notas C (secundaria solo C requiere conclusión)
SET @c_arte = 'La estudiante presenta dificultades significativas en el desarrollo de la apreciación artística y la creación de proyectos desde los lenguajes artístico-culturales. Se recomienda reforzar la participación en actividades creativas en casa y motivar la exploración de manifestaciones artísticas de la región Ancash para fortalecer esta competencia durante el siguiente bimestre.';
SET @c_com  = 'La estudiante muestra dificultades en la producción escrita de textos en su lengua materna. Presenta errores frecuentes en coherencia, cohesión y ortografía. Es necesario practicar la escritura diaria mediante redacciones breves y lectura de textos variados para mejorar progresivamente su desempeño en esta competencia durante el siguiente periodo evaluativo.';
SET @c_ing  = 'La estudiante no ha alcanzado el nivel mínimo esperado en la comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Se sugiere el uso de aplicaciones de aprendizaje de idiomas y práctica diaria de vocabulario fundamental para mejorar su rendimiento en el siguiente bimestre.';
SET @c_fis  = 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante situaciones problemáticas de su entorno inmediato. Se evidencia poco dominio en el uso de herramientas y en la planificación de proyectos tecnológicos. Se recomienda reforzar los conceptos básicos de física aplicada y participar activamente en las prácticas del laboratorio escolar.';
SET @c_rm   = 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta dificultades para resolver situaciones que requieren análisis lógico y pensamiento abstracto. Se recomienda practicar ejercicios de razonamiento de nivel básico en casa con apoyo de materiales visuales y concretos para consolidar los fundamentos requeridos.';

-- ════════════════════════════════════════════════════════════
-- CARGAS FALTANTES — ÁREAS-CURSO
-- ════════════════════════════════════════════════════════════

-- Arte y Cultura
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 2, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- Inglés
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 2, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- Educación Religiosa
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 2, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- Educación para el Trabajo (EPT)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 2, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Educación para el Trabajo'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- Taller de Razonamiento Matemático
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 2, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Taller de Razonamiento Matemático'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- Competencias Transversales
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, area_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, ar.id, 0, 'activa'
FROM areas ar WHERE ar.nivel_id=2 AND ar.nombre='Competencias Transversales'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND area_id=ar.id);

-- ════════════════════════════════════════════════════════════
-- CARGAS FALTANTES — CON SUBÁREAS
-- ════════════════════════════════════════════════════════════

-- Geografía (Ciencias Sociales)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 2, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Ciencias Sociales'
WHERE sa.nombre='Geografía'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Economía (Ciencias Sociales)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 2, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Ciencias Sociales'
WHERE sa.nombre='Economía'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Razonamiento Verbal (Comunicación)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Comunicación'
WHERE sa.nombre='Razonamiento Verbal'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Literatura (Comunicación)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Comunicación'
WHERE sa.nombre='Literatura'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Lenguaje (Comunicación)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Comunicación'
WHERE sa.nombre='Lenguaje'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Geometría (Matemática)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Matemática'
WHERE sa.nombre='Geometría'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Trigonometría (Matemática)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Matemática'
WHERE sa.nombre='Trigonometría'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Química (Ciencia y Tecnología)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Ciencia y Tecnología'
WHERE sa.nombre='Química'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Biología (Ciencia y Tecnología)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Ciencia y Tecnología'
WHERE sa.nombre='Biología'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- Física (Ciencia y Tecnología)
INSERT INTO cargas_academicas (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT @usr_id, @sec_id, @anio_id, sa.id, 3, 'activa'
FROM subareas sa INNER JOIN areas ar ON ar.id=sa.area_id AND ar.nivel_id=2 AND ar.nombre='Ciencia y Tecnología'
WHERE sa.nombre='Física'
  AND NOT EXISTS (SELECT 1 FROM cargas_academicas WHERE seccion_id=@sec_id AND anio_id=@anio_id AND subarea_id=sa.id);

-- ════════════════════════════════════════════════════════════
-- VARIABLES DE CARGA (después de crearlas todas)
-- ════════════════════════════════════════════════════════════
SET @ca_dpcc  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Desarrollo Personal, Ciudadanía y Cívica' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_ef    = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Educación Física' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_arte  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Arte y Cultura' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_ing   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Inglés' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_rel   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Educación Religiosa' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_ept   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Educación para el Trabajo' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_rm    = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Taller de Razonamiento Matemático' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_trans = (SELECT ca.id FROM cargas_academicas ca INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2 AND ar.nombre='Competencias Transversales' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_hist  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Historia' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_geo   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Geografía' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_eco   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Economía' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_rv    = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Razonamiento Verbal' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_lit   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Literatura' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_len   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Lenguaje' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_arit  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Aritmética' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_alg   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Álgebra' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_geom  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Geometría' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_trig  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Trigonometría' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_quim  = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Química' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_bio   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Biología' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);
SET @ca_fis   = (SELECT ca.id FROM cargas_academicas ca INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Física' WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

-- ════════════════════════════════════════════════════════════
-- CALIFICACIONES — 27 competencias de 1° Secundaria
-- Notas: mezcla realista de AD/A/B con 5 notas C + conclusión larga
-- ════════════════════════════════════════════════════════════

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id, nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)

-- ── DPCC ────────────────────────────────────────────────────
SELECT @mat_id, @ca_dpcc, @per_id, id, 16, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C1'  AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica')
UNION ALL
SELECT @mat_id, @ca_dpcc, @per_id, id, 15, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C16' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica')

-- ── Ciencias Sociales ────────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_hist, @per_id, id, 14, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C17' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Historia' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales'))
UNION ALL
SELECT @mat_id, @ca_geo,  @per_id, id, 12, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C18' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Geografía' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales'))
UNION ALL
SELECT @mat_id, @ca_eco,  @per_id, id, 18, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C19' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Economía' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales'))

-- ── Educación Física ─────────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_ef, @per_id, id, 17, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C13' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')
UNION ALL
SELECT @mat_id, @ca_ef, @per_id, id, 16, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C14' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')
UNION ALL
SELECT @mat_id, @ca_ef, @per_id, id, 15, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C15' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')

-- ── Arte y Cultura (2 notas C con conclusión larga) ──────────
UNION ALL
SELECT @mat_id, @ca_arte, @per_id, id, 10, @c_arte, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C21' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura')
UNION ALL
SELECT @mat_id, @ca_arte, @per_id, id,  9, @c_arte, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C22' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura')

-- ── Comunicación ─────────────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_rv,  @per_id, id, 14, NULL,   @usr_id, NOW() FROM competencias WHERE codigo_minedu='C7' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Razonamiento Verbal' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'))
UNION ALL
SELECT @mat_id, @ca_lit, @per_id, id, 15, NULL,   @usr_id, NOW() FROM competencias WHERE codigo_minedu='C8' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Literatura'          AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'))
UNION ALL
SELECT @mat_id, @ca_len, @per_id, id, 10, @c_com, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C9' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Lenguaje'            AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación'))

-- ── Inglés (nota C con conclusión larga) ─────────────────────
UNION ALL
SELECT @mat_id, @ca_ing, @per_id, id, 8, @c_ing, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C4' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Inglés')

-- ── Matemática ───────────────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_arit, @per_id, id, 16, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C23' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Aritmética'    AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'))
UNION ALL
SELECT @mat_id, @ca_alg,  @per_id, id, 14, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C24' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Álgebra'       AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'))
UNION ALL
SELECT @mat_id, @ca_geom, @per_id, id, 13, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C26' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Geometría'     AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'))
UNION ALL
SELECT @mat_id, @ca_trig, @per_id, id, 12, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C25' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Trigonometría' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática'))

-- ── Ciencia y Tecnología ─────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_quim, @per_id, id, 15, NULL,   @usr_id, NOW() FROM competencias WHERE codigo_minedu='C21' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Química'   AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'))
UNION ALL
SELECT @mat_id, @ca_bio,  @per_id, id, 16, NULL,   @usr_id, NOW() FROM competencias WHERE codigo_minedu='C20' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Biología'  AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'))
UNION ALL
SELECT @mat_id, @ca_fis,  @per_id, id,  9, @c_fis, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C22' AND subarea_id=(SELECT id FROM subareas WHERE nombre='Física'    AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología'))

-- ── Educación Religiosa ──────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_rel, @per_id, id, 17, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C27' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa')
UNION ALL
SELECT @mat_id, @ca_rel, @per_id, id, 16, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C28' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa')

-- ── EPT ──────────────────────────────────────────────────────
UNION ALL
SELECT @mat_id, @ca_ept, @per_id, id, 14, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C29' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación para el Trabajo')

-- ── Taller de Razonamiento Matemático (nota C + conclusión) ──
UNION ALL
SELECT @mat_id, @ca_rm, @per_id, id, 10, @c_rm, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C25' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Taller de Razonamiento Matemático')

-- ── Competencias Transversales ───────────────────────────────
UNION ALL
SELECT @mat_id, @ca_trans, @per_id, id, 15, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C2' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales')
UNION ALL
SELECT @mat_id, @ca_trans, @per_id, id, 14, NULL, @usr_id, NOW() FROM competencias WHERE codigo_minedu='C3' AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales')

ON DUPLICATE KEY UPDATE
    nota_numerica          = VALUES(nota_numerica),
    conclusion_descriptiva = VALUES(conclusion_descriptiva),
    modificado_en          = NOW();

SET FOREIGN_KEY_CHECKS = 1;
