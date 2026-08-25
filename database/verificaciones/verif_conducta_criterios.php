<?php

/**
 * Verificación — código de criterio, pie de grilla y la vista compartida
 * de la grilla Sí/No de conducta (25/08/2026). Solo lectura sobre la BD.
 *
 * QUÉ COMPRUEBA
 *   1. El código sale del MODELO, no de las vistas. Se rotulaba a mano como
 *      `C{$i + 1}` en dos sitios y estaba a punto de ser un tercero.
 *   2. La migración 056 sembró códigos estables y NO cambió lo ya impreso.
 *   3. RENDER REAL: ningún pie de grilla cuelga dentro del área de scroll.
 *      Metido ahí se desplaza con la tabla y lo recorta el `overflow: hidden`
 *      del card — los tres fallos que tenía la grilla de conducta.
 *   4. La vista de la grilla Sí/No la comparten TUTOR y DIRECCIÓN, y su chrome
 *      cambia según quién la llame.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('VIEW_PATH', ROOT_PATH . '/resources/views');

require ROOT_PATH . '/app/Helpers/helpers.php';

spl_autoload_register(function (string $c): void {
    foreach (['Core\\' => '/core/', 'App\\Models\\' => '/app/Models/'] as $pre => $base) {
        if (str_starts_with($c, $pre)) {
            $f = ROOT_PATH . $base . str_replace('\\', '/', substr($c, strlen($pre))) . '.php';
            if (is_file($f)) { require $f; }
        }
    }
});

$ok  = true;
$chk = function (string $t, bool $c) use (&$ok) {
    printf("  [%s] %s\n", $c ? 'OK ' : 'FAIL', $t);
    $ok = $ok && $c;
};

$cond = new App\Models\ConductaModel();

echo "1) El codigo sale del MODELO, no de las vistas\n";
$vistas = [
    'admin/conducta/imprimir.php',
    'docente/conducta-criterios.php',
    'consulta-notas/conducta.php',
];
foreach ($vistas as $rel) {
    $html = file_get_contents(ROOT_PATH . '/resources/views/' . $rel);
    // La regresion concreta: volver a calcular la posicion en la vista.
    $chk("{$rel} no recalcula C\$i+1", !preg_match('/C<\?=\s*\$i\s*\+\s*1/', $html));
    $chk("{$rel} consume \$c['codigo'] del modelo",
        (bool) preg_match("/\\\$c(?:r)?\['codigo'\]/", $html));
}

echo "\n2) La migracion 056 sembro codigos estables\n";
$criterios = $cond->getCriterios();
$total     = count($criterios);
printf("       criterios vigentes: %d\n", $total);
$chk('hay criterios que comprobar', $total > 0);

$codigos = array_column($criterios, 'codigo');
$chk('ninguno llega sin codigo', count(array_filter($codigos, fn($c) => trim((string) $c) !== '')) === $total);
$chk('los codigos son unicos', count(array_unique($codigos)) === $total);

// 🔴 LA GARANTIA DE LA MIGRACION: no cambiar lo ya impreso. Antes se rotulaba
// por POSICION, asi que el codigo de cada criterio debe seguir siendo el mismo
// que daba `C{posicion}`. Si algun dia se reordena a proposito, este aserto lo
// dira en voz alta en vez de dejar que el papel viejo deje de cuadrar en silencio.
$divergen = [];
foreach ($criterios as $i => $c) {
    $posicional = 'C' . ($i + 1);
    if ($c['codigo'] !== $posicional) {
        $divergen[] = sprintf('%s (posicion daba %s)', $c['codigo'], $posicional);
    }
}
$chk('cada codigo coincide con el que daba la posicion: nada impreso cambia'
    . ($divergen ? ' | DIVERGEN: ' . implode(', ', $divergen) : ''), $divergen === []);

// El fallback de la otra rama: un criterio sin codigo cae a la posicion.
$col = array_column($cond->query("SHOW COLUMNS FROM criterios_conducta"), 'Field');
$chk('la columna `codigo` existe (migracion 056 aplicada)', in_array('codigo', $col, true));

echo "\n3) RENDER REAL: el pie de grilla NO cuelga del area de scroll\n";
// Seccion con conducta cerrada en sus DOS etapas Y CON MATRIZ DE RESPUESTAS.
//
// ⚠️ LA MATRIZ NO ES OPCIONAL PARA ESTA PRUEBA. El I Bimestre es LEGADO (literal
// directo, sin criterios): alli la vista se va por la rama del empty-state y no
// pinta ni pie, ni codigos, ni grilla. Elegir el primer periodo cerrado daba B1
// y hacia fallar cuatro asertos por falta de datos, no por un defecto — el
// verificador acusando a la vista de algo que la vista hace bien.
$destino = null;
$conMatriz = array_column(
    $cond->query("SELECT DISTINCT periodo_id FROM conducta_respuestas"),
    'periodo_id'
);
foreach ($conMatriz as $pidCand) {
    foreach ($cond->query("SELECT id FROM secciones ORDER BY id") as $s) {
        $c = $cond->getCierreDetalle((int) $s['id'], (int) $pidCand);
        if ($c && !empty($c['ra_bloqueado_en']) && !empty($c['tutor_cerrado_en'])) {
            $destino = [(int) $s['id'], (int) $pidCand, $c];
            break 2;
        }
    }
}
if ($destino === null) {
    echo "  SIN DATOS: ninguna seccion tiene conducta cerrada en sus dos etapas\n";
    echo "  CON matriz de respuestas. No se puede renderizar la rama que importa.\n";
    exit($ok ? 0 : 1);
}
[$sid, $pid, $cierre] = $destino;

$sec = $cond->queryOne("
    SELECT s.id, s.nombre AS seccion_nombre, g.nombre_display AS grado_nombre,
           n.nombre AS nivel_nombre, n.id AS nivel_id
    FROM secciones s
    INNER JOIN grados g ON g.id = s.grado_id
    INNER JOIN niveles n ON n.id = g.nivel_id
    WHERE s.id = ?", [$sid]);
$per = $cond->queryOne("SELECT p.id, p.numero, p.nombre_display, p.estado, a.anio
    FROM periodos p INNER JOIN anios_academicos a ON a.id = p.anio_id WHERE p.id = ?", [$pid]);
printf("       seccion %d (%s %s), periodo %d\n",
    $sid, $sec['grado_nombre'], $sec['seccion_nombre'], $pid);

/** Renderiza una vista con sus variables y devuelve el HTML. */
$render = function (string $vista, array $vars): string {
    extract($vars);
    ob_start();
    require VIEW_PATH . '/' . $vista . '.php';
    return (string) ob_get_clean();
};

