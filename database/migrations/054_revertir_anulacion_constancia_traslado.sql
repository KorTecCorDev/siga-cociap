-- ════════════════════════════════════════════════════════════════════
-- Migración 054: devolver a VIGENTE la constancia de traslado N° 052-2026
--                (4.º A de secundaria, traslado a IEP LAS AMERICAS SCHOOL)
-- ════════════════════════════════════════════════════════════════════
-- QUÉ CORRIGE: la constancia quedó marcada `anulado` el 07/07/2026, 34 minutos
--   después de emitirse, con la intención de poder imprimir la boleta del último
--   bimestre que cursó la estudiante. El traslado SÍ se consumó, así que el
--   libro oficial de constancias está diciendo lo contrario de lo que ocurrió.
--
-- POR QUÉ LA ANULACIÓN NO PODÍA CONSEGUIR SU PROPÓSITO (verificado en el
--   código el 22/08/2026): el flujo de la boleta NO consulta la tabla
--   `traslados` en ningún punto — 0 referencias en `BoletaModel`,
--   `BoletaPublicaModel` y `Boleta\BoletaController`. Lo que decide el trato de
--   la boleta es la pareja `matriculas.estado` + `matriculas.tipo`, que esta
--   corrección NO toca. Y esa pareja ya está en su mejor caso:
--   `optsBoletaGestion()` le da al trasladado consumado su ÚLTIMA BOLETA
--   OFICIAL de archivo — estructura anual completa, CON firma, SIN QR (su token
--   está muerto), ignorando la compuerta de publicación 044 por ser un documento
--   administrativo de staff. Un desactivado por cualquier otro motivo caería en
--   BORRADOR forzado, sin firma: es decir, si la anulación hubiera logrado lo
--   que se buscaba, la boleta habría salido PEOR.
--   Comprobado además ejercitando `BoletaModel::armar(..., 'archivo', true)`:
--   la boleta del I Bimestre se arma con sus notas (25 competencias).
--
-- CAMBIO: `traslados.estado = 'vigente'` y los tres campos de la anulación a
--   NULL (`anulado_motivo`, `anulado_en`, `anulado_por`). La fila queda
--   exactamente como una constancia que nunca se anuló. Decisión del usuario
--   del 22/08/2026: anularla fue un error y el registro no debe conservar su
--   huella.
--
-- NO SE TOCA — y es lo importante de esta migración:
--   * `matriculas` 4.º A (el traslado es REAL): sigue `tipo = 'trasladado'`,
--     `estado = 'desactivado'`, con su `motivo_estado` y sus `observaciones`.
--     La baja fue correcta; lo único erróneo fue anular la constancia.
--   * `calificaciones` (25 notas del I Bimestre), `bloqueos_competencia`,
--     `inasistencias` (1 fila) ni `conducta`.
--   * `orden_merito_snapshot`: la estudiante figura en el I Bimestre (puesto 42
--     de grado, 26 de sección). B1 está PUBLICADO → el candado 046 lo protege y
--     esta migración no lo roza. Tampoco cambia el roster: `ROSTER_MERITO` mira
--     `matriculas`, no `traslados`.
--   * `boletas_publicas`: su fila del I Bimestre queda con `activa = 0`. Es lo
--     CORRECTO por diseño — al trasladado se le omite el QR a propósito porque
--     el token está muerto, y reactivarla publicaría un enlace que lleva a "no
--     encontrado". La entrega a la familia va por impresión desde gestión.
--   * `correlativo`, `numero_constancia` ni ningún dato del destino.
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * Ancla por DNI del estudiante (`78313569`) + `correlativo = 52` + año
--     académico ACTIVO. NUNCA por `traslados.id` ni `matricula_id`: en esta copia
--     son 4 y 307, y no tienen por qué coincidir en producción. Es la regla del
--     repo desde el endurecimiento de la 048.
--   * El DNI y el correlativo son ASCII puro. **NO se ancla por
--     `numero_constancia`**, que contiene «N°» (U+00B0): un literal no-ASCII
--     resuelve 0 filas en silencio si el cliente no manda el mismo UTF-8. Es el
--     fallo que ya ocurrió al ensayar la migración 050.
--   * GUARD DURO 1 — CORRELATIVO: una constancia anulada LIBERA su número
--     (`TrasladoModel`, cabecera) y `correlativoDisponible()` permite reusarlo a
--     mano. Si alguien emitió otra constancia VIGENTE con el correlativo 52,
--     reactivar esta crearía DOS documentos oficiales con el mismo número. El
--     UPDATE no toca nada en ese caso. En la copia local el 52 no se reutilizó
--     (las siguientes tomaron 53 y 54), pero eso HAY QUE VERIFICARLO EN PROD:
--     el PASO 1 lo mide.
--   * GUARD DURO 2 — COHERENCIA: solo actúa si la matrícula sigue
--     `desactivado` + `trasladado`. Si el traslado se hubiera revertido, una
--     constancia vigente contradiría a la matrícula.
--   * Idempotente: exige `estado = 'anulado'`, así que una segunda corrida
--     afecta 0 filas.
--   * Reversible con el PASO 4.
--
-- ⚠️ CORRER EL PASO 1 PRIMERO. Debe devolver EXACTAMENTE 1 fila con veredicto
--   `PUEDE_REACTIVARSE`. Si devuelve 0 filas, DETENERSE: o el anclaje no
--   resolvió, o el DNI no es el de producción. Son casos distintos y el PASO 1
--   los distingue con sus columnas.
--
-- ★ VÍA RECOMENDADA — NO pegar este archivo en phpMyAdmin:
--     cd ~/domains/sigacociap.net/public_html
--     php database/aplicar_054_revertir_anulacion.php              # ensayo + ROLLBACK
--     php database/aplicar_054_revertir_anulacion.php --confirmar  # aplica
--   El script hace lo mismo que este .sql pero **aborta de verdad** si el
--   veredicto no es PUEDE_REACTIVARSE. Pegar este archivo entero en phpMyAdmin
--   ejecuta el PASO 2 AUNQUE el PASO 1 salga en rojo — son sentencias sueltas,
--   igual que pasó con la 048.
--
-- Ejecutar en cualquier momento; no depende de ninguna otra migración.
-- La 053 está RESERVADA para `cambio_seccion` (ver docs/modulos/cambio-seccion.md).
-- Conexión utf8mb4. Ver docs/ESTADO.md.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PASO 0 — HUELLA DEL SERVIDOR ────────────────────────────────────
-- Capturar SIEMPRE esta salida junto a la del PASO 3: es lo ÚNICO que prueba
-- contra qué entorno se ejecutó. La salida del PASO 1 es casi idéntica en local
-- y en prod, así que por sí sola no identifica nada — lección de la 048.
--   Local: `siga_cociap` · `root@localhost` · Win64 · MariaDB 10.4.x
--   Prod:  `u761410128_siga_cociap` · Linux · MariaDB 11.8.x · Hostinger
SELECT DATABASE() AS db, USER() AS usr, @@hostname AS host,
       VERSION() AS ver, @@version_compile_os AS so, @@datadir AS datadir;

