<?php

/**
 * Verificación — la ZONA DE RESULTADO no se pierde bajo el hover (25/08/2026).
 * No toca la base de datos: mide el CSS servido y las vistas.
 *
 * `.col-resultado` marca las columnas CALCULADAS (promedio, nota final, literal)
 * para que no se confundan con las de origen. Existe en `components/_tables.scss`
 * y la usan SEIS vistas, de consulta y del docente.
 *
 * EL FALLO QUE ESTO ANCLA: su fondo era `#f8fafc`, el MISMO valor literal que
 * `$bg-secondary`, que es el color del hover de fila. Al pasar por una fila, toda
 * ella tomaba ese gris y la zona de resultado DESAPARECIA — justo cuando se está
 * señalando la fila, y justo la función para la que la clase existe.
 *
 * Se comprueba la PROPIEDAD —que el escalón de contraste sobreviva al hover— y
 * no un color concreto: fijar el valor convertiría cualquier retoque legítimo de
 * la paleta en un fallo.
 */

define('ROOT_PATH', dirname(__DIR__, 2));

$ok  = true;
$chk = function (string $t, bool $c) use (&$ok) {
    printf("  [%s] %s\n", $c ? 'OK ' : 'FAIL', $t);
    $ok = $ok && $c;
};

$css = file_get_contents(ROOT_PATH . '/public/css/app.css');

/** El selector INDIVIDUAL que contiene el fragmento (parte el grupo por comas). */
$selectorDe = function (string $frag) use ($css): ?string {
    if (!preg_match('/(?:^|[};])([^{};]*' . preg_quote($frag, '/') . '[^{};]*)\{/', $css, $m)) {
        return null;
    }
    foreach (explode(',', $m[1]) as $sel) {
        if (str_contains($sel, $frag)) { return trim($sel); }
    }
    return null;
};

/** El `background` declarado por el bloque que contiene el fragmento. */
$fondoDe = function (string $frag) use ($css): ?string {
    if (!preg_match('/(?:^|[};])[^{};]*' . preg_quote($frag, '/') . '[^{};]*\{([^}]*)\}/', $css, $m)) {
        return null;
    }
    return preg_match('/background(?:-color)?:\s*([^;}]+)/', $m[1], $b) ? trim($b[1]) : null;
};

/** Especificidad [clases+pseudoclases, elementos] de un selector simple. */
$espec = function (string $sel): array {
    return [
        preg_match_all('/\.[\w-]+|\[[^\]]+\]|:(?!:)[\w-]+/', $sel),
        preg_match_all('/(?:^|\s|>|\+|~)([a-z]+)(?=[.\[:\s]|$)/', $sel),
    ];
};

echo "1) El escalon de contraste sobrevive al hover\n";
$fondoZona = $fondoDe('.col-resultado');
$chk('la zona de resultado declara un fondo en reposo', $fondoZona !== null);
printf("       zona en reposo: %s\n", $fondoZona ?? '(ninguno)');

foreach (['tabla-resumen', 'tabla-notas'] as $tabla) {
    $fondoFila = $fondoDe(".{$tabla} tr:hover td");
    $fondoZonaHover = $fondoDe(".{$tabla} tr:hover td.col-resultado");
    printf("       %-14s hover fila: %-10s hover zona: %s\n",
        $tabla, $fondoFila ?? '(ninguno)', $fondoZonaHover ?? '(ninguno)');

    if ($fondoFila === null) {
        // Si la tabla deja de tener hover, este bloque no prueba nada: se dice.
        echo "       [--] {$tabla} ya no define hover de fila: nada que separar\n";
        continue;
    }
    $chk("{$tabla}: la zona tiene su propio fondo en hover", $fondoZonaHover !== null);
    $chk("{$tabla}: ese fondo DIFIERE del de la fila (el escalon existe)",
        $fondoZonaHover !== null && $fondoZonaHover !== $fondoFila);

    // Y en reposo tambien tienen que diferir, o la zona no se veria nunca.
    $chk("{$tabla}: en reposo la zona tampoco iguala al hover de fila",
        $fondoZona !== null && $fondoZona !== $fondoZonaHover);
}

