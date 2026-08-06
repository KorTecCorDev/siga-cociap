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
-- ════════════════════════════════════════════════════════════════════


-- ════════════════════════════════════════════════════════════════════
-- PASO 1 — VERIFICACIÓN PREVIA (SOLO LECTURA). Correr y LEER antes de escribir.
-- ════════════════════════════════════════════════════════════════════
-- ⚠️ ESTE PASO NO PROTEGE AL PASO 2: son sentencias sueltas. Correr este paso
--   solo, LEER el resultado y recién entonces el PASO 2. (Si algo sale mal, a
--   diferencia de un DROP esto se puede deshacer — pero no es excusa.)
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
SET @usuario     := (SELECT u.id FROM usuarios u
                       JOIN roles r ON r.id = u.rol_id
                      WHERE r.nombre LIKE 'Registro Acad%' ORDER BY u.id LIMIT 1);

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
                   WHERE e.matricula_id = m.id AND e.revocado_en IS NULL)
GROUP BY g.nombre_display WITH ROLLUP;

-- 1.c Contraste con el snapshot oficial de B1 y estado de partida.
--     Esperado: universo = 275, snapshot_secundaria = 275, notas_previas = 0,
--     cargas_activas = 11, snapshot_total = 528.
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
                       WHERE e.matricula_id = m.id AND e.revocado_en IS NULL)
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


-- ════════════════════════════════════════════════════════════════════
-- PASO 2 — ESCRITURA. Ejecutar SOLO si el PASO 1 dio 'ANCLAJES OK' y 275.
-- ════════════════════════════════════════════════════════════════════
-- Las variables se REDEFINEN aquí a propósito: phpMyAdmin no garantiza que
-- sobrevivan entre ejecuciones, y una variable NULL en un INSERT ... SELECT
-- escribiría basura. Con la guarda `IS NOT NULL` de cada WHERE, un anclaje que
-- no resuelva inserta CERO filas en vez de datos corruptos: falla cerrado.
--
-- Para ENSAYAR sin escribir, envolver este paso completo:
--     START TRANSACTION;  … (los 4 INSERT y el PASO 3) …  ROLLBACK;
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
SET @usuario     := (SELECT u.id FROM usuarios u
                       JOIN roles r ON r.id = u.rol_id
                      WHERE r.nombre LIKE 'Registro Acad%' ORDER BY u.id LIMIT 1);
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
  AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                   WHERE e.matricula_id = m.id AND e.revocado_en IS NULL)
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
  AND NOT EXISTS (SELECT 1 FROM exoneraciones e
                   WHERE e.matricula_id = m.id AND e.revocado_en IS NULL);

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
