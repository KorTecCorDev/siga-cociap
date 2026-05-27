-- Migración 009: token de acceso permanente por matrícula
-- Un token único por alumno/año → URL pública que no expone IDs numéricos.
-- Idempotente: ADD COLUMN IF NOT EXISTS no existe en MariaDB 10.4,
-- se usa un bloque condicional vía stored procedure temporal.

SET @existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'matriculas'
      AND COLUMN_NAME  = 'token_acceso'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE matriculas ADD COLUMN token_acceso VARCHAR(32) NULL UNIQUE AFTER estado',
    'SELECT ''token_acceso ya existe, omitiendo'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
