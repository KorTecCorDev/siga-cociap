-- 017_estados_matricula_consolidacion.sql
--
-- Consolidacion FINAL de los estados de matricula a SOLO TRES:
--     - pendiente    : documentos/observaciones pendientes (nace asi)
--     - aprobada     : estudiante correctamente matriculado, sin pendientes
--                      (cuenta para boleta, orden de merito, notas, etc.)
--     - desactivado  : no matriculado por algun motivo (motivo OBLIGATORIO)
--
-- Contexto: el enum tenia 'aprobada' y 'activo' como SINONIMOS ("matricula
-- vigente"), introducidos en momentos distintos. El modulo de matriculas
-- activaba a 'activo', pero boleta, boleta publica, asistencia, conducta,
-- panel del padre y parte de orden de merito filtran por 'aprobada' -> un
-- alumno 'activo' quedaba INVISIBLE en esas superficies (bug latente).
-- Se elimina 'activo' y la activacion pasa a 'aprobada' (un solo estado vigente).
--
-- (1) Columna motivo_estado: motivo visible del estado (lo que se muestra junto
--     al badge). `observaciones` se conserva como traza de auditoria historica.
-- (2) Normaliza cualquier fila 'activo' -> 'aprobada' ANTES de reducir el enum.
--     Verificado antes de migrar: 528 filas 'aprobada', 0 'activo' (sin perdida).
-- (3) Enum reducido a tres estados, default 'pendiente'.
--
-- Idempotente: MariaDB 10.4 soporta ADD COLUMN IF NOT EXISTS y el MODIFY/UPDATE
-- se pueden re-ejecutar sin efecto.

-- (1) Motivo visible del estado.
ALTER TABLE matriculas
    ADD COLUMN IF NOT EXISTS motivo_estado TEXT NULL AFTER estado;

-- (2) Normaliza filas legacy 'activo' antes de quitar el valor del enum.
UPDATE matriculas SET estado = 'aprobada' WHERE estado = 'activo';

-- (3) Enum final de tres estados.
ALTER TABLE matriculas
    MODIFY COLUMN estado
        ENUM('pendiente','aprobada','desactivado')
        NOT NULL DEFAULT 'pendiente';
