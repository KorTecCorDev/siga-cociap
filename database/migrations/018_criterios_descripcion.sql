-- ============================================================
-- 018 — Criterios: descripción opcional
-- ============================================================
-- El nombre del criterio queda limitado a 100 caracteres en la
-- APLICACIÓN (validación en CalificacionController; la columna
-- sigue siendo VARCHAR(120) para no tocar los 143 nombres
-- existentes que superan los 100). El texto largo se traslada
-- a la nueva columna `descripcion`.
--
-- Idempotente: MariaDB 10.4 soporta ADD COLUMN IF NOT EXISTS.
-- ============================================================

ALTER TABLE criterios
    ADD COLUMN IF NOT EXISTS descripcion TEXT NULL AFTER nombre;
