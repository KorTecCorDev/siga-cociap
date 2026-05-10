-- ============================================================
-- SIGA-COCIAP — Seed 005: Currícula completa del COCIAP
-- Colegio de Aplicación "Víctor Valenzuela Guardia"
-- Huaraz, Ancash, Perú — 2026
--
-- Cubre: competencias (primaria + secundaria) y reglas especiales
-- Idempotente: cada INSERT usa NOT EXISTS — seguro de ejecutar
-- varias veces sin duplicar registros.
--
-- Prerequisito: siga_cociap.sql ya ejecutado
--   (define áreas, subáreas, niveles y grados)
--
-- Orden de ejecución sugerido:
--   1. migrations/000_crear_base_de_datos.sql
--   2. migrations/siga_cociap.sql
--   3. migrations/002_criterios_calificaciones.sql
--   4. migrations/003_bloqueos_competencia.sql
--   5. seeds/001_datos_prueba.sql
--   6. seeds/002_completar_sistema.sql
--   7. seeds/005_curricula_cociap.sql  ← este archivo
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- REFERENCIA RÁPIDA — Estructura curricular COCIAP
-- ============================================================
--
-- PRIMARIA (nivel_id = 1)
-- ┌─────────────────────────┬─────────────┬────────────────────────────────┐
-- │ Área                    │ Tipo        │ Subáreas                       │
-- ├─────────────────────────┼─────────────┼────────────────────────────────┤
-- │ Personal Social         │ area_curso  │ —                              │
-- │ Educación Física        │ area_curso  │ —                              │
-- │ Arte y Cultura          │ area_curso  │ —                              │
-- │ Inglés                  │ area_curso  │ —                              │
-- │ Educación Religiosa     │ area_curso  │ — alias: (Ética y Valores)     │
-- │ Comunicación            │ con_subareas│ Comunicación, Plan Lector,     │
-- │                         │             │ Razonamiento Verbal             │
-- │ Matemática              │ con_subareas│ Aritmética, Álgebra,           │
-- │                         │             │ Geometría, Raz. Mat.           │
-- │ Ciencia y Tecnología    │ con_subareas│ Biología, Química, Física      │
-- │ Competencias Transv.    │ transversal │ — (a cargo del tutor)          │
-- └─────────────────────────┴─────────────┴────────────────────────────────┘
--
-- SECUNDARIA (nivel_id = 2)
-- ┌────────────────────────────────┬─────────────┬───────────────────────────────┐
-- │ Área                           │ Tipo        │ Subáreas                      │
-- ├────────────────────────────────┼─────────────┼───────────────────────────────┤
-- │ DPCC                           │ area_curso  │ —                             │
-- │ Educación Física               │ area_curso  │ —                             │
-- │ Arte y Cultura                 │ area_curso  │ —                             │
-- │ Inglés                         │ area_curso  │ —                             │
-- │ Educación Religiosa            │ area_curso  │ — alias: (Ética y Valores)    │
-- │ EPT                            │ area_curso  │ — alias: (Habilidades Pedag.) │
-- │ Taller de Raz. Matemático      │ area_curso  │ — SIAGIE: Ed. Religiosa (1°-3°)│
-- │                                │             │          Arte y Cultura (4°-5°)│
-- │ Ciencias Sociales              │ con_subareas│ Historia, Geografía, Economía │
-- │ Comunicación                   │ con_subareas│ Raz. Verbal, Literatura,      │
-- │                                │             │ Lenguaje                      │
-- │ Matemática                     │ con_subareas│ Aritmética, Álgebra,          │
-- │                                │             │ Geometría, Trigonometría      │
-- │ Ciencia y Tecnología           │ con_subareas│ Biología, Química, Física     │
-- │ Competencias Transv.           │ transversal │ — (a cargo del tutor)         │
-- └────────────────────────────────┴─────────────┴───────────────────────────────┘
--
-- ESCALA DE CALIFICACIONES (misma para ambos niveles en BD):
--   AD: 18-20 | A: 14-17 | B: 11-13 | C: 00-10
--   Primaria → solo muestra literal  |  Secundaria → muestra numeral + literal
--
-- CONCLUSIÓN DESCRIPTIVA:
--   Primaria: obligatoria en B y C   |  Secundaria: obligatoria solo en C
-- ============================================================


-- ════════════════════════════════════════════════════════════
-- 1. COMPETENCIAS PRIMARIA
-- ════════════════════════════════════════════════════════════

