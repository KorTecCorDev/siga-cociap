-- ════════════════════════════════════════════════════════════════════
-- Migración 050: Ética y Valores del I Bimestre como CALIFICACIÓN
--                EXTRAORDINARIA (15 = A) para secundaria
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO. En el I Bimestre nadie evaluó Ética y Valores: los tutores no
--   remitieron las calificaciones dentro del plazo. Pese a eso, el 20/07/2026
--   entre las 01:44 y las 01:45 se BLOQUEÓ la competencia en las 11 secciones
--   de secundaria (origen 'docente', 11 bloqueos) y horas después se cerró B1.
--   Resultado: el sistema tiene B1 con Ética "terminada" y CERO notas.
--
--   ⚠️ Ningún indicador del cierre podía detectarlo, y conviene saberlo:
--     · el TERMÓMETRO cuenta pares CON notas y SIN bloqueo → un par sin notas
--       nunca aparece (por eso B1 daba 0);
--     · la ALERTA de evaluación incompleta solo aflora un criterio cuando algún
--       compañero de sección ya tiene nota en él → si nadie la tiene, no hay
--       nada con qué comparar.
--
--   En el SIAGIE ya se consignó 15 (A) a mano, en las DOS competencias de
--   Educación Religiosa (C51, C52), por el vínculo de la hoja 035-EREL.
--
-- QUÉ HACE. Registra 15 (literal A) como CALIFICACIÓN EXTRAORDINARIA a los
--   estudiantes de secundaria que CURSARON el I Bimestre, replicando
--   exactamente el flujo de `RectificacionController::guardarExtraordinaria`:
--       1. criterio único "Calificación extraordinaria" por carga (11 filas)
--       2. nota de criterio = 15 por alumno
--       3. calificación final = 15 con `extraordinaria = 1`
--       4. fila de auditoría en `rectificaciones_calificacion` con MOTIVO
--
-- POR QUÉ EXTRAORDINARIA Y NO UNA NOTA NORMAL. Un 15 uniforme no es una
--   evaluación, es un acto administrativo. `OrdenMeritoModel` filtra
--   `extraordinaria = 0` en sus DOS queries, así que esta nota **NO computa en
--   el orden de mérito** — que es exactamente lo que corresponde a una nota que
--   nadie evaluó. Registrarla como normal la volvería indistinguible de una real.
--
-- EL SNAPSHOT DE B1 NO SE TOCA, por tres vías independientes:
--   1. el filtro `extraordinaria = 0` la deja fuera del promedio y de los
--      desempates, en vivo y al generar cualquier snapshot;
--   2. el snapshot oficial de B1 es INMUTABLE (candado 046: B1 estuvo publicado);
--   3. los lectores de B1 usan el snapshot (528 filas), no el cálculo en vivo.
--
-- 🔴 EFECTO VISIBLE INMEDIATO: B1 está PUBLICADO, así que en cuanto se aplique,
--   la boleta digital por token de B1 mostrará el 15 a las familias. No existe
--   forma de registrar el dato y diferir su visibilidad. La boleta que se
--   entregue en B2 (documento anual, 4 columnas) también lo mostrará.
--
-- ✅ REVERSIBLE Y ENSAYABLE, a diferencia de la 048: aquí NO hay DDL, así que
--   corre entera dentro de `START TRANSACTION` … `ROLLBACK` para ensayarla, y
--   se deshace con el DELETE acotado del PASO 4.
--
-- Idempotente: las 4 inserciones traen guarda de existencia. `calificaciones` y
--   `calificaciones_criterio` tienen UNIQUE; `criterios` NO tiene, por eso su
--   guarda es un `NOT EXISTS` explícito (mismo patrón que el código).
--
-- ⚠️ ANCLAJES: la competencia se resuelve por `codigo_minedu = 'C57'`, NUNCA por
--   id (en este entorno es 127, pero los ids difieren entre entornos; recordar
--   que el id 57 es GAMA). El área sale de la propia competencia, el periodo del
--   año activo y el usuario firmante de su ROL.
--
-- ⚠️ EL UNIVERSO REPLICA `RectificacionModel::esInsertable`, que es la guarda que
--   el flujo real evalúa alumno por alumno. Tres exclusiones, ninguna opcional:
--     · sin nota previa en la competencia (idéntica al código);
--     · EXONERADO **DEL ÁREA DE ÉTICA** y del año de su matrícula — NO cualquier
--       exoneración: el código acota por `area_id`/`subarea_id` + `anio_id`. Un
--       filtro global (el que traía esta migración) sacaba del universo, EN
--       SILENCIO, a quien estuviera exonerado de cualquier otra área;
--     · matrícula OFICIAL de un RETORNO DE GRADO ACTIVO: registrar una nota es
--       EVALUAR, y por la Regla A se evalúa en la OPERATIVA. Es el mismo anclaje
--       que usan los 9 rosters de evaluación y `alertasEvaluacionIncompleta`.
--   ✅ Medido en local: las tres variantes dan **275** — los guards NO cambian el
--   resultado ya ensayado, solo cierran huecos que en PROD sí pueden morder (hoy
--   local no tiene ni exonerados de secundaria ni retornos en ese nivel).
--
-- ⚠️ POR QUÉ EL `uq_nota` NO BASTA COMO RED: la clave única de `calificaciones`
--   es (matricula, **carga**, periodo, competencia). Si una sección tuviera DOS
--   cargas activas del área —`cargas_academicas` no tiene UNIQUE KEY—, el mismo
--   alumno recibiría DOS notas sin violar nada. Por eso el PASO 1 exige 1 carga
--   por sección, y el PASO 3 verifica que ninguna matrícula quedó con 2 filas.
--
-- ⚠️ Y POR QUÉ SE EXIGE EL BLOQUEO: la boleta solo muestra competencias
--   BLOQUEADAS (`getBoletaAlumno` hace INNER JOIN a `bloqueos_competencia`). Una
--   carga sin bloqueo recibiría la nota y NO la mostraría: la migración habría
--   "funcionado" sin cumplir su objetivo. El PASO 1 lo mide.
--
-- ✅ NO AGRAVA LA ALERTA DE EVALUACIÓN INCOMPLETA DE B1 (verificado en el código,
--   no supuesto): `ControlOperativoModel::alertasEvaluacionIncompleta` filtra
--   `cr.extraordinario = 0`, así que el criterio que crea el PASO 2.a es invisible
--   para ella. Los 4 alumnos que llegaron después de B1 y quedan sin esta nota NO
--   se suman a los 12 blancos que hoy impedirían re-cerrar B1 si se reabriera.
-- ════════════════════════════════════════════════════════════════════


