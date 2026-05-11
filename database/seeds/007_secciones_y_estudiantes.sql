-- ============================================================
-- SIGA-COCIAP — Seed 007: Secciones completas + estudiantes de prueba
--
-- Crea todas las secciones para el año académico activo (2026):
--   Primaria  : 2 secciones por grado (6 grados × 2 = 12 secciones)
--   Secundaria: 3 secciones para 1° Sec, 2 para 2°–5° Sec (11 secciones)
--               ↳ 1°Sec A ya existe en seed 001, se omite
--
-- Crea 5 estudiantes de prueba por sección nueva con matrícula aprobada.
--   1°Sec A ya tiene 5 estudiantes (seeds 001), no se duplica.
--   Total nuevos: 22 secciones × 5 = 110 estudiantes
--
-- Idempotente: INSERT IGNORE en personas/estudiantes,
--              NOT EXISTS en secciones y matrículas.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ════════════════════════════════════════════════════════════
-- 1. SECCIONES — PRIMARIA
--    Grados 1°-3°: es_unidocente = TRUE
--    Grados 4°-6°: es_unidocente = FALSE
-- ════════════════════════════════════════════════════════════
INSERT INTO secciones (grado_id, anio_id, nombre, es_unidocente, estado_nomina)
SELECT g.id, aa.id, lote.snom, lote.uni, 'aprobada'
FROM (
    SELECT 1 AS gnum, 'A' AS snom, TRUE AS uni UNION ALL
    SELECT 1, 'B', TRUE UNION ALL
    SELECT 2, 'A', TRUE UNION ALL
    SELECT 2, 'B', TRUE UNION ALL
    SELECT 3, 'A', TRUE UNION ALL
    SELECT 3, 'B', TRUE UNION ALL
    SELECT 4, 'A', FALSE UNION ALL
    SELECT 4, 'B', FALSE UNION ALL
    SELECT 5, 'A', FALSE UNION ALL
    SELECT 5, 'B', FALSE UNION ALL
    SELECT 6, 'A', FALSE UNION ALL
    SELECT 6, 'B', FALSE
) AS lote
INNER JOIN grados g            ON g.numero = lote.gnum AND g.nivel_id = 1
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
WHERE NOT EXISTS (
    SELECT 1 FROM secciones s2
    WHERE s2.grado_id = g.id AND s2.anio_id = aa.id AND s2.nombre = lote.snom
);

-- ════════════════════════════════════════════════════════════
-- 2. SECCIONES — SECUNDARIA
--    1°Sec: B y C (A ya existe en seed 001)
--    2°–5°Sec: A y B
-- ════════════════════════════════════════════════════════════
INSERT INTO secciones (grado_id, anio_id, nombre, es_unidocente, estado_nomina)
SELECT g.id, aa.id, lote.snom, FALSE, 'aprobada'
FROM (
    SELECT 1 AS gnum, 'B' AS snom UNION ALL
    SELECT 1, 'C' UNION ALL
    SELECT 2, 'A' UNION ALL
    SELECT 2, 'B' UNION ALL
    SELECT 3, 'A' UNION ALL
    SELECT 3, 'B' UNION ALL
    SELECT 4, 'A' UNION ALL
    SELECT 4, 'B' UNION ALL
    SELECT 5, 'A' UNION ALL
    SELECT 5, 'B'
) AS lote
INNER JOIN grados g            ON g.numero = lote.gnum AND g.nivel_id = 2
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
WHERE NOT EXISTS (
    SELECT 1 FROM secciones s2
    WHERE s2.grado_id = g.id AND s2.anio_id = aa.id AND s2.nombre = lote.snom
);

-- ════════════════════════════════════════════════════════════
-- 3. PERSONAS — 110 estudiantes de prueba
--    DNI range: 10000001 – 10000110
--    Grupos de 5 por sección (ver distribución al final del archivo)
-- ════════════════════════════════════════════════════════════
INSERT IGNORE INTO personas
    (dni, apellido_paterno, apellido_materno, nombres, sexo, fecha_nacimiento)
