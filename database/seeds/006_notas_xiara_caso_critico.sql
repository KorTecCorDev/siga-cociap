-- ============================================================
-- SIGA-COCIAP — Seed 006: Notas caso crítico Xiara Daleshka
-- Alumna: Xiara Daleshka Angeles Fernandez (DNI 77752898)
-- Nivel: 1° Secundaria — Sección A
--
-- Escenario: estudiante con deficiencias generalizadas en todas
-- las competencias. Calificación C (00-10) en los 4 bimestres.
-- Conclusiones descriptivas detalladas (400-500 caracteres c/u).
--
-- Prerequisitos: seeds 001 al 005 ejecutados previamente.
-- Idempotente: usa ON DUPLICATE KEY UPDATE.
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
SET @anio_id = (SELECT anio_id FROM matriculas WHERE id = @mat_id);
SET @sec_id  = (SELECT seccion_id FROM matriculas WHERE id = @mat_id);
SET @usr_id  = (
    SELECT u.id FROM usuarios u
    INNER JOIN personas p ON p.id = u.persona_id
    WHERE p.dni = '12345678' LIMIT 1
);

-- Períodos del año académico
SET @per1 = (SELECT id FROM periodos WHERE anio_id = @anio_id AND numero = 1);
SET @per2 = (SELECT id FROM periodos WHERE anio_id = @anio_id AND numero = 2);
SET @per3 = (SELECT id FROM periodos WHERE anio_id = @anio_id AND numero = 3);
SET @per4 = (SELECT id FROM periodos WHERE anio_id = @anio_id AND numero = 4);

-- ════════════════════════════════════════════════════════════
-- VARIABLES DE CARGA ACADÉMICA (1° Sec A)
-- ════════════════════════════════════════════════════════════

-- Áreas-curso
SET @ca_dpcc  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Desarrollo Personal, Ciudadanía y Cívica'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_ef    = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Educación Física'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_arte  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Arte y Cultura'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_ing   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Inglés'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_rel   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Educación Religiosa'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_ept   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Educación para el Trabajo'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_rm    = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Taller de Razonamiento Matemático'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_trans = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN areas ar ON ar.id=ca.area_id AND ar.nivel_id=2
        AND ar.nombre='Competencias Transversales'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

-- Áreas con subáreas
SET @ca_hist  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Historia'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_geo   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Geografía'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_eco   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Economía'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_rv    = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Razonamiento Verbal'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Comunicación'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_lit   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Literatura'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_len   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Lenguaje'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_arit  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Aritmética'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Matemática' AND ar.nivel_id=2
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_alg   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Álgebra'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Matemática' AND ar.nivel_id=2
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_geom  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Geometría'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Matemática' AND ar.nivel_id=2
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_trig  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Trigonometría'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_bio   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Biología'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Ciencia y Tecnología'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_quim  = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Química'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Ciencia y Tecnología'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

SET @ca_fis   = (SELECT ca.id FROM cargas_academicas ca
    INNER JOIN subareas sa ON sa.id=ca.subarea_id AND sa.nombre='Física'
    INNER JOIN areas ar    ON ar.id=sa.area_id AND ar.nombre='Ciencia y Tecnología'
    WHERE ca.seccion_id=@sec_id AND ca.anio_id=@anio_id LIMIT 1);

-- ════════════════════════════════════════════════════════════
-- CONCLUSIONES DESCRIPTIVAS (400-500 caracteres c/u)
-- Una por competencia — reflejan el caso crítico de la alumna
-- ════════════════════════════════════════════════════════════

-- DPCC
SET @c_dpcc_c1 = 'La estudiante presenta serias dificultades para construir su identidad personal. No logra reflexionar sobre sus emociones, valores ni reconocer su historia personal con profundidad. Muestra inseguridad al relacionarse con sus pares y al expresar sus puntos de vista. Es necesario brindarle acompañamiento emocional permanente, fortalecer el vínculo familiar y promover actividades que desarrollen su autoestima y sentido de pertenencia a la comunidad.';

SET @c_dpcc_c16 = 'La estudiante no logra convivir ni participar democráticamente de forma efectiva en el aula. Muestra dificultades para respetar normas de convivencia, escuchar opiniones distintas y asumir compromisos ciudadanos básicos. Su participación en actividades grupales es mínima y poco propositiva. Se recomienda reforzar hábitos de convivencia en el hogar, dialogar sobre el respeto mutuo y motivarla a involucrarse en espacios de participación estudiantil del colegio.';

