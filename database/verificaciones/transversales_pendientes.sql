-- ============================================================================
-- TRANSVERSALES PENDIENTES DE APROBAR/BLOQUEAR — consulta de solo lectura
-- ============================================================================
-- Lista a los DOCENTES que todavia no aprobaron+bloquearon las competencias
-- transversales (TIC / GAMA) en sus cargas. Es el bloqueador tipico del cierre
-- del tutor: /docente/tutoria solo habilita "Cerrar" cuando TODAS las cargas
-- activas de la seccion tienen sus transversales bloqueadas.
--
-- PARA QUE SIRVE: medir contra PRODUCCION desde phpMyAdmin (Hostinger) a quien
-- hay que perseguir, sin depender del panel del director.
--
-- QUE CUENTA COMO PENDIENTE: falta la fila en `bloqueos_competencia`
-- (carga + competencia transversal + periodo). El universo de cargas replica
-- EXACTO la regla de `CalificacionController::formulario()`, o sea las cargas
-- que de verdad MUESTRAN la seccion "Competencias Transversales" al docente:
--   1. carga `estado = 'activa'` del anio del periodo;
--   2. area resuelta COALESCE(ca.area_id, subarea->area_id) con
--      tipo NOT IN ('transversal','tutoria')  — la carga transversal del modelo
--      viejo (migracion 019) y la de Tutoria/Etica NO llevan TIC/GAMA;
--   3. seccion UNIDOCENTE: solo la carga DUEÑA del area (subarea de menor
--      orden, desempate por id) — el mismo docente dicta todas las subareas y
--      registra las TIC/GAMA UNA sola vez;
--      seccion POLIDOCENTE: cada carga lleva las suyas;
--   4. competencias transversales = las del area tipo='transversal' del NIVEL
--      de la seccion (competencias duplicadas por nivel: nunca cruzar niveles).
-- Validado contra PHP: el universo de cargas del bloque coincide 1 a 1 con
-- `cargaDuenaTransversales()` + la regla del formulario (345 = 345 en el anio
-- activo local, 0 diferencias).
--
-- COMO LEER `criterios` y `alumnos_con_nota`: distinguen al docente que ni
-- empezo (0 y 0) del que ya califico y solo le falta aprobar (>0).
--
-- USO: cambiar el `SET @periodo` (2 = II Bimestre) y, para acotar la seccion,
-- `SET @seccion_id` (id exacto, ver Bloque 0) o `SET @seccion_txt` (texto libre
-- tipo '3ro' / '1° A' / ' A'). NULL en ambos = TODAS las secciones.
-- Cada bloque es autocontenido a proposito: se puede ejecutar suelto en
-- phpMyAdmin sin arrastrar la variable de otra conexion.
--
-- SOLO LECTURA: no escribe nada.
--


-- ============================================================================
-- BLOQUE 0a — Periodos (para confirmar el id del bimestre en PRODUCCION)
-- ============================================================================
SELECT pe.id AS periodo_id, an.anio, an.estado AS anio_estado,
       pe.numero AS bimestre, pe.estado AS periodo_estado,
       pe.fecha_inicio, pe.fecha_fin
FROM periodos pe
INNER JOIN anios_academicos an ON an.id = pe.anio_id
ORDER BY an.anio DESC, pe.numero;


-- ============================================================================
-- BLOQUE 0b — Secciones del periodo (para elegir @seccion_id)
-- ============================================================================
SET @periodo := 2;

SELECT s.id                                   AS seccion_id,
       n.nombre                               AS nivel,
       CONCAT(g.nombre_display, ' ', s.nombre) AS seccion,
       IF(s.es_unidocente = 1, 'unidocente', 'polidocente') AS tipo_seccion,
       CONCAT(pt.apellido_paterno, ' ', pt.apellido_materno, ', ', pt.nombres) AS tutor
FROM secciones s
INNER JOIN grados  g  ON g.id  = s.grado_id
INNER JOIN niveles n  ON n.id  = g.nivel_id
LEFT  JOIN usuarios ut ON ut.id = s.tutor_id
LEFT  JOIN personas pt ON pt.id = ut.persona_id
WHERE s.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
ORDER BY n.id, g.numero, s.nombre;


-- ============================================================================
-- BLOQUE 1 — RESUMEN POR DOCENTE (a quien perseguir)
-- ============================================================================
SET @periodo     := 2;
SET @seccion_id  := NULL;   -- id exacto de seccion; NULL = todas
SET @seccion_txt := NULL;   -- texto libre sobre "1° A"; NULL = todas

SELECT d.docente,
       COUNT(DISTINCT d.seccion_id)      AS secciones,
       COUNT(DISTINCT d.carga_id)        AS cargas_pendientes,
       COUNT(*)                          AS competencias_pendientes,
       SUM(d.alumnos_con_nota > 0)       AS comp_ya_calificadas,
       GROUP_CONCAT(DISTINCT CONCAT(d.seccion, ' - ', d.area)
                    ORDER BY d.seccion, d.area SEPARATOR ' | ') AS detalle
