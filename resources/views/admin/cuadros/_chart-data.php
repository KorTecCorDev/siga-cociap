<?php
/**
 * Arma $chartData para los gráficos del tablero de Dirección.
 * Lo comparten la pantalla (index.php) y su imprimible (imprimir.php): punto
 * ÚNICO, para que el papel no pueda dibujar algo distinto de la pantalla.
 *
 * Vive en la VISTA y no en el controlador a propósito. El verificador
 * `verif_direccion_superficies.php` duplica a mano las transformaciones del
 * controlador, pero RENDERIZA la vista de verdad: lo que se calcula aquí
 * queda cubierto por él; lo que se mueva al controlador crea una tercera
 * copia que puede divergir en silencio.
 *
 * Toda clave se lee con `??` porque ese mismo verificador arma su propio
 * `$bloques`: una clave inesperada sería un warning y, para él, un FAIL.
 *
 * @var array|null $bloques
 * @var array|null $periodo
 * @var array      $chartData  (salida)
 */

$evo      = $bloques['evolucion'] ?? null;
$condSecc = $bloques['conducta_secciones'] ?? [];
$cond     = $bloques['conducta'] ?? [];
$condLit  = $bloques['conducta_literales'] ?? null;
$condCrit = $bloques['conducta_criterios'] ?? [];
$asisSecc = $bloques['asistencia_secciones'] ?? [];
$asisEvo  = $bloques['asistencia_evolucion'] ?? [];

$pidActual = (int) ($periodo['id'] ?? 0);

$chartData = [];

// ─────────────────────────────────────────────────────────────────────
// Bimestres COMPARABLES — criterio único de las tres series históricas.
//
// Al eje X solo entran los bimestres en los que TODOS los niveles tienen
// dato. Los otros dos criterios posibles fallan, y de forma poco evidente:
//
//  · Rellenar con 0 el nivel que aún no tiene datos dibuja un desplome al
//    0% que no ocurrió — es justo lo contrario de la realidad (todavía no
//    hay nada que medir). Pasa de verdad: en el bimestre en curso un nivel
//    suele arrancar antes que el otro.
//  · Rellenar con null depende de que Frappe Charts trate los huecos sin
//    hacer aritmética con ellos; si los coerciona, el path del SVG se llena
//    de NaN y desaparece la línea ENTERA, no solo el punto.
//
// Además es lo correcto de fondo: un nivel con 72 notas de 28.000 daría un
// "100% en logro" que un director leería como una mejora, cuando es una
// muestra sin representatividad.
//
// 🔴 Está extraído porque lo usan G2 (notas) y G7 (conducta) sobre la MISMA
// forma de dato. Copiarlo habría sido otra regla duplicada de las que este
// repositorio ya ha visto divergir cuatro veces.
// ─────────────────────────────────────────────────────────────────────
$bimestresComparables = static function (?array $fuente, string $campoTotal): array {
    if (!$fuente || empty($fuente['niveles'])) {
        return [];
    }

    $comparables = [];
    foreach ($fuente['periodos'] ?? [] as $per) {
        $todosConDatos = true;
        foreach ($fuente['niveles'] as $n) {
            foreach ($n['serie'] as $celda) {
                if ($celda['periodo_id'] === $per['id'] && (int) $celda[$campoTotal] === 0) {
                    $todosConDatos = false;
                    break 2;
                }
            }
        }
        if ($todosConDatos) {
            $comparables[] = $per;
        }
    }

    // Con un solo punto no hay evolución que dibujar, solo un dato suelto que
    // la línea haría parecer una tendencia.
    return count($comparables) >= 2 ? $comparables : [];
};

/** Serie por nivel recortada a los bimestres comparables. */
$serieComparable = static function (array $fuente, array $comparables, string $campo): array {
    $ids    = array_column($comparables, 'id');
    $series = [];

    foreach ($fuente['niveles'] as $n) {
        $valores = [];
        foreach ($n['serie'] as $celda) {
            if (in_array($celda['periodo_id'], $ids, true)) {
                $valores[] = $celda[$campo];
            }
        }
        $series[] = ['name' => $n['nivel_nombre'], 'values' => $valores];
    }

    return $series;
};

/** Etiqueta de sección del tablero: primaria y secundaria repiten los grados. */
$etqSeccion = static fn(array $s): string => (int) $s['grado_numero'] . '° ' . $s['seccion_nombre']
    . ' (' . strtoupper(substr((string) $s['nivel_codigo'], 0, 1)) . ')';

