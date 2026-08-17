-- ════════════════════════════════════════════════════════════════════
-- Migración 052: quitar el ALIAS HUÉRFANO «(Ética y Valores)» del área
--                Educación Religiosa de SECUNDARIA
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO: en el plan de encendido de Ética y Valores (07/07/2026) el paso 3
--   pedía quitar el alias huérfano del área de Ed. Religiosa de secundaria,
--   porque la nota de esa asignatura pasó a vivir en el área de Tutoría (TOE),
--   cuyo `nombre_boleta` es «Ética y Valores» y cuyo `alias_boleta` es
--   «(Educación Religiosa)». Dejar el alias en las DOS áreas hace que el mismo
--   par de nombres aparezca cruzado en dos filas del catálogo.
--
--   `docs/ESTADO.md` daba ese paso por ejecutado el 05/08/2026 y por eso la
--   regla de mérito del mismo día dice que el alias quedó en NULL. **No es
--   cierto: nunca se ejecutó.** Verificado el 17/08/2026 en la copia local ya
--   sincronizada con producción: el área conserva `(Ética y Valores)`.
--
-- POR QUÉ IMPORTA POCO, Y AUN ASÍ SE CORRIGE: el área NO tiene cargas ni notas
--   (medido: 0 y 0), así que ese alias no llega a imprimirse en ninguna boleta.
--   El daño es de CONFIANZA en el catálogo y de divergencia entre el dato y lo
--   que la documentación afirma — que es justo lo que la pantalla
--   /admin/actas-siagie/vinculos existe para no esconder.
--
-- CAMBIO: `areas.alias_boleta = NULL` en el área de Ed. Religiosa de secundaria.
--
-- NO SE TOCA:
--   * El área de Ed. Religiosa de PRIMARIA, que se dicta con normalidad y cuyo
--     alias ya es NULL. La migración la excluye por `nivel_id`.
--   * El área de Tutoría (TOE) de secundaria, que conserva `nombre_boleta =
--     'Ética y Valores'` y `alias_boleta = '(Educación Religiosa)'`. Ese par ES
--     el vínculo visible de la asignatura y NO es huérfano.
--   * `nombre`, `nombre_boleta`, `codigo_siagie`, `tipo` ni `activa`. El área se
--     queda ACTIVA: desactivarla se probó y se descartó el 10/08/2026 (sacaba la
--     fila donde se audita el vínculo `035-EREL` de la pantalla de vínculos y
--     daba un rojo falso en verif_plan_completo_boleta.php).
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * Ancla por `nombre` + `nivel_id`, NUNCA por id: el id 14 es de esta copia y
--     no tiene por qué coincidir en otro entorno. Es la regla del repo desde el
--     endurecimiento de la 048.
--   * El matcher del nombre va en ASCII (`LIKE 'Educaci_n Religiosa'`) para no
--     depender de que el cliente mande la tilde en UTF-8. Ese fallo YA ocurrió
--     de verdad al ensayar la migración 050: el anclaje resolvía NULL y la
--     migración insertaba 0 filas sin decir por qué.
--   * GUARD DURO: solo actúa si el área NO tiene ninguna carga académica. Si
--     algún día llegara a tener cargas, su alias dejaría de ser huérfano y esta
--     migración debe ser un no-op — además de que el invariante de CLAUDE.md
--     exige que esa área siga SIN cargas (si recibiera notas, el mismo curso
--     contaría dos veces en el orden de mérito).
--   * Idempotente: exige `alias_boleta IS NOT NULL`, así que una segunda corrida
--     afecta 0 filas.
--   * Reversible con el PASO 4.
--
-- ⚠️ CORRER EL PASO 1 PRIMERO. Debe devolver EXACTAMENTE 1 fila con
--   veredicto `PUEDE_LIMPIARSE`. Si devuelve 0 filas, DETENERSE: o ya está
--   aplicada, o el anclaje no resolvió (que es distinto, y el PASO 1 lo
--   distingue con su columna `alias_actual`).
--
-- ★ VÍA RECOMENDADA — NO pegar este archivo en phpMyAdmin:
--     cd ~/domains/sigacociap.net/public_html
--     php database/aplicar_052_alias_huerfano.php              # ensayo + ROLLBACK
--     php database/aplicar_052_alias_huerfano.php --confirmar  # aplica
--   El script hace lo mismo que este .sql pero **aborta de verdad** si el
--   veredicto no es PUEDE_LIMPIARSE. Pegar este archivo entero en phpMyAdmin
--   ejecuta el PASO 2 AUNQUE el PASO 1 salga en rojo — son sentencias sueltas,
--   igual que pasó con la 048. Además el script imprime la huella del servidor
--   y verifica en conexión nueva. Probado en sus CUATRO ramas el 17/08/2026.
--
-- Ejecutar en cualquier momento; no depende de ninguna otra migración.
-- Conexión utf8mb4. Ver docs/ESTADO.md (Pendientes operativos, OP-7).
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PASO 0 — HUELLA DEL SERVIDOR ────────────────────────────────────
-- Capturar SIEMPRE esta salida junto a la del PASO 3: es lo ÚNICO que prueba
-- contra qué entorno se ejecutó. La salida del PASO 1 es idéntica en local y en
-- prod (local es copia fiel), así que por sí sola no identifica nada — es la
-- lección que costó la 048, cuando una migración se dio por aplicada en prod
-- habiendo caído en local.
--   Local: `siga_cociap` · `root@localhost` · Win64 · MariaDB 10.4.x
--   Prod:  `u761410128_siga_cociap` · Linux · MariaDB 11.8.x · Hostinger
SELECT DATABASE() AS db, USER() AS usr, @@hostname AS host,
       VERSION() AS ver, @@version_compile_os AS so, @@datadir AS datadir;

