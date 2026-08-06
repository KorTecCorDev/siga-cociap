-- ════════════════════════════════════════════════════════════════════
-- Migración 048: retirar los respaldos de conducta de la matrícula 541
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO. El 22/07/2026 se marcó como `retirado` a la matrícula 541 (DNI
--   63361405, 3.º A primaria, sección 18) y el 24/07 se hizo en PRODUCCIÓN una
--   limpieza quirúrgica de su conducta del II Bimestre: se eliminaron 10 filas
--   de `conducta_respuestas` (no tenía fila en `calificaciones_conducta`).
--
--   Esa limpieza dejó DOS TABLAS DE RESPALDO como red de seguridad, con el
--   acuerdo explícito de borrarlas "tras el cierre de conducta de la sección A":
--       _bkp_conducta_resp_541    (10 filas)
--       _bkp_calif_conducta_541   (0 filas)
--
--   ⚠️ La 541 dejó de ser `retirado`: su traslado se consumó el 04/08 y hoy es
--   `trasladado`. Eso NO cambia nada aquí — el respaldo era de su conducta.
--
-- POR QUÉ SE PUEDEN BORRAR YA. La condición acordada se cumplió: la conducta del
--   II Bimestre de su sección está cerrada en sus DOS etapas.
--   Verificado el 05/08/2026 (cierre id 33, sección 18): `ra_bloqueado_en`
--   2026-07-24 16:14 y `tutor_cerrado_en` 2026-07-31 12:32, sin anular. Y las 23
--   secciones del nivel tienen cierre vigente de conducta en B2.
--
-- 🔴 ESTO ES IRREVERSIBLE Y NO SE PUEDE PROBAR CON ROLLBACK: `DROP TABLE` es DDL
--   y MySQL hace commit implícito. Si se quiere conservar el dato fuera de la BD,
--   exportarlo ANTES:
--       mysqldump -u USUARIO -p BASE _bkp_conducta_resp_541 _bkp_calif_conducta_541 > bkp_541.sql
--
-- Idempotente (`IF EXISTS`): correrla dos veces no falla.
-- ════════════════════════════════════════════════════════════════════


-- ════════════════════════════════════════════════════════════════════
-- PASO 1 — VERIFICACIÓN PREVIA (SOLO LECTURA). Correr y LEER antes de borrar.
-- ════════════════════════════════════════════════════════════════════
-- Debe devolver `PUEDE_BORRARSE`. Si devuelve `NO_BORRAR`, detenerse: la
-- conducta de esa sección no está cerrada en sus dos etapas y el respaldo
-- todavía cumple su función.
SELECT
    CASE
        WHEN cc.id IS NOT NULL
             AND cc.ra_bloqueado_en  IS NOT NULL
             AND cc.tutor_cerrado_en IS NOT NULL
             AND cc.anulado_en       IS NULL
        THEN 'PUEDE_BORRARSE'
        ELSE 'NO_BORRAR'
    END                    AS veredicto,
    cc.id                  AS cierre_id,
    cc.seccion_id,
    cc.ra_bloqueado_en,
    cc.tutor_cerrado_en,
    cc.anulado_en
FROM (SELECT seccion_id FROM matriculas WHERE id = 541) m
LEFT JOIN periodos p
       ON p.numero  = 2
      AND p.anio_id = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
LEFT JOIN cierres_conducta cc
       ON cc.seccion_id = m.seccion_id
      AND cc.periodo_id = p.id
      AND cc.anulado_en IS NULL;

-- Constancia de lo que se va a perder (para dejarlo en el log de la sesión).
-- Si alguna tabla ya no existe, esta consulta falla: es esperado, significa que
-- el PASO 2 ya se aplicó.
SELECT '_bkp_conducta_resp_541' AS tabla, COUNT(*) AS filas FROM _bkp_conducta_resp_541
UNION ALL
SELECT '_bkp_calif_conducta_541', COUNT(*) FROM _bkp_calif_conducta_541;


-- ════════════════════════════════════════════════════════════════════
-- PASO 2 — BORRADO. Ejecutar SOLO si el PASO 1 devolvió `PUEDE_BORRARSE`.
-- ════════════════════════════════════════════════════════════════════
DROP TABLE IF EXISTS _bkp_conducta_resp_541;
DROP TABLE IF EXISTS _bkp_calif_conducta_541;


-- ════════════════════════════════════════════════════════════════════
-- PASO 3 — VERIFICACIÓN POSTERIOR (SOLO LECTURA)
-- ════════════════════════════════════════════════════════════════════
-- Debe devolver 0 filas. Si devuelve alguna, el DROP no se aplicó.
SHOW TABLES LIKE '_bkp%';
