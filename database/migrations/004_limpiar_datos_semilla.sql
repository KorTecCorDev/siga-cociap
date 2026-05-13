-- ================================================================
-- 004_limpiar_datos_semilla.sql
-- Elimina los registros huérfanos generados por seeds que fueron
-- aplicados con FOREIGN_KEY_CHECKS=0 cuando la tabla `competencias`
-- tenía IDs distintos (78-104, 96, 97).
--
-- Datos PRESERVADOS (no se tocan):
--   - cargas 10 y 14 (docente SOTELO, Inglés) con competencias 41-43
--   - Todo registro cuya carga Y competencia existan en el schema actual
--
-- Ejecutar en phpMyAdmin o desde MySQL CLI sobre siga_cociap.
-- ================================================================

-- 1. Criterios con competencia_id que ya no existe en `competencias`
--    El ON DELETE CASCADE en calificaciones_criterio limpia
--    automáticamente los registros de notas vinculados a estos criterios.
DELETE FROM criterios
WHERE competencia_id NOT IN (SELECT id FROM competencias);

-- 2. Calificaciones cuya carga o competencia ya no existen
DELETE FROM calificaciones
WHERE carga_id       NOT IN (SELECT id FROM cargas_academicas)
   OR competencia_id NOT IN (SELECT id FROM competencias);

-- 3. Bloqueos cuya carga o competencia ya no existen
DELETE FROM bloqueos_competencia
WHERE carga_id       NOT IN (SELECT id FROM cargas_academicas)
   OR competencia_id NOT IN (SELECT id FROM competencias);