-- ════════════════════════════════════════════════════════════════════
-- PASO 1 — VERIFICACIÓN PREVIA (SOLO LECTURA). Correr y LEER antes de escribir.
-- ════════════════════════════════════════════════════════════════════
-- ⚠️ ESTE PASO NO PROTEGE AL PASO 2: son sentencias sueltas. Correr este paso
--   solo, LEER el resultado y recién entonces el PASO 2. (Si algo sale mal, a
--   diferencia de un DROP esto se puede deshacer — pero no es excusa.)

-- 1.0 ¿EN QUÉ ENTORNO ESTOY? Esta es la lección de la 048, resuelta: aquella se
--     dio por aplicada en prod cuando había caído en LOCAL, porque su veredicto
--     era IDÉNTICO en los dos (local es copia de prod: mismos ids, mismas fechas
--     al segundo). Ningún conteo de DATOS sirve para distinguirlos — la huella
--     tiene que ser del SERVIDOR. Correr esto ANTES que nada, en cada entorno.
--       · LOCAL (XAMPP):  bd 'siga_cociap', root@localhost, so 'Win64',
--                         datadir 'C:\xampp\mysql\data\'.
--       · PROD (Hostinger): otra bd, otro usuario, so Linux.
--     Si la fila dice Win64, estás en tu máquina.
SELECT DATABASE() AS bd, USER() AS usuario_conexion, @@hostname AS hostname,
       @@version AS version, @@version_compile_os AS so, @@datadir AS datadir;

SET @periodo := (SELECT id FROM periodos
                  WHERE numero = 1
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));
SET @competencia := (SELECT id FROM competencias WHERE codigo_minedu = 'C57');
SET @area        := (SELECT area_id FROM competencias WHERE id = @competencia);
-- El rol se busca con un patrón ASCII a propósito: `= 'Registro Académico'`
-- depende de que el cliente envíe la tilde en UTF-8, y un cliente mal
-- configurado la manda en latin1 → el anclaje resuelve NULL y la migración
-- inserta 0 filas sin decir por qué. Medido: pasa de verdad. Solo un rol
-- empieza por 'Registro Acad', así que el patrón es igual de preciso.
-- `estado = 'activo'`: sin ese filtro, un RA dado de baja con id menor que el
-- vigente firmaría las 275 filas — y la firma es lo que hace auditable el acto.
SET @usuario     := (SELECT u.id FROM usuarios u
                       JOIN roles r ON r.id = u.rol_id
                      WHERE r.nombre LIKE 'Registro Acad%'
                        AND u.estado = 'activo' ORDER BY u.id LIMIT 1);

