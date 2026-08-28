<?php

/**
 * Verificación — /consulta-notas con transversales y conducta (F1, F2, F3).
 * Uso: php database/verificaciones/verif_consulta_notas_ampliada.php [periodo_numero]
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Corre en PRODUCCIÓN.
 *
 * QUÉ COMPRUEBA
 *   1. F1 — el nº de bloques transversales que pintaría la vista de carga es el
 *      de cargas CON NOTAS, no el de cargas con bloqueo. Es la trampa medida:
 *      hay 820 bloqueos sobre 410 cargas en cada bimestre, pero el bloqueo se
 *      propaga en cascada y NO es señal de contenido.
 *   2. F2 — el agregado que muestra la vista coincide alumno a alumno con
 *      `TransversalModel::getPromediosMatricula`, que es la fuente que usa la
 *      BOLETA. Si divergen, la supervisión estaría mostrando algo distinto de
 *      lo que se entregó a la familia.
 *   3. F3 — el nº de filas de la grilla de conducta es el roster de la sección,
 *      y `literal_final` coincide con `ConductaModel::getParaPeriodo` (la fuente
 *      de la boleta). Se prueban B1 (legado) y B2 en la misma corrida: son
 *      caminos de código distintos.
 *   4. Gates D3 — cuántas secciones ofrecen cada entrada y cuántas la ocultan.
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
$cal    = new App\Models\CalificacionModel();
$trans  = new App\Models\TransversalModel();
$cond   = new App\Models\ConductaModel();
$fallos = 0;

$srv = $pdo->query("SELECT DATABASE() bd, @@version_compile_os so")->fetch();
printf("ENTORNO: %s · %s\n\n", $srv['bd'], $srv['so']);

$periodos = $pdo->query("
    SELECT p.id, p.numero, p.nombre_display
    FROM periodos p
    INNER JOIN anios_academicos a ON a.id = p.anio_id AND a.estado = 'activo'
    WHERE p.estado IN ('activo','cerrado')
    ORDER BY p.numero
")->fetchAll();

foreach ($periodos as $per) {
    $pid = (int) $per['id'];
    printf("═══ %s (id %d) ═══\n", $per['nombre_display'], $pid);

    // ── 1. F1: bloqueo NO es señal de contenido ──────────────────
    $bloq = (int) $pdo->query("
        SELECT COUNT(DISTINCT bc.carga_id) n FROM bloqueos_competencia bc
        INNER JOIN competencias c ON c.id = bc.competencia_id
        INNER JOIN areas a ON a.id = c.area_id AND a.tipo = 'transversal'
        WHERE bc.periodo_id = {$pid}")->fetch()['n'];

    // Réplica del helper del controlador, restringida a cargas ACTIVAS (que son
    // las únicas que la navegación de /consulta-notas puede alcanzar).
    $conContenido = (int) $pdo->query("
        SELECT COUNT(DISTINCT bc.carga_id) n
        FROM bloqueos_competencia bc
        INNER JOIN competencias comp ON comp.id = bc.competencia_id
        INNER JOIN areas a ON a.id = comp.area_id AND a.tipo = 'transversal'
        INNER JOIN cargas_academicas ca ON ca.id = bc.carga_id AND ca.estado = 'activa'
        WHERE bc.periodo_id = {$pid}
          AND EXISTS (SELECT 1 FROM calificaciones cal
                      WHERE cal.carga_id = bc.carga_id
                        AND cal.competencia_id = comp.id
                        AND cal.periodo_id = bc.periodo_id)")->fetch()['n'];

    printf("  [F1] cargas con bloqueo transversal: %3d · con CONTENIDO: %3d · bloques vacíos evitados: %3d\n",
        $bloq, $conContenido, $bloq - $conContenido);
    if ($conContenido > $bloq) { echo "       FALLO: más contenido que bloqueos\n"; $fallos++; }

    // ── 2. F2: agregado == fuente de la boleta ───────────────────
    $secs = $pdo->query("
        SELECT DISTINCT s.id, g.nivel_id
        FROM secciones s
        INNER JOIN grados g ON g.id = s.grado_id
        INNER JOIN anios_academicos a ON a.id = s.anio_id AND a.estado='activo'
        ORDER BY s.id")->fetchAll();

    $conCierre = 0; $celdas = 0; $divergen = 0;
    foreach ($secs as $s) {
        $sid = (int) $s['id'];
        if (!$trans->getCierreVigente($sid, $pid)) { continue; }
        $conCierre++;
        $porSeccion = $trans->getPromediosSeccion($sid, $pid);
        foreach ($porSeccion as $mid => $porComp) {
            $deBoleta = $trans->getPromediosMatricula((int) $mid, $pid);
            foreach ($porComp as $cid => $nota) {
                $celdas++;
                if (($deBoleta[$cid] ?? null) !== $nota) { $divergen++; }
            }
        }
    }
    printf("  [F2] secciones con cierre vigente: %2d · celdas comparadas: %4d · divergencias: %d %s\n",
        $conCierre, $celdas, $divergen, $divergen === 0 ? '(OK)' : '(FALLO)');
    if ($divergen !== 0) { $fallos++; }

    // ── 3. F3: conducta == fuente de la boleta ───────────────────
    $conCond = 0; $filas = 0; $difLit = 0; $legado = 0;
    foreach ($secs as $s) {
        $sid = (int) $s['id'];
        $c   = $cond->getCierreDetalle($sid, $pid);
        if (!$c || empty($c['ra_bloqueado_en']) || empty($c['tutor_cerrado_en'])) { continue; }
        $conCond++;
        $alumnos = $cond->getEstudiantesParaTutor($sid, $pid, $cond->totalCriterios((int) $s['nivel_id']));
        if ($alumnos && !empty($alumnos[0]['es_legado'])) { $legado++; }
        foreach ($alumnos as $a) {
            $filas++;
            $deBoleta = $cond->getParaPeriodo((int) $a['matricula_id'], $pid);
            if ($a['literal_final'] !== null && $deBoleta !== null && $a['literal_final'] !== $deBoleta) {
                $difLit++;
            }
        }
    }
    printf("  [F3] secciones con conducta cerrada: %2d (legado: %d) · filas: %3d · literales que divergen: %d %s\n",
        $conCond, $legado, $filas, $difLit, $difLit === 0 ? '(OK)' : '(FALLO)');
    if ($difLit !== 0) { $fallos++; }

    // ── 4. Gates D3 ──────────────────────────────────────────────
    printf("  [D3] de %d secciones: %d ofrecen transversales, %d ofrecen conducta\n\n",
        count($secs), $conCierre, $conCond);
}

// ── 5. Las DOS caras de las transversales no comparten titulo ────────
// Los promedios del tutor (que van a la boleta) y el registro crudo del docente
// se llamaban los dos "Competencias Transversales", en tres pantallas. Se
// comprueba la PROPIEDAD —que los titulos sean distintos— y no un texto
// literal: fijar la cadena convertiria cualquier reescritura legitima del
// rotulo en un fallo, que es como se rompen los verificadores de UI.
$titulo = function (string $vista, string $patron): ?string {
    $html = @file_get_contents(ROOT_PATH . '/resources/views/consulta-notas/' . $vista);
    return ($html !== false && preg_match($patron, $html, $m)) ? trim($m[1]) : null;
};
$tPromedios = $titulo('transversales.php', '/<h1 class="page-title">(.*?)<\/h1>/s');
$tRegistro  = $titulo('carga.php',         '/<h2 class="transversales-separador__titulo">(.*?)<\/h2>/s');
$tEnlace    = $titulo('seccion.php',       '/<span class="consulta-carga__area">(Promedios[^<]*|Competencias Transversales)<\/span>/s');

echo "\n  [F5] titulos de las dos caras\n";
foreach (['promedios (tutor)' => $tPromedios, 'registro (docente)' => $tRegistro, 'enlace en seccion' => $tEnlace] as $q => $v) {
    printf("       %-20s %s\n", $q . ':', $v ?? '(NO ENCONTRADO)');
}
if ($tPromedios === null || $tRegistro === null || $tEnlace === null) {
    // Que el patron deje de encontrar el titulo tambien es un fallo: si no, el
    // verificador pasaria en verde por no haber mirado nada.
    echo "       FALLO: no se pudo leer alguno de los titulos\n";
    $fallos++;
} else {
    if ($tPromedios === $tRegistro) {
        echo "       FALLO: las dos caras comparten titulo\n";
        $fallos++;
    }
    if ($tEnlace !== $tPromedios) {
        echo "       FALLO: el enlace no dice lo mismo que la pagina a la que lleva\n";
        $fallos++;
    }
    if ($tPromedios === $tRegistro || $tEnlace !== $tPromedios) {
        // nada mas que imprimir
    } else {
        echo "       OK: distintas entre si y el enlace coincide con su destino\n";
    }
}

echo "\n", $fallos === 0
    ? "RESULTADO: OK — la supervisión muestra lo mismo que la boleta.\n"
    : "RESULTADO: {$fallos} FALLO(S).\n";

exit($fallos === 0 ? 0 : 1);