-- Ciencias Sociales
SET @c_hist = 'La estudiante muestra dificultades significativas para construir interpretaciones históricas. No logra analizar fuentes ni relacionar causas y consecuencias de los procesos del Perú y el mundo. Sus producciones escritas presentan escasa argumentación e incoherencia histórica. Se recomienda apoyarla con lecturas accesibles sobre historia peruana, ver documentales históricos, practicar líneas de tiempo y solicitar acompañamiento en tutoría para reforzar esta competencia.';

SET @c_geo = 'La estudiante presenta dificultades para gestionar responsablemente el espacio y el ambiente. No identifica problemáticas ambientales de su entorno ni propone soluciones concretas. Muestra desinterés por el cuidado del medio ambiente y desconoce nociones básicas de geografía local y regional. Se sugiere promover hábitos ecológicos en casa, visitar espacios naturales de Áncash y consultar materiales sobre geografía e impacto ambiental de la región para mejorar su comprensión.';

SET @c_eco = 'La estudiante no ha desarrollado capacidades para gestionar responsablemente los recursos económicos. Presenta dificultades para comprender nociones básicas de economía familiar y ciudadana, no aplica estrategias de ahorro ni reconoce la importancia del trabajo productivo. Se recomienda trabajar situaciones económicas de la vida cotidiana en familia, dialogar sobre el manejo del dinero y apoyarse en material audiovisual básico de educación financiera para adolescentes.';

-- Educación Física
SET @c_ef_c13 = 'La estudiante muestra dificultades para desenvolverse de manera autónoma a través de su motricidad. No controla su cuerpo con eficiencia en actividades físicas básicas ni desarrolla su coordinación motora de forma adecuada. Presenta poca confianza en sus capacidades físicas y evita ejercicios que requieran esfuerzo. Se recomienda practicar actividades físicas sencillas en casa, caminar a diario y participar en juegos recreativos para fortalecer gradualmente su motricidad.';

SET @c_ef_c14 = 'La estudiante presenta dificultades para asumir hábitos de vida saludable. No demuestra conciencia sobre la importancia del ejercicio, la alimentación balanceada ni el descanso adecuado para su desarrollo. Sus rutinas cotidianas no favorecen el bienestar físico y emocional esperado para su edad. Se recomienda establecer horarios de alimentación y descanso regulares en casa, reducir el sedentarismo y orientar a la familia sobre nutrición básica para adolescentes de su etapa.';

SET @c_ef_c15 = 'La estudiante presenta dificultades para interactuar a través de sus habilidades sociomotrices. No logra participar de forma activa y colaborativa en juegos y deportes grupales. Muestra limitada tolerancia a la frustración y dificultad para respetar reglas de juego en equipo. Se recomienda motivarla a practicar deportes sencillos con familiares, participar en actividades recreativas de la comunidad y desarrollar el trabajo colaborativo mediante juegos grupales y de equipo.';

-- Arte y Cultura
SET @c_arte_c21 = 'La estudiante presenta dificultades para apreciar de manera crítica manifestaciones artístico-culturales. No logra analizar ni emitir juicios fundamentados sobre obras artísticas o expresiones culturales de su entorno. Muestra desinterés por el arte y las manifestaciones culturales locales. Se recomienda visitar el Museo Regional de Áncash, participar en actividades culturales de Huaraz, explorar el arte andino y ver documentales sobre manifestaciones culturales del Perú.';

SET @c_arte_c22 = 'La estudiante muestra dificultades para crear proyectos desde los lenguajes artístico-culturales. Sus producciones carecen de creatividad, planificación y uso apropiado de los elementos expresivos propios de cada lenguaje artístico. No logra comunicar ideas o emociones de forma efectiva a través del arte. Se sugiere practicar dibujo, pintura o danza en casa, explorar técnicas artísticas sencillas disponibles en internet y participar en talleres culturales de la comunidad.';

-- Comunicación
SET @c_rv = 'La estudiante presenta dificultades para comunicarse oralmente con coherencia y fluidez en su lengua materna. No logra expresar ideas de forma organizada en conversaciones ni exposiciones orales, muestra inseguridad al hablar ante el grupo y emplea vocabulario muy reducido. Se recomienda practicar la lectura en voz alta, participar en diálogos familiares sobre temas cotidianos, escuchar audios educativos y realizar pequeñas exposiciones en casa para ganar confianza y soltura.';