-- ⚠️ phpMyAdmin IGNORA `USE` y reselecciona la base según la página. Si esta
--   consulta no devuelve la base esperada, entrar a la base desde el panel
--   izquierdo antes de seguir, o todo fallará con «#1109 Tabla desconocida».

-- ── PASO 1 — VERIFICACIÓN (solo lectura). 1 fila PUEDE_REACTIVARSE ──
SELECT
    t.id                       AS traslado_id,
    p.dni,
    CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS estudiante,
    t.correlativo,
    t.numero_constancia,
    t.estado                   AS estado_actual,
    t.fecha_constancia,
    t.ie_destino_nombre,
    t.veces_impresa,
    t.anulado_en,
    m.id                       AS matricula_id,
    m.estado                   AS matricula_estado,   -- debe ser 'desactivado'
    m.tipo                     AS matricula_tipo,     -- debe ser 'trasladado'
    viv.n_vigentes             AS otras_vigentes_con_correlativo_52,
    CASE
        WHEN t.estado = 'vigente'                THEN 'YA_VIGENTE'
        WHEN COALESCE(viv.n_vigentes, 0) > 0     THEN 'NO_TOCAR_CORRELATIVO_EN_USO'
        WHEN m.estado <> 'desactivado'
          OR m.tipo   <> 'trasladado'            THEN 'NO_TOCAR_MATRICULA_NO_TRASLADADA'
        ELSE 'PUEDE_REACTIVARSE'
    END                        AS veredicto
FROM traslados t
INNER JOIN matriculas        m ON m.id = t.matricula_id
INNER JOIN estudiantes       e ON e.id = m.estudiante_id
INNER JOIN personas          p ON p.id = e.persona_id
INNER JOIN anios_academicos  a ON a.id = t.anio_id AND a.estado = 'activo'
LEFT  JOIN (
        SELECT anio_id, correlativo, COUNT(*) AS n_vigentes
        FROM traslados
        WHERE estado = 'vigente'
        GROUP BY anio_id, correlativo
     ) viv ON viv.anio_id = t.anio_id AND viv.correlativo = t.correlativo
WHERE p.dni = '78313569'
  AND t.correlativo = 52;

-- ── PASO 1.b — CONTROL: el libro de constancias del año, entero. Sirve para
--    ver de un vistazo que ningún otro número queda duplicado. ──
SELECT t.id, t.correlativo, t.numero_constancia, t.estado,
       t.fecha_constancia, t.ie_destino_nombre
FROM traslados t
INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
ORDER BY t.correlativo, t.id;

