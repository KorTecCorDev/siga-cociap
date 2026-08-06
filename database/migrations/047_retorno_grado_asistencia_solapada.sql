-- ════════════════════════════════════════════════════════════════════
-- Migración 047: Retorno de grado — quitar la asistencia SOLAPADA
--                que quedó en la matrícula OFICIAL
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO: un retorno de grado da al estudiante DOS matrículas del mismo año,
--   la OFICIAL (grado SIAGIE) y la OPERATIVA (grado donde asiste). Por la
--   REGLA A (05/08/2026), cada bimestre queda en la matrícula donde se cursó:
--   antes del retorno en la oficial, DESDE el retorno en la OPERATIVA. La
--   boleta las une al leer.
--
--   El 04/08/2026 la asistencia del II Bimestre se registró primero con el
--   roster VIEJO de /admin/asistencia (que aún mostraba la matrícula oficial de
--   un retorno activo). Al desplegar el roster corregido se rehicieron las
--   secciones, pero eso NO borró las filas ya escritas del lado equivocado.
--
--   Resultado: la matrícula OFICIAL y la OPERATIVA quedaron ambas con fila de
--   `inasistencias` del MISMO bimestre. Y `AsistenciaModel::getDelBimestreUnion`
--   —que alimenta la boleta— SUMA campo a campo las dos fuentes, bajo la premisa
--   declarada de que "por bimestre solo una tiene datos". Con el solape, la
--   boleta muestra el DOBLE de inasistencias.
--
--   Caso medido en producción (05/08/2026): BALTAZAR PINTO, SHALOM CRISTEL
--   (oficial 2° B / operativa 1° B, retorno del 21/06/2026) aparecía con
--   4 faltas en el II Bimestre en vez de 2.
--
--   La UI NO puede corregirlo: con el roster nuevo la matrícula oficial ya no
--   está en la grilla y `matriculaEnRoster` rechaza toda escritura sobre ella
--   (403). Por eso hace falta esta migración.
--
-- CAMBIO: borra la fila de `inasistencias` de la matrícula OFICIAL únicamente
--   cuando la OPERATIVA tiene fila del MISMO periodo (es decir, solo donde hay
--   solape real). El dato correcto es el de la operativa.
--
-- NO SE TOCA:
--   * La asistencia de bimestres ANTERIORES al retorno, que vive legítimamente
--     en la matrícula oficial (en el caso real, el I Bimestre: cerró el 16/06 y
--     el retorno es del 21/06). Doblemente protegida: no hay solape allí, y
--     además se exige `p.fecha_fin >= r.fecha_retorno`.
--   * Las CALIFICACIONES duplicadas del I Bimestre. Se conservan a propósito
--     (decisión del usuario, 05/08/2026): son la base probatoria del snapshot
--     OFICIAL de orden de mérito de B1, que está publicado e inmutable por el
--     candado de la migración 046. Sin ellas, su promedio (12.05 sobre 20
--     competencias) dejaría de ser reproducible.
--   * La conducta: se verificó que no hay solape (su unión usa `array_replace`,
--     que no infla). El PREVIEW de abajo la vigila igual.
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * Ancla por el vínculo `retornos_grado`, NO por ids auto-incrementales
--     (difieren entre entornos). Portable local ↔ producción.
--   * Se autolimita al SOLAPE: si la operativa no tiene fila de ese periodo, no
--     borra nada. Es imposible que deje al estudiante sin asistencia.
--   * Guarda temporal `p.fecha_fin >= r.fecha_retorno`: un bimestre que terminó
--     antes del retorno pertenece a la oficial y nunca se toca.
--   * Solo retornos con `estado = 'activo'`.
--   * Idempotente: una segunda corrida no encuentra filas (no-op).
--
-- ⚠️ ANTES DE APLICAR EN PRODUCCIÓN: correr el PREVIEW. Debe devolver
--   EXACTAMENTE 1 fila (matrícula oficial, II Bimestre, faltas=2). Si devuelve
--   0, ya está aplicada. Si devuelve más de 1, DETENERSE y revisar: apareció un
--   caso nuevo que no se midió.
--
-- Ejecutar DESPUÉS de 046_orden_merito_inmutable.sql. Conexión utf8mb4.
-- Acompaña al código de la Regla A (F1/F2/F3); ver docs/modulos/retorno-grado.md.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto PRIMERO en prod: ──
--   SELECT  CONCAT(per.apellido_paterno,' ',per.apellido_materno,', ',per.nombres) AS estudiante,
--           p.nombre_display                       AS bimestre,
--           ofi.matricula_id                       AS matricula_oficial,
--           ope.matricula_id                       AS matricula_operativa,
--           ofi.faltas    AS ofi_faltas,  ope.faltas    AS ope_faltas,
--           ofi.tardanzas AS ofi_tardanzas, ope.tardanzas AS ope_tardanzas,
--           (ofi.faltas + ope.faltas)              AS faltas_que_ve_la_boleta,
--           ofi.registrado_en                      AS ofi_registrado_en
--   FROM inasistencias ofi
--   INNER JOIN retornos_grado r
--           ON r.matricula_oficial_id = ofi.matricula_id AND r.estado = 'activo'
--   INNER JOIN inasistencias ope
--           ON ope.matricula_id = r.matricula_operativa_id
--          AND ope.periodo_id   = ofi.periodo_id
--   INNER JOIN periodos p    ON p.id = ofi.periodo_id AND p.fecha_fin >= r.fecha_retorno
--   INNER JOIN matriculas m  ON m.id = ofi.matricula_id
--   INNER JOIN estudiantes e ON e.id = m.estudiante_id
--   INNER JOIN personas per  ON per.id = e.persona_id
--   ORDER BY p.numero;
--
-- ── PREVIEW 2 — vigilar que la conducta no tenga el mismo solape: ──
--   (debe devolver 0 filas; si devuelve algo, DETENERSE y consultar)
--   SELECT 'calificaciones_conducta' AS tabla, c.periodo_id, c.matricula_id
--   FROM calificaciones_conducta c
--   INNER JOIN retornos_grado r ON r.matricula_oficial_id = c.matricula_id AND r.estado='activo'
--   INNER JOIN calificaciones_conducta c2
--           ON c2.matricula_id = r.matricula_operativa_id AND c2.periodo_id = c.periodo_id
--   UNION ALL
--   SELECT 'conducta_respuestas', cr.periodo_id, cr.matricula_id
--   FROM conducta_respuestas cr
--   INNER JOIN retornos_grado r ON r.matricula_oficial_id = cr.matricula_id AND r.estado='activo'
--   INNER JOIN conducta_respuestas cr2
--           ON cr2.matricula_id = r.matricula_operativa_id AND cr2.periodo_id = cr.periodo_id
--   GROUP BY 1, 2, 3;

