-- ════════════════════════════════════════════════════════════════════
-- Migración 048: retirar los respaldos de conducta de la matrícula 541
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO. El 22/07/2026 se marcó como `retirado` a la matrícula 541 (DNI
--   63361405, 3.º A primaria, sección 18) y el 24/07 se hizo en PRODUCCIÓN una
--   limpieza quirúrgica de su conducta del II Bimestre: se eliminaron 10 filas
--   de `conducta_respuestas` (no tenía fila en `calificaciones_conducta` DEL II
--   BIMESTRE; por eso `_bkp_calif_conducta_541` quedó en 0 filas).
--   ⚠️ No confundir: la 541 SÍ tiene una fila viva en `calificaciones_conducta`,
--   pero es del I BIMESTRE (literal AD, registrada el 23/05) y esta migración no
--   la toca. Verificado el 06/08/2026.
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
--   y MySQL hace commit implícito. EXPORTAR ANTES no es opcional: es lo único que
--   convierte esto en reversible fuera de la BD, y el expediente de esta matrícula
--   todavía se mueve (fue `retirado` hasta el 04/08 y hoy es `trasladado`).
--       · EN PROD (hosting compartido, normalmente SIN shell ni `mysqldump`):
--         phpMyAdmin → marcar las 2 tablas → Exportar → SQL con estructura Y datos,
--         y guardar el archivo FUERA del servidor.
--       · Si hay shell disponible:
--         mysqldump -u USUARIO -p BASE _bkp_conducta_resp_541 _bkp_calif_conducta_541 > bkp_541.sql
--   ⚠️ El respaldo ya viene degradado: las tablas se crearon con
--   `CREATE TABLE ... SELECT`, así que perdieron PRIMARY KEY, AUTO_INCREMENT e
--   índices. Restaurar exigiría un `INSERT ... SELECT` explícito, no un rename.
--
-- Idempotente (`IF EXISTS`): correrla dos veces no falla.
-- ════════════════════════════════════════════════════════════════════


-- ════════════════════════════════════════════════════════════════════
-- PASO 1 — VERIFICACIÓN PREVIA (SOLO LECTURA). Correr y LEER antes de borrar.
-- ════════════════════════════════════════════════════════════════════
-- ⚠️ ESTE PASO NO PROTEGE AL PASO 2. Son sentencias sueltas en el mismo archivo:
--   si se pega el archivo ENTERO de una vez, el DROP se ejecuta igual aunque el
--   veredicto diga NO_BORRAR. Correr ESTE paso solo, LEER el resultado, y recién
--   entonces pegar el PASO 2. No hay forma de automatizar el aborto: `DROP TABLE`
--   es DDL y haría commit implícito de todos modos.
--
-- DEBE DEVOLVER EXACTAMENTE 1 FILA, con DNI 63361405 y veredicto
-- `PUEDE_BORRARSE`. Cualquier otro resultado ⇒ DETENERSE:
--   · `NO_BORRAR` → la conducta de esa sección no está cerrada en sus dos etapas
--                   (o el cierre está anulado) y el respaldo todavía cumple su
--                   función.
--   · 0 filas     → en ESTE entorno el id 541 no es esa estudiante. Los ids
--                   difieren entre entornos, así que el respaldo que se iba a
--                   tirar no es el que la consulta juzgó. NO borrar.
--
-- Por eso la consulta exige el DNI ADEMÁS del id: el ancla real es el DNI y el id
-- solo acota. Sin esa exigencia, un id que apunta a otro estudiante devolvía un
-- veredicto perfectamente válido... sobre la sección EQUIVOCADA.
--
-- Valores esperados en PROD (medidos el 05/08/2026 sobre la copia local):
--   RODRIGUEZ MENDEZ, GUSTAVO CHRISTIAN · sección 18 · cierre id 33 ·
--   ra_bloqueado_en 2026-07-24 16:14 · tutor_cerrado_en 2026-07-31 12:32.
SELECT
    CASE
        WHEN cc.id IS NOT NULL
             AND cc.ra_bloqueado_en  IS NOT NULL
             AND cc.tutor_cerrado_en IS NOT NULL
             AND cc.anulado_en       IS NULL
        THEN 'PUEDE_BORRARSE'
        ELSE 'NO_BORRAR'
    END                    AS veredicto,
    m.id                   AS matricula_id,
    pe.dni,
    CONCAT(pe.apellido_paterno, ' ', pe.apellido_materno, ', ', pe.nombres) AS estudiante,
    m.seccion_id,
    m.tipo,
    cc.id                  AS cierre_id,
    cc.ra_bloqueado_en,
    cc.tutor_cerrado_en,
    cc.anulado_en
FROM matriculas m
JOIN estudiantes e  ON e.id  = m.estudiante_id
JOIN personas   pe  ON pe.id = e.persona_id
LEFT JOIN periodos p
       ON p.numero  = 2
      -- ORDER BY explícito: sin él, `LIMIT 1` es no determinista si alguna vez
      -- hubiera dos años en 'activo' y el veredicto podría juzgar el B2 del año
      -- equivocado. Hoy hay exactamente uno (2026).
      AND p.anio_id = (SELECT id FROM anios_academicos
                        WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1)
LEFT JOIN cierres_conducta cc
       ON cc.seccion_id = m.seccion_id
      AND cc.periodo_id = p.id
      AND cc.anulado_en IS NULL
WHERE m.id   = 541
  AND pe.dni = '63361405';

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
-- El guion bajo va ESCAPADO (`\_`): en LIKE, `_` es comodín de un carácter, así
-- que `'_bkp%'` también matchearía tablas tipo `Xbkp…` y daría un falso "no se
-- aplicó". Con el escape, el patrón es literal.
SHOW TABLES LIKE '\_bkp%';