-- ── PASO 2 — CAMBIO ─────────────────────────────────────────────────
-- Los dos guards duros viajan en el WHERE: correlativo libre entre vigentes y
-- matrícula todavía trasladada. La derivada `viv` NO es correlacionada, así que
-- MariaDB la materializa y el UPDATE sobre la misma tabla es legal.
--
-- ⚠️ `SELECT ROW_COUNT()` DEVUELVE 0 EN phpMyAdmin y NO significa que el UPDATE
--   fallara: ejecuta las sentencias por separado y el contador ya no refleja al
--   UPDATE. La cifra buena es la que informa el propio UPDATE («1 fila
--   afectada») y quien manda es el PASO 3. Pasó tal cual con la 051.
UPDATE traslados t
INNER JOIN matriculas        m ON m.id = t.matricula_id
INNER JOIN estudiantes       e ON e.id = m.estudiante_id
INNER JOIN personas          p ON p.id = e.persona_id
INNER JOIN anios_academicos  a ON a.id = t.anio_id AND a.estado = 'activo'
LEFT  JOIN (
        SELECT anio_id, correlativo, COUNT(*) AS n_vigentes
        FROM traslados
        WHERE estado = 'vigente'
        GROUP BY anio_id, correlativo
     ) viv ON viv.anio_id = t.anio_id AND viv.correlativo = t.correlativo
SET t.estado         = 'vigente',
    t.anulado_motivo = NULL,
    t.anulado_en     = NULL,
    t.anulado_por    = NULL
WHERE p.dni = '78313569'
  AND t.correlativo = 52
  AND t.estado      = 'anulado'
  AND COALESCE(viv.n_vigentes, 0) = 0
  AND m.estado      = 'desactivado'
  AND m.tipo        = 'trasladado';

-- ── PASO 3 — VERIFICACIÓN POSTERIOR (correr en CONEXIÓN NUEVA) ──────
-- 3.a  La constancia quedó vigente y SIN rastro de anulación. Los tres campos
--      `anulado_*` deben ser NULL y todo lo demás estar intacto:
SELECT t.id, t.correlativo, t.numero_constancia,
       t.estado,                                    -- debe ser 'vigente'
       t.anulado_motivo, t.anulado_en, t.anulado_por,  -- los tres NULL
       t.fecha_constancia, t.ie_destino_nombre,     -- SIN CAMBIOS
       t.ie_destino_codigo_modular, t.veces_impresa -- SIN CAMBIOS
FROM traslados t
INNER JOIN matriculas       m ON m.id = t.matricula_id
INNER JOIN estudiantes      e ON e.id = m.estudiante_id
INNER JOIN personas         p ON p.id = e.persona_id
INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
WHERE p.dni = '78313569' AND t.correlativo = 52;

-- 3.b  CONTROL DURO — la matrícula NO se movió. Debe seguir 'desactivado' +
--      'trasladado', con su motivo y sus observaciones originales:
SELECT m.id, m.estado, m.tipo, m.tipo_anterior, m.motivo_estado, m.updated_at
FROM matriculas m
INNER JOIN estudiantes e ON e.id = m.estudiante_id
INNER JOIN personas    p ON p.id = e.persona_id
WHERE p.dni = '78313569';

-- 3.c  CONTROL — ningún correlativo del año quedó duplicado entre vigentes.
--      Debe devolver 0 filas:
SELECT t.anio_id, t.correlativo, COUNT(*) AS n_vigentes
FROM traslados t
INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
WHERE t.estado = 'vigente'
GROUP BY t.anio_id, t.correlativo
HAVING COUNT(*) > 1;

-- 3.d  CONTROL — el reparto de estados del libro. Debe haber UNA constancia
--      vigente más que antes y NINGUNA anulada (si la 052 era la única):
SELECT t.estado, COUNT(*) AS n
FROM traslados t
INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
GROUP BY t.estado;

-- ── PASO 4 — REVERSIÓN (solo si hiciera falta) ──────────────────────
-- Devuelve la constancia a 'anulado' con su motivo y su fecha originales.
-- El `anulado_por` era el usuario #1; ajústalo si en producción difiere.
--
--   UPDATE traslados t
--   INNER JOIN matriculas       m ON m.id = t.matricula_id
--   INNER JOIN estudiantes      e ON e.id = m.estudiante_id
--   INNER JOIN personas         p ON p.id = e.persona_id
--   INNER JOIN anios_academicos a ON a.id = t.anio_id AND a.estado = 'activo'
--   SET t.estado         = 'anulado',
--       t.anulado_motivo = 'Se anuló el traslado para poder imprimir boleta de notas del último bimestre que cursó el estudiante.',
--       t.anulado_en     = '2026-07-07 10:23:48',
--       t.anulado_por    = 1
--   WHERE p.dni = '78313569' AND t.correlativo = 52 AND t.estado = 'vigente';