-- ── 1.1 Personal Social (área-curso) ─────────────────────────
-- Área: Personal Social — 5 competencias C1, C16, C17, C18, C19

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C1',
    'Construye su identidad.',
    'Construye su identidad',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Personal Social'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C1' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C16',
    'Convive y participa democráticamente en la búsqueda del bien común.',
    'Convive y participa democráticamente',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Personal Social'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C16' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C17',
    'Construye interpretaciones históricas.',
    'Construye interpretaciones históricas',
    a.id, 3
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Personal Social'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C17' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C18',
    'Gestiona responsablemente el espacio y el ambiente.',
    'Gestiona el espacio y el ambiente',
    a.id, 4
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Personal Social'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C18' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C19',
    'Gestiona responsablemente los recursos económicos.',
    'Gestiona los recursos económicos',
    a.id, 5
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Personal Social'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C19' AND c.area_id = a.id
  );

-- ── 1.2 Educación Física (área-curso) ────────────────────────
-- C13, C14, C15

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C13',
    'Se desenvuelve de manera autónoma a través de su motricidad.',
    'Motricidad autónoma',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C13' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C14',
    'Asume una vida saludable.',
    'Vida saludable',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C14' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C15',
    'Interactúa a través de sus habilidades sociomotrices.',
    'Habilidades sociomotrices',
    a.id, 3
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C15' AND c.area_id = a.id
  );

-- ── 1.3 Arte y Cultura (área-curso) ──────────────────────────
-- C21, C22

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C21',
    'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia manifestaciones artístico-culturales',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Arte y Cultura'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C21' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C22',
    'Crea proyectos desde los lenguajes artístico-culturales.',
    'Crea proyectos artístico-culturales',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Arte y Cultura'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C22' AND c.area_id = a.id
  );

-- ── 1.4 Inglés (área-curso) ───────────────────────────────────
-- C4

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C4',
    'Se comunica en inglés como lengua extranjera.',
    'Comunicación en inglés',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Inglés'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C4' AND c.area_id = a.id
  );

-- ── 1.5 Educación Religiosa (área-curso) ─────────────────────
-- Alias en boleta: (Ética y Valores) — ya definido en areas.alias_boleta
-- C27, C28

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C27',
    'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente.',
    'Identidad como persona amada por Dios',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Educación Religiosa'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C27' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C28',
    'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.',
    'Encuentro personal y comunitario con Dios',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Educación Religiosa'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C28' AND c.area_id = a.id
  );

-- ── 1.6 Comunicación — con subáreas (primaria) ───────────────
-- Subárea Comunicación → C7
-- Subárea Plan Lector  → C8
-- Subárea Raz. Verbal  → C9

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C7',
    'Se comunica oralmente en su lengua materna.',
    'Comunicación oral',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Comunicación'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C7' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C8',
    'Lee diversos tipos de textos escritos en su lengua materna.',
    'Lectura de textos escritos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Plan Lector'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C8' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C9',
    'Escribe diversos tipos de textos en su lengua materna.',
    'Escritura de textos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Razonamiento Verbal'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C9' AND c.subarea_id = sa.id
  );

-- ── 1.7 Matemática — con subáreas (primaria) ─────────────────
-- Subárea Aritmética → C23
-- Subárea Álgebra    → C24
-- Subárea Geometría  → C26
-- Subárea Raz. Mat.  → C25  (Taller de razonamiento / Estadística)

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C23',
    'Resuelve problemas de cantidad.',
    'Resuelve problemas de cantidad',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Aritmética'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C23' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C24',
    'Resuelve problemas de regularidad, equivalencia y cambio.',
    'Regularidad, equivalencia y cambio',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Álgebra'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C24' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C26',
    'Resuelve problemas de forma, movimiento y localización.',
    'Forma, movimiento y localización',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Geometría'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C26' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C25',
    'Resuelve problemas de gestión de datos e incertidumbre.',
    'Gestión de datos e incertidumbre',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Raz. Mat.'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C25' AND c.subarea_id = sa.id
  );

-- ── 1.8 Ciencia y Tecnología — con subáreas (primaria) ───────
-- Subárea Biología → C20
-- Subárea Química  → C21
-- Subárea Física   → C22

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C20',
    'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante métodos científicos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Biología'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C20' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C21',
    'Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y universo.',
    'Explica el mundo físico',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Química'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C21' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C22',
    'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
    'Diseña y construye soluciones tecnológicas',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 1 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C22' AND c.subarea_id = sa.id
  );

-- ── 1.9 Competencias Transversales (primaria) ────────────────
-- A cargo del tutor de sección — no tienen docente asignado
-- C2, C3

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C2',
    'Se desenvuelve en entornos virtuales generados por las TIC.',
    'Entornos virtuales / TIC',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Competencias Transversales'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C2' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C3',
    'Gestiona su aprendizaje de manera autónoma.',
    'Aprendizaje autónomo',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 1 AND a.nombre = 'Competencias Transversales'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C3' AND c.area_id = a.id
  );


