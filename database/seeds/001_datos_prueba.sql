-- ============================================================
-- SIGA-COCIAP — Seed de prueba
-- Datos mínimos para probar el módulo de calificaciones
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── COMPETENCIAS ────────────────────────────────────────────
-- Primaria — Personal Social (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C1', 'Construye su identidad.', 'Construye su identidad', id, 1
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C16', 'Convive y participa democráticamente en la búsqueda del bien común.',
'Convive y participa', id, 2
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C17', 'Construye interpretaciones históricas.',
'Construye interpretaciones', id, 3
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C18', 'Gestiona responsablemente el espacio y el ambiente.',
'Gestiona el espacio', id, 4
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C19', 'Gestiona responsablemente los recursos económicos.',
'Gestiona recursos', id, 5
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

-- Primaria — Comunicación (subáreas)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C7', 'Se comunica oralmente en su lengua materna.',
'Comunicación oral', id, 1
FROM subareas WHERE nombre='Comunicación'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C8', 'Lee diversos tipos de textos escritos en su lengua materna.',
'Lectura', id, 1
FROM subareas WHERE nombre='Plan Lector'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C9', 'Escribe diversos tipos de textos en su lengua materna.',
'Escritura', id, 1
FROM subareas WHERE nombre='Razonamiento Verbal'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Comunicación');

-- Primaria — Matemática (subáreas)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C23', 'Resuelve problemas de cantidad.',
'Problemas de cantidad', id, 1
FROM subareas WHERE nombre='Aritmética'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C24', 'Resuelve problemas de regularidad, equivalencia y cambio.',
'Regularidad y cambio', id, 1
FROM subareas WHERE nombre='Álgebra'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C26', 'Resuelve problemas de forma, movimiento y localización.',
'Forma y movimiento', id, 1
FROM subareas WHERE nombre='Geometría'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C25', 'Resuelve problemas de gestión de datos e incertidumbre.',
'Gestión de datos', id, 1
FROM subareas WHERE nombre='Raz. Mat.'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');

-- Secundaria — DPCC (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C1', 'Construye su identidad.',
'Construye su identidad', id, 1
FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C16', 'Convive y participa democráticamente en la búsqueda del bien común.',
'Convive y participa', id, 2
FROM areas WHERE nivel_id=2 AND nombre='Desarrollo Personal, Ciudadanía y Cívica';

-- Secundaria — Matemática (subáreas)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C23', 'Resuelve problemas de cantidad.',
'Problemas de cantidad', id, 1
FROM subareas WHERE nombre='Aritmética'
AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C24', 'Resuelve problemas de regularidad, equivalencia y cambio.',
'Regularidad y cambio', id, 1
FROM subareas WHERE nombre='Álgebra'
AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C26', 'Resuelve problemas de forma, movimiento y localización.',
'Forma y movimiento', id, 1
FROM subareas WHERE nombre='Geometría'
AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C25', 'Resuelve problemas de gestión de datos e incertidumbre.',
'Gestión de datos', id, 1
FROM subareas WHERE nombre='Trigonometría'
AND area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Matemática');

-- ─── AÑO ACADÉMICO ───────────────────────────────────────────
INSERT INTO anios_academicos (anio, fecha_inicio, fecha_fin, estado) VALUES
(2026, '2026-03-09', '2026-12-18', 'activo');

-- ─── PERIODOS ────────────────────────────────────────────────
INSERT INTO periodos (anio_id, numero, tipo, nombre_display, fecha_inicio, fecha_fin, limite_notas, estado)
VALUES
(1, 1, 'bimestre', 'I Bimestre',  '2026-03-09', '2026-05-15',
 '2026-05-20 23:59:00', 'activo'),
(1, 2, 'bimestre', 'II Bimestre', '2026-05-19', '2026-07-17',
 NULL, 'pendiente'),
(1, 3, 'bimestre', 'III Bimestre','2026-08-03', '2026-10-02',
 NULL, 'pendiente'),
(1, 4, 'bimestre', 'IV Bimestre', '2026-10-05', '2026-12-04',
 NULL, 'pendiente');

-- ─── DOCENTE DE PRUEBA ───────────────────────────────────────
INSERT INTO personas
    (dni, apellido_paterno, apellido_materno, nombres, correo, sexo)
VALUES
('12345678', 'Castillejo', 'Morales', 'Nacho Eduar',
 'ncastillejo@cociap.edu.pe', 'M');

INSERT INTO usuarios (persona_id, rol_id, password_hash, estado)
SELECT p.id, r.id,
    '$2y$12$Lq8.NpB9XM1RkJ7sA0uZ8eKv3cQwY4nH6mG2oD5tF1iV9xWjPsE0.',
    'activo'
FROM personas p, roles r
WHERE p.dni='12345678' AND r.codigo='docente';

-- ─── SECCIÓN DE PRUEBA ───────────────────────────────────────
-- 1° Secundaria Sección A — con tutor el docente de prueba
INSERT INTO secciones (grado_id, anio_id, nombre, tutor_id, es_unidocente, estado_nomina)
SELECT
    g.id,
    1,
    'A',
    u.id,
    0,
    'aprobada'
FROM grados g, usuarios u
INNER JOIN personas p ON p.id = u.persona_id
WHERE g.nivel_id=2 AND g.numero=1
  AND p.dni='12345678';

-- ─── CARGA ACADÉMICA ─────────────────────────────────────────
-- Docente dicta Aritmética en 1° Sec A
INSERT INTO cargas_academicas
    (docente_id, seccion_id, anio_id, subarea_id, horas_semanales, estado)
SELECT
    u.id,
    s.id,
    1,
    sa.id,
    4,
    'activa'
FROM usuarios u
INNER JOIN personas p   ON p.id  = u.persona_id
INNER JOIN secciones s  ON s.nombre = 'A'
INNER JOIN grados g     ON g.id  = s.grado_id AND g.numero=1 AND g.nivel_id=2
INNER JOIN subareas sa  ON sa.nombre = 'Aritmética'
INNER JOIN areas a      ON a.id  = sa.area_id AND a.nivel_id=2
WHERE p.dni='12345678' AND s.anio_id=1;

-- ─── ESTUDIANTES Y MATRÍCULAS DE PRUEBA ──────────────────────
INSERT INTO personas (dni, apellido_paterno, apellido_materno, nombres, sexo) VALUES
('77752898', 'Angeles',  'Fernandez', 'Xiara Daleshka', 'F'),
('61314557', 'Aguilar',  'Rosario',   'Vanessa Yanneth','F'),
('45678901', 'Ramirez',  'Torres',    'Carlos Alberto', 'M'),
('56789012', 'Mendoza',  'Quispe',    'Lucia Valentina','F'),
('67890123', 'Huanca',   'Vidal',     'Diego Alejandro','M');

INSERT INTO estudiantes (persona_id)
SELECT id FROM personas
WHERE dni IN ('77752898','61314557','45678901','56789012','67890123');

-- Apoderado de prueba
INSERT INTO personas (dni, apellido_paterno, apellido_materno, nombres, correo, telefono)
VALUES ('99999999','Angeles','Torres','Maria Elena','papito@gmail.com','943123456');

INSERT INTO apoderados (persona_id)
SELECT id FROM personas WHERE dni='99999999';

-- Vínculo familiar
INSERT INTO vinculo_familiar (estudiante_id, apoderado_id, tipo_vinculo, es_responsable)
SELECT e.id, ap.id, 'madre', 1
FROM estudiantes e
INNER JOIN personas pe ON pe.id = e.persona_id AND pe.dni='77752898'
CROSS JOIN apoderados ap
INNER JOIN personas pa ON pa.id = ap.persona_id AND pa.dni='99999999';

-- Matrículas aprobadas en 1° Sec A
INSERT INTO matriculas
    (estudiante_id, seccion_id, anio_id, tipo_matricula,
     estado, fecha_registro, registrado_por)
SELECT
    e.id,
    s.id,
    1,
    'regular',
    'aprobada',
    CURDATE(),
    (SELECT id FROM usuarios ORDER BY id LIMIT 1)
FROM estudiantes e
INNER JOIN personas p ON p.id = e.persona_id
INNER JOIN secciones s ON s.nombre='A'
INNER JOIN grados g ON g.id=s.grado_id AND g.numero=1 AND g.nivel_id=2
WHERE p.dni IN ('77752898','61314557','45678901','56789012','67890123')
  AND s.anio_id=1;

SET FOREIGN_KEY_CHECKS = 1;