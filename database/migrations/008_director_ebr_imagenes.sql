-- ============================================================
-- Migración 008 — Imágenes de firma y sello del Director EBR
-- firma_path: ruta relativa a public/ de la firma manuscrita (solo impresión)
-- sello_path: ruta relativa a public/ del sello institucional (digital + impresión)
-- ============================================================

ALTER TABLE director_ebr_historial
    ADD COLUMN firma_path VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Ruta relativa a public/ de la firma PNG (para reportes impresos)'
        AFTER asignado_en,
    ADD COLUMN sello_path VARCHAR(255) NULL DEFAULT NULL
        COMMENT 'Ruta relativa a public/ del sello PNG (para vistas digitales)'
        AFTER firma_path;
