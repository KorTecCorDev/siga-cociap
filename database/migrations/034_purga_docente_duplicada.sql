-- ════════════════════════════════════════════════════════════════════
-- Migración 034: Purga de docente duplicada (DNI imaginario 20000777)
-- ════════════════════════════════════════════════════════════════════
-- SÍNTOMA: la docente BEATRIZ MILAGROS GUERRERO MILLA quedó cargada dos
--   veces por error, con dos DNI imaginarios distintos:
--     * DNI 20000203 → persona 1318 → usuario 37  (tiene 2 cargas académicas)
--     * DNI 20000777 → persona 1320 → usuario 39  (SIN cargas, nunca accedió)
--
-- DECISIÓN: se conserva el registro con cargas (DNI 20000203) y se elimina
--   por completo el duplicado sin cargas (DNI 20000777), tanto su fila en
--   `usuarios` como su fila en `personas`.
--
-- VERIFICACIÓN PREVIA (local, 07/07/2026): el usuario 39 NO está referenciado
--   en ninguna de las ~40 columnas FK que apuntan a `usuarios.id`, y la
--   persona 1320 no figura en `estudiantes` ni `apoderados`. Borrado limpio.
--
-- SEGURIDAD (sin IDs hardcodeados): la migración se ancla al DNI 20000777.
--   El DELETE del usuario está GUARDADO: solo procede si ese usuario no tiene
--   NINGUNA carga académica. El DELETE de la persona solo procede si ya no
--   queda usuario asociado y no está vinculada a estudiante ni apoderado.
--   Si en producción el estado fuese distinto, la migración es un NO-OP
--   seguro (no borra nada) en lugar de romper integridad.
-- IDEMPOTENTE: tras correr, el DNI 20000777 ya no existe; una segunda corrida
--   no encuentra nada que borrar.
-- Ejecutar DESPUÉS de 033_purga_competencias_fantasma.sql.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto primero en prod: ──
--   SELECT p.id AS persona_id, p.dni,
--          CONCAT_WS(' ', p.apellido_paterno, p.apellido_materno, p.nombres) AS docente,
--          u.id AS usuario_id, u.estado,
--          (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.docente_id = u.id) AS cargas
--   FROM personas p
--   LEFT JOIN usuarios u ON u.persona_id = p.id
--   WHERE p.dni = '20000777';
--   -- Debe mostrar 1 fila con cargas = 0. Si cargas > 0, DETENERSE y avisar.

-- 1. Borrar el usuario duplicado — SOLO si no tiene cargas académicas.
DELETE u FROM usuarios u
INNER JOIN personas p ON p.id = u.persona_id
WHERE p.dni = '20000777'
  AND NOT EXISTS (
        SELECT 1 FROM cargas_academicas ca WHERE ca.docente_id = u.id
      );

-- 2. Borrar la persona duplicada — SOLO si ya no queda usuario asociado
--    (el guard del paso 1 encadena la seguridad) ni vínculo académico.
DELETE p FROM personas p
WHERE p.dni = '20000777'
  AND NOT EXISTS (SELECT 1 FROM usuarios    u WHERE u.persona_id = p.id)
  AND NOT EXISTS (SELECT 1 FROM estudiantes e WHERE e.persona_id = p.id)
  AND NOT EXISTS (SELECT 1 FROM apoderados  a WHERE a.persona_id = p.id);

-- Verificación (debe devolver 0):
--   SELECT COUNT(*) FROM personas WHERE dni = '20000777';