VALUES
-- ── 1°Prim A (01-05) ────────────────────────────────────────
('10000001','QUISPE',    'FLORES',    'JUAN CARLOS',        'M','2019-04-10'),
('10000002','MAMANI',    'GARCIA',    'ANA LUCIA',          'F','2019-06-22'),
('10000003','ROJAS',     'TORRES',    'MIGUEL ANGEL',       'M','2019-02-14'),
('10000004','FLORES',    'RAMIREZ',   'SOFIA CAMILA',       'F','2019-08-30'),
('10000005','GARCIA',    'MENDOZA',   'PEDRO PABLO',        'M','2019-05-18'),
-- ── 1°Prim B (06-10) ────────────────────────────────────────
('10000006','HUANCA',    'CHAVEZ',    'VALERIA NICOL',      'F','2019-01-25'),
('10000007','TORRES',    'VARGAS',    'ANDRES MARTIN',      'M','2018-11-08'),
('10000008','RAMOS',     'QUISPE',    'DIANA PAOLA',        'F','2019-03-17'),
('10000009','CONDORI',   'APAZA',     'FRANCO ALEXIS',      'M','2018-12-05'),
('10000010','MENDOZA',   'HUAMAN',    'LUCIA VALENTINA',    'F','2019-07-14'),
-- ── 2°Prim A (11-15) ────────────────────────────────────────
('10000011','CASTILLO',  'MORALES',   'BRYAN SEBASTIAN',    'M','2018-02-28'),
('10000012','RAMIREZ',   'SANCHEZ',   'ROCIO PAMELA',       'F','2018-05-11'),
('10000013','VARGAS',    'PEREZ',     'DAVID ALEJANDRO',    'M','2018-09-20'),
('10000014','GONZALES',  'LLANOS',    'KAREN ELIZABETH',    'F','2018-01-07'),
('10000015','CHAVEZ',    'DIAZ',      'CARLOS ENRIQUE',     'M','2018-07-03'),
-- ── 2°Prim B (16-20) ────────────────────────────────────────
('10000016','TAPIA',     'ROJAS',     'ESTEFANY MISHEL',    'F','2017-11-14'),
('10000017','PIZARRO',   'CASTILLO',  'AARON MATIAS',       'M','2018-04-22'),
('10000018','MORALES',   'FLORES',    'YESENIA PAOLA',      'F','2017-12-30'),
('10000019','SALAZAR',   'TORRES',    'FABIAN RODRIGO',     'M','2018-08-16'),
('10000020','HUAMAN',    'QUISPE',    'CINTHIA MILAGROS',   'F','2018-03-09'),
-- ── 3°Prim A (21-25) ────────────────────────────────────────
('10000021','DIAZ',      'RAMIREZ',   'ERICK PAUL',         'M','2017-06-18'),
('10000022','PEREZ',     'MENDOZA',   'ANALI DEL PILAR',    'F','2017-02-24'),
('10000023','LLANOS',    'VARGAS',    'OMAR GABRIEL',       'M','2017-09-05'),
('10000024','CCORI',     'GONZALES',  'BRENDA LORENA',      'F','2017-04-12'),
('10000025','APAZA',     'HUANCA',    'WILDER JESUS',       'M','2017-11-28'),
-- ── 3°Prim B (26-30) ────────────────────────────────────────
('10000026','MEZA',      'CONDORI',   'LEIDY JOHANA',       'F','2016-12-07'),
('10000027','ESPINOZA',  'APAZA',     'KEVIN DANIEL',       'M','2017-03-15'),
('10000028','GUTIERREZ', 'CCORI',     'ROSA MARIA',         'F','2017-07-21'),
('10000029','RIVERA',    'PIZARRO',   'JHON ALEX',          'M','2017-01-08'),
('10000030','SANCHEZ',   'MEZA',      'DANI LUCERO',        'F','2017-05-30'),
-- ── 4°Prim A (31-35) ────────────────────────────────────────
('10000031','FLORES',    'ESPINOZA',  'SERGIO ANTONIO',     'M','2016-03-22'),
('10000032','TORRES',    'GUTIERREZ', 'MILUSKA ALEJANDRA',  'F','2016-06-14'),
('10000033','QUISPE',    'RIVERA',    'JUNIOR JHAIR',       'M','2016-09-08'),
('10000034','GARCIA',    'SANCHEZ',   'FERNANDA ANAIS',     'F','2016-01-27'),
('10000035','MAMANI',    'DIAZ',      'NELSON FELIX',       'M','2016-11-03'),
-- ── 4°Prim B (36-40) ────────────────────────────────────────
('10000036','ROJAS',     'MAMANI',    'ASHLEY NICHOLE',     'F','2015-12-19'),
('10000037','RAMIREZ',   'GARCIA',    'JOSE LUIS',          'M','2016-04-07'),
('10000038','VARGAS',    'ROJAS',     'KIARA DANIELA',      'F','2016-08-25'),
('10000039','MENDOZA',   'FLORES',    'LUIS ALBERTO',       'M','2016-02-11'),
('10000040','CHAVEZ',    'TORRES',    'MARIA JOSE',         'F','2016-10-16'),
-- ── 5°Prim A (41-45) ────────────────────────────────────────
('10000041','CASTILLO',  'CHAVEZ',    'RAUL EMILIO',        'M','2015-05-20'),
('10000042','GONZALES',  'VARGAS',    'PAOLA STEFANY',      'F','2015-08-03'),
('10000043','HUANCA',    'MENDOZA',   'RODRIGO ANDRE',      'M','2015-01-17'),
('10000044','MORALES',   'CASTILLO',  'NADIA ROSARIO',      'F','2015-11-29'),
('10000045','TAPIA',     'GONZALES',  'ALEXIS RENATO',      'M','2015-03-08'),
-- ── 5°Prim B (46-50) ────────────────────────────────────────
('10000046','PIZARRO',   'HUANCA',    'ISABEL CRISTINA',    'F','2014-12-14'),
('10000047','CCORI',     'MORALES',   'CESAR AUGUSTO',      'M','2015-07-26'),
('10000048','LLANOS',    'TAPIA',     'GABRIELA YASMIN',    'F','2015-04-09'),
('10000049','APAZA',     'PIZARRO',   'CRISTIAN JAVIER',    'M','2014-10-31'),
('10000050','MEZA',      'CCORI',     'MARIA FERNANDA',     'F','2015-09-22'),
-- ── 6°Prim A (51-55) ────────────────────────────────────────
('10000051','ESPINOZA',  'LLANOS',    'VICTOR MANUEL',      'M','2014-02-18'),
('10000052','GUTIERREZ', 'APAZA',     'XIOMARA ELENA',      'F','2014-06-07'),
('10000053','RIVERA',    'MEZA',      'JOSE MANUEL',        'M','2014-11-25'),
('10000054','SANCHEZ',   'ESPINOZA',  'NATALIA BELEN',      'F','2014-04-14'),
('10000055','FLORES',    'GUTIERREZ', 'MARIO ANTONIO',      'M','2014-08-30'),
-- ── 6°Prim B (56-60) ────────────────────────────────────────
('10000056','TORRES',    'RIVERA',    'ADRIANA NICOLE',     'F','2013-12-05'),
('10000057','QUISPE',    'SANCHEZ',   'HENRY OMAR',         'M','2014-03-19'),
('10000058','MAMANI',    'FLORES',    'CARMEN ROSA',        'F','2014-07-08'),
('10000059','GARCIA',    'TORRES',    'EDWIN RAUL',         'M','2013-09-24'),
('10000060','ROJAS',     'QUISPE',    'ELIZABETH PILAR',    'F','2014-01-12'),
-- ── 1°Sec B (61-65) ─────────────────────────────────────────
('10000061','RAMIREZ',   'MAMANI',    'SEBASTIAN ALAN',     'M','2013-05-07'),
('10000062','VARGAS',    'GARCIA',    'STEPHANIE MARIE',    'F','2013-08-21'),
('10000063','MENDOZA',   'ROJAS',     'GABRIEL ALEJANDRO',  'M','2013-01-14'),
('10000064','CHAVEZ',    'RAMIREZ',   'FIORELLA LUCIA',     'F','2013-10-29'),
('10000065','CASTILLO',  'VARGAS',    'ANDERSON PAUL',      'M','2013-04-16'),
-- ── 1°Sec C (66-70) ─────────────────────────────────────────
('10000066','GONZALES',  'MENDOZA',   'DANNA VALERIA',      'F','2012-11-03'),
('10000067','HUANCA',    'CASTILLO',  'PIERO ALEJANDRO',    'M','2013-03-18'),
('10000068','MORALES',   'GONZALES',  'NAOMI ESTHER',       'F','2013-07-02'),
('10000069','TAPIA',     'HUANCA',    'RUDOLFO CESAR',      'M','2012-09-27'),
('10000070','PIZARRO',   'MORALES',   'ALISON DIANA',       'F','2013-02-11'),
-- ── 2°Sec A (71-75) ─────────────────────────────────────────
('10000071','CCORI',     'TAPIA',     'ANTHONY JAMES',      'M','2012-06-15'),
('10000072','LLANOS',    'PIZARRO',   'XIOMARA VALERIA',    'F','2012-09-28'),
('10000073','APAZA',     'CCORI',     'LUIS RODRIGO',       'M','2012-02-07'),
('10000074','MEZA',      'LLANOS',    'CINDY LORENA',       'F','2012-12-21'),
('10000075','ESPINOZA',  'APAZA',     'JORGE LUIS',         'M','2012-05-04'),
-- ── 2°Sec B (76-80) ─────────────────────────────────────────
('10000076','GUTIERREZ', 'MEZA',      'JESSICA PAMELA',     'F','2011-11-18'),
('10000077','RIVERA',    'ESPINOZA',  'RONALDO DAVID',      'M','2012-04-03'),
('10000078','SANCHEZ',   'GUTIERREZ', 'DIANA CAROLINA',     'F','2012-07-16'),
('10000079','FLORES',    'RIVERA',    'IVAN AUGUSTO',       'M','2011-10-09'),
('10000080','TORRES',    'SANCHEZ',   'MELISSA ANAHI',      'F','2012-01-25'),
-- ── 3°Sec A (81-85) ─────────────────────────────────────────
('10000081','QUISPE',    'TORRES',    'RENATO FABIAN',      'M','2011-05-12'),
('10000082','MAMANI',    'QUISPE',    'YOLANDA CRISTINA',   'F','2011-08-27'),
('10000083','GARCIA',    'MAMANI',    'JHONATAN ALEXIS',    'M','2011-02-18'),
('10000084','ROJAS',     'GARCIA',    'MAYRA STEFANI',      'F','2011-11-04'),
('10000085','RAMIREZ',   'ROJAS',     'ANGEL GABRIEL',      'M','2011-06-30'),
-- ── 3°Sec B (86-90) ─────────────────────────────────────────
('10000086','VARGAS',    'RAMIREZ',   'DENISSE PAMELA',     'F','2010-12-15'),
('10000087','MENDOZA',   'VARGAS',    'RODRIGO ALONSO',     'M','2011-04-08'),
('10000088','CHAVEZ',    'MENDOZA',   'VERONICA MILAGROS',  'F','2011-07-23'),
('10000089','CASTILLO',  'CHAVEZ',    'JOEL CRISTIAN',      'M','2010-09-17'),
('10000090','GONZALES',  'CASTILLO',  'CARLA MELISA',       'F','2011-01-05'),
-- ── 4°Sec A (91-95) ─────────────────────────────────────────
('10000091','HUANCA',    'GONZALES',  'MARCO ANTONIO',      'M','2010-06-19'),
('10000092','MORALES',   'HUANCA',    'PRISCILA RUTH',      'F','2010-09-02'),
('10000093','TAPIA',     'MORALES',   'JESUS ALEJANDRO',    'M','2010-03-14'),
('10000094','PIZARRO',   'TAPIA',     'PAMELA ESTHER',      'F','2010-12-28'),
('10000095','CCORI',     'PIZARRO',   'RICHARD SMITH',      'M','2010-05-07'),
-- ── 4°Sec B (96-100) ────────────────────────────────────────
('10000096','LLANOS',    'CCORI',     'GLORIA ESTEFANI',    'F','2009-11-22'),
('10000097','APAZA',     'LLANOS',    'CHRISTIAN ALBERTO',  'M','2010-04-16'),
('10000098','MEZA',      'APAZA',     'PATRICIA LORENA',    'F','2010-08-09'),
('10000099','ESPINOZA',  'MEZA',      'ALEX RODRIGO',       'M','2009-10-25'),
('10000100','GUTIERREZ', 'ESPINOZA',  'ROSA ELENA',         'F','2010-01-13'),
-- ── 5°Sec A (101-105) ───────────────────────────────────────
('10000101','RIVERA',    'GUTIERREZ', 'FRANK ALDAIR',       'M','2009-07-04'),
('10000102','SANCHEZ',   'RIVERA',    'MARIELA ROSA',       'F','2009-11-18'),
('10000103','FLORES',    'SANCHEZ',   'PEDRO JOSE',         'M','2009-04-26'),
('10000104','TORRES',    'FLORES',    'WENDY CAROLINA',     'F','2009-08-12'),
('10000105','QUISPE',    'TORRES',    'JOSUE ELIAS',        'M','2009-02-20'),
-- ── 5°Sec B (106-110) ───────────────────────────────────────
('10000106','MAMANI',    'QUISPE',    'KERLY DIANA',        'F','2008-12-09'),
('10000107','GARCIA',    'MAMANI',    'JULIO CESAR',        'M','2009-05-27'),
('10000108','ROJAS',     'GARCIA',    'NORMA YANETH',       'F','2009-09-13'),
('10000109','RAMIREZ',   'ROJAS',     'FRANK OSWALDO',      'M','2008-11-08'),
('10000110','VARGAS',    'RAMIREZ',   'CONNIE MISHEL',      'F','2009-03-01');

