-- 027_desactivar_cargas_transversales.sql
-- Desactiva las cargas academicas TRANSVERSALES independientes que aun queden
-- con estado 'activa'. Son del modelo VIEJO: una sola carga por seccion donde el
-- TUTOR ingresaba los promedios de TIC/GAMA de toda la seccion.
--
-- Modelo nuevo (desde el II Bimestre): cada docente registra sus TIC/GAMA DENTRO
-- de su propia carga (seccion "Competencias Transversales" del formulario) y el
-- TUTOR solo agrega conclusiones + cierra el bimestre desde /docente/tutoria.
-- Una carga transversal 'activa' reaparece como tarjeta ("carga fantasma") en
-- /docente/mis-cargas y en el dashboard del tutor, reabriendo el flujo viejo.
--
-- La 019 desactivo la mayoria, pero quedaron rezagadas (p. ej. la carga 44 de la
-- seccion 1 del demo). Esta migracion barre las que falten.
--
-- SEGURO PARA LAS BOLETAS B1: NO se tocan calificaciones ni bloqueos. La
-- agregacion de boletas (TransversalModel::getPromediosMatricula/Seccion) lee
-- 'calificaciones' via 'bloqueos_competencia' SIN filtrar cargas_academicas.estado,
-- asi que las notas/cierres del I Bimestre siguen visibles. El cierre tampoco se
-- afecta: estadoCargasSeccion ya excluye las cargas transversales.
--
-- Idempotente: re-ejecutarla no cambia nada si ya estan inactivas.

UPDATE `cargas_academicas` ca
  INNER JOIN `areas` a ON a.id = ca.area_id
  SET ca.estado = 'inactiva'
  WHERE a.tipo = 'transversal'
    AND ca.estado = 'activa';
