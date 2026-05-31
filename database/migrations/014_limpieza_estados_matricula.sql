-- 014_limpieza_estados_matricula.sql
--
-- (1) Reversibilidad 100% del traslado de salida:
--     columna `tipo_anterior` que preserva el origen real (continuador/nuevo)
--     cuando desactivar() marca `tipo='trasladado'`. activar() lo restaura y
--     limpia. Asi un ciclo desactivar -> activar no pierde el origen.
--
-- (2) Limpieza del enum `estado`: quita los estados legacy sin uso
--     (registrada, pendiente_documentos, observada, retirada). Quedan solo los
--     que el sistema usa de verdad:
--       - pendiente   : nace toda matricula del modulo
--       - activo      : matricula completa y activada
--       - desactivado : traslado de salida
--       - aprobada    : datos de la demo del I Bimestre (528 filas)
--     El default pasa de 'registrada' a 'pendiente'.
--
-- Verificado antes de migrar: la BD no tiene filas en estados legacy
-- (solo aprobada=528, pendiente=1), por lo que el MODIFY del enum no pierde datos.

-- (1) Columna para preservar el origen al trasladar.
ALTER TABLE matriculas
    ADD COLUMN IF NOT EXISTS tipo_anterior ENUM('continuador','nuevo') NULL AFTER tipo;

-- (2) Enum de estado depurado, default 'pendiente'.
ALTER TABLE matriculas
    MODIFY COLUMN estado
        ENUM('pendiente','activo','desactivado','aprobada')
        NOT NULL DEFAULT 'pendiente';
