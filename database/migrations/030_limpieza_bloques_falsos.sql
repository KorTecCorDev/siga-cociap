-- ════════════════════════════════════════════════════════════════════
-- Migración 030: Limpieza de bloques falsos de 1 minuto + horas académicas
-- ════════════════════════════════════════════════════════════════════
-- El horario real del colegio se define por ÁREA, pero el sistema exigía que
-- CADA carga (subárea) tuviera bloques propios y prohibía compartirlos (solape
-- por sección y docente). Para esquivar esas validaciones se registraron ~189
-- bloques falsos de 1 minuto a medianoche (00:06-00:07, 00:11-00:12, ...) en
-- unidocentes 1°-3°, especialistas de primaria 4°-6° y CCSS de secundaria.
--
-- Esta migración los elimina (la aplicación ahora permite cargas SIN horario
-- propio) y recalcula `horas_semanales` de TODAS las cargas en HORAS
-- ACADÉMICAS: ROUND(minutos / duracion_hora_min) por bloque, sumado por carga
-- (misma regla que el horario imprimible). Antes se guardaba ROUND(min/60)
-- (horas reloj), inconsistente con el resto del sistema.
--
-- Idempotente: los DELETE no encuentran filas en una segunda corrida y el
-- UPDATE recalcula siempre el mismo valor. Ejecutar DESPUÉS de
-- 029_reemplazo_docente.sql.
-- ════════════════════════════════════════════════════════════════════

-- 1. Sesiones que apuntan a bloques falsos (duración <= 1 minuto).
DELETE sh
FROM sesiones_horario sh
INNER JOIN bloques_horario bh ON bh.id = sh.bloque_id
WHERE TIMESTAMPDIFF(MINUTE, bh.hora_inicio, bh.hora_fin) <= 1;

-- 2. Bloques falsos huérfanos (sin ninguna sesión que los use).
DELETE bh
FROM bloques_horario bh
WHERE TIMESTAMPDIFF(MINUTE, bh.hora_inicio, bh.hora_fin) <= 1
  AND NOT EXISTS (
      SELECT 1 FROM sesiones_horario sh WHERE sh.bloque_id = bh.id
  );

-- 3. Recalcular horas_semanales en horas académicas (redondeo POR BLOQUE,
--    igual que el imprimible: 45→1, 90→2; duración de la hora según la
--    configuración del año del bloque). Cargas sin sesiones → 0.
UPDATE cargas_academicas ca
LEFT JOIN (
    SELECT sh.carga_id,
           SUM(ROUND(
               TIMESTAMPDIFF(MINUTE, bh.hora_inicio, bh.hora_fin)
               / IF(COALESCE(ch.duracion_hora_min, 0) > 0, ch.duracion_hora_min, 45)
           )) AS horas
    FROM sesiones_horario sh
    INNER JOIN bloques_horario bh      ON bh.id = sh.bloque_id
    LEFT  JOIN configuracion_horario ch ON ch.id = bh.config_id
    GROUP BY sh.carga_id
) t ON t.carga_id = ca.id
SET ca.horas_semanales = COALESCE(t.horas, 0);