FROM (
    -- El nivel va DENTRO de la etiqueta: "1° A" existe en primaria y en
    -- secundaria, y el GROUP_CONCAT del detalle las mezclaria.
    SELECT s.id AS seccion_id,
           CONCAT(UPPER(n.codigo), ' ', g.nombre_display, ' ', s.nombre) AS seccion,
           CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS docente,
           COALESCE(sa.nombre, a.nombre) AS area,
           ca.id AS carga_id,
           (SELECT COUNT(*) FROM calificaciones cal
             WHERE cal.carga_id = ca.id AND cal.competencia_id = ct.id
               AND cal.periodo_id = @periodo) AS alumnos_con_nota
    FROM cargas_academicas ca
    INNER JOIN secciones s  ON s.id  = ca.seccion_id
    INNER JOIN grados    g  ON g.id  = s.grado_id
    INNER JOIN niveles   n  ON n.id  = g.nivel_id
    INNER JOIN usuarios  u  ON u.id  = ca.docente_id
    INNER JOIN personas  p  ON p.id  = u.persona_id
    LEFT  JOIN subareas  sa ON sa.id = ca.subarea_id
    INNER JOIN areas     a  ON a.id  = COALESCE(ca.area_id, sa.area_id)
    INNER JOIN competencias ct  ON ct.area_id IS NOT NULL
    INNER JOIN areas        atr ON atr.id       = ct.area_id
                               AND atr.tipo     = 'transversal'
                               AND atr.nivel_id = n.id
    LEFT  JOIN bloqueos_competencia bc
           ON bc.carga_id       = ca.id
          AND bc.competencia_id = ct.id
          AND bc.periodo_id     = @periodo
    WHERE ca.estado  = 'activa'
      AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
      AND a.tipo NOT IN ('transversal', 'tutoria')
      AND (
            s.es_unidocente = 0
         OR ca.id = (
                SELECT cad.id
                FROM cargas_academicas cad
                LEFT JOIN subareas sad ON sad.id = cad.subarea_id
                WHERE cad.seccion_id = ca.seccion_id
                  AND cad.estado     = 'activa'
                  AND COALESCE(cad.area_id, sad.area_id) = a.id
                ORDER BY COALESCE(sad.orden, 0), cad.id
                LIMIT 1
            )
      )
      AND bc.id IS NULL
      AND (@seccion_id  IS NULL OR s.id = @seccion_id)
      AND (@seccion_txt IS NULL
           OR CONCAT(g.nombre_display, ' ', s.nombre) LIKE CONCAT('%', @seccion_txt, '%'))
) d
GROUP BY d.docente
ORDER BY competencias_pendientes DESC, d.docente;


-- ============================================================================
-- BLOQUE 2 — DETALLE (una fila por carga + competencia transversal faltante)
-- ============================================================================
SET @periodo     := 2;
SET @seccion_id  := NULL;
SET @seccion_txt := NULL;

SELECT n.nombre                                 AS nivel,
       CONCAT(g.nombre_display, ' ', s.nombre)  AS seccion,
       IF(s.es_unidocente = 1, 'unidocente', 'polidocente') AS tipo_seccion,
       CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS docente,
       p.dni                                    AS docente_dni,
       p.telefono                               AS docente_telefono,
       COALESCE(a.nombre_boleta, a.nombre)      AS area,
       COALESCE(sa.nombre, '-')                 AS subarea,
       ca.id                                    AS carga_id,
       ct.codigo_minedu                         AS comp_codigo,
       ct.nombre_corto                          AS competencia_transversal,
       (SELECT COUNT(*) FROM criterios cr
         WHERE cr.carga_id      = ca.id
           AND cr.competencia_id = ct.id
           AND cr.periodo_id     = @periodo
           AND cr.eliminado_en  IS NULL)        AS criterios,
       (SELECT COUNT(*) FROM calificaciones cal
         WHERE cal.carga_id      = ca.id
           AND cal.competencia_id = ct.id
           AND cal.periodo_id     = @periodo)   AS alumnos_con_nota
FROM cargas_academicas ca
INNER JOIN secciones s  ON s.id  = ca.seccion_id
INNER JOIN grados    g  ON g.id  = s.grado_id
INNER JOIN niveles   n  ON n.id  = g.nivel_id
INNER JOIN usuarios  u  ON u.id  = ca.docente_id
INNER JOIN personas  p  ON p.id  = u.persona_id
LEFT  JOIN subareas  sa ON sa.id = ca.subarea_id
INNER JOIN areas     a  ON a.id  = COALESCE(ca.area_id, sa.area_id)
INNER JOIN competencias ct  ON ct.area_id IS NOT NULL
INNER JOIN areas        atr ON atr.id       = ct.area_id
                           AND atr.tipo     = 'transversal'
                           AND atr.nivel_id = n.id
LEFT  JOIN bloqueos_competencia bc
       ON bc.carga_id       = ca.id
      AND bc.competencia_id = ct.id
      AND bc.periodo_id     = @periodo
