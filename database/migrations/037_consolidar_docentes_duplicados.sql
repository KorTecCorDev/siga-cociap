-- ════════════════════════════════════════════════════════════════════
-- Migración 037: Consolidar 3 docentes duplicados (persona doble)
-- ════════════════════════════════════════════════════════════════════
-- SÍNTOMA: tres docentes fueron cargados dos veces en `personas` (misma
--   persona humana, dos filas):
--     * una ficha DOCENTE con DNI imaginario (2000000x) que tiene el `usuario`
--       y sus cargas académicas;
--     * una ficha APODERADO con el DNI REAL, apoderada de su hijo/a (con
--       vinculo_familiar y matrícula).
--   El DNI real ya está ocupado por la ficha apoderado, así que no se puede
--   simplemente cambiar el DNI de la ficha docente (personas.dni es UNIQUE).
--
-- SOLUCIÓN (misma que se validó en LOCAL, aquí anclada por DNI, sin IDs):
--   por cada par (DNI falso del docente -> DNI real del apoderado):
--     1. Reconciliar: rellenar la ficha REAL con los datos que aporte la FALSA
--        (COALESCE — no pisa lo que la real ya tiene; no se pierde nada).
--     2. Repuntar el `usuario` docente de la ficha FALSA a la ficha REAL
--        (las cargas cuelgan de usuarios.id → NO se tocan; el docente pasa a
--        loguear con su DNI real).
--     3. Borrar la ficha FALSA, ya huérfana.
--   Resultado: una sola persona con DNI real, que es a la vez docente
--   (conserva cargas) y apoderado.
--
-- Los 3 pares (docente ↔ apoderado, por nombre idéntico):
--   LOLI LOLI GERSON ADEMIR        20000113 -> 40039884
--   HUAYANEY GRANADOS KATTY JANETH 20000002 -> 70137131
--   MONTES DEPAZ HILBER CARLOS     20000008 -> 42446867
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * Anclado por DNI con LITERALES (no IDs, que difieren entre local y prod;
--     no tabla temporal → evita choques de collation columna-vs-columna).
--   * El repunte solo ocurre si la ficha real NO tiene ya un usuario (evita
--     colisión con el UNIQUE de usuarios.persona_id).
--   * El borrado solo ocurre si la ficha falsa quedó sin usuario, sin
--     estudiante y sin apoderado (no borra nada con referencias vivas).
--   * En una BD ya consolidada (p. ej. LOCAL) las fichas falsas no existen →
--     los tres pasos son NO-OP. Reejecutar en prod tras la 1.ª corrida: NO-OP.
-- Ejecutar DESPUÉS de 036_competencia_etica_valores.sql. Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; correr ANTES en prod, un par por línea): ──
--   SELECT '20000113' AS fake, '40039884' AS real_dni,
--          f.id AS persona_falsa, uf.id AS usuario, r.id AS persona_real,
--          (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.docente_id = uf.id) AS cargas,
--          (SELECT COUNT(*) FROM usuarios ur WHERE ur.persona_id = r.id) AS real_ya_tiene_usuario
--   FROM (SELECT 1) x
--   LEFT JOIN personas f  ON f.dni = '20000113'
--   LEFT JOIN personas r  ON r.dni = '40039884'
--   LEFT JOIN usuarios uf ON uf.persona_id = f.id;
--   -- (repetir cambiando los dos DNI por cada par). Esperado en prod: persona_falsa,
--   -- usuario y persona_real NO nulos, cargas>0, real_ya_tiene_usuario=0.

-- ─────────────────────────────────────────────────────────────────────
-- LOLI LOLI GERSON ADEMIR   20000113 (falso) -> 40039884 (real)
-- ─────────────────────────────────────────────────────────────────────
UPDATE personas r
JOIN personas f ON f.dni = '20000113'
SET r.fecha_nacimiento = COALESCE(r.fecha_nacimiento, f.fecha_nacimiento),
    r.sexo             = COALESCE(r.sexo,             f.sexo),
    r.telefono         = COALESCE(r.telefono,         f.telefono),
    r.correo           = COALESCE(r.correo,           f.correo),
    r.direccion        = COALESCE(r.direccion,        f.direccion)
WHERE r.dni = '40039884';

UPDATE usuarios u
JOIN personas f ON f.id = u.persona_id AND f.dni = '20000113'
JOIN personas r ON r.dni = '40039884'
SET u.persona_id = r.id
WHERE NOT EXISTS (SELECT 1 FROM usuarios u2 WHERE u2.persona_id = r.id);

DELETE f FROM personas f
WHERE f.dni = '20000113'
  AND NOT EXISTS (SELECT 1 FROM usuarios    u WHERE u.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM estudiantes e WHERE e.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM apoderados  a WHERE a.persona_id = f.id);

-- ─────────────────────────────────────────────────────────────────────
-- HUAYANEY GRANADOS KATTY JANETH   20000002 (falso) -> 70137131 (real)
-- ─────────────────────────────────────────────────────────────────────
UPDATE personas r
JOIN personas f ON f.dni = '20000002'
SET r.fecha_nacimiento = COALESCE(r.fecha_nacimiento, f.fecha_nacimiento),
    r.sexo             = COALESCE(r.sexo,             f.sexo),
    r.telefono         = COALESCE(r.telefono,         f.telefono),
    r.correo           = COALESCE(r.correo,           f.correo),
    r.direccion        = COALESCE(r.direccion,        f.direccion)
WHERE r.dni = '70137131';

UPDATE usuarios u
JOIN personas f ON f.id = u.persona_id AND f.dni = '20000002'
JOIN personas r ON r.dni = '70137131'
SET u.persona_id = r.id
WHERE NOT EXISTS (SELECT 1 FROM usuarios u2 WHERE u2.persona_id = r.id);

DELETE f FROM personas f
WHERE f.dni = '20000002'
  AND NOT EXISTS (SELECT 1 FROM usuarios    u WHERE u.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM estudiantes e WHERE e.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM apoderados  a WHERE a.persona_id = f.id);

-- ─────────────────────────────────────────────────────────────────────
-- MONTES DEPAZ HILBER CARLOS   20000008 (falso) -> 42446867 (real)
-- ─────────────────────────────────────────────────────────────────────
UPDATE personas r
JOIN personas f ON f.dni = '20000008'
SET r.fecha_nacimiento = COALESCE(r.fecha_nacimiento, f.fecha_nacimiento),
    r.sexo             = COALESCE(r.sexo,             f.sexo),
    r.telefono         = COALESCE(r.telefono,         f.telefono),
    r.correo           = COALESCE(r.correo,           f.correo),
    r.direccion        = COALESCE(r.direccion,        f.direccion)
WHERE r.dni = '42446867';

UPDATE usuarios u
JOIN personas f ON f.id = u.persona_id AND f.dni = '20000008'
JOIN personas r ON r.dni = '42446867'
SET u.persona_id = r.id
WHERE NOT EXISTS (SELECT 1 FROM usuarios u2 WHERE u2.persona_id = r.id);

DELETE f FROM personas f
WHERE f.dni = '20000008'
  AND NOT EXISTS (SELECT 1 FROM usuarios    u WHERE u.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM estudiantes e WHERE e.persona_id = f.id)
  AND NOT EXISTS (SELECT 1 FROM apoderados  a WHERE a.persona_id = f.id);

-- Verificación (debe devolver 0 — ninguna ficha falsa sobrevive):
--   SELECT COUNT(*) FROM personas WHERE dni IN ('20000113','20000002','20000008');
