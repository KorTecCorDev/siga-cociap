-- ============================================================
-- SIGA-COCIAP — Seed 002: Completar sistema
-- Corrige datos faltantes en la BD:
--   1. Usuario padre de prueba (DNI 99999999 / admin1234)
--   2. Competencias completas para primaria y secundaria
--
-- Es idempotente: usa NOT EXISTS para no duplicar registros.
-- Ejecutar DESPUÉS de: siga_cociap.sql + 002 + 003 + 001_datos_prueba.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ════════════════════════════════════════════════════════════
-- 1. USUARIO PADRE DE PRUEBA
--    La persona (DNI 99999999) y el apoderado ya existen en
--    001_datos_prueba.sql pero nunca se creó su cuenta de usuario.
-- ════════════════════════════════════════════════════════════
INSERT INTO usuarios (persona_id, rol_id, password_hash, estado)
SELECT
    p.id,
    r.id,
    '$2y$10$NWvhpNy1mHlJXDH/ofWadeU2LDGBypIQSfbywvmnTVPJc1RU1PXeG',
    'activo'
FROM personas p
CROSS JOIN roles r
WHERE p.dni    = '99999999'
  AND r.codigo = 'padre'
  AND NOT EXISTS (
      SELECT 1 FROM usuarios u WHERE u.persona_id = p.id
  );


SET FOREIGN_KEY_CHECKS = 1;
