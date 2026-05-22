-- ============================================================
-- Migración 006 — Soft-delete en criterios
-- Agrega columnas de auditoría para registrar quién y cuándo
-- eliminó un criterio, sin borrar el registro de la BD.
-- Ejecutar sobre cualquier backup >= backup_13_05_2026.sql
-- ============================================================

ALTER TABLE criterios
    ADD COLUMN eliminado_en  DATETIME     NULL DEFAULT NULL AFTER updated_at,
    ADD COLUMN eliminado_por INT UNSIGNED NULL DEFAULT NULL AFTER eliminado_en,
    ADD CONSTRAINT fk_criterios_eliminado_por
        FOREIGN KEY (eliminado_por) REFERENCES usuarios(id);