-- 1.a Los cuatro anclajes resolvieron. NINGUNO puede ser NULL.
--     Esperado: periodo = I Bimestre 'cerrado', area = Tutoría (TOE) de
--     Secundaria con nombre_boleta 'Ética y Valores', usuario = Registro
--     Académico. LEER el nombre: es quien va a firmar las 275 filas.
SELECT @periodo AS periodo_id, p.nombre_display, p.estado AS estado_periodo,
       @competencia AS competencia_id, @area AS area_id,
       a.nombre_boleta AS area_boleta, n.nombre AS nivel,
       @usuario AS usuario_id,
       CONCAT(pe.apellido_paterno,' ',pe.apellido_materno,', ',pe.nombres) AS firma,
       CASE WHEN @periodo IS NULL OR @competencia IS NULL
                 OR @area IS NULL OR @usuario IS NULL
            THEN 'ABORTAR: algun anclaje es NULL'
            ELSE 'ANCLAJES OK' END AS veredicto
FROM periodos p
LEFT JOIN areas   a  ON a.id = @area
LEFT JOIN niveles n  ON n.id = a.nivel_id
LEFT JOIN usuarios u ON u.id = @usuario
LEFT JOIN personas pe ON pe.id = u.persona_id
WHERE p.id = @periodo;

-- 1.b El universo. Debe dar EXACTAMENTE 275 y coincidir con los alumnos de
--     secundaria del snapshot oficial de B1 (que también son 275): el anclaje
--     "tiene al menos una nota en B1" reproduce el mismo roster que se congeló.
--     Desglose esperado: 1° 72 · 2° 52 · 3° 47 · 4° 55 · 5° 49.
SELECT g.nombre_display AS grado, COUNT(*) AS alumnos
FROM matriculas m
JOIN secciones s ON s.id = m.seccion_id
JOIN grados    g ON g.id = s.grado_id
JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                         AND ca.area_id    = @area
                         AND ca.estado     = 'activa'
