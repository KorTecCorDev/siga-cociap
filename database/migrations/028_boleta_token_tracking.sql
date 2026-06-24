-- ============================================================
-- 028 — Tracking de visitas de la boleta por TOKEN (desacoplado del codigo)
-- ============================================================
-- El contador de visitas vivia en `boletas_publicas` (sistema de CODIGO),
-- cuyas filas solo las crea la generacion masiva de codigos. Al jubilar el
-- codigo en favor del TOKEN permanente, el tracking quedaria muerto.
--
-- Se mueve el contador a la propia matricula: el token es uno por estudiante
-- y permanente todo el anio, asi que la unidad natural de conteo es el
-- estudiante (no el periodo: el QR es el MISMO token en el papel de B1..B4).
-- Cuenta TODO acceso que pase por el token (escaneo de QR o portal del padre).
--
-- Backfill: preserva el historico del codigo (B1) sumando `veces_consultada`
-- por matricula. Solo toca filas aun en 0 -> seguro de re-ejecutar.
--
-- Idempotente: ADD COLUMN IF NOT EXISTS (MariaDB 10.4+).
-- ============================================================

ALTER TABLE `matriculas`
    ADD COLUMN IF NOT EXISTS `token_consultas`       INT UNSIGNED NOT NULL DEFAULT 0 AFTER `token_acceso`,
    ADD COLUMN IF NOT EXISTS `token_ultima_consulta` DATETIME     NULL DEFAULT NULL  AFTER `token_consultas`;

-- Backfill desde el contador del codigo (no se pierde el historico del I Bimestre).
UPDATE `matriculas` m
INNER JOIN (
    SELECT bp.`matricula_id`,
           SUM(bp.`veces_consultada`) AS total,
           MAX(bp.`ultima_consulta`)  AS ultima
    FROM `boletas_publicas` bp
    GROUP BY bp.`matricula_id`
) agg ON agg.`matricula_id` = m.`id`
SET m.`token_consultas`       = agg.`total`,
    m.`token_ultima_consulta` = agg.`ultima`
WHERE m.`token_consultas` = 0
  AND agg.`total` > 0;
