-- ════════════════════════════════════════════════════════════════════
-- Migración 035: Competencia "Ética y Valores" en la carga TOE (secundaria)
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: encender la evaluación de Ética y Valores (Educación Religiosa)
--   en la carga del tutor (área Tutoría/TOE de SECUNDARIA). Es el PASO FINAL
--   del plan documentado en docs/modulos/calificaciones.md y docs/ESTADO.md:
--   el área tutoria es future-proof por datos → al existir 1 competencia en
--   ella, la card aparece sola en /docente/mis-cargas a los 11 tutores de
--   secundaria (el filtro EXISTS de getCargas es a nivel de área).
--
-- CONTENIDO (Alternativa A, decidida 08/07/2026): parafrasea en clave ética
--   la dimensión de conciencia moral de la competencia oficial de Educación
--   Religiosa (C51/C52 secundaria), sin lenguaje religioso en boleta.
--     codigo_minedu   = C57  (libre; precedente de códigos inventados C54-C56)
--     nombre_corto    = Actúa con valores éticos y conciencia moral
--     nombre_completo = Actúa con valores éticos según los principios de su
--                       conciencia moral en situaciones concretas de la vida
--                       escolar y comunitaria.
--
-- SIAGIE: es UNA sola competencia para el tutor; la duplicación de su nota en
--   las DOS competencias oficiales de Ed. Religiosa la hará el exportador de
--   secundaria (pendiente), NO este dato.
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * area_id NO hardcodeado: se resuelve por tipo='tutoria' + nivel Secundaria
--     (en prod el id puede no ser 24). `competencias` NO tiene UNIQUE KEY →
--     el NOT EXISTS blinda contra duplicados y hace la migración idempotente,
--     y a la vez es el candado del interruptor (si el área ya tiene competencia,
--     no reinserta nada).
--
-- ⚠️ SECUENCIA EN PROD (antes de correr esta migración):
--   1. Deben existir ya las 11 cargas TOE de secundaria (área 24, docente=tutor).
--   2. Área 24 con nombre_boleta='Ética y Valores' y alias_boleta='(Educación Religiosa)'.
--   Al aparecer la card el tutor califica de inmediato y la boleta debe mostrar
--   el nombre correcto. Detalle en docs/ESTADO.md (plan de encendido).
-- Ejecutar DESPUÉS de 035_area_etica_boleta.sql (el nombre de boleta debe estar
-- puesto ANTES de encender el interruptor). Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto primero en prod: ──
--   SELECT a.id AS area_id, a.nombre, n.nombre AS nivel,
--          (SELECT COUNT(*) FROM competencias c WHERE c.area_id = a.id) AS competencias
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE a.tipo = 'tutoria' AND n.nombre = 'Secundaria';
--   -- Debe devolver 1 área con competencias = 0. Si competencias > 0, DETENERSE
--   -- (Ética ya está encendida; una segunda corrida no insertaría nada de todos modos).

INSERT INTO competencias (codigo_minedu, nombre_completo, nombre_corto, area_id, orden)
SELECT 'C57',
       'Actúa con valores éticos según los principios de su conciencia moral en situaciones concretas de la vida escolar y comunitaria.',
       'Actúa con valores éticos y conciencia moral',
       a.id,
       1
FROM areas a
JOIN niveles n ON n.id = a.nivel_id
WHERE a.tipo = 'tutoria'
  AND n.nombre = 'Secundaria'
  AND NOT EXISTS (SELECT 1 FROM competencias c WHERE c.area_id = a.id);

-- Verificación (debe devolver la competencia recién creada, 1 fila):
--   SELECT c.id, c.codigo_minedu, c.nombre_corto, c.area_id
--   FROM competencias c JOIN areas a ON a.id = c.area_id
--   JOIN niveles n ON n.id = a.nivel_id
--   WHERE a.tipo = 'tutoria' AND n.nombre = 'Secundaria';