-- ════════════════════════════════════════════════════════════
-- 2. COMPETENCIAS SECUNDARIA
-- ════════════════════════════════════════════════════════════

-- ── 2.1 DPCC — Desarrollo Personal, Ciudadanía y Cívica (área-curso) ──
-- C1, C16

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C1',
    'Construye su identidad.',
    'Construye su identidad',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Desarrollo Personal, Ciudadanía y Cívica'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C1' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C16',
    'Convive y participa democráticamente en la búsqueda del bien común.',
    'Convive y participa democráticamente',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Desarrollo Personal, Ciudadanía y Cívica'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C16' AND c.area_id = a.id
  );

-- ── 2.2 Educación Física (área-curso) ────────────────────────
-- C13, C14, C15

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C13',
    'Se desenvuelve de manera autónoma a través de su motricidad.',
    'Motricidad autónoma',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C13' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C14',
    'Asume una vida saludable.',
    'Vida saludable',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C14' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C15',
    'Interactúa a través de sus habilidades sociomotrices.',
    'Habilidades sociomotrices',
    a.id, 3
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C15' AND c.area_id = a.id
  );

-- ── 2.3 Arte y Cultura (área-curso) ──────────────────────────
-- Nota SIAGIE (4°-5° sec): Las notas del Taller Raz. Matemático
-- se registran bajo este campo en el SIAGIE — ver reglas_especiales
-- C21, C22

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C21',
    'Aprecia de manera crítica manifestaciones artístico-culturales.',
    'Aprecia manifestaciones artístico-culturales',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Arte y Cultura'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C21' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C22',
    'Crea proyectos desde los lenguajes artístico-culturales.',
    'Crea proyectos artístico-culturales',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Arte y Cultura'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C22' AND c.area_id = a.id
  );

-- ── 2.4 Inglés (área-curso) ───────────────────────────────────
-- C4

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C4',
    'Se comunica en inglés como lengua extranjera.',
    'Comunicación en inglés',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Inglés'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C4' AND c.area_id = a.id
  );

-- ── 2.5 Educación Religiosa (área-curso) ─────────────────────
-- Alias en boleta: (Ética y Valores) — ya definido en areas.alias_boleta
-- SIAGIE 1°-3° sec: Taller Raz. Mat. se registra aquí (ver reglas_especiales)
-- C27, C28

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C27',
    'Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente.',
    'Identidad como persona amada por Dios',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación Religiosa'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C27' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C28',
    'Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.',
    'Encuentro personal y comunitario con Dios',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación Religiosa'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C28' AND c.area_id = a.id
  );

-- ── 2.6 EPT — Educación para el Trabajo (área-curso) ─────────
-- Alias en boleta: (Habilidades Pedagógicas) — ya en areas.alias_boleta
-- C29

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C29',
    'Gestiona proyectos de emprendimiento económico o social.',
    'Emprendimiento económico y social',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Educación para el Trabajo'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C29' AND c.area_id = a.id
  );

-- ── 2.7 Taller de Razonamiento Matemático (área-curso) ───────
-- SIAGIE:
--   1°-3° sec → se registra como Educación Religiosa en SIAGIE
--               (codificado en areas.nombre_siagie = 'Educación Religiosa')
--   4°-5° sec → se registra como Arte y Cultura en SIAGIE
--               (ver reglas_especiales sección 3 de este archivo)
-- C25

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C25',
    'Resuelve problemas de gestión de datos e incertidumbre.',
    'Razonamiento matemático aplicado',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Taller de Razonamiento Matemático'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C25' AND c.area_id = a.id
  );

-- ── 2.8 Ciencias Sociales — con subáreas ─────────────────────
-- Subárea Historia  → C17
-- Subárea Geografía → C18
-- Subárea Economía  → C19

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C17',
    'Construye interpretaciones históricas.',
    'Interpretaciones históricas',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencias Sociales'
WHERE sa.nombre = 'Historia'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C17' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C18',
    'Gestiona responsablemente el espacio y el ambiente.',
    'Gestiona el espacio y el ambiente',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencias Sociales'
WHERE sa.nombre = 'Geografía'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C18' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C19',
    'Gestiona responsablemente los recursos económicos.',
    'Gestiona los recursos económicos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencias Sociales'
WHERE sa.nombre = 'Economía'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C19' AND c.subarea_id = sa.id
  );

-- ── 2.9 Comunicación — con subáreas (secundaria) ─────────────
-- Subárea Razonamiento Verbal → C7
-- Subárea Literatura          → C8
-- Subárea Lenguaje            → C9

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C7',
    'Se comunica oralmente en su lengua materna.',
    'Comunicación oral',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Razonamiento Verbal'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C7' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C8',
    'Lee diversos tipos de textos escritos en su lengua materna.',
    'Lectura de textos escritos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Literatura'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C8' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C9',
    'Escribe diversos tipos de textos en su lengua materna.',
    'Escritura de textos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Comunicación'
