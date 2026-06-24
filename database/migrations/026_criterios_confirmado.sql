-- ============================================================
-- 026 — Criterios: marca de confirmacion explicita del docente
-- ============================================================
-- Distingue una nota GUARDADA por el boton "Confirmar" (endpoint
-- /guardar) de una simplemente AUTOGUARDADA en blur (/autosave).
-- Solo el "Confirmar" sella `confirmado_en`; el autosave nunca.
--
-- Para que: el boton "Ver resumen" de la competencia se habilita
-- recien cuando hay >=1 criterio confirmado. Asi el docente no llega
-- al resumen salteandose el filtro de omision ayudandose del
-- autoguardado. Al ser una columna persistida (no estado de sesion),
-- el desbloqueo sobrevive al recargado de la pagina.
--
-- Backfill: todo criterio VIVO que ya tiene notas se marca confirmado.
-- Preserva el acceso de quienes ya estaban calificando (su trabajo no
-- se re-bloquea). El distingo autosave/confirmar aplica solo a
-- ediciones futuras.
--
-- Idempotente: ADD COLUMN IF NOT EXISTS (MariaDB 10.4+) y el UPDATE
-- solo toca filas aun en NULL.
-- ============================================================

ALTER TABLE `criterios`
    ADD COLUMN IF NOT EXISTS `confirmado_en`  DATETIME     NULL DEFAULT NULL AFTER `eliminado_por`,
    ADD COLUMN IF NOT EXISTS `confirmado_por` INT UNSIGNED NULL DEFAULT NULL AFTER `confirmado_en`;

UPDATE `criterios` c
SET c.`confirmado_en` = NOW()
WHERE c.`eliminado_en`  IS NULL
  AND c.`confirmado_en` IS NULL
  AND EXISTS (
      SELECT 1 FROM `calificaciones_criterio` cc
      WHERE cc.`criterio_id` = c.`id`
  );
