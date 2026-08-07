<?php

/**
 * Verificación — bloqueos transversales fantasma del cierre forzado (F1 + F2).
 * Uso: php database/verificaciones/verif_transversales_fantasma.php [periodo_numero]
 *      (por defecto el II Bimestre, que es el afectado)
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Se puede correr en
 * PRODUCCIÓN (por eso NO lleva el guard de secretos que sí tienen los que escriben).
 *
 * QUÉ COMPRUEBA
 *   1. CLASIFICACIÓN de los bloqueos transversales `origen='cierre'` del bimestre
 *      en A (carga TOE) / B (carga no dueña de unidocente) / C (olvido real).
 *      Tras el fix + la migración 051, A y B deben ser 0. Una fila en C nunca fue
 *      un fantasma: es un docente que de verdad no bloqueó.
 *   2. GUARD: ninguna de esas cargas tiene notas ni criterios transversales
 *      colgando. Es la condición que hace seguro borrar sus bloqueos.
 *   3. EQUIVALENCIA — el conjunto de cargas al que el CIERRE FORZADO adjuntaría
 *      transversales debe ser IDÉNTICO al conjunto al que se las adjunta el
 *      FORMULARIO del docente. Es la invariante que se rompió: misma regla, dos
 *      implementaciones. Este bloque es el que detecta si vuelven a divergir.
 *   4. Lo que NO se debe mover: bloqueos `origen='docente'` y cierres
 *      transversales vigentes de la sección.
 *
 * CONTEXTO. `AnioAcademicoModel::bloquearCompetenciasPendientes` (bloque 2)
 * recorría TODAS las cargas activas sin las dos exclusiones que el formulario
 * (`CalificacionController::calificaciones`) sí aplica. En B2 eso creó 130
 * bloqueos en 65 cargas que ningún docente podía bloquear, y `/admin/control`
 * los reportaba como olvido de 23 docentes. Olvidos reales medidos: CERO.
 * Corregido el 06/08/2026; limpieza de lo ya creado en la migración 051.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = ['Core\\' => CORE_PATH . '/', 'App\\Models\\' => APP_PATH . '/Models/'];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});
require_once CONFIG_PATH . '/app.php';
require_once APP_PATH . '/Helpers/helpers.php';

$pdo    = Core\Database::connect();
$fallos = 0;

$numero = isset($argv[1]) ? (int) $argv[1] : 2;

// Fragmento SQL de la regla de "carga dueña" — la MISMA que aplican los cuatro
// sitios canónicos (formulario, gate del tutor, cargaDuenaTransversales y el
// cierre forzado). Se repite aquí a propósito: si el código divergiera de esta
// definición, el bloque 3 lo delata.
$noDuena = "(s.es_unidocente = 1 AND ca.id <> (
    SELECT cad.id FROM cargas_academicas cad
    LEFT JOIN subareas sad ON sad.id = cad.subarea_id
    WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
      AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
    ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1))";

// ── Entorno y anclaje ────────────────────────────────────────────
$srv = $pdo->query("SELECT DATABASE() bd, USER() usr, @@version_compile_os so, @@version v")->fetch();
printf("ENTORNO: %s · %s · %s · MariaDB %s\n", $srv['bd'], $srv['usr'], $srv['so'], $srv['v']);

$st = $pdo->prepare("
    SELECT p.id, p.nombre_display, p.estado
    FROM periodos p
    INNER JOIN anios_academicos a ON a.id = p.anio_id AND a.estado = 'activo'
    WHERE p.numero = ?
");
$st->execute([$numero]);
$periodo = $st->fetch();

if (!$periodo) {
    echo "ABORTA: no se resolvió el periodo número {$numero} del año activo.\n";
    exit(1);
}
$pid = (int) $periodo['id'];
printf("PERIODO: %s (id %d, estado '%s')\n\n", $periodo['nombre_display'], $pid, $periodo['estado']);

// ── 1. Clasificación A/B/C ───────────────────────────────────────
echo "=== 1. Bloqueos transversales del CIERRE FORZADO, clasificados ===\n";
$st = $pdo->prepare("
    SELECT
      CASE
        WHEN ar.tipo = 'tutoria' THEN 'A_TOE'
        WHEN {$noDuena} THEN 'B_NO_DUENA'
        ELSE 'C_OLVIDO_REAL'
      END AS clase,
      COUNT(*)                      AS n_bloqueos,
      COUNT(DISTINCT bc.carga_id)   AS n_cargas,
      COUNT(DISTINCT ca.docente_id) AS n_docentes
    FROM bloqueos_competencia bc
    INNER JOIN competencias c ON c.id = bc.competencia_id
    INNER JOIN areas a  ON a.id = c.area_id AND a.tipo = 'transversal'
    INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id
    INNER JOIN secciones s ON s.id = ca.seccion_id
    LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
    LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
    WHERE bc.periodo_id = ? AND bc.origen = 'cierre'
    GROUP BY clase
");
$st->execute([$pid]);
$clases = ['A_TOE' => 0, 'B_NO_DUENA' => 0, 'C_OLVIDO_REAL' => 0];
foreach ($st->fetchAll() as $f) {
    $clases[$f['clase']] = (int) $f['n_bloqueos'];
    printf("  %-14s %4d bloqueo(s) · %3d carga(s) · %2d docente(s)\n",
        $f['clase'], $f['n_bloqueos'], $f['n_cargas'], $f['n_docentes']);
}
if ($clases['A_TOE'] === 0 && $clases['B_NO_DUENA'] === 0) {
    echo "  OK — 0 fantasmas (ni cargas TOE ni no-dueñas con bloqueo forzado).\n";
} else {
    printf("  FALLO — quedan %d fantasma(s): el fix no está aplicado o falta la migración 051.\n",
        $clases['A_TOE'] + $clases['B_NO_DUENA']);
    $fallos++;
}
if ($clases['C_OLVIDO_REAL'] > 0) {
    printf("  NOTA: %d bloqueo(s) en C son OLVIDOS REALES y NO se tocan (son legítimos).\n",
        $clases['C_OLVIDO_REAL']);
}

// ── 2. Guard: notas y criterios colgando ─────────────────────────
echo "\n=== 2. Guard — notas/criterios transversales en cargas TOE o no-dueñas ===\n";
foreach ([
    'notas'     => "calificaciones cal",
    'criterios' => "criterios cal",
] as $etiqueta => $from) {
    $extra = $etiqueta === 'criterios' ? "AND cal.eliminado_en IS NULL" : "";
    $st = $pdo->prepare("
        SELECT COUNT(*) AS n
        FROM {$from}
        INNER JOIN competencias c ON c.id = cal.competencia_id
        INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
        INNER JOIN cargas_academicas ca ON ca.id = cal.carga_id
        INNER JOIN secciones s ON s.id = ca.seccion_id
        LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
        LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
        WHERE cal.periodo_id = ? {$extra}
          AND (ar.tipo = 'tutoria' OR {$noDuena})
    ");
    $st->execute([$pid]);
    $n = (int) $st->fetch()['n'];
    printf("  %-10s %d %s\n", $etiqueta, $n, $n === 0 ? '(OK)' : '(FALLO: borrar sus bloqueos dejaría datos huérfanos)');
    if ($n !== 0) { $fallos++; }
}

// ── 3. Equivalencia cierre forzado ↔ formulario del docente ──────
echo "\n=== 3. Equivalencia — universo del CIERRE vs universo del FORMULARIO ===\n";
$st = $pdo->prepare("
    SELECT
      SUM(CASE WHEN ar.tipo = 'tutoria' OR {$noDuena} THEN 0 ELSE 1 END) AS incluidas,
      SUM(CASE WHEN ar.tipo = 'tutoria' THEN 1 ELSE 0 END)               AS excluidas_toe,
      SUM(CASE WHEN ar.tipo <> 'tutoria' AND {$noDuena} THEN 1 ELSE 0 END) AS excluidas_no_duena,
      COUNT(*) AS total_activas
    FROM cargas_academicas ca
    INNER JOIN secciones s ON s.id = ca.seccion_id
    LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
    LEFT  JOIN areas ar    ON ar.id = COALESCE(ca.area_id, sa.area_id)
    WHERE ca.estado = 'activa'
      AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
");
$st->execute([$pid]);
$u = $st->fetch();
printf("  cargas activas: %d → formulario adjunta a %d (excluye %d TOE + %d no-dueñas)\n",
    $u['total_activas'], $u['incluidas'], $u['excluidas_toe'], $u['excluidas_no_duena']);

// Cargas que el CIERRE FORZADO alcanzaría hoy, replicando su SELECT real.
$st = $pdo->prepare("
    SELECT COUNT(DISTINCT ca.id) AS n
    FROM cargas_academicas ca
    INNER JOIN secciones s ON s.id = ca.seccion_id
    INNER JOIN grados    g ON g.id = s.grado_id
    INNER JOIN areas     a ON a.tipo = 'transversal' AND a.nivel_id = g.nivel_id
    LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
    LEFT  JOIN areas    ar ON ar.id = COALESCE(ca.area_id, sa.area_id)
    WHERE ca.estado = 'activa'
      AND ca.anio_id = (SELECT anio_id FROM periodos WHERE id = ?)
      AND (ar.tipo IS NULL OR ar.tipo <> 'tutoria')
      AND (s.es_unidocente = 0 OR ca.id = (
            SELECT cad.id FROM cargas_academicas cad
            LEFT JOIN subareas sad ON sad.id = cad.subarea_id
            WHERE cad.seccion_id = ca.seccion_id AND cad.estado = 'activa'
              AND COALESCE(cad.area_id, sad.area_id) = COALESCE(ca.area_id, sa.area_id)
            ORDER BY COALESCE(sad.orden, 0), cad.id LIMIT 1))
");
$st->execute([$pid]);
$cierre = (int) $st->fetch()['n'];
printf("  el cierre forzado alcanzaría a %d carga(s)\n", $cierre);
if ($cierre === (int) $u['incluidas']) {
    echo "  OK — los dos universos coinciden.\n";
} else {
    printf("  FALLO — divergen (%d vs %d): la regla volvió a escribirse en dos formas.\n",
        $cierre, $u['incluidas']);
    $fallos++;
}

// ── 4. Lo que no se debe mover ───────────────────────────────────
echo "\n=== 4. Registros que la limpieza NO debe tocar ===\n";
$st = $pdo->prepare("
    SELECT
      (SELECT COUNT(*) FROM bloqueos_competencia bc
         INNER JOIN competencias c ON c.id = bc.competencia_id
         INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
        WHERE bc.periodo_id = ? AND bc.origen = 'docente')      AS transv_docente,
      (SELECT COUNT(*) FROM cierres_transversales
        WHERE periodo_id = ? AND anulado_en IS NULL)            AS cierres_vigentes,
      (SELECT COUNT(*) FROM cierres_transversales
        WHERE periodo_id = ?)                                   AS cierres_totales
");
$st->execute([$pid, $pid, $pid]);
$n = $st->fetch();
printf("  bloqueos transversales de docente: %d (deben quedar intactos)\n", $n['transv_docente']);
printf("  cierres transversales: %d vigente(s) de %d total(es)\n",
    $n['cierres_vigentes'], $n['cierres_totales']);

// Notas transversales sin su bloqueo: el "estado fantasma" inverso.
$st = $pdo->prepare("
    SELECT COUNT(*) AS n
    FROM calificaciones cal
    INNER JOIN competencias c ON c.id = cal.competencia_id
    INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
    LEFT JOIN bloqueos_competencia bc
           ON  bc.carga_id       = cal.carga_id
           AND bc.competencia_id = cal.competencia_id
           AND bc.periodo_id     = cal.periodo_id
    WHERE cal.periodo_id = ? AND bc.id IS NULL
");
$st->execute([$pid]);
$huerfanas = (int) $st->fetch()['n'];
printf("  notas transversales SIN bloqueo: %d %s\n", $huerfanas,
    $huerfanas === 0 ? '(OK)' : '(FALLO: quedarían fuera de la boleta)');
if ($huerfanas !== 0) { $fallos++; }

echo "\n" . ($fallos === 0
    ? "RESULTADO: OK — sin bloqueos fantasma y los dos universos coinciden.\n"
    : "RESULTADO: {$fallos} FALLO(S).\n");

exit($fallos === 0 ? 0 : 1);
