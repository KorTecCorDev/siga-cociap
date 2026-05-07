-- ============================================================
-- SIGA-COCIAP — 000: Crear base de datos
-- Ejecutar PRIMERO, antes que cualquier otro script SQL.
-- Requiere usuario MySQL con privilegio CREATE DATABASE (root).
-- ============================================================

CREATE DATABASE IF NOT EXISTS siga_cociap
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE siga_cociap;
