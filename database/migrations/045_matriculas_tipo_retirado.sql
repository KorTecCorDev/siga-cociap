-- ============================================================
-- 045_matriculas_tipo_retirado.sql
-- Nuevo tipo de matricula: 'retirado'.
--
-- Un estudiante que YA NO ASISTE al colegio pero NO se traslado de
-- manera oficial (no hay constancia ni IE destino; la familia guarda
-- la esperanza de que regrese). Hasta ahora el unico marcador de
-- "abandono" era tipo='trasladado', que exige el tramite oficial.
--
-- Semantica (todos son estado='desactivado'):
--   tipo IN ('continuador','nuevo') -> baja administrativa (p.ej. deuda):
--                                      SIGUE calificable (aun asiste).
--   tipo='retirado'                 -> ya no asiste: NO calificable.
--   tipo='trasladado'               -> traslado oficial: NO calificable.
--
-- Los rosters de evaluacion (calificaciones, conducta, transversales,
-- tutoria) excluyen ahora tipo IN ('trasladado','retirado'). La boleta,
-- el orden de merito y el export SIAGIE no cambian: un 'retirado' es un
-- desactivado no-trasladado, asi que ya cae en "boleta BORRADOR interna,
-- fuera de merito y de export".
--
-- Reversible: al marcar 'retirado' se guarda el tipo real en
-- tipo_anterior; al revertir (o al reactivar la matricula) se restaura.
-- Por eso tipo_anterior NO incluye 'retirado' (nunca se revierte hacia el).
--
-- Idempotente: MODIFY re-ejecutable sin dano.
-- ============================================================

ALTER TABLE matriculas
    MODIFY tipo ENUM('continuador','nuevo','trasladado','retirado')
    NOT NULL DEFAULT 'continuador';