WHERE sa.nombre = 'Lenguaje'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C9' AND c.subarea_id = sa.id
  );

-- ── 2.10 Matemática — con subáreas (secundaria) ───────────────
-- Subárea Aritmética    → C23
-- Subárea Álgebra       → C24
-- Subárea Geometría     → C26
-- Subárea Trigonometría → C25

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C23',
    'Resuelve problemas de cantidad.',
    'Resuelve problemas de cantidad',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Aritmética'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C23' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C24',
    'Resuelve problemas de regularidad, equivalencia y cambio.',
    'Regularidad, equivalencia y cambio',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Álgebra'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C24' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C26',
    'Resuelve problemas de forma, movimiento y localización.',
    'Forma, movimiento y localización',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Geometría'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C26' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C25',
    'Resuelve problemas de gestión de datos e incertidumbre.',
    'Gestión de datos e incertidumbre',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Matemática'
WHERE sa.nombre = 'Trigonometría'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C25' AND c.subarea_id = sa.id
  );

-- ── 2.11 Ciencia y Tecnología — con subáreas (secundaria) ─────
-- Subárea Biología → C20
-- Subárea Química  → C21
-- Subárea Física   → C22

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C20',
    'Indaga mediante métodos científicos para construir sus conocimientos.',
    'Indaga mediante métodos científicos',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Biología'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C20' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C21',
    'Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y universo.',
    'Explica el mundo físico',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Química'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C21' AND c.subarea_id = sa.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, subarea_id, orden)
SELECT 'C22',
    'Diseña y construye soluciones tecnológicas para resolver problemas de su entorno.',
    'Diseña y construye soluciones tecnológicas',
    sa.id, 1
FROM subareas sa
INNER JOIN areas a ON a.id = sa.area_id AND a.nivel_id = 2 AND a.nombre = 'Ciencia y Tecnología'
WHERE sa.nombre = 'Física'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C22' AND c.subarea_id = sa.id
  );

-- ── 2.12 Competencias Transversales (secundaria) ─────────────
-- A cargo del tutor de sección — no tienen docente asignado
-- C2, C3

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C2',
    'Se desenvuelve en entornos virtuales generados por las TIC.',
    'Entornos virtuales / TIC',
    a.id, 1
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Competencias Transversales'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C2' AND c.area_id = a.id
  );

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C3',
    'Gestiona su aprendizaje de manera autónoma.',
    'Aprendizaje autónomo',
    a.id, 2
FROM areas a
WHERE a.nivel_id = 2 AND a.nombre = 'Competencias Transversales'
  AND NOT EXISTS (
      SELECT 1 FROM competencias c
      WHERE c.codigo_minedu = 'C3' AND c.area_id = a.id
  );


-- ════════════════════════════════════════════════════════════
-- 3. REGLAS ESPECIALES SIAGIE (secundaria)
-- ════════════════════════════════════════════════════════════
--
-- Las siguientes reglas NO CAMBIAN lo que ve el padre en la boleta
-- interna del COCIAP; solo indican cómo registrar en el SIAGIE.
--
-- Regla implícita (codificada en areas.nombre_siagie):
--   Taller Raz. Matemático (todos los grados) → nombre_siagie='Educación Religiosa'
--   Esta es la regla por DEFECTO; los grados 4°-5° la SOBREESCRIBEN.
--
-- Regla explícita 4°-5° sec:
--   Taller Raz. Matemático → registrar bajo Arte y Cultura en SIAGIE.

INSERT INTO reglas_especiales
    (area_id, nivel_id, grado_desde, grado_hasta, area_siagie_id, descripcion)
SELECT
    ar_rm.id,
    2,
    4, 5,
    ar_arte.id,
    '4°-5° sec: Taller Raz. Matemático se registra como Arte y Cultura en SIAGIE (sobreescribe el nombre_siagie por defecto)'
FROM areas ar_rm
CROSS JOIN areas ar_arte
WHERE ar_rm.nivel_id  = 2 AND ar_rm.nombre   = 'Taller de Razonamiento Matemático'
  AND ar_arte.nivel_id = 2 AND ar_arte.nombre = 'Arte y Cultura'
  AND NOT EXISTS (
      SELECT 1 FROM reglas_especiales re
      WHERE re.area_id      = ar_rm.id
        AND re.nivel_id     = 2
        AND re.grado_desde  = 4
        AND re.grado_hasta  = 5
        AND re.area_siagie_id = ar_arte.id
  );

-- ════════════════════════════════════════════════════════════
-- FIN DEL SEED
-- ════════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 1;
