-- ============================================================
-- SIGA-COCIAP — Seed 002: Completar sistema
-- Corrige datos faltantes en la BD:
--   1. Usuario padre de prueba (DNI 99999999 / admin1234)
--   2. Competencias completas para primaria y secundaria
--
-- Es idempotente: usa NOT EXISTS para no duplicar registros.
-- Ejecutar DESPUÉS de: siga_cociap.sql + 002 + 003 + 001_datos_prueba.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ════════════════════════════════════════════════════════════
-- 1. USUARIO PADRE DE PRUEBA
--    La persona (DNI 99999999) y el apoderado ya existen en
--    001_datos_prueba.sql pero nunca se creó su cuenta de usuario.
-- ════════════════════════════════════════════════════════════
INSERT INTO usuarios (persona_id, rol_id, password_hash, estado)
SELECT
    p.id,
    r.id,
    '$2y$12$Lq8.NpB9XM1RkJ7sA0uZ8eKv3cQwY4nH6mG2oD5tF1iV9xWjPsE0.',
    'activo'
FROM personas p
CROSS JOIN roles r
WHERE p.dni    = '99999999'
  AND r.codigo = 'padre'
  AND NOT EXISTS (
      SELECT 1 FROM usuarios u WHERE u.persona_id = p.id
  );

-- ════════════════════════════════════════════════════════════
-- 2. COMPETENCIAS PRIMARIA — FALTANTES
-- ════════════════════════════════════════════════════════════

-- ── Educación Física ─────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C13', 'Se desenvuelve de manera autónoma a través de su motricidad.',
    'Motricidad autónoma', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C13');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C14', 'Asume una vida saludable.',
    'Vida saludable', a.id, 2
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C14');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C15', 'Interactúa a través de sus habilidades sociomotrices.',
    'Habilidades sociomotrices', a.id, 3
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C15');

-- ── Arte y Cultura ───────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C21', 'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia manifestaciones artísticas', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C21');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C22', 'Crea proyectos desde los lenguajes artístico-culturales.',
    'Crea proyectos artísticos', a.id, 2
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C22');

-- ── Inglés ───────────────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C4', 'Se comunica en inglés como lengua extranjera.',
    'Comunicación en inglés', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Educación Religiosa ──────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C27', 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente.',
    'Identidad como persona amada por Dios', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C27');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C28', 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida.',
    'Encuentro personal con Dios', a.id, 2
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C28');

-- ── Ciencia y Tecnología — Química ───────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C21', 'Explica el mundo físico basándose en conocimientos sobre materia y energía.',
    'Explica el mundo físico', sa.id, 1
FROM subareas sa WHERE sa.nombre='Química'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencia y Tecnología — Biología ──────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C20', 'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante métodos científicos', sa.id, 1
FROM subareas sa WHERE sa.nombre='Biología'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencia y Tecnología — Física ────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C22', 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
    'Diseña soluciones tecnológicas', sa.id, 1
FROM subareas sa WHERE sa.nombre='Física'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Competencias Transversales ───────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C2', 'Se desenvuelve en entornos virtuales generados por las TIC.',
    'Entornos virtuales / TIC', a.id, 1
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Competencias Transversales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C2');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C3', 'Gestiona su aprendizaje de manera autónoma.',
    'Aprendizaje autónomo', a.id, 2
FROM areas a WHERE a.nivel_id=1 AND a.nombre='Competencias Transversales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C3');

-- ════════════════════════════════════════════════════════════
-- 3. COMPETENCIAS SECUNDARIA — FALTANTES
-- ════════════════════════════════════════════════════════════

-- ── Ciencias Sociales — Historia ─────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C17', 'Construye interpretaciones históricas.',
    'Interpretaciones históricas', sa.id, 1
FROM subareas sa WHERE sa.nombre='Historia'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencias Sociales — Geografía ────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C18', 'Gestiona responsablemente el espacio y el ambiente.',
    'Gestiona el espacio y ambiente', sa.id, 1
FROM subareas sa WHERE sa.nombre='Geografía'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencias Sociales — Economía ─────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C19', 'Gestiona responsablemente los recursos económicos.',
    'Gestiona recursos económicos', sa.id, 1
FROM subareas sa WHERE sa.nombre='Economía'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Comunicación — Razonamiento Verbal ───────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C7', 'Se comunica oralmente en su lengua materna.',
    'Comunicación oral', sa.id, 1
FROM subareas sa WHERE sa.nombre='Razonamiento Verbal'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Comunicación — Literatura ─────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C8', 'Lee diversos tipos de textos escritos en su lengua materna.',
    'Lectura de textos', sa.id, 1
FROM subareas sa WHERE sa.nombre='Literatura'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Comunicación — Lenguaje ───────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C9', 'Escribe diversos tipos de textos en su lengua materna.',
    'Escritura de textos', sa.id, 1
FROM subareas sa WHERE sa.nombre='Lenguaje'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Comunicación')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Educación Física ─────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C13', 'Se desenvuelve de manera autónoma a través de su motricidad.',
    'Motricidad autónoma', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C13');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C14', 'Asume una vida saludable.',
    'Vida saludable', a.id, 2
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C14');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C15', 'Interactúa a través de sus habilidades sociomotrices.',
    'Habilidades sociomotrices', a.id, 3
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Física'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C15');

-- ── Arte y Cultura ───────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C21', 'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia manifestaciones artísticas', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C21');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C22', 'Crea proyectos desde los lenguajes artístico-culturales.',
    'Crea proyectos artísticos', a.id, 2
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Arte y Cultura'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C22');

-- ── Inglés ───────────────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C4', 'Se comunica en inglés como lengua extranjera.',
    'Comunicación en inglés', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Inglés'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Educación Religiosa ──────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C27', 'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente.',
    'Identidad como persona amada por Dios', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C27');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C28', 'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida.',
    'Encuentro personal con Dios', a.id, 2
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación Religiosa'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C28');

-- ── EPT ──────────────────────────────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C29', 'Gestiona proyectos de emprendimiento económico o social.',
    'Emprendimiento económico y social', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Educación para el Trabajo'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Taller de Razonamiento Matemático ────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C25', 'Resuelve problemas de gestión de datos e incertidumbre.',
    'Razonamiento matemático aplicado', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Taller de Razonamiento Matemático'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id);

-- ── Ciencia y Tecnología — Química ───────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C21', 'Explica el mundo físico basándose en conocimientos sobre materia y energía.',
    'Explica el mundo físico', sa.id, 1
FROM subareas sa WHERE sa.nombre='Química'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencia y Tecnología — Biología ──────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C20', 'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante métodos científicos', sa.id, 1
FROM subareas sa WHERE sa.nombre='Biología'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Ciencia y Tecnología — Física ────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C22', 'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
    'Diseña soluciones tecnológicas', sa.id, 1
FROM subareas sa WHERE sa.nombre='Física'
  AND sa.area_id=(SELECT id FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología')
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.subarea_id=sa.id);

-- ── Competencias Transversales ───────────────────────────────
INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C2', 'Se desenvuelve en entornos virtuales generados por las TIC.',
    'Entornos virtuales / TIC', a.id, 1
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Competencias Transversales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C2');

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C3', 'Gestiona su aprendizaje de manera autónoma.',
    'Aprendizaje autónomo', a.id, 2
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Competencias Transversales'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id=a.id AND c.codigo_minedu='C3');

SET FOREIGN_KEY_CHECKS = 1;