SET @c_lit = 'La estudiante muestra serias dificultades para leer y comprender diversos tipos de textos escritos en su lengua materna. No identifica la información explícita ni realiza inferencias básicas sobre lo que lee. Su nivel de comprensión lectora está significativamente por debajo de lo esperado para su grado. Se recomienda leer diariamente textos breves de su interés, aplicar estrategias de subrayado y resumen, y participar activamente en el plan lector del colegio cada semana.';

SET @c_len = 'La estudiante presenta dificultades significativas para escribir diversos tipos de textos en su lengua materna. Sus producciones escritas evidencian graves errores de coherencia, cohesión, ortografía y puntuación. No logra planificar ni revisar sus textos antes de entregarlos. Se recomienda practicar la escritura diaria mediante redacciones cortas, leer textos modelo de distintos tipos, revisar normas ortográficas básicas y solicitar retroalimentación continua del docente.';

-- Inglés
SET @c_ing = 'La estudiante no ha alcanzado el nivel esperado en comunicación en inglés como lengua extranjera. Presenta dificultades para comprender y producir textos orales y escritos básicos del idioma. Su vocabulario en inglés es muy reducido y muestra escaso dominio de estructuras gramaticales elementales. Se recomienda el uso diario de aplicaciones de aprendizaje de idiomas, practicar vocabulario básico mediante canciones en inglés, videos cortos y ejercicios interactivos en línea.';

-- Matemática
SET @c_arit = 'La estudiante presenta serias dificultades para resolver problemas de cantidad. No logra aplicar estrategias de cálculo aritmético básico ni comprender el sistema de numeración de manera adecuada. Sus errores son frecuentes en operaciones con números naturales, fracciones y decimales. Se recomienda reforzar las operaciones básicas con material concreto en casa, practicar el cálculo mental a diario y solicitar apoyo de tutoría para lograr una nivelación matemática efectiva.';

SET @c_alg = 'La estudiante presenta dificultades para resolver problemas de regularidad, equivalencia y cambio. No logra identificar patrones, trabajar con expresiones algebraicas simples ni resolver ecuaciones de primer grado. Sus errores reflejan falta de comprensión conceptual básica en álgebra. Se recomienda trabajar con material visual y concreto, practicar problemas de forma progresiva, utilizar recursos interactivos de matemáticas en línea y pedir apoyo de tutoría escolar.';

SET @c_geom = 'La estudiante muestra dificultades para resolver problemas de forma, movimiento y localización. No logra identificar figuras geométricas básicas ni calcular perímetros y áreas elementales. Presenta escasa comprensión de conceptos espaciales requeridos para su nivel. Se recomienda practicar con figuras geométricas concretas en casa, utilizar aplicaciones de geometría interactiva, reforzar con ejercicios progresivos de nivel básico y solicitar apoyo en horas de tutoría.';

SET @c_trig = 'La estudiante presenta dificultades para resolver problemas de gestión de datos e incertidumbre. No logra recopilar, organizar ni interpretar información estadística básica. Le cuesta representar datos en tablas o gráficos simples y obtener conclusiones a partir de ellos. Se recomienda practicar ejercicios de estadística descriptiva básica, interpretar gráficos de la vida cotidiana, trabajar con datos reales del entorno familiar y solicitar refuerzo en tutoría escolar.';

-- Ciencia y Tecnología
SET @c_bio = 'La estudiante presenta dificultades para indagar mediante métodos científicos. No logra formular preguntas de investigación, planificar procedimientos ni registrar observaciones con orden y precisión. Muestra poco interés por la ciencia y escaso dominio del método científico. Se recomienda realizar experimentos sencillos en casa con materiales cotidianos, observar fenómenos naturales del entorno, llevar un cuaderno de ciencias y ver videos educativos de ciencias naturales.';

SET @c_quim = 'La estudiante muestra dificultades para explicar el mundo físico basándose en conocimientos científicos sobre materia, energía y seres vivos. No logra relacionar conceptos con fenómenos cotidianos ni usa terminología básica con precisión. Se recomienda revisar contenidos de ciencias con videos educativos accesibles, leer resúmenes sobre biología y química básica, practicar ejercicios de aplicación conceptual y solicitar apoyo a su docente en horas de tutoría.';

SET @c_fis = 'La estudiante presenta dificultades para diseñar y construir soluciones tecnológicas ante problemas de su entorno. No logra planificar procesos tecnológicos ni seleccionar materiales adecuados para sus proyectos. Sus producciones carecen de planificación y no responden a los requerimientos del nivel. Se recomienda explorar proyectos tecnológicos sencillos en casa, ver tutoriales de construcción básica, practicar manualidades funcionales y reforzar los contenidos con tutoría.';