-- ════════════════════════════════════════════════════════════
-- 4. ESTUDIANTES
-- ════════════════════════════════════════════════════════════
INSERT IGNORE INTO estudiantes (persona_id)
SELECT id FROM personas
WHERE dni BETWEEN '10000001' AND '10000110';

-- ════════════════════════════════════════════════════════════
-- 5. MATRÍCULAS — una sección a la vez
--    Usa NOT EXISTS para idempotencia
-- ════════════════════════════════════════════════════════════

-- ─── 1°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000001','10000002','10000003','10000004','10000005')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 1°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000006','10000007','10000008','10000009','10000010')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 2°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000011','10000012','10000013','10000014','10000015')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 2 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 2°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000016','10000017','10000018','10000019','10000020')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 2 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 3°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000021','10000022','10000023','10000024','10000025')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 3 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 3°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000026','10000027','10000028','10000029','10000030')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 3 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 4°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000031','10000032','10000033','10000034','10000035')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 4 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 4°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000036','10000037','10000038','10000039','10000040')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 4 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 5°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000041','10000042','10000043','10000044','10000045')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 5 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 5°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000046','10000047','10000048','10000049','10000050')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 5 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 6°Prim A ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000051','10000052','10000053','10000054','10000055')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 6 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 6°Prim B ───────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000056','10000057','10000058','10000059','10000060')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 6 AND g.nivel_id = 1
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 1°Sec B ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000061','10000062','10000063','10000064','10000065')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 1°Sec C ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000066','10000067','10000068','10000069','10000070')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'C'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 1 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 2°Sec A ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000071','10000072','10000073','10000074','10000075')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 2 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 2°Sec B ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000076','10000077','10000078','10000079','10000080')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 2 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 3°Sec A ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000081','10000082','10000083','10000084','10000085')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 3 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 3°Sec B ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000086','10000087','10000088','10000089','10000090')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 3 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 4°Sec A ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000091','10000092','10000093','10000094','10000095')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 4 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 4°Sec B ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000096','10000097','10000098','10000099','10000100')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 4 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 5°Sec A ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000101','10000102','10000103','10000104','10000105')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'A'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 5 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

