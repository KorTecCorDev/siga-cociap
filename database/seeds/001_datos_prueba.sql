-- ============================================================
-- SIGA-COCIAP — Seed de prueba
-- Datos mínimos para probar el módulo de calificaciones
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════
-- 1. COMPETENCIAS PRIMARIA
-- ════════════════════════════════════════════════════════════
-- ── Inglés como lengua extranjera (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C1', 'Se comunica en inglés como lengua extranjera.',
    'Comunicación en inglés', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C2', 'Lee diversos tipos de textos escritos en inglés como lengua extranjera',
    'Lee y comprende en inglés', a.id, 2
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C3', 'Escribe diversos tipos de textos en inglés como lengua extranjera',
    'Redacción en inglés', a.id, 3
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- — Personal Social (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C4', 'Construye su identidad.', 'Construye su identidad', id, 4
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C5', 'Convive y participa democráticamente en la búsqueda del bien común.',
'Convive y participa por el bien común', id, 5
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C6', 'Construye interpretaciones históricas.',
'Construye interpretaciones históricas', id, 6
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C7', 'Gestiona responsablemente el espacio y el ambiente.',
'Gestiona el espacio y el ambiente', id, 7
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C8', 'Gestiona responsablemente los recursos económicos.',
'Gestiona los recursos económicos', id, 8
FROM areas WHERE nivel_id=1 AND nombre='Personal Social';

-- ── Educación Religiosa (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C9', 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.',
    'Identidad como persona amada por Dios', a.id, 9
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Religiosa';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C10', 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa','Asume la experiencia de Dios en su proyecto de vida', a.id, 10
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Religiosa';

-- ── Educación Física (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C13', 'Se desenvuelve de manera autónoma a través de su motricidad.',
    'Motricidad autónoma', a.id, 11
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C14', 'Asume una vida saludable.',
    'Vida saludable', a.id, 12
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C15', 'Interactúa a través de sus habilidades sociomotrices.',
    'Habilidades sociomotrices', a.id, 13
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física';

-- ── Comunicación (área)
-- Comunicación(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C16', 'Se comunica oralmente en su lengua materna.',
    'Se comunica oralmente en sulengua materna.', a.id, 14
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Comunicación';

-- Plan lector(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C17', 'Lee diversos tipos de textos escritos en su lengua materna.',
    'Lee diversos tipos de textos escritos en su lengua materna', a.id, 15
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Comunicación';

-- Razonamiento Verbal (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C18', 'Escribe diversos tipos de textos en su lengua materna.',
    'Escribe diversos tipos de textos en su lengua materna', a.id, 16
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Comunicación';

-- ── Arte y Cultura (área-curso) ───────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C19', 'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia manifestaciones artísticas', a.id, 17
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Arte y Cultura';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C20', 'Crea proyectos desde los lenguajes artísticos.',
    'Crea proyectos artísticos', a.id, 18
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Arte y Cultura';

-- ── Matemática(área) ───────────────────────────────────────────

-- Aritmética(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C21', 'Resuelve problemas de cantidad.',
'Resuelve problemas de cantidad', id, 19
FROM subareas WHERE nombre='Aritmética'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');
-- Álgebra(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C22', 'Resuelve problemas de regularidad, equivalencia y cambio.',
'Resuelve problemas de regularidad, equivalencia y cambio', id, 20
FROM subareas WHERE nombre='Álgebra'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');
-- Geometría(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C23', 'Resuelve problemas de forma, movimiento y localización.',
'Resuelve problemas de forma, movimiento y localización', id, 21
FROM subareas WHERE nombre='Geometría'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');
-- Razonamiento Matemático(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C24', 'Resuelve problemas de gestión de datos e incertidumbre.',
'Gestión de datos', id, 22
FROM subareas WHERE nombre='Razonamiento Matemático'
AND area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Matemática');

-- ── Ciencia y Tecnología (área)
-- Química (subarea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C25', 'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante el método científico', sa.id, 23
FROM subareas sa WHERE sa.nombre='Química'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- Biología (subarea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C26', 'Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía; biodiversidad, Tierra y Universo.',
    'Explica el mundo físico basándose en los seres vivos', sa.id, 24
FROM subareas sa WHERE sa.nombre='Biología'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- Física (subarea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C27', 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.', 'Diseña y construye soluciones tecnológicas', sa.id, 25
FROM subareas sa WHERE sa.nombre='Física'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Competencias Transversales (caso-especial)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'CT1', 'Se desenvuelve en entornos virtuales generados por las TIC.',
    'Entornos virtuales / TIC', a.id, 26
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Competencias Transversales';

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'CT2', 'Gestiona su aprendizaje de manera autónoma.',
    'Aprendizaje autónomo', a.id, 27
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Competencias Transversales';



-- ════════════════════════════════════════════════════════════
-- 1. COMPETENCIAS SECUNDARIA
-- ════════════════════════════════════════════════════════════
-- ── Desarrollo personal, Ciudadanía y Cívica (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C28', 'Construye su identidad.',
    'Construye su identidad', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Desarrollo Personal, Ciudadanía y Cívica'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C29', 'Convive y participa democráticamente en la búsqueda del bien común.',
    'Convive democráticamente en la búsqueda del bien común', a.id, 2
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Desarrollo Personal, Ciudadanía y Cívica'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Ciencias sociales (área)
-- Historia (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C30', 'Construye interpretaciones históricas.',
    'Construye interpretaciones históricas', a.id, 3
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencias sociales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);
-- Geografía (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C31', 'Gestiona responsablemente el espacio y el ambiente.',
    'Gestiona responsablemente el espacio y el ambiente', a.id, 4
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencias sociales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);
-- Economía (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C32', 'Gestiona responsablemente los recursos económicos.',
    'Gestiona responsablemente los recursos económicos', a.id, 5
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencias sociales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Educación Física (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C33', 'Asume una vida saludable.',
    'Asume una vida saludable.', a.id, 6
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C34', 'Interactúa a través de sus habilidades sociomotrices.',
    'Interactúa a través de sus habilidades sociomotrices.', a.id, 7
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C35', 'Asume una vida saludable.',
    'Asume una vida saludable.', a.id, 8
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Arte y Cultura (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C36', 'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia de manera crítica manifestaciones artístico-culturales', a.id, 9
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C37', 'Crea proyectos desde los lenguajes artísticos.',
    'Crea proyectos desde los lenguajes artísticos', a.id, 10
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Comunicación (área)
-- Razonamiento Verbal (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C38', 'Se comunica oralmente en su lengua materna.',
    'Se comunica oralmente', a.id, 11
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Comunicación'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Literatura (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C39', 'Lee diversos tipos de textos escritos en su lengua materna.',
    'Lee diversos tipos de textos', a.id, 12
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Comunicación'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Comunicación (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C40', 'Escribe diversos tipos de textos en su lengua materna.',
    'Escribe diversos tipos de textos', a.id, 13
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Comunicación'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Inglés (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C41', 'Se comunica oralmente.',
    'Se comunica oralmente', a.id, 14
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C42', 'Lee diversos tipos de textos escritos.',
    'Lee diversos tipos de textos', a.id, 15
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C43', 'Escribe diversos tipos de texto.',
    'Escribe diversos tipos de textos', a.id, 16
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Matemática (área)
-- Aritmética(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C44', 'Resuelve problemas de cantidad.',
    'Resuelve problemas de cantidad', a.id, 17
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Matemática'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Álgebra(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C45', 'Resuelve problemas de regularidad, equivalencia y cambio.',
    'Resuelve problemas de regularidad, equivalencia y cambio', a.id, 18
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Matemática'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Geometría(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C46', 'Resuelve problemas de forma, movimiento y localización.',
    'Resuelve problemas de forma, movimiento y localización', a.id, 19
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Matemática'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Trigonometría(subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C47', 'Resuelve problemas de gestión de datos e incertidumbre.',
    'Resuelve problemas de gestión de datos e incertidumbre', a.id, 20
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Matemática'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);


-- ── Ciencia y Tecnología (área)
-- Química (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C48', 'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante métodos científicos', a.id, 21
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencia y Tecnología'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Biología (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C49', 'Explica el mundo físico basándose en conocimientos sobre los seres vivos; materia y energía;
biodiversidad, Tierra y Universo.',
    'Explica el mundo físico basándose en conocimientos sobre la Tierra y el Universo.', a.id, 22
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencia y Tecnología'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- Física (subárea)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C50', 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
    'Diseña y construye soluciones tecnológicas', a.id, 23 
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Ciencia y Tecnología'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Educación Religiosa (área-curso)
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C51', 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.',
    'Construye su identidad como persona amada por Dios', a.id, 24
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C52', 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.',
    'Asume la experiencia del encuentro con Dios en su vida', a.id, 25
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Educación para el Trabajo (área-curso)

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C53', 'Gestiona proyectos de emprendimiento económico o social.',
    'Gestiona proyectos de emprendimiento', a.id, 26
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación para el Trabajo'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);


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

-- ─── DOCENTES DE PRUEBA ───────────────────────────────────────
INSERT INTO personas
    (dni, apellido_paterno, apellido_materno, nombres, correo, sexo)
VALUES
('12345678', 'Guillermo', 'Chavez', 'Luis Waldir',
 'waldirguillermoc@gmail.com', 'M');

INSERT INTO usuarios (persona_id, rol_id, password_hash, estado)
SELECT p.id, r.id,
    '$2y$10$HfNJaWDDQrGujO9FkqlAjeO2l2t46KGasEY2W3cz2a4Y2jlcl9.3O',
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
VALUES ('99999999','Fernandez','Torres','Maria Elena','fertome1983@gmail.com','943123456');

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