-- Educación Religiosa
SET @c_rel_c27 = 'La estudiante presenta dificultades para construir su identidad como persona humana amada por Dios. No logra reflexionar sobre su dignidad, libertad y trascendencia desde una perspectiva de fe. Muestra escasa capacidad para relacionar los valores religiosos con su vida cotidiana. Se recomienda propiciar momentos de reflexión personal en familia, leer textos religiosos adecuados para adolescentes y dialogar sobre la importancia de los valores espirituales en la vida.';

SET @c_rel_c28 = 'La estudiante muestra dificultades para asumir la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida. No logra relacionar los principios religiosos aprendidos con sus decisiones cotidianas ni participa activamente en celebraciones comunitarias. Se recomienda fomentar la práctica religiosa en familia, participar en actividades parroquiales, leer sobre espiritualidad para adolescentes y reflexionar sobre su proyecto de vida personal.';

-- EPT
SET @c_ept = 'La estudiante presenta dificultades para gestionar proyectos de emprendimiento económico o social. No logra identificar oportunidades, planificar acciones ni evaluar los recursos disponibles en su entorno. Muestra escaso dominio de conceptos básicos de economía y empresa. Se recomienda explorar historias de emprendimiento local de Huaraz, participar en ferias escolares, diseñar pequeños proyectos en casa con apoyo familiar y ver contenidos sobre emprendimiento juvenil.';

-- Taller de Razonamiento Matemático
SET @c_rm = 'La estudiante se encuentra en nivel de inicio en el desarrollo del razonamiento matemático aplicado. Presenta serias dificultades para resolver situaciones que requieren análisis lógico, pensamiento abstracto y aplicación de estrategias matemáticas no rutinarias. Sus respuestas evidencian falta de comprensión del enunciado y ausencia de procedimientos. Se recomienda practicar ejercicios de lógica básica en casa, usar juegos matemáticos y solicitar apoyo en tutoría.';

-- Competencias Transversales
SET @c_trans_c2 = 'La estudiante presenta dificultades para desenvolverse en entornos virtuales generados por las TIC. No logra usar herramientas digitales básicas de forma segura ni aprovecharlas para su aprendizaje. Muestra escasa competencia digital para buscar información confiable, comunicarse y crear contenidos simples. Se recomienda orientarla en el uso responsable de dispositivos e internet, guiarla para explorar plataformas educativas digitales y practicar habilidades básicas de informática.';

SET @c_trans_c3 = 'La estudiante muestra dificultades para gestionar su aprendizaje de manera autónoma. No organiza su tiempo de estudio, no establece metas claras ni reflexiona sobre sus avances y dificultades. Depende excesivamente de la orientación del docente y no aplica estrategias propias de aprendizaje. Se recomienda establecer horarios de estudio fijos en casa, usar una agenda para organizar tareas, practicar técnicas básicas de estudio y reflexionar diariamente sobre sus metas.';

-- ════════════════════════════════════════════════════════════
-- CALIFICACIONES — 4 BIMESTRES × 27 COMPETENCIAS = 108 REGISTROS
-- Todos en escala C (00-10). Conclusiones obligatorias en sec+C.
-- ════════════════════════════════════════════════════════════

-- ── DPCC (C1=07, C16=06) ─────────────────────────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_dpcc, p.id, comp.id, 7, @c_dpcc_c1, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C1'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica          = VALUES(nota_numerica),
    conclusion_descriptiva = VALUES(conclusion_descriptiva),
    modificado_en          = NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_dpcc, p.id, comp.id, 6, @c_dpcc_c16, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C16'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica          = VALUES(nota_numerica),
    conclusion_descriptiva = VALUES(conclusion_descriptiva),
    modificado_en          = NOW();