-- ── CAMBIO ──────────────────────────────────────────────────────────
-- Borra la fila de la matrícula OFICIAL solo donde la OPERATIVA tiene fila del
-- mismo periodo, y solo en bimestres que no habían terminado al momento del
-- retorno. El dato válido es el de la operativa.
DELETE ofi
FROM inasistencias ofi
INNER JOIN retornos_grado r
        ON r.matricula_oficial_id = ofi.matricula_id
       AND r.estado = 'activo'
INNER JOIN inasistencias ope
        ON ope.matricula_id = r.matricula_operativa_id
       AND ope.periodo_id   = ofi.periodo_id
INNER JOIN periodos p
        ON p.id = ofi.periodo_id
       AND p.fecha_fin >= r.fecha_retorno;

-- ── VERIFICACIÓN (correr DESPUÉS; ambas deben dar 0 filas / el valor correcto) ──
--
-- 1) Ya no queda ningún solape de asistencia (debe devolver 0):
--   SELECT COUNT(*) AS solapes_restantes
--   FROM inasistencias ofi
--   INNER JOIN retornos_grado r
--           ON r.matricula_oficial_id = ofi.matricula_id AND r.estado = 'activo'
--   INNER JOIN inasistencias ope
--           ON ope.matricula_id = r.matricula_operativa_id
--          AND ope.periodo_id   = ofi.periodo_id;
--
-- 2) Lo que verá la boleta por bimestre (una fila por bimestre con dato; en el
--    caso real: I Bim = 2 faltas, II Bim = 2 faltas, NO 4):
--   SELECT p.nombre_display AS bimestre,
--          SUM(i.faltas)                 AS faltas,
--          SUM(i.faltas_justificadas)    AS faltas_justificadas,
--          SUM(i.tardanzas)              AS tardanzas,
--          SUM(i.tardanzas_justificadas) AS tardanzas_justificadas
--   FROM retornos_grado r
--   INNER JOIN inasistencias i
--           ON i.matricula_id IN (r.matricula_oficial_id, r.matricula_operativa_id)
--   INNER JOIN periodos p ON p.id = i.periodo_id
--   WHERE r.estado = 'activo'
--   GROUP BY p.id
--   ORDER BY p.numero;
--
-- 3) Comprobación integral desde la aplicación (solo lectura, corre en prod):
--      php database/verificaciones/verif_retorno_grado.php
--    Su bloque 5 debe quedar en OK.