WHERE EXISTS (SELECT 1 FROM calificaciones c
               WHERE c.matricula_id = m.id AND c.periodo_id = @periodo)
  AND NOT EXISTS (SELECT 1 FROM calificaciones c
                   WHERE c.matricula_id   = m.id
                     AND c.competencia_id = @competencia
                     AND c.periodo_id     = @periodo)
  AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                   WHERE e.matricula_id = m.id
                     AND e.anio_id      = m.anio_id
                     AND e.area_id      = @area
                     AND e.revocado_en  IS NULL)
  AND NOT EXISTS (SELECT 1 FROM retornos_grado rg
                   WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo')
GROUP BY g.nombre_display WITH ROLLUP;

-- 1.c Contraste con el snapshot oficial de B1 y estado de partida.
--     Esperado: universo = 275, snapshot_secundaria = 275, notas_previas = 0,
--     cargas_activas = 11, secciones_con_carga = 11, cargas_sin_bloqueo = 0,
--     secciones_con_carga_duplicada = 0, snapshot_total = 528.
--     ⚠️ Las tres cifras estructurales son de ABORTO, no informativas:
--       · `cargas_sin_bloqueo` > 0  → esas notas no saldrían en la boleta;
--       · `secciones_con_carga_duplicada` > 0 → doble nota al mismo alumno;
--       · `notas_previas` > 0 → ya hay algo escrito ahí; el PASO 4 dejaría de
--         ser equivalente a "deshacer solo lo mío" (borra TODA extraordinaria).
SELECT
  (SELECT COUNT(*) FROM matriculas m
     JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                              AND ca.area_id = @area AND ca.estado = 'activa'
    WHERE EXISTS (SELECT 1 FROM calificaciones c
                   WHERE c.matricula_id = m.id AND c.periodo_id = @periodo)
      AND NOT EXISTS (SELECT 1 FROM calificaciones c
                       WHERE c.matricula_id = m.id AND c.competencia_id = @competencia
                         AND c.periodo_id = @periodo)
      AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                       WHERE e.matricula_id = m.id AND e.anio_id = m.anio_id
                         AND e.area_id = @area AND e.revocado_en IS NULL)
      AND NOT EXISTS (SELECT 1 FROM retornos_grado rg
                       WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo')
  ) AS universo,
  (SELECT COUNT(*) FROM orden_merito_snapshot oms
     JOIN matriculas m ON m.id = oms.matricula_id
     JOIN secciones  s ON s.id = m.seccion_id
     JOIN grados     g ON g.id = s.grado_id
     JOIN niveles    n ON n.id = g.nivel_id AND n.nombre = 'Secundaria'
    WHERE oms.periodo_id = @periodo) AS snapshot_secundaria,
  (SELECT COUNT(*) FROM calificaciones
    WHERE competencia_id = @competencia AND periodo_id = @periodo) AS notas_previas,
  (SELECT COUNT(*) FROM cargas_academicas
    WHERE area_id = @area AND estado = 'activa') AS cargas_activas,
  (SELECT COUNT(DISTINCT seccion_id) FROM cargas_academicas
    WHERE area_id = @area AND estado = 'activa') AS secciones_con_carga,
  (SELECT COUNT(*) FROM cargas_academicas ca
    WHERE ca.area_id = @area AND ca.estado = 'activa'
      AND NOT EXISTS (SELECT 1 FROM bloqueos_competencia bc
                       WHERE bc.carga_id       = ca.id
                         AND bc.competencia_id = @competencia
                         AND bc.periodo_id     = @periodo)) AS cargas_sin_bloqueo,
  (SELECT COUNT(*) FROM (
      SELECT seccion_id FROM cargas_academicas
       WHERE area_id = @area AND estado = 'activa'
       GROUP BY seccion_id HAVING COUNT(*) > 1) d) AS secciones_con_carga_duplicada,
  (SELECT COUNT(*) FROM orden_merito_snapshot WHERE periodo_id = @periodo) AS snapshot_total;

-- 1.d Los que quedan FUERA a propósito: llegaron después de B1 y no tienen
--     ninguna nota de ese bimestre. Esperado: 4 filas (matrículas 693, 694,
--     695, 696). Darles la nota sería inventarles un bimestre que no cursaron.
SELECT m.id AS matricula, m.tipo, g.nombre_display AS grado, s.nombre AS sec,
       CONCAT(pe.apellido_paterno,' ',pe.apellido_materno) AS alumno
FROM matriculas m
JOIN secciones s ON s.id = m.seccion_id
JOIN grados    g ON g.id = s.grado_id
JOIN niveles   n ON n.id = g.nivel_id AND n.nombre = 'Secundaria'
JOIN estudiantes e ON e.id = m.estudiante_id
JOIN personas  pe  ON pe.id = e.persona_id
WHERE NOT EXISTS (SELECT 1 FROM calificaciones c
                   WHERE c.matricula_id = m.id AND c.periodo_id = @periodo);

-- 1.e Los que quedan fuera POR LAS OTRAS DOS GUARDAS, con el motivo. En local
--     da 0 filas (no hay exonerados de Ética ni retornos en secundaria), pero en
--     PROD hay que LEERLO: cada fila aquí es un alumno de secundaria que cursó
--     B1 y NO recibirá la nota, y conviene que sea una decisión, no un descubrimiento.
--       · 'EXONERADO DE ETICA'  → correcto: un exonerado no lleva nota del área.
--       · 'OFICIAL DE RETORNO'  → correcto: se evalúa en su matrícula OPERATIVA;
--         verificar que la operativa SÍ aparece en el universo (si no, ese alumno
--         se queda sin Ética y hay que resolverlo a mano por Rectificación).
SELECT m.id AS matricula, g.nombre_display AS grado, s.nombre AS sec,
       CONCAT(pe.apellido_paterno,' ',pe.apellido_materno,', ',pe.nombres) AS alumno,
       CASE WHEN EXISTS (SELECT 1 FROM retornos_grado rg
                          WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo')
            THEN 'OFICIAL DE RETORNO' ELSE 'EXONERADO DE ETICA' END AS motivo
FROM matriculas m
JOIN secciones s ON s.id = m.seccion_id
JOIN grados    g ON g.id = s.grado_id
JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                         AND ca.area_id    = @area
                         AND ca.estado     = 'activa'
JOIN estudiantes e ON e.id = m.estudiante_id
JOIN personas  pe  ON pe.id = e.persona_id
WHERE EXISTS (SELECT 1 FROM calificaciones c
               WHERE c.matricula_id = m.id AND c.periodo_id = @periodo)
  AND NOT EXISTS (SELECT 1 FROM calificaciones c
                   WHERE c.matricula_id = m.id AND c.competencia_id = @competencia
                     AND c.periodo_id = @periodo)
  AND (EXISTS (SELECT 1 FROM exoneraciones ex
                WHERE ex.matricula_id = m.id AND ex.anio_id = m.anio_id
                  AND ex.area_id = @area AND ex.revocado_en IS NULL)
       OR EXISTS (SELECT 1 FROM retornos_grado rg
                   WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo'));


-- ════════════════════════════════════════════════════════════════════
-- PASO 2 — ESCRITURA. Ejecutar SOLO si el PASO 1 dio 'ANCLAJES OK' y 275.
-- ════════════════════════════════════════════════════════════════════
-- Las variables se REDEFINEN aquí a propósito: phpMyAdmin no garantiza que
-- sobrevivan entre ejecuciones, y una variable NULL en un INSERT ... SELECT
-- escribiría basura. Con la guarda `IS NOT NULL` de cada WHERE, un anclaje que
-- no resuelva inserta CERO filas en vez de datos corruptos: falla cerrado.
--
-- 🔴 EJECUTAR SIEMPRE ENVUELTO EN TRANSACCIÓN, también en producción:
--     START TRANSACTION;  … (los 4 INSERT) …  COMMIT;
--   Son CUATRO sentencias sueltas y dependientes entre sí: si la 2.c fallara, el
--   criterio y las 275 notas de criterio ya estarían escritos y la calificación
--   no — un estado a medias, con criterio confirmado y sin nota final. Aquí no
--   hay DDL (a diferencia de la 048), así que la transacción SÍ protege: ante un
--   error, phpMyAdmin corta la ejecución y la conexión muere sin COMMIT →
--   ROLLBACK implícito, cero filas escritas.
--   Para ENSAYAR sin escribir, el mismo bloque terminado en ROLLBACK.
--
-- ⚠️ PROD Y LOCAL NO CORREN LA MISMA MARIADB (verificado el 06/08/2026 con la
--   huella 1.0): local **10.4.32**, prod **11.8.8**. El ensayo en local prueba la
--   LÓGICA, no el plan del optimizador. Importa sobre todo por el patrón
--   `NOT EXISTS (SELECT 1 FROM (SELECT * FROM tabla) x)` de 2.b/2.c/2.d, que
--   fuerza la materialización de la derivada para poder leer la MISMA tabla en la
--   que se inserta. Si una versión decidiera fusionarla, el INSERT abortaría con
--   error — ruidoso, no silencioso, y con la transacción no deja rastro.
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = 1
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));
SET @competencia := (SELECT id FROM competencias WHERE codigo_minedu = 'C57');
SET @area        := (SELECT area_id FROM competencias WHERE id = @competencia);
-- El rol se busca con un patrón ASCII a propósito: `= 'Registro Académico'`
-- depende de que el cliente envíe la tilde en UTF-8, y un cliente mal
-- configurado la manda en latin1 → el anclaje resuelve NULL y la migración
-- inserta 0 filas sin decir por qué. Medido: pasa de verdad. Solo un rol
-- empieza por 'Registro Acad', así que el patrón es igual de preciso.
-- `estado = 'activo'`: sin ese filtro, un RA dado de baja con id menor que el
-- vigente firmaría las 275 filas — y la firma es lo que hace auditable el acto.
SET @usuario     := (SELECT u.id FROM usuarios u
                       JOIN roles r ON r.id = u.rol_id
                      WHERE r.nombre LIKE 'Registro Acad%'
                        AND u.estado = 'activo' ORDER BY u.id LIMIT 1);
SET @nota   := 15;
SET @motivo := 'Ética y Valores no fue evaluada en el I Bimestre. Por acuerdo de dirección se ingresará una calificación estándar, se registra 15 (A) uniforme para todos los estudiantes de secundaria que cursaron el I Bimestre, en concordancia con lo ya consignado en el acta SIAGIE bajo Educación Religiosa. Calificación administrativa, no evaluativa: no computa en el orden de mérito.';

-- 2.a Criterio único por carga (11 filas). Nombre y descripción IDÉNTICOS a los
--     que escribe `CriterioModel::obtenerOCrearExtraordinario`. Nace confirmado:
--     el promedio agregado y el blindaje anti-fantasma lo exigen.
INSERT INTO criterios
    (carga_id, competencia_id, periodo_id, nombre, descripcion,
     orden, confirmado_en, confirmado_por, extraordinario)
SELECT ca.id, @competencia, @periodo,
       'Calificación extraordinaria',
       'Calificación registrada por Registro Académico a alumnos sin nota del docente. El motivo por alumno queda en la auditoría del módulo de Rectificación.',
       COALESCE(cr_max.ultimo, 0) + 1,
       NOW(), @usuario, 1
FROM cargas_academicas ca
LEFT JOIN (
    SELECT carga_id, competencia_id, periodo_id, MAX(orden) AS ultimo
    FROM criterios WHERE eliminado_en IS NULL
    GROUP BY carga_id, competencia_id, periodo_id
) cr_max ON cr_max.carga_id       = ca.id
        AND cr_max.competencia_id = @competencia
        AND cr_max.periodo_id     = @periodo
WHERE ca.area_id = @area
  AND ca.estado  = 'activa'
  AND @periodo IS NOT NULL AND @competencia IS NOT NULL
  AND @area    IS NOT NULL AND @usuario     IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM criterios) cr
      WHERE cr.carga_id       = ca.id
        AND cr.competencia_id = @competencia
        AND cr.periodo_id     = @periodo
        AND cr.extraordinario = 1
        AND cr.eliminado_en   IS NULL
  );