-- ⚠️ phpMyAdmin IGNORA `USE` y reselecciona la base según la página. Si esta
--   consulta no devuelve la base esperada, entrar a la base desde el panel
--   izquierdo antes de seguir, o todo fallará con «#1109 Tabla desconocida».

-- ── PASO 1 — VERIFICACIÓN (solo lectura). Debe dar 1 fila PUEDE_LIMPIARSE ──
SELECT
    a.id                                   AS area_id,
    a.nombre                               AS area,
    n.nombre                               AS nivel,
    a.nombre_boleta,
    a.alias_boleta                         AS alias_actual,
    a.activa,
    (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) AS cargas,
    (SELECT COUNT(*) FROM competencias  c  WHERE c.area_id  = a.id)     AS competencias,
    CASE
        WHEN a.alias_boleta IS NULL                                          THEN 'YA_LIMPIO'
        WHEN (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) > 0
             THEN 'NO_LIMPIAR_TIENE_CARGAS'
        ELSE 'PUEDE_LIMPIARSE'
    END                                    AS veredicto
FROM areas a
INNER JOIN niveles n ON n.id = a.nivel_id
WHERE a.nombre LIKE 'Educaci_n Religiosa'
  AND n.codigo = 'sec';

-- ── PASO 1.b — CONTROL: el área de Tutoría (TOE) que SÍ lleva el par de
--    nombres no debe verse afectada. Se lista para dejar constancia. ──
SELECT a.id, a.nombre, n.nombre AS nivel, a.nombre_boleta, a.alias_boleta,
       (SELECT COUNT(*) FROM cargas_academicas ca WHERE ca.area_id = a.id) AS cargas
FROM areas a
INNER JOIN niveles n ON n.id = a.nivel_id
WHERE a.nombre_boleta LIKE '_tica y Valores';

-- ── PASO 2 — CAMBIO ─────────────────────────────────────────────────
-- Limpia el alias huérfano. El `NOT EXISTS` es el guard duro: si el área
-- llegara a tener cargas, esta sentencia no toca nada.
--
-- ⚠️ `SELECT ROW_COUNT()` DEVUELVE 0 EN phpMyAdmin y NO significa que el UPDATE
--   fallara: ejecuta las sentencias por separado y el contador ya no refleja al
--   UPDATE. La cifra buena es la que informa el propio UPDATE («1 fila
--   afectada») y quien manda es el PASO 3. Pasó tal cual con la 051.
UPDATE areas a
INNER JOIN niveles n ON n.id = a.nivel_id
SET a.alias_boleta = NULL
WHERE a.nombre LIKE 'Educaci_n Religiosa'
  AND n.codigo = 'sec'
  AND a.alias_boleta IS NOT NULL
  AND NOT EXISTS (
        SELECT 1 FROM cargas_academicas ca WHERE ca.area_id = a.id
      );

-- ── PASO 3 — VERIFICACIÓN POSTERIOR (correr en CONEXIÓN NUEVA) ──────
-- 3.a  El área quedó con alias NULL y todo lo demás intacto:
SELECT a.id, a.nombre, n.nombre AS nivel, a.nombre_boleta,
       a.alias_boleta,                     -- debe ser NULL
       a.codigo_siagie, a.tipo, a.activa   -- deben estar SIN CAMBIOS
FROM areas a
INNER JOIN niveles n ON n.id = a.nivel_id
WHERE a.nombre LIKE 'Educaci_n Religiosa'
  AND n.codigo = 'sec';

-- 3.b  CONTROL — primaria y Tutoría (TOE) intactas. Debe devolver 2 filas:
--      Ed. Religiosa de primaria con alias NULL, y Tutoría (TOE) de secundaria
--      con nombre_boleta 'Ética y Valores' + alias '(Educación Religiosa)'.
SELECT a.id, a.nombre, n.nombre AS nivel, a.nombre_boleta, a.alias_boleta
FROM areas a
INNER JOIN niveles n ON n.id = a.nivel_id
WHERE (a.nombre LIKE 'Educaci_n Religiosa' AND n.codigo = 'prim')
   OR  a.nombre_boleta LIKE '_tica y Valores'
ORDER BY a.id;

-- 3.c  Nadie más ganó ni perdió alias. Debe devolver el MISMO número de filas
--      que antes de aplicarla, menos una:
SELECT COUNT(*) AS areas_con_alias FROM areas WHERE alias_boleta IS NOT NULL;

-- ── PASO 4 — REVERSIÓN (solo si hiciera falta) ──────────────────────
--   UPDATE areas a
--   INNER JOIN niveles n ON n.id = a.nivel_id
--   SET a.alias_boleta = '(Ética y Valores)'
--   WHERE a.nombre LIKE 'Educaci_n Religiosa'
--     AND n.codigo = 'sec'
--     AND a.alias_boleta IS NULL;