WHERE ca.estado  = 'activa'
  AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
  AND a.tipo NOT IN ('transversal', 'tutoria')
  AND (
        s.es_unidocente = 0
     OR ca.id = (
            SELECT cad.id
            FROM cargas_academicas cad
            LEFT JOIN subareas sad ON sad.id = cad.subarea_id
            WHERE cad.seccion_id = ca.seccion_id
              AND cad.estado     = 'activa'
              AND COALESCE(cad.area_id, sad.area_id) = a.id
            ORDER BY COALESCE(sad.orden, 0), cad.id
            LIMIT 1
        )
  )
  AND bc.id IS NULL
  AND (@seccion_id  IS NULL OR s.id = @seccion_id)
  AND (@seccion_txt IS NULL
       OR CONCAT(g.nombre_display, ' ', s.nombre) LIKE CONCAT('%', @seccion_txt, '%'))
ORDER BY n.id, g.numero, s.nombre, docente, area, ct.orden;


-- ============================================================================
-- BLOQUE 3 — RESUMEN POR SECCION (que le falta a cada tutor para poder cerrar)
-- ============================================================================
-- Incluye las secciones SIN pendientes (LEFT JOIN) para ver el avance completo,
-- junto al estado del cierre transversal vigente del tutor.
SET @periodo := 2;

SELECT n.nombre                                  AS nivel,
       CONCAT(g.nombre_display, ' ', s.nombre)   AS seccion,
       s.id                                      AS seccion_id,
       IF(s.es_unidocente = 1, 'unidocente', 'polidocente') AS tipo_seccion,
       CONCAT(pt.apellido_paterno, ' ', pt.apellido_materno, ', ', pt.nombres) AS tutor,
       COUNT(pend.carga_id)                      AS competencias_pendientes,
       COUNT(DISTINCT pend.carga_id)             AS cargas_pendientes,
       COUNT(DISTINCT pend.docente_id)           AS docentes_pendientes,
       GROUP_CONCAT(DISTINCT pend.docente
                    ORDER BY pend.docente SEPARATOR ' | ') AS docentes,
       IF(cie.id IS NULL, 'SIN CERRAR', CONCAT('cerrado ', cie.cerrado_en)) AS cierre_transversal
FROM secciones s
INNER JOIN grados  g  ON g.id  = s.grado_id
INNER JOIN niveles n  ON n.id  = g.nivel_id
LEFT  JOIN usuarios ut ON ut.id = s.tutor_id
LEFT  JOIN personas pt ON pt.id = ut.persona_id
LEFT  JOIN cierres_transversales cie
       ON cie.seccion_id = s.id
      AND cie.periodo_id = @periodo
      AND cie.anulado_en IS NULL
LEFT JOIN (
    SELECT ca.seccion_id,
           ca.id          AS carga_id,
           ca.docente_id,
           CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS docente
    FROM cargas_academicas ca
    INNER JOIN secciones s2 ON s2.id = ca.seccion_id
    INNER JOIN grados    g2 ON g2.id = s2.grado_id
    INNER JOIN niveles   n2 ON n2.id = g2.nivel_id
    INNER JOIN usuarios  u  ON u.id  = ca.docente_id
    INNER JOIN personas  p  ON p.id  = u.persona_id
    LEFT  JOIN subareas  sa ON sa.id = ca.subarea_id
    INNER JOIN areas     a  ON a.id  = COALESCE(ca.area_id, sa.area_id)
    INNER JOIN competencias ct  ON ct.area_id IS NOT NULL
    INNER JOIN areas        atr ON atr.id       = ct.area_id
                               AND atr.tipo     = 'transversal'
                               AND atr.nivel_id = n2.id
    LEFT  JOIN bloqueos_competencia bc
           ON bc.carga_id       = ca.id
          AND bc.competencia_id = ct.id
          AND bc.periodo_id     = @periodo
    WHERE ca.estado  = 'activa'
      AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
      AND a.tipo NOT IN ('transversal', 'tutoria')
      AND (
            s2.es_unidocente = 0
         OR ca.id = (
                SELECT cad.id
                FROM cargas_academicas cad
                LEFT JOIN subareas sad ON sad.id = cad.subarea_id
                WHERE cad.seccion_id = ca.seccion_id
                  AND cad.estado     = 'activa'
                  AND COALESCE(cad.area_id, sad.area_id) = a.id
                ORDER BY COALESCE(sad.orden, 0), cad.id
                LIMIT 1
            )
      )
      AND bc.id IS NULL
) pend ON pend.seccion_id = s.id
WHERE s.anio_id = (SELECT anio_id FROM periodos WHERE id = @periodo)
GROUP BY s.id, n.nombre, g.nombre_display, g.numero, s.nombre, s.es_unidocente,
         pt.apellido_paterno, pt.apellido_materno, pt.nombres, cie.id, cie.cerrado_en, n.id
ORDER BY competencias_pendientes DESC, n.id, g.numero, s.nombre;
