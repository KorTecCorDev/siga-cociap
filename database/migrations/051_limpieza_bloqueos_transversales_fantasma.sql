-- ════════════════════════════════════════════════════════════════════
-- Migración 051: limpieza de los BLOQUEOS TRANSVERSALES FANTASMA que el
--                cierre forzado creó en el II Bimestre
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO. El panel `/admin/control` afirma en B2:
--     "Competencias que el cierre bloqueó automáticamente porque el docente no
--      las había bloqueado al aprobar el bimestre. 130 competencia(s) en 65
--      carga(s) de 23 docente(s); 130 sin ningún criterio registrado."
--
--   El mensaje es FALSO y acusa a 23 docentes de un olvido inexistente. Las 130
--   se explican al 100% sin ningún olvido, y así están clasificadas (medido):
--     · A) 46 bloqueos en 23 cargas de TUTORÍA (TOE) — el formulario del docente
--          NO adjunta transversales a la carga de tutoría (decisión 07/07/2026).
--     · B) 84 bloqueos en 42 cargas NO DUEÑAS de secciones unidocentes — las
--          TIC/GAMA se adjuntan UNA sola vez por área, en la carga dueña.
--     · C) OLVIDOS REALES: **CERO**.
--   23 + 42 = 65 cargas × 2 competencias = 130. Cuadra exacto.
--
-- CAUSA RAÍZ (ya corregida en código, 06/08/2026).
--   `AnioAcademicoModel::bloquearCompetenciasPendientes` (bloque 2) recorría
--   TODAS las cargas activas sin las dos exclusiones que el formulario del
--   docente sí aplica. Misma regla, dos implementaciones divergentes. El fix
--   añade ambas exclusiones al SELECT del cierre.
--
-- 🔴 EL ORDEN NO ES NEGOCIABLE — y choca con el cierre de B2:
--        FIX DE CÓDIGO EN PRODUCCIÓN  →  ESTA MIGRACIÓN  →  CERRAR B2
--   Si se aplica esta limpieza con el código viejo todavía en producción, el
--   siguiente cierre forzado vuelve a crear los 130. Y si B2 se cierra antes de
--   que el fix esté desplegado, se crean fantasmas nuevos sobre los que falten.
--
-- ✅ APLICADA EN PRODUCCIÓN EL 06/08/2026, con toda la cadena de evidencia
--   capturada ALLÍ (huella: u761410128_siga_cociap · Linux · MariaDB 11.8.8):
--     PASO 1.b → A_TOE 46/23 cargas · B_NO_DUENA 84/42 cargas · C_OLVIDO_REAL
--       SIN NINGUNA FILA (o sea, cero olvidos reales, igual que en local).
--     PASO 1.c → 0 notas y 0 criterios colgando.  PASO 1.d → 690 y 23.
--     PASO 2   → "130 filas eliminadas", COMMIT.
--     PASO 3   → 0/0/0 · 690 y 23 intactos · 0 notas sin bloqueo · B1 en 774.
--   NO aplicada en local a propósito (local se queda con los 130 para poder
--   volver a probar el escenario).
--
-- ⚠️ EL `SELECT ROW_COUNT()` DEL PASO 2 DEVUELVE 0 EN phpMyAdmin, y NO significa
--   que el DELETE fallara. phpMyAdmin ejecuta las sentencias por separado, así
--   que cuando corre ese SELECT el contador ya no refleja al DELETE anterior.
--   **La cifra buena es la que el propio DELETE reporta** ("130 filas
--   eliminadas"), y quien manda de verdad es el PASO 3 en conexión nueva.
--   Pasó tal cual al aplicarla. Vale para cualquier migración futura que copie
--   este patrón: no fiarse de ROW_COUNT() como constancia en phpMyAdmin.
--
-- 🔎 UNA HIPÓTESIS QUE LOS HECHOS DESMINTIERON, anotada para no repetirla:
--   antes de aplicarla se advirtió aquí que en producción podía no haber nada
--   que borrar, razonando que B2 seguía ABIERTO y que los fantasmas los crea el
--   cierre forzado. **FALSO: los 130 estaban, exactamente los mismos que en
--   local.** O sea que en prod el cierre forzado de B2 SÍ llegó a correr en
--   algún momento y el bimestre se reabrió después. Lección: el estado ACTUAL
--   de un periodo no dice nada sobre los procesos que ya corrieron sobre él;
--   eso solo lo responde consultar los datos.
--
-- QUÉ HACE. Borra ÚNICAMENTE filas de `bloqueos_competencia` que cumplan las
--   CUATRO condiciones a la vez: periodo = II Bimestre, `origen = 'cierre'`,
--   competencia de un área `tipo='transversal'`, y carga TOE o no-dueña. No
--   toca ninguna otra tabla.
--
-- POR QUÉ ES SEGURO BORRARLOS. Un bloqueo transversal sin notas ni criterios no
--   aporta nada a ningún cálculo: `getPromediosSeccion` une bloqueos con
--   `calificaciones`, así que una carga sin notas no produce filas y el promedio
--   agregado de la boleta NO cambia. El gate del tutor (`estadoCargasSeccion`)
--   ya aplica por su cuenta la lógica de dueña, así que tampoco se mueve. Aun
--   así, que no haya notas ni criterios NO SE ASUME: se comprueba en el PASO 1.
--
-- ⚠️ B1 NO SE TOCA (decisión del usuario, 06/08/2026). Sus 774 transversales
--   forzadas son mayoritariamente del modelo viejo (carga única del tutor), pero
--   conviene saber que 84 de ellas SÍ son este mismo defecto de no-dueña. B1
--   está cerrado y publicado; su aviso de incidencias queda como registro
--   histórico. Por eso el PASO 2 se ancla al II Bimestre y solo a él.
--
-- ✅ ENSAYABLE Y REVERSIBLE: no hay DDL, así que corre entera dentro de
--   `START TRANSACTION` … `ROLLBACK` para ensayarla (hacerlo EN LA PROPIA
--   PRODUCCIÓN antes del envío definitivo, que es el procedimiento que funcionó
--   con la 050). El PASO 4 la deshace.
--
-- Idempotente: una segunda corrida borra 0 filas (ya no queda ninguna que
--   cumpla las cuatro condiciones).
--
-- ⚠️ ANCLAJES: el periodo se resuelve por `numero = 2` + año activo, NUNCA por
--   `id = 2` (los ids difieren entre entornos). Las áreas transversales se
--   resuelven por `tipo = 'transversal'`, nunca por id (9 primaria / 21
--   secundaria en este entorno).
-- ════════════════════════════════════════════════════════════════════


-- ════════════════════════════════════════════════════════════════════
-- PASO 1 — VERIFICACIÓN PREVIA (SOLO LECTURA). Correr y LEER antes de escribir.
-- ════════════════════════════════════════════════════════════════════
-- ⚠️ ESTE PASO NO PROTEGE AL PASO 2: son sentencias sueltas y pegar el archivo
--   entero ejecuta el DELETE igual. Correr este paso SOLO, LEER el resultado, y
--   recién entonces el PASO 2.

-- 1.0 ¿EN QUÉ ENTORNO ESTOY? (lección de la 048). Ningún conteo de DATOS
--     distingue local de prod — local es copia fiel—, así que la huella tiene
--     que ser del SERVIDOR. Correr esto ANTES que nada, en cada entorno.
--       · LOCAL (XAMPP):    bd 'siga_cociap', root@localhost, so 'Win64'.
--       · PROD (Hostinger): otra bd, otro usuario, so Linux.
--     Si la fila dice Win64, estás en tu máquina.
SELECT DATABASE() AS bd, USER() AS usuario_conexion, @@hostname AS hostname,
       @@version AS version, @@version_compile_os AS so, @@datadir AS datadir;

SET @periodo := (SELECT id FROM periodos
                  WHERE numero = 2
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

-- 1.a El anclaje resolvió. NO puede ser NULL. LEER el nombre y el estado.
SELECT @periodo AS periodo_id, p.nombre_display, p.estado AS estado_periodo,
       CASE WHEN @periodo IS NULL THEN '*** ABORTAR: periodo no resuelto ***'
            ELSE 'ANCLAJE OK' END AS veredicto
FROM periodos p WHERE p.id = @periodo;

-- 1.b CLASIFICACIÓN A/B/C de los bloqueos transversales forzados del bimestre.
--     Esperado: A_TOE = 46 (23 cargas) · B_NO_DUENA = 84 (42 cargas) · y la
--     clase C_OLVIDO_REAL SIN NINGUNA FILA.
--     🔴 SI APARECE UNA SOLA FILA EN C_OLVIDO_REAL, ABORTAR: ahí sí hay un
--     docente que no bloqueó su carga y borrar ese bloqueo destruiría trabajo
--     legítimo. Esta migración solo puede borrar A y B.
SELECT
  CASE
    WHEN ar.tipo = 'tutoria' THEN 'A_TOE'
    WHEN s.es_unidocente = 1 AND ca.id <> (
         SELECT cad.id FROM cargas_academicas cad
         LEFT JOIN subareas sad ON sad.id = cad.subarea_id
         WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
           AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
         ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1)
      THEN 'B_NO_DUENA'
    ELSE 'C_OLVIDO_REAL'
  END AS clase,
  COUNT(*)                       AS n_bloqueos,
  COUNT(DISTINCT bc.carga_id)    AS n_cargas,
  COUNT(DISTINCT ca.docente_id)  AS n_docentes
FROM bloqueos_competencia bc
INNER JOIN competencias c ON c.id = bc.competencia_id
INNER JOIN areas a  ON a.id = c.area_id AND a.tipo = 'transversal'
INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
INNER JOIN secciones s ON s.id = ca.seccion_id
LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
WHERE bc.periodo_id = @periodo
  AND bc.origen     = 'cierre'
GROUP BY clase;

-- 1.c 🔴 GUARD INDISPENSABLE: ninguna de esas cargas puede tener NOTAS ni
--     CRITERIOS transversales colgando. Si los tuviera, borrar el bloqueo
--     dejaría notas sin bloqueo —el "estado fantasma" que el proyecto ya
--     persiguió— y además movería el promedio agregado de la boleta.
--     LAS DOS CIFRAS DEBEN SER 0. Si alguna no lo es, ABORTAR.
SELECT
  (SELECT COUNT(*)
     FROM calificaciones cal
     INNER JOIN competencias c ON c.id = cal.competencia_id
     INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
     INNER JOIN cargas_academicas ca ON ca.id = cal.carga_id
     INNER JOIN secciones s ON s.id = ca.seccion_id
     LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
     LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
    WHERE cal.periodo_id = @periodo
      AND (ar.tipo = 'tutoria' OR (s.es_unidocente = 1 AND ca.id <> (
            SELECT cad.id FROM cargas_academicas cad
            LEFT JOIN subareas sad ON sad.id = cad.subarea_id
            WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
              AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
            ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1)))
  ) AS notas_colgando_DEBE_SER_0,
  (SELECT COUNT(*)
     FROM criterios cr
     INNER JOIN competencias c ON c.id = cr.competencia_id
     INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
     INNER JOIN cargas_academicas ca ON ca.id = cr.carga_id
     INNER JOIN secciones s ON s.id = ca.seccion_id
     LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
     LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
    WHERE cr.periodo_id = @periodo
      AND cr.eliminado_en IS NULL
      AND (ar.tipo = 'tutoria' OR (s.es_unidocente = 1 AND ca.id <> (
            SELECT cad.id FROM cargas_academicas cad
            LEFT JOIN subareas sad ON sad.id = cad.subarea_id
            WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
              AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
            ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1)))
  ) AS criterios_colgando_DEBE_SER_0;

-- 1.d CONSTANCIA de lo que NO se debe mover. Anotar estas cifras: el PASO 3 las
--     vuelve a pedir y deben salir IDÉNTICAS.
--       · bloqueos transversales de origen 'docente' (esperado 690) — intactos;
--       · cierres transversales vigentes del bimestre (esperado 23) — intactos.
SELECT
  (SELECT COUNT(*) FROM bloqueos_competencia bc
     INNER JOIN competencias c ON c.id = bc.competencia_id
     INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
    WHERE bc.periodo_id = @periodo AND bc.origen = 'docente') AS transv_docente_NO_SE_TOCAN,
  (SELECT COUNT(*) FROM cierres_transversales
    WHERE periodo_id = @periodo AND anulado_en IS NULL)       AS cierres_vigentes_NO_SE_TOCAN;


-- ════════════════════════════════════════════════════════════════════
-- PASO 2 — ESCRITURA. Ejecutar SOLO si el PASO 1 dio:
--            · ANCLAJE OK,
--            · NINGUNA fila en C_OLVIDO_REAL,
--            · notas_colgando = 0 y criterios_colgando = 0.
-- ════════════════════════════════════════════════════════════════════
-- Va envuelto en transacción SIEMPRE, también en prod: prod corre MariaDB 11.8 y
-- local 10.4, así que un ensayo en local prueba la LÓGICA, no el plan del
-- optimizador (lección de la 050).
--
-- PARA ENSAYAR: cambiar el COMMIT final por ROLLBACK, correr el bloque entero y
-- comprobar que `borradas` = 130. Después, el envío definitivo idéntico con
-- COMMIT. La verificación del PASO 3 se hace EN CONEXIÓN NUEVA: es lo único que
-- prueba que el COMMIT persistió.

SET @periodo := (SELECT id FROM periodos
                  WHERE numero = 2
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

START TRANSACTION;

DELETE bc
FROM bloqueos_competencia bc
INNER JOIN competencias c ON c.id = bc.competencia_id
INNER JOIN areas a  ON a.id = c.area_id AND a.tipo = 'transversal'
INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
INNER JOIN secciones s ON s.id = ca.seccion_id
LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
WHERE bc.periodo_id = @periodo
  AND bc.origen     = 'cierre'
  AND (
        -- (A) carga de TUTORÍA: nunca recibe transversales del formulario
        ar.tipo = 'tutoria'
        -- (B) carga NO DUEÑA de una sección unidocente
        OR (s.es_unidocente = 1 AND ca.id <> (
              SELECT cad.id FROM cargas_academicas cad
              LEFT JOIN subareas sad ON sad.id = cad.subarea_id
              WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
                AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
              ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1))
      );

SELECT ROW_COUNT() AS borradas_esperado_130;

COMMIT;


-- ════════════════════════════════════════════════════════════════════
-- PASO 3 — VERIFICACIÓN POSTERIOR (SOLO LECTURA). En CONEXIÓN NUEVA.
-- ════════════════════════════════════════════════════════════════════
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = 2
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

-- 3.a El aviso de /admin/control para el bimestre queda en CERO. Esta consulta
--     es la de `ControlOperativoModel::incidenciasCierre`, reducida a su resumen.
--     Las tres cifras deben ser 0.
SELECT COUNT(*)                      AS competencias_forzadas_DEBE_SER_0,
       COUNT(DISTINCT bc.carga_id)   AS cargas_DEBE_SER_0,
       COUNT(DISTINCT ca.docente_id) AS docentes_DEBE_SER_0
FROM bloqueos_competencia bc
INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
WHERE bc.origen     = 'cierre'
  AND bc.periodo_id = @periodo;

-- 3.b Lo que NO se debía mover sigue intacto: mismas cifras que el PASO 1.d.
SELECT
  (SELECT COUNT(*) FROM bloqueos_competencia bc
     INNER JOIN competencias c ON c.id = bc.competencia_id
     INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
    WHERE bc.periodo_id = @periodo AND bc.origen = 'docente') AS transv_docente_esperado_690,
  (SELECT COUNT(*) FROM cierres_transversales
    WHERE periodo_id = @periodo AND anulado_en IS NULL)       AS cierres_vigentes_esperado_23;

-- 3.c NINGUNA nota transversal quedó sin su bloqueo (el estado fantasma que se
--     quería evitar). DEBE SER 0.
SELECT COUNT(*) AS notas_transversales_sin_bloqueo_DEBE_SER_0
FROM calificaciones cal
INNER JOIN competencias c ON c.id = cal.competencia_id
INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
LEFT JOIN bloqueos_competencia bc
       ON  bc.carga_id       = cal.carga_id
       AND bc.competencia_id = cal.competencia_id
       AND bc.periodo_id     = cal.periodo_id
WHERE cal.periodo_id = @periodo
  AND bc.id IS NULL;

-- 3.d B1 NO se tocó: sus transversales forzadas siguen ahí (esperado 774).
SELECT COUNT(*) AS b1_forzadas_esperado_774
FROM bloqueos_competencia bc
INNER JOIN competencias c ON c.id = bc.competencia_id
INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
WHERE bc.origen = 'cierre'
  AND bc.periodo_id = (SELECT id FROM periodos
                        WHERE numero = 1
                          AND anio_id = (SELECT id FROM anios_academicos
                                          WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));


-- ════════════════════════════════════════════════════════════════════
-- PASO 4 — DESHACER (solo si hiciera falta; NO ejecutar en el flujo normal)
-- ════════════════════════════════════════════════════════════════════
-- Recrea exactamente las filas borradas: el mismo SELECT del cierre forzado
-- ANTERIOR al fix, acotado a las clases A y B. Solo tiene sentido si se
-- descubriera que el panel necesitaba esas filas para algo — no las necesita.
-- ⚠️ Con el fix desplegado, un cierre normal NO las volverá a crear: esta es la
--    única forma de recuperarlas.
--
-- SET @periodo := (SELECT id FROM periodos
--                   WHERE numero = 2
--                     AND anio_id = (SELECT id FROM anios_academicos
--                                     WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));
-- SET @usuario := (SELECT u.id FROM usuarios u JOIN roles r ON r.id = u.rol_id
--                   WHERE r.nombre LIKE 'Registro Acad%' AND u.estado = 'activo'
--                   ORDER BY u.id LIMIT 1);
--
-- INSERT IGNORE INTO bloqueos_competencia
--     (carga_id, competencia_id, periodo_id, bloqueado_por, origen)
-- SELECT ca.id, comp.id, @periodo, @usuario, 'cierre'
-- FROM cargas_academicas ca
-- INNER JOIN secciones s ON s.id = ca.seccion_id
-- INNER JOIN grados    g ON g.id = s.grado_id
-- INNER JOIN areas     a ON a.tipo = 'transversal' AND a.nivel_id = g.nivel_id
-- INNER JOIN competencias comp ON comp.area_id = a.id
-- LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
-- LEFT  JOIN areas    ar ON ar.id = COALESCE(ca.area_id, sa.area_id)
-- WHERE ca.estado  = 'activa'
--   AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
--   AND (ar.tipo = 'tutoria' OR (s.es_unidocente = 1 AND ca.id <> (
--         SELECT cad.id FROM cargas_academicas cad
--         LEFT JOIN subareas sad ON sad.id = cad.subarea_id
--         WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
--           AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
--         ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1)));
