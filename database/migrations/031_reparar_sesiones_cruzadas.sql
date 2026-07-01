-- ════════════════════════════════════════════════════════════════════
-- Migración 031: Reparar sesiones de horario cruzadas (1°A / 3°B secundaria)
-- ════════════════════════════════════════════════════════════════════
-- 11 filas antiguas de `sesiones_horario` (lote de la carga inicial de datos)
-- apuntan a cargas de 1°A secundaria pero su `seccion_id` desnormalizado dice
-- 3°B. El horario que describen es el REAL de 3°B colgado de las cargas
-- equivocadas: encaja sin choques con los bloques ya registrados bien en 3°B,
-- y como horario de 1°A chocaría con lo que 1°A sí tiene correcto.
--
-- Efectos del estado roto: registrar el horario de 3°B era imposible
-- (verificarSolapes encontraba estas filas por sección Y por docente y
-- rechazaba), y el horario imprimible del docente mostraba estas clases
-- como 3°B aunque su carga fuera de 1°A.
--
-- Reparación: mover cada sesión cruzada a su carga GEMELA en la sección que
-- la propia sesión declara (misma materia área/subárea + mismo docente +
-- activa). No se toca día/hora/docente/sección → no puede introducir solapes
-- nuevos. Las cargas de 1°A que pierden estas sesiones quedan "sin horario
-- propio" hasta que se digite el horario real de 1°A.
--
-- Dinámica (sin IDs hardcodeados: el estado inconsistente se detecta por
-- sh.seccion_id != ca.seccion_id) e idempotente (tras la corrida no quedan
-- filas que cumplan el WHERE). Si una sesión cruzada no tuviera gemela activa
-- se deja intacta — la query de verificación del pie la delata.
-- Ejecutar DESPUÉS de 030_limpieza_bloques_falsos.sql.
-- ════════════════════════════════════════════════════════════════════

-- 1. Mover las sesiones cruzadas a su carga gemela.
UPDATE sesiones_horario sh
INNER JOIN cargas_academicas ca ON ca.id = sh.carga_id
INNER JOIN cargas_academicas ct
        ON ct.seccion_id = sh.seccion_id          -- la sección que declara la sesión
       AND ct.docente_id = sh.docente_id          -- mismo docente
       AND COALESCE(ct.area_id, 0)    = COALESCE(ca.area_id, 0)    -- misma materia
       AND COALESCE(ct.subarea_id, 0) = COALESCE(ca.subarea_id, 0)
       AND ct.estado = 'activa'
SET sh.carga_id = ct.id
WHERE sh.seccion_id != ca.seccion_id;

-- 2. Recalcular horas_semanales de TODAS las cargas en horas académicas
--    (mismo criterio que la migración 030: redondeo POR BLOQUE según
--    duracion_hora_min). Las cargas que perdieron sesiones bajan a 0; las
--    que las recibieron suben.
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

-- Verificación (debe devolver 0):
--   SELECT COUNT(*) FROM sesiones_horario sh
--   INNER JOIN cargas_academicas ca ON ca.id = sh.carga_id
--   WHERE sh.seccion_id != ca.seccion_id;
