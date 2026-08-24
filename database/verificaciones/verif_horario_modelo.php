<?php

/**
 * Verificación de la FASE 1B — extracción de HorarioModel.
 *
 * CONTRASTE: arma la grilla con el código NUEVO (HorarioModel::armarGrilla) y
 * con el código VIEJO (extraído en caliente de `git HEAD`, antes del refactor)
 * para TODOS los docentes con horario, y compara los resultados.
 * Si la extracción cambió algo, aquí se ve. Solo lectura.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('CONFIG_PATH', ROOT_PATH . '/config');

require ROOT_PATH . '/app/Helpers/helpers.php';

spl_autoload_register(function (string $c): void {
    $map = ['Core\\' => '/core/', 'App\\Models\\' => '/app/Models/'];
    foreach ($map as $pre => $base) {
        if (str_starts_with($c, $pre)) {
            $f = ROOT_PATH . $base . str_replace('\\', '/', substr($c, strlen($pre))) . '.php';
            if (is_file($f)) { require $f; }
        }
    }
});

// ── Reconstruir el algoritmo VIEJO desde git HEAD ────────────────
$head = (string) shell_exec(
    'git -C ' . escapeshellarg(ROOT_PATH) . ' show HEAD:app/Controllers/Docente/PanelController.php'
);
$ini = "        // D\u{00ED}as fijos lunes-viernes (la BD no maneja fin de semana).";
$fin = "        // Documento \u{2192} nombre legal completo del docente (no el nombre corto).";
$p0  = strpos($head, $ini);
$p1  = strpos($head, $fin);

if ($p0 === false || $p1 === false || $p1 <= $p0) {
    fwrite(STDERR, "FALLO: no se pudo extraer el bloque viejo de git HEAD\n");
    exit(1);
}

$bloqueViejo = substr($head, $p0, $p1 - $p0);
// El bloque viejo lee $anio y $this->calModel para la duracion; se la inyectamos
// ya resuelta para aislar EXACTAMENTE el algoritmo de armado.
$bloqueViejo = preg_replace(
    '/\$anio = \$this->getAnioActivo\(\);.*?\$duracionHora = .*?;/s',
    '$duracionHora = $duracionInyectada;',
    $bloqueViejo
);

eval('function armarViejo(array $sesiones, int $duracionInyectada): array {' . $bloqueViejo . '
    return ["dias"=>$dias, "segmentos"=>$segmentos, "startAt"=>$startAt,
            "covered"=>$covered, "leyenda"=>array_values($leyenda), "totalHoras"=>$totalHoras];
}');

// ── Contraste sobre datos reales ─────────────────────────────────
$modelo = new App\Models\HorarioModel();
$anio   = $modelo->queryOne("SELECT id FROM anios_academicos WHERE estado='activo' LIMIT 1");
$dur    = $modelo->duracionHoraAcademica($anio ? (int) $anio['id'] : null);

echo "Duracion de hora academica: {$dur} min\n\n";

$docentes = $modelo->query("
    SELECT DISTINCT sh.docente_id
    FROM sesiones_horario sh
    INNER JOIN cargas_academicas ca ON ca.id = sh.carga_id AND ca.estado = 'activa'
    ORDER BY sh.docente_id
");

$iguales = $distintos = $sinHorario = 0;
$fallos  = [];

foreach ($docentes as $d) {
    $id  = (int) $d['docente_id'];
    $ses = $modelo->getSesionesDocente($id);

    if (empty($ses)) { $sinHorario++; continue; }

    $nuevo = $modelo->armarGrilla($ses, $dur, 'seccion');
    $viejo = armarViejo($ses, $dur);

    // El nuevo añade la clave 'docente' (vacia en este eje); se ignora para
    // comparar contra la estructura vieja, que no la tenia.
    $limpiar = function (array $g): array {
        foreach ($g['leyenda'] as &$l) { unset($l['docente']); }
        foreach ($g['startAt'] as &$dia) {
            foreach ($dia as &$celda) { unset($celda['docente']); }
        }
        return $g;
    };

    if ($limpiar($nuevo) == $viejo) {
        $iguales++;
    } else {
        $distintos++;
        $fallos[] = $id;
    }
}

printf("Docentes con horario comparados : %d\n", $iguales + $distintos);
printf("  identicos al codigo viejo     : %d\n", $iguales);
printf("  DIVERGENTES                   : %d\n", $distintos);
printf("Docentes sin sesiones (saltados): %d\n", $sinHorario);

if ($fallos) {
    echo "\nDocentes divergentes: " . implode(', ', $fallos) . "\n";
}

// ── El eje nuevo: horario por SECCION ────────────────────────────
echo "\nEje nuevo — horario por seccion:\n";
$secciones = $modelo->query("SELECT DISTINCT seccion_id FROM sesiones_horario ORDER BY seccion_id");
$conGrilla = 0;
foreach ($secciones as $s) {
    $ses = $modelo->getSesionesSeccion((int) $s['seccion_id']);
    $g   = $modelo->armarGrilla($ses, $dur, 'docente');
    if (!empty($g['segmentos']) && !empty($g['leyenda'])) { $conGrilla++; }
}
printf("  secciones que arman grilla    : %d de %d\n", $conGrilla, count($secciones));

$ok = ($distintos === 0) && ($iguales > 0) && ($conGrilla === count($secciones));
echo "\n", $ok ? "== FASE 1B EN VERDE ==\n" : "== HAY FALLOS ==\n";
exit($ok ? 0 : 1);
