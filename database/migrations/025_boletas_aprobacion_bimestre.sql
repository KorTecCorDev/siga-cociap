-- 025_boletas_aprobacion_bimestre.sql
-- Hito A del cierre de bimestre: "bloquear y aprobar el bimestre" genera las
-- boletas BORRADOR (vista previa para los docentes). Se marca con un timestamp
-- en el periodo, SIN tocar periodos.estado (para no afectar las ~30 queries que
-- usan estado = 'activo' / IN ('activo','cerrado')).
--
-- ESTADO DE BOLETA derivado (no es una columna, se calcula):
--   estado='activo'  + boletas_aprobadas_en IS NULL     -> EN REGISTRO (sin boleta)
--   estado='activo'  + boletas_aprobadas_en IS NOT NULL -> BORRADOR (lo ven docentes)
--   estado='cerrado'                                    -> OFICIAL (docentes + padres)
-- Reapertura (cerrado -> activo) conserva el flag -> el bimestre vuelve a BORRADOR
-- y los padres dejan de verlo (vuelven al ultimo bimestre cerrado).
--
-- Idempotente: ADD COLUMN IF NOT EXISTS (MariaDB 10.4+).

ALTER TABLE `periodos`
  ADD COLUMN IF NOT EXISTS `boletas_aprobadas_en`  DATETIME      NULL AFTER `estado`,
  ADD COLUMN IF NOT EXISTS `boletas_aprobadas_por` INT UNSIGNED  NULL AFTER `boletas_aprobadas_en`;
