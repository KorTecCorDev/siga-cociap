-- ════════════════════════════════════════════════════════════════════
-- Migración 022: Reversión del retorno de grado (auditoría)
-- ════════════════════════════════════════════════════════════════════
-- Permite "deshacer" un retorno de grado: la estudiante vuelve a calificarse
-- en su grado/sección OFICIAL (SIAGIE). El estado 'revertido' ya existía en
-- el enum de retornos_grado; aquí solo se agregan las columnas de auditoría
-- de la reversión. La boleta siempre muestra el grado/sección oficial (la
-- consolidación de notas se resuelve por UNIÓN en la capa de lectura, no se
-- duplican datos), por eso no hace falta tocar calificaciones ni bloqueos.
--
-- MariaDB 10.4 soporta ADD COLUMN ... IF NOT EXISTS → idempotente.
-- ════════════════════════════════════════════════════════════════════

ALTER TABLE retornos_grado
    ADD COLUMN IF NOT EXISTS fecha_reversion  DATE         NULL AFTER estado;

ALTER TABLE retornos_grado
    ADD COLUMN IF NOT EXISTS motivo_reversion TEXT         NULL AFTER fecha_reversion;

ALTER TABLE retornos_grado
    ADD COLUMN IF NOT EXISTS revertido_por    INT UNSIGNED NULL AFTER motivo_reversion;

-- FK de revertido_por → usuarios (condicional: solo si aún no existe).
SET @hay_fk = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA   = DATABASE()
      AND TABLE_NAME     = 'retornos_grado'
      AND CONSTRAINT_NAME = 'fk_retorno_revertido_por'
);
SET @sql = IF(@hay_fk = 0,
    'ALTER TABLE retornos_grado ADD CONSTRAINT fk_retorno_revertido_por FOREIGN KEY (revertido_por) REFERENCES usuarios(id)',
    'SELECT ''fk_retorno_revertido_por ya existe, omitiendo'' AS info'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