-- 2.b Nota de criterio = 15 por alumno (275 filas).
INSERT INTO calificaciones_criterio (criterio_id, matricula_id, nota, registrado_en)
SELECT cr.id, m.id, @nota, NOW()
FROM matriculas m
JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                         AND ca.area_id    = @area
                         AND ca.estado     = 'activa'
JOIN criterios cr ON cr.carga_id       = ca.id
                 AND cr.competencia_id = @competencia
                 AND cr.periodo_id     = @periodo
                 AND cr.extraordinario = 1
                 AND cr.eliminado_en   IS NULL
WHERE @periodo IS NOT NULL AND @competencia IS NOT NULL
  AND @area    IS NOT NULL AND @usuario     IS NOT NULL
  AND EXISTS (SELECT 1 FROM calificaciones c
               WHERE c.matricula_id = m.id AND c.periodo_id = @periodo)
  AND NOT EXISTS (SELECT 1 FROM calificaciones c
                   WHERE c.matricula_id   = m.id
                     AND c.competencia_id = @competencia
                     AND c.periodo_id     = @periodo)
  -- Exoneración acotada al ÁREA y al AÑO, como `esInsertable` (ver cabecera).
  AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                   WHERE e.matricula_id = m.id
                     AND e.anio_id      = m.anio_id
                     AND e.area_id      = @area
                     AND e.revocado_en  IS NULL)
  -- Regla A del retorno de grado: se EVALÚA en la operativa.
  AND NOT EXISTS (SELECT 1 FROM retornos_grado rg
                   WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo')
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM calificaciones_criterio) cc
      WHERE cc.criterio_id = cr.id AND cc.matricula_id = m.id
  );