-- ─── 5°Sec B ────────────────────────────────────────────────
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula, estado,
     fecha_registro, fecha_aprobacion, registrado_por)
SELECT e.id, s.id, aa.id, 'regular', 'aprobada',
       '2026-03-01', '2026-03-08',
       (SELECT u.id FROM usuarios u INNER JOIN personas p ON p.id=u.persona_id WHERE p.dni='00000000')
FROM estudiantes e
INNER JOIN personas      p  ON p.id  = e.persona_id AND p.dni IN ('10000106','10000107','10000108','10000109','10000110')
INNER JOIN anios_academicos aa ON aa.estado = 'activo'
INNER JOIN secciones     s  ON s.anio_id = aa.id AND s.nombre = 'B'
INNER JOIN grados        g  ON g.id = s.grado_id AND g.numero = 5 AND g.nivel_id = 2
WHERE NOT EXISTS (SELECT 1 FROM matriculas m WHERE m.estudiante_id = e.id AND m.anio_id = aa.id);

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════
-- RESUMEN DE DISTRIBUCIÓN
-- ════════════════════════════════════════════════════════════
-- Nivel     Grado  Secc  DNIs estudiantes
-- ────────  ─────  ────  ──────────────────
-- Primaria  1°     A     10000001–10000005
-- Primaria  1°     B     10000006–10000010
-- Primaria  2°     A     10000011–10000015
-- Primaria  2°     B     10000016–10000020
-- Primaria  3°     A     10000021–10000025
-- Primaria  3°     B     10000026–10000030
-- Primaria  4°     A     10000031–10000035
-- Primaria  4°     B     10000036–10000040
-- Primaria  5°     A     10000041–10000045
-- Primaria  5°     B     10000046–10000050
-- Primaria  6°     A     10000051–10000055
-- Primaria  6°     B     10000056–10000060
-- Secundaria 1°    B     10000061–10000065  (1°A preexistente: DNIs 77752898, etc.)
-- Secundaria 1°    C     10000066–10000070
-- Secundaria 2°    A     10000071–10000075
-- Secundaria 2°    B     10000076–10000080
-- Secundaria 3°    A     10000081–10000085
-- Secundaria 3°    B     10000086–10000090
-- Secundaria 4°    A     10000091–10000095
-- Secundaria 4°    B     10000096–10000100
-- Secundaria 5°    A     10000101–10000105
-- Secundaria 5°    B     10000106–10000110