-- ── Ciencias Sociales (Hist=08, Geog=07, Eco=06) ─────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_hist, p.id, comp.id, 8, @c_hist, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C17'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencias Sociales' WHERE sa.nombre='Historia')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_geo, p.id, comp.id, 7, @c_geo, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C18'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencias Sociales' WHERE sa.nombre='Geografía')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_eco, p.id, comp.id, 6, @c_eco, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C19'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencias Sociales' WHERE sa.nombre='Economía')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Educación Física (C13=09, C14=08, C15=09) ────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_ef, p.id, comp.id, 9, @c_ef_c13, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C13'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_ef, p.id, comp.id, 8, @c_ef_c14, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C14'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_ef, p.id, comp.id, 9, @c_ef_c15, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C15'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Física')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Arte y Cultura (C21=07, C22=07) ──────────────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_arte, p.id, comp.id, 7, @c_arte_c21, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C21'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_arte, p.id, comp.id, 7, @c_arte_c22, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C22'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Arte y Cultura')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Comunicación (Raz.Verbal=06, Literatura=07, Lenguaje=05) ─
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_rv, p.id, comp.id, 6, @c_rv, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C7'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Comunicación' WHERE sa.nombre='Razonamiento Verbal')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_lit, p.id, comp.id, 7, @c_lit, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C8'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Comunicación' WHERE sa.nombre='Literatura')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_len, p.id, comp.id, 5, @c_len, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C9'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Comunicación' WHERE sa.nombre='Lenguaje')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Inglés (C4=05) ────────────────────────────────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_ing, p.id, comp.id, 5, @c_ing, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C4'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Inglés')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Matemática (Arit=07, Álg=06, Geom=07, Trig=06) ───────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_arit, p.id, comp.id, 7, @c_arit, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C23'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Matemática' WHERE sa.nombre='Aritmética')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_alg, p.id, comp.id, 6, @c_alg, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C24'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Matemática' WHERE sa.nombre='Álgebra')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_geom, p.id, comp.id, 7, @c_geom, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C26'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Matemática' WHERE sa.nombre='Geometría')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_trig, p.id, comp.id, 6, @c_trig, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C25'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Matemática' WHERE sa.nombre='Trigonometría')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Ciencia y Tecnología (Bio=08, Quím=07, Fís=06) ───────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_bio, p.id, comp.id, 8, @c_bio, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C20'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencia y Tecnología' WHERE sa.nombre='Biología')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_quim, p.id, comp.id, 7, @c_quim, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C21'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencia y Tecnología' WHERE sa.nombre='Química')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_fis, p.id, comp.id, 6, @c_fis, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C22'
  AND comp.subarea_id = (SELECT sa.id FROM subareas sa INNER JOIN areas a ON a.id=sa.area_id AND a.nivel_id=2 AND a.nombre='Ciencia y Tecnología' WHERE sa.nombre='Física')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Educación Religiosa (C27=09, C28=08) ─────────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_rel, p.id, comp.id, 9, @c_rel_c27, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C27'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_rel, p.id, comp.id, 8, @c_rel_c28, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C28'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación Religiosa')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── EPT (C29=08) ──────────────────────────────────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_ept, p.id, comp.id, 8, @c_ept, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C29'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Educación para el Trabajo')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Taller de Razonamiento Matemático (C25=06) ────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_rm, p.id, comp.id, 6, @c_rm, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C25'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Taller de Razonamiento Matemático')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ── Competencias Transversales (C2=07, C3=06) ────────────────
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_trans, p.id, comp.id, 7, @c_trans_c2, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C2'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, registrado_por, registrado_en)
SELECT @mat_id, @ca_trans, p.id, comp.id, 6, @c_trans_c3, @usr_id, NOW()
FROM competencias comp CROSS JOIN periodos p
WHERE comp.codigo_minedu = 'C3'
  AND comp.area_id = (SELECT id FROM areas WHERE nivel_id=2 AND nombre='Competencias Transversales')
  AND p.anio_id = @anio_id
ON DUPLICATE KEY UPDATE
    nota_numerica=VALUES(nota_numerica), conclusion_descriptiva=VALUES(conclusion_descriptiva), modificado_en=NOW();

-- ════════════════════════════════════════════════════════════
-- BLOQUEOS DE COMPETENCIA — marca las notas como aprobadas
-- por el docente en los 4 bimestres (INSERT IGNORE es seguro)
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO bloqueos_competencia (carga_id, competencia_id, periodo_id, bloqueado_por)
SELECT cal.carga_id, cal.competencia_id, cal.periodo_id, @usr_id
FROM calificaciones cal
WHERE cal.matricula_id = @mat_id;

-- ════════════════════════════════════════════════════════════
-- FIN DEL SEED — Resumen esperado:
--   108 calificaciones (27 comp × 4 bimestres)
--   108 bloqueos de competencia
--   Todas en escala C (00-10), conclusiones 400-500 chars c/u
-- ════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 1;