-- 2.c Calificación final = 15, marcada EXTRAORDINARIA (275 filas).
--     Sin conclusión descriptiva: el literal de 15 es A y en secundaria la
--     conclusión solo es obligatoria para C (`conclusionObligatoria`).
INSERT INTO calificaciones
    (matricula_id, carga_id, periodo_id, competencia_id,
     nota_numerica, conclusion_descriptiva, extraordinaria,
     registrado_por, registrado_en)
SELECT m.id, ca.id, @periodo, @competencia, @nota, NULL, 1, @usuario, NOW()
FROM matriculas m
JOIN cargas_academicas ca ON ca.seccion_id = m.seccion_id
                         AND ca.area_id    = @area
                         AND ca.estado     = 'activa'
WHERE @periodo IS NOT NULL AND @competencia IS NOT NULL
  AND @area    IS NOT NULL AND @usuario     IS NOT NULL
  AND EXISTS (SELECT 1 FROM (SELECT * FROM calificaciones) c
               WHERE c.matricula_id = m.id AND c.periodo_id = @periodo)
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM calificaciones) c
                   WHERE c.matricula_id   = m.id
                     AND c.competencia_id = @competencia
                     AND c.periodo_id     = @periodo)
  -- Exoneración acotada al ÁREA y al AÑO, como `esInsertable` (ver cabecera).
  AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                   WHERE e.matricula_id = m.id
                     AND e.anio_id      = m.anio_id
                     AND e.area_id      = @area
                     AND e.revocado_en  IS NULL)
  -- Regla A del retorno de grado: se EVALÚA en la operativa.
  AND NOT EXISTS (SELECT 1 FROM retornos_grado rg
                   WHERE rg.matricula_oficial_id = m.id AND rg.estado = 'activo');

-- 2.d Auditoría: una fila por alumno con el MOTIVO (275 filas). Se ancla en las
--     calificaciones que acaban de marcarse extraordinarias, así que si 2.c
--     insertó 275, esto inserta 275.
INSERT INTO rectificaciones_calificacion
    (matricula_id, carga_id, periodo_id, competencia_id, tipo,
     nota_anterior, nota_nueva, conclusion_anterior, conclusion_nueva,
     motivo, rectificado_por, rectificado_en)
SELECT c.matricula_id, c.carga_id, c.periodo_id, c.competencia_id, 'extraordinaria',
       NULL, c.nota_numerica, NULL, NULL, @motivo, @usuario, NOW()
FROM calificaciones c
WHERE c.competencia_id = @competencia
  AND c.periodo_id     = @periodo
  AND c.extraordinaria = 1
  AND @usuario IS NOT NULL AND @motivo IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM rectificaciones_calificacion) r
      WHERE r.matricula_id   = c.matricula_id
        AND r.carga_id       = c.carga_id
        AND r.periodo_id     = c.periodo_id
        AND r.competencia_id = c.competencia_id
        AND r.tipo           = 'extraordinaria'
  );


-- ════════════════════════════════════════════════════════════════════
-- PASO 3 — VERIFICACIÓN POSTERIOR (SOLO LECTURA)
-- ════════════════════════════════════════════════════════════════════
-- 3.a Lo escrito. Esperado: criterios = 11, notas_criterio = 275,
--     calificaciones = 275, TODAS extraordinarias, auditoria = 275.
SELECT
  (SELECT COUNT(*) FROM criterios
    WHERE competencia_id = @competencia AND periodo_id = @periodo
      AND extraordinario = 1 AND eliminado_en IS NULL) AS criterios,
  (SELECT COUNT(*) FROM calificaciones_criterio cc
     JOIN criterios cr ON cr.id = cc.criterio_id
    WHERE cr.competencia_id = @competencia AND cr.periodo_id = @periodo
      AND cr.extraordinario = 1) AS notas_criterio,
  (SELECT COUNT(*) FROM calificaciones
    WHERE competencia_id = @competencia AND periodo_id = @periodo) AS calificaciones,
  (SELECT COUNT(*) FROM calificaciones
    WHERE competencia_id = @competencia AND periodo_id = @periodo
      AND extraordinaria = 1) AS marcadas_extraordinarias,
  (SELECT COUNT(*) FROM calificaciones
    WHERE competencia_id = @competencia AND periodo_id = @periodo
      AND nota_numerica <> @nota) AS con_nota_distinta_de_15,
  (SELECT COUNT(*) FROM rectificaciones_calificacion
    WHERE competencia_id = @competencia AND periodo_id = @periodo
      AND tipo = 'extraordinaria') AS auditoria;

-- 3.b EL SNAPSHOT DE B1 NO SE MOVIÓ. Esperado, idéntico a antes:
--     filas = 528, mn = 1, mx = 72, grados = 11, secciones = 23.
SELECT COUNT(*) AS filas, MIN(puesto_grado) AS mn, MAX(puesto_grado) AS mx,
       COUNT(DISTINCT grado_id) AS grados, COUNT(DISTINCT seccion_id) AS secciones
FROM orden_merito_snapshot WHERE periodo_id = @periodo;

-- 3.c Ninguna de las notas nuevas entra al mérito. Debe dar 0: es el mismo
--     filtro `extraordinaria = 0` que aplica OrdenMeritoModel en sus 2 queries.
SELECT COUNT(*) AS notas_que_entrarian_al_merito
FROM calificaciones
WHERE competencia_id = @competencia AND periodo_id = @periodo
  AND extraordinaria = 0;

-- 3.c-bis EL MOTIVO SE GUARDÓ CON LAS TILDES BIEN. Este texto es el único
--     registro permanente de por qué existen estas 275 notas, así que su
--     codificación importa. Esperado: 'MOTIVO OK'.
--       · 'SIN MULTIBYTE'  → el cliente comió las tildes al insertar.
--       · 'DOBLE CODIF'    → mojibake (UTF-8 interpretado como latin1).
--     En cualquiera de los dos casos: deshacer con el PASO 4 y repetir desde
--     phpMyAdmin, que trabaja en utf8mb4.
--     ⚠️ La búsqueda del patrón mojibake va SOBRE LOS BYTES
--     (`CONVERT(... USING binary)`) y no sobre el texto: la columna es
--     `utf8mb4_unicode_ci`, la colación que equipara Ã con A —la misma que
--     hacía Ñ ≡ N en el orden alfabético—, así que un `INSTR` normal encuentra
--     la primera 'a' del motivo y da un FALSO POSITIVO. Medido en el ensayo.
SELECT CHAR_LENGTH(motivo) AS caracteres, LENGTH(motivo) AS bytes,
       CASE WHEN LENGTH(motivo) = CHAR_LENGTH(motivo)                THEN 'SIN MULTIBYTE: las tildes se perdieron'
            WHEN INSTR(CONVERT(motivo USING binary), X'C383') > 0    THEN 'DOBLE CODIF: mojibake'
            ELSE 'MOTIVO OK' END AS veredicto,
       LEFT(motivo, 60) AS inicio
FROM rectificaciones_calificacion
WHERE competencia_id = @competencia AND periodo_id = @periodo
  AND tipo = 'extraordinaria'
LIMIT 1;

-- 3.c-ter INTEGRIDAD DE LO ESCRITO. Las tres cifras deben dar 0.
--     · `matriculas_con_dos_notas`: el `uq_nota` incluye `carga_id`, así que NO
--       protege de una sección con dos cargas activas del área. Esta es la red.
--     · `notas_en_carga_ajena`: la nota tiene que colgar de la carga de LA SECCIÓN
--       del alumno; cualquier otra cosa es un JOIN que se fue de rango.
--     · `sin_bloqueo`: una nota en carga sin bloqueo no se vería en la boleta.
SELECT
  (SELECT COUNT(*) FROM (
      SELECT matricula_id FROM calificaciones
       WHERE competencia_id = @competencia AND periodo_id = @periodo
       GROUP BY matricula_id HAVING COUNT(*) > 1) d) AS matriculas_con_dos_notas,
  (SELECT COUNT(*) FROM calificaciones c
     JOIN matriculas m         ON m.id  = c.matricula_id
     JOIN cargas_academicas ca ON ca.id = c.carga_id
    WHERE c.competencia_id = @competencia AND c.periodo_id = @periodo
      AND (ca.seccion_id <> m.seccion_id OR ca.area_id <> @area)) AS notas_en_carga_ajena,
  (SELECT COUNT(*) FROM calificaciones c
    WHERE c.competencia_id = @competencia AND c.periodo_id = @periodo
      AND NOT EXISTS (SELECT 1 FROM bloqueos_competencia bc
                       WHERE bc.carga_id       = c.carga_id
                         AND bc.competencia_id = @competencia
                         AND bc.periodo_id     = @periodo)) AS sin_bloqueo;

-- 3.d Los 4 que llegaron después siguen SIN nota de B1. Debe dar 0 filas.
SELECT c.matricula_id
FROM calificaciones c
WHERE c.competencia_id = @competencia AND c.periodo_id = @periodo
  AND NOT EXISTS (SELECT 1 FROM (SELECT * FROM calificaciones) c2
                   WHERE c2.matricula_id = c.matricula_id
                     AND c2.periodo_id   = c.periodo_id
                     AND c2.competencia_id <> c.competencia_id);


-- ════════════════════════════════════════════════════════════════════
-- PASO 4 — DESHACER (solo si hiciera falta; NO ejecutar en el flujo normal)
-- ════════════════════════════════════════════════════════════════════
-- Acotado a lo que creó esta migración: la competencia C57 del I Bimestre y
-- solo las filas marcadas como extraordinarias. Ejecutar en este orden.
--
-- ⚠️ "Lo que creó esta migración" == "toda extraordinaria de C57 en B1" SOLO
--   porque el PASO 1.c verificó `notas_previas = 0`. Si ese conteo NO dio 0, este
--   DELETE se lleva por delante notas que registró otra persona por Rectificación:
--   en ese caso hay que acotarlo a mano (p. ej. por `rectificado_en`, la marca de
--   tiempo de la corrida). Verificar antes de ejecutar.
--
-- SET @periodo := (SELECT id FROM periodos WHERE numero = 1
--                   AND anio_id = (SELECT id FROM anios_academicos
--                                   WHERE estado='activo' ORDER BY anio DESC LIMIT 1));
-- SET @competencia := (SELECT id FROM competencias WHERE codigo_minedu = 'C57');
--
-- DELETE FROM rectificaciones_calificacion
--  WHERE competencia_id = @competencia AND periodo_id = @periodo
--    AND tipo = 'extraordinaria';
-- DELETE cc FROM calificaciones_criterio cc
--   JOIN criterios cr ON cr.id = cc.criterio_id
--  WHERE cr.competencia_id = @competencia AND cr.periodo_id = @periodo
--    AND cr.extraordinario = 1;
-- DELETE FROM calificaciones
--  WHERE competencia_id = @competencia AND periodo_id = @periodo
--    AND extraordinaria = 1;
-- DELETE FROM criterios
--  WHERE competencia_id = @competencia AND periodo_id = @periodo
--    AND extraordinario = 1;