/** ¿Algún .tabla-pie cuelga de un .tabla-notas-wrapper? */
$pieDentroDelScroll = function (string $html): ?bool {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    if (!$doc->loadHTML('<meta charset="utf-8"><div>' . $html . '</div>')) {
        libxml_clear_errors();
        return null;
    }
    libxml_clear_errors();
    $xp   = new DOMXPath($doc);
    $pies = $xp->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' tabla-pie ')]");
    if ($pies->length === 0) { return null; }
    foreach ($pies as $pie) {
        for ($n = $pie->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
            if (str_contains(' ' . $n->getAttribute('class') . ' ', ' tabla-notas-wrapper ')) {
                return true;
            }
        }
    }
    return false;
};

$alumnos = $cond->getEstudiantesParaTutor($sid, $pid, $cond->totalCriterios((int) $sec['nivel_id']));
$htmlConducta = $render('consulta-notas/conducta', [
    'periodo'   => $per,
    'seccion'   => ['seccion_id' => $sid, 'seccion_nombre' => $sec['seccion_nombre'],
                    'grado_nombre' => $sec['grado_nombre'], 'nivel_nombre' => $sec['nivel_nombre']],
    'cierre'    => $cierre,
    'alumnos'   => $alumnos,
    'criterios' => $criterios,
    'esLegado'  => !empty($alumnos[0]['es_legado']),
]);
$dentro = $pieDentroDelScroll($htmlConducta);
$chk('consulta-notas/conducta pinta un pie de grilla', $dentro !== null);
$chk('y ese pie NO esta dentro del area de scroll', $dentro === false);

// El codigo tiene que llegar de verdad a la pantalla, no solo al modelo.
$chk('el panel de criterios muestra los codigos',
    str_contains($htmlConducta, '>' . $criterios[0]['codigo'] . '<'));
$chk('y ofrece el acceso a la grilla Si/No',
    str_contains($htmlConducta, '/conducta/criterios'));

echo "\n4) La grilla Si/No la comparten TUTOR y DIRECCION\n";
$estudiantes = $cond->getEstudiantesParaRegistro($sid, $pid);
$hayResp = false;
foreach ($estudiantes as $e) { if (!empty($e['respuestas'])) { $hayResp = true; break; } }

$base = [
    'seccion'       => ['id' => $sid, 'nombre' => $sec['seccion_nombre'],
                        'grado_nombre' => $sec['grado_nombre'], 'nivel_nombre' => $sec['nivel_nombre'],
                        'nivel_id' => (int) $sec['nivel_id']],
    'periodo'       => $per,
    'cierre'        => $cierre,
    'criterios'     => $criterios,
    'estudiantes'   => $estudiantes,
    'hayRespuestas' => $hayResp,
];
$htmlTutor = $render('docente/conducta-criterios', $base);
$htmlDir   = $render('docente/conducta-criterios', $base + [
    'volverUrl'   => '/RUTA-DE-CONSULTA',
    'tituloClase' => 'page-title',
]);

