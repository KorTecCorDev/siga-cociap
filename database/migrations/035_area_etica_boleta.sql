-- ════════════════════════════════════════════════════════════════════
-- Migración 035: Nombre de boleta "Ética y Valores" en el área TOE (secundaria)
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: que las familias vean en boleta y en /padre/notas
--   "Ética y Valores (Educación Religiosa)" para la carga del tutor, mientras
--   docentes y horarios siguen viendo "Tutoría (TOE)" (areas.nombre no cambia).
--   Mecanismo existente, cero código: la vista concatena
--   nombre_boleta + ' ' + alias_boleta (ver docs/modulos/calificaciones.md).
--
-- Es el PASO 2 del plan de encendido (docs/ESTADO.md). Debe correr ANTES de la
--   036 (competencia/interruptor): así, cuando la card se encienda y se cierre
--   un bimestre, la boleta ya muestra el nombre correcto — nunca "Tutoría (TOE)".
--
-- ALCANCE: SOLO SECUNDARIA (área tutoria, nivel Secundaria). Primaria NO se toca.
--   area_id NO hardcodeado: se resuelve por tipo='tutoria' + nivel Secundaria.
--   Idempotente: reejecutar deja los mismos valores.
--
-- NOTA (fuera de alcance, NO se toca aquí): el plan menciona además
--   "verificar nombre_siagie NULL" en el área 24 y "quitar el alias huérfano
--   '(Ética y Valores)' del área 14 (Ed. Religiosa)". Hoy el área 24 tiene
--   nombre_siagie='Tutoría (TOE)' (no NULL). Se decidirá aparte al construir el
--   exportador SIAGIE de secundaria; esta migración solo ajusta boleta/alias.
-- Ejecutar DESPUÉS de 034_purga_docente_duplicada.sql. Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto primero en prod: ──
--   SELECT a.id, a.nombre, a.nombre_boleta, a.alias_boleta, n.nombre AS nivel
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE a.tipo = 'tutoria' AND n.nombre = 'Secundaria';
--   -- Debe devolver 1 área (Tutoría TOE secundaria). Confirma su id antes de aplicar.

UPDATE areas a
JOIN niveles n ON n.id = a.nivel_id
SET a.nombre_boleta = 'Ética y Valores',
    a.alias_boleta  = '(Educación Religiosa)'
WHERE a.tipo = 'tutoria'
  AND n.nombre = 'Secundaria';

-- Verificación (debe mostrar nombre_boleta='Ética y Valores' y alias='(Educación Religiosa)'):
--   SELECT a.nombre, a.nombre_boleta, a.alias_boleta
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE a.tipo = 'tutoria' AND n.nombre = 'Secundaria';