// G2 — Evolución del % en logro por bimestre.
$compNotas = $bimestresComparables($evo, 'total_calif');
if ($compNotas) {
    $chartData['evolucion'] = [
        'labels'   => array_column($compNotas, 'nombre'),
        'datasets' => $serieComparable($evo, $compNotas, 'pct_logro'),
    ];
}

// G3 — Brecha interna de cada grado: primer puesto vs último.
// `peores` llega ordenado de mejor a peor, así que el último es el último
// puesto. Un grado de un solo estudiante lo deja vacío y no entra.
$lblGrado = $mejores = $ultimos = [];
foreach ($bloques['merito']['por_grado'] ?? [] as $g) {
    $peores = $g['peores'] ?? [];
    $peor   = $peores ? $peores[count($peores) - 1] : null;

    if (!isset($g['mejor']['promedio_general']) || !isset($peor['promedio_general'])) {
        continue;
    }

    // Primaria y Secundaria repiten los numeros de grado: sin la inicial del
    // nivel el eje mostraria dos "1°", dos "2°"... y no se sabria cual es cual.
    $lblGrado[] = $g['grado']['nombre_display'] . ' '
        . strtoupper(substr((string) $g['grado']['nivel_codigo'], 0, 1));
    $mejores[]  = (float) $g['mejor']['promedio_general'];
    $ultimos[]  = (float) $peor['promedio_general'];
}
if ($lblGrado) {
    $chartData['brecha'] = ['labels' => $lblGrado, 'mejor' => $mejores, 'peor' => $ultimos];
}

// G4 — Embudo del cierre de conducta. Verde/ámbar/rojo es correcto aquí:
// son ETAPAS de un proceso (estados), no categorías arbitrarias.
$embudo = [
    (int) ($cond['cerradas'] ?? 0),
    (int) ($cond['pend_tutor'] ?? 0),
    (int) ($cond['pend_auxiliar'] ?? 0),
];
if (array_sum($embudo) > 0) {
    $chartData['conductaEmbudo'] = [
        'labels' => ['Cerradas', 'Esperan al tutor', 'Esperan al auxiliar'],
        'values' => $embudo,
    ];
}

// G5 — Secciones con menor avance en conducta. Se muestran SIEMPRE las de
// menor cobertura (no solo las incompletas) para que la pantalla no cambie
// de forma según el día del bimestre.
$avance = [];
foreach ($condSecc as $s) {
    $esperados = (int) ($s['esperados'] ?? 0);
    $avance[] = [
        'etq' => $etqSeccion($s),
        'pct' => $esperados > 0
            ? round((int) $s['calificados'] / $esperados * 100, 1)
            : 0.0,
    ];
}
usort($avance, static fn(array $x, array $y): int => $x['pct'] <=> $y['pct']);
$avance = array_slice($avance, 0, 12);
if ($avance) {
    $chartData['conductaSecciones'] = [
        'labels' => array_column($avance, 'etq'),
        'values' => array_column($avance, 'pct'),
    ];
}

// G6 — Distribución de literales de conducta por nivel, en el bimestre a la
// vista. Barras apiladas: la altura es el total calificado y los tramos, el
// reparto. AD/A/B/C son ESTADOS ordenados, no categorías, y por eso llevan la
// misma paleta que el donut del panel de literales.
$lblNivel = [];
$porLit   = ['ad' => [], 'a' => [], 'b' => [], 'c' => []];
foreach ($condLit['niveles'] ?? [] as $n) {
    foreach ($n['serie'] as $celda) {
        if ((int) $celda['periodo_id'] !== $pidActual || (int) $celda['total'] === 0) {
            continue;
        }
        $lblNivel[] = $n['nivel_nombre'];
        foreach ($porLit as $k => $_) {
            $porLit[$k][] = (int) $celda[$k];
        }
    }
}
if ($lblNivel) {
    $chartData['conductaLiterales'] = [
        'labels'   => $lblNivel,
        'datasets' => [
            ['name' => 'AD', 'values' => $porLit['ad']],
            ['name' => 'A',  'values' => $porLit['a']],
            ['name' => 'B',  'values' => $porLit['b']],
            ['name' => 'C',  'values' => $porLit['c']],
        ],
    ];
}

// G7 — Evolución del % en logro (AD+A) de CONDUCTA, una serie por nivel.
// Mismo criterio de comparabilidad que G2: ver el closure de arriba.
$compCond = $bimestresComparables($condLit, 'total');
if ($compCond) {
    $chartData['conductaEvolucion'] = [
        'labels'   => array_column($compCond, 'nombre'),
        'datasets' => $serieComparable($condLit, $compCond, 'pct_logro'),
    ];
}