echo "\n2) La regla gana por ESPECIFICIDAD, no por orden del archivo\n";
// El hover vive en OTRO bloque del mismo parcial; confiar en el orden es
// exactamente lo que fallaba antes en la tabla de asistencia.
foreach (['tabla-resumen', 'tabla-notas'] as $tabla) {
    $selFila = $selectorDe(".{$tabla} tr:hover td");
    $selZona = $selectorDe(".{$tabla} tr:hover td.col-resultado");
    if ($selFila === null || $selZona === null) {
        $chk("{$tabla}: se encontraron los dos selectores", false);
        continue;
    }
    [$cF, $eF] = $espec($selFila);
    [$cZ, $eZ] = $espec($selZona);
    $gana = $cZ > $cF || ($cZ === $cF && $eZ > $eF);
    $chk(sprintf('%s: la zona (%d clases) gana a la fila (%d clases)', $tabla, $cZ, $cF), $gana);
}

echo "\n3) Las seis vistas siguen usando la zona de resultado\n";
$vistas = [
    'consulta-notas/conducta.php',
    'consulta-notas/transversales.php',
    'consulta-notas/_tabla.php',
    'docente/conducta.php',
    'docente/resumen-competencia.php',
    'docente/tutoria.php',
];
foreach ($vistas as $rel) {
    $ruta = ROOT_PATH . '/resources/views/' . $rel;
    $chk("{$rel} usa col-resultado",
        is_file($ruta) && str_contains(file_get_contents($ruta), 'col-resultado'));
}

echo "\n4) La zona de resultado CIERRA la fila en conducta\n";
// El separador `--inicio` abre un bloque calculado; si queda una columna suelta
// a su derecha, el bloque se lee interrumpido. Se mide la ULTIMA <th> del thead.
$conducta = file_get_contents(ROOT_PATH . '/resources/views/consulta-notas/conducta.php');
if (preg_match('/<thead>(.*?)<\/thead>/s', $conducta, $m)
    && preg_match_all('/<th\b[^>]*>/s', $m[1], $ths)) {
    $ultima = end($ths[0]);
    $chk('la ultima columna del thead pertenece a la zona de resultado',
        str_contains($ultima, 'col-resultado'));

    // ⚠️ EL THEAD TIENE DOS RAMAS PHP mutuamente excluyentes (legado / nueva) y
    // las dos estan en el fuente. Medirlas juntas hacia que la primera columna
    // "de la zona" fuese la del legado, que pertenece a otra rama. Se separan.
    //
    // Y se cuenta sobre los <th> EXTRAIDOS, no sobre el HTML crudo: los
    // comentarios de la vista mencionan `col-resultado--inicio` al explicar el
    // orden, y contarlos ahi hacia que el aserto se acusara a si mismo.
    $ramas = [];
    if (preg_match('/if\s*\(\$esLegado\)\s*:(.*?)else\s*:(.*?)endif/s', $m[1], $r)) {
        $ramas = ['legado' => $r[1], 'nueva' => $r[2]];
    }
    $chk('el thead tiene sus dos ramas identificables', count($ramas) === 2);

    foreach ($ramas as $nombre => $html) {
        preg_match_all('/<th\b[^>]*>/s', $html, $t);
        $zona = array_values(array_filter($t[0], fn($th) => str_contains($th, 'col-resultado')));
        if (!$zona) {
            $chk("rama {$nombre}: tiene columnas de resultado", false);
            continue;
        }
        $conInicio = array_filter($zona, fn($th) => str_contains($th, 'col-resultado--inicio'));
        $chk("rama {$nombre}: exactamente una columna abre la zona (--inicio)",
            count($conInicio) === 1);
        $chk("rama {$nombre}: el separador esta en la PRIMERA de la zona",
            str_contains($zona[0], 'col-resultado--inicio'));
    }
} else {
    $chk('se pudo leer el thead de conducta', false);
}

echo "\n5) Las grillas de datos comparten UNA leyenda\n";
// ⚠️ ALCANCE: esto NO gobierna todas las clases `*-leyenda` del sistema. Existen
// otras legitimas y de otro contexto —boleta impresa, horario, donut de bloqueos—
// que no son leyendas de grilla y no deben unificarse con esta. Lo que se ancla
// es el refactor concreto: asistencia y conducta usan la MISMA, y la copia que
// habia en asistencia no vuelve.
$chk('`.tabla-leyenda` existe en el CSS servido', str_contains($css, '.tabla-leyenda'));
$chk('la copia `asistencia-leyenda` ya no existe en el CSS',
    !str_contains($css, 'asistencia-leyenda'));

foreach ([
    'admin/asistencia/_tabla-incidencias.php',
    'consulta-notas/conducta.php',
] as $rel) {
    $html = file_get_contents(ROOT_PATH . '/resources/views/' . $rel);
    $chk("{$rel} usa la leyenda del sistema", str_contains($html, 'class="tabla-leyenda"'));
}

echo "\n", $ok ? "TODO OK\n" : "HAY FALLOS\n";
exit($ok ? 0 : 1);