// Las dos ramas del parametrizado: por defecto el chrome del tutor; con las
// variables, el de Direccion. Si el default se rompe, el tutor pierde su vuelta.
$chk('por defecto vuelve al panel del tutor', str_contains($htmlTutor, 'docente/conducta/' . $pid));
$chk('por defecto lleva el wayfinding del docente', str_contains($htmlTutor, 'page-title--conducta'));
$chk('llamada desde consulta usa SU enlace de volver', str_contains($htmlDir, '/RUTA-DE-CONSULTA'));
$chk('y NO arrastra el wayfinding del docente', !str_contains($htmlDir, 'page-title--conducta'));
$chk('las dos pintan la misma grilla', str_contains($htmlTutor, 'conducta-grilla')
    && str_contains($htmlDir, 'conducta-grilla'));
$chk('las cabeceras de criterio usan el codigo del modelo',
    str_contains($htmlDir, '>' . $criterios[0]['codigo'] . '<'));

echo "\n5) La grilla Si/No cumple el estandar de codigo y conserva la nota\n";
// Se mide sobre el HTML RENDERIZADO, no sobre el fuente: lo que importa es lo
// que llega al navegador.
$chipsCod = substr_count($htmlDir, 'competencia-card__codigo');
printf("       chips de codigo en la grilla: %d (criterios: %d)\n", $chipsCod, $total);
// Uno por cabecera y uno por linea de la leyenda desplegable.
$chk('los codigos usan el chip del sistema, no texto suelto', $chipsCod >= $total);
$chk('las cabeceras usan el modificador que centra el chip',
    str_contains($htmlDir, 'competencia-card__codigo--solo'));
$chk('el chip `--solo` existe en el CSS servido',
    str_contains(file_get_contents(ROOT_PATH . '/public/css/app.css'),
                 '.competencia-card__codigo--solo'));

// 🔴 LO QUE EL USUARIO PIDIO NO PERDER. El literal se anadio JUNTO al numeral,
// no en su lugar: si alguien "simplifica" quitando uno de los dos, esto lo dice.
// Se cuentan las filas con nota calculada, para no exigir literal donde el
// registro esta incompleto y la celda muestra un guion.
$conNota = 0;
foreach ($estudiantes as $e) {
    $resp = $e['respuestas'] ?? [];
    if ($total > 0 && count($resp) >= $total) { $conNota++; }
}
printf("       estudiantes con nota calculable: %d de %d\n", $conNota, count($estudiantes));
if ($conNota === 0) {
    echo "       [--] ningun registro completo: la celda de nota no es observable\n";
} else {
    $chk('la grilla conserva el NUMERAL', substr_count($htmlDir, 'nota-numeral') >= $conNota);
    $chk('y muestra tambien el LITERAL', substr_count($htmlDir, 'nota-literal') >= $conNota);
}

// 🔴 EL LITERAL VA EN COLUMNA PROPIA, no como segundo valor de la celda de nota.
// Se mide sobre el DOM: contar clases no distingue "dos <td>" de "un <td> con
// dos <span>", que es exactamente el cambio que se pidio.
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML('<meta charset="utf-8"><div>' . $htmlDir . '</div>');
libxml_clear_errors();
$xp = new DOMXPath($doc);

$thLiteral = $xp->query("//th[contains(concat(' ', normalize-space(@class), ' '), ' conducta-th-literal ')]");
$tdLiteral = $xp->query("//td[contains(concat(' ', normalize-space(@class), ' '), ' conducta-td-literal ')]");
$tdNota    = $xp->query("//td[contains(concat(' ', normalize-space(@class), ' '), ' conducta-td-nota ')]");
printf("       columnas: 1 cabecera Literal? %s · celdas literal: %d · celdas nota: %d\n",
    $thLiteral->length === 1 ? 'si' : 'NO(' . $thLiteral->length . ')',
    $tdLiteral->length, $tdNota->length);

$chk('hay UNA cabecera "Literal" propia', $thLiteral->length === 1);
$chk('hay una celda de literal por estudiante', $tdLiteral->length === count($estudiantes));
$chk('y sigue habiendo una celda de nota por estudiante', $tdNota->length === count($estudiantes));

// La otra rama de la separacion: la celda de NOTA ya no lleva dentro el literal.
$notaConLiteral = 0;
foreach ($tdNota as $td) {
    if (str_contains($doc->saveHTML($td), 'nota-literal')) { $notaConLiteral++; }
}
$chk('la celda de nota ya NO contiene el literal dentro', $notaConLiteral === 0);

// Y las dos columnas calculadas van en la zona de resultado del sistema.
$chk('Nota y Literal estan en la zona de resultado',
    $thLiteral->length === 1
    && str_contains($thLiteral->item(0)->getAttribute('class'), 'col-resultado'));

echo "\n", $ok ? "TODO OK\n" : "HAY FALLOS\n";
exit($ok ? 0 : 1);