// G8 — Criterios de convivencia con mayor incumplimiento (institucional).
// El orden es por incumplimiento, no por código: la pregunta es "qué norma
// cuesta más", no "cómo está numerada".
$crits = $condCrit['criterios'] ?? [];
if ($crits) {
    usort($crits, static fn(array $x, array $y): int => $y['pct'] <=> $x['pct']);
    $chartData['conductaCriterios'] = [
        'labels' => array_column($crits, 'codigo'),
        'values' => array_map(static fn(array $k): float => (float) $k['pct'], $crits),
        // El tooltip necesita el texto: un eje de códigos C1..C10 no dice nada
        // por sí solo, y la leyenda de la tabla queda lejos del gráfico.
        'textos' => array_map(static fn(array $k): string => (string) $k['texto'], $crits),
    ];
}

// G9 y G10 — Comparativa entre secciones. Descendente: la sección que más
// acumula primero, que es la lectura que busca Dirección.
//
// 🔴 `faltas` y `tardanzas` SON YA las no justificadas: en `inasistencias` los
// cuatro contadores son columnas independientes, no un total y su subconjunto.
// Nunca restar (ver el docblock de AsistenciaModel).
foreach ([['asisFaltas', 'faltas'], ['asisTardanzas', 'tardanzas']] as [$clave, $campo]) {
    $filas = [];
    foreach ($asisSecc as $s) {
        $filas[] = ['etq' => $etqSeccion($s), 'v' => (int) $s[$campo]];
    }
    usort($filas, static fn(array $x, array $y): int => $y['v'] <=> $x['v']);

    // Con todo en cero el gráfico son 23 barras planas: no hay nada que
    // comparar y ocupa media pantalla diciéndolo.
    if ($filas && array_sum(array_column($filas, 'v')) > 0) {
        $chartData[$clave] = [
            'labels' => array_column($filas, 'etq'),
            'values' => array_column($filas, 'v'),
        ];
    }
}

// G11 — Evolución anual de faltas y tardanzas sin justificar.
// Solo entran los bimestres CON registro: uno sin registrar valdría 0 y la
// línea lo dibujaría como una mejora que no ocurrió (mismo razonamiento que
// los bimestres comparables, aplicado a una serie institucional).
$lblAsis = $vFaltas = $vTardanzas = [];
foreach ($asisEvo as $perAsis) {
    if ((int) ($perAsis['registrados'] ?? 0) === 0) {
        continue;
    }
    $lblAsis[]    = (string) $perAsis['periodo_nombre'];
    $vFaltas[]    = (int) $perAsis['faltas'];
    $vTardanzas[] = (int) $perAsis['tardanzas'];
}
if (count($lblAsis) >= 2) {
    $chartData['asisEvolucion'] = [
        'labels'   => $lblAsis,
        'datasets' => [
            ['name' => 'Faltas',    'values' => $vFaltas],
            ['name' => 'Tardanzas', 'values' => $vTardanzas],
        ],
    ];
}

// G12 — Justificadas vs sin justificar, por nivel. Mide la CULTURA de
// justificación, que difiere mucho entre niveles y no se ve en ningún otro
// sitio: en B2, primaria justifica 257 de 443 faltas y secundaria 42 de 229.
// Por nivel y no por sección a propósito: 23 barras partidas en dos no se
// leen, y menos en papel.
$porNivel = [];
foreach ($asisSecc as $s) {
    $nid = (int) $s['nivel_id'];
    if (!isset($porNivel[$nid])) {
        $porNivel[$nid] = ['nombre' => (string) $s['nivel_nombre'], 'f' => 0, 'fj' => 0, 't' => 0, 'tj' => 0];
    }
    $porNivel[$nid]['f']  += (int) $s['faltas'];
    $porNivel[$nid]['fj'] += (int) $s['faltas_justificadas'];
    $porNivel[$nid]['t']  += (int) $s['tardanzas'];
    $porNivel[$nid]['tj'] += (int) $s['tardanzas_justificadas'];
}
$lblJust = $vSin = $vJust = [];
foreach ($porNivel as $n) {
    $lblJust[] = $n['nombre'] . ' · faltas';
    $vSin[]    = $n['f'];
    $vJust[]   = $n['fj'];

    $lblJust[] = $n['nombre'] . ' · tardanzas';
    $vSin[]    = $n['t'];
    $vJust[]   = $n['tj'];
}
if ($lblJust && (array_sum($vSin) > 0 || array_sum($vJust) > 0)) {
    $chartData['asisJustificacion'] = [
        'labels'   => $lblJust,
        'datasets' => [
            ['name' => 'Sin justificar', 'values' => $vSin],
            ['name' => 'Justificadas',   'values' => $vJust],
        ],
    ];
}
