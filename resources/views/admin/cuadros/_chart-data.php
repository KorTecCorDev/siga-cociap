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
 * @var array      $chartData  (salida)
 */

$evo      = $bloques['evolucion'] ?? null;
$condSecc = $bloques['conducta_secciones'] ?? [];
$cond     = $bloques['conducta'] ?? [];

$chartData = [];

// G2 — Evolución del % en logro por bimestre.
//
// Al eje X solo entran los bimestres COMPARABLES: aquellos en los que TODOS
// los niveles tienen calificaciones. Los otros dos criterios posibles fallan,
// y de forma poco evidente:
//
//  · Rellenar con 0 el nivel que aún no tiene notas dibuja un desplome al
//    0% en logro que no ocurrió — es justo lo contrario de la realidad
//    (todavía no hay nada que medir). Pasa de verdad: en el bimestre en
//    curso un nivel suele arrancar antes que el otro.
//  · Rellenar con null depende de que Frappe Charts trate los huecos sin
//    hacer aritmética con ellos; si los coerciona, el path del SVG se
//    llena de NaN y desaparece la línea ENTERA, no solo el punto.
//
// Además es lo correcto de fondo: un nivel con 72 notas de 28.000 daría un
// "100% en logro" que un director leería como una mejora, cuando es una
// muestra sin representatividad.
if ($evo && !empty($evo['niveles'])) {
    $comparables = [];
    foreach ($evo['periodos'] ?? [] as $per) {
        $todosConDatos = true;
        foreach ($evo['niveles'] as $n) {
            foreach ($n['serie'] as $celda) {
                if ($celda['periodo_id'] === $per['id'] && (int) $celda['total_calif'] === 0) {
                    $todosConDatos = false;
                    break 2;
                }
            }
        }
        if ($todosConDatos) {
            $comparables[] = $per;
        }
    }

    if (count($comparables) >= 2) {
        $idsComparables = array_column($comparables, 'id');
        $series         = [];

        foreach ($evo['niveles'] as $n) {
            $valores = [];
            foreach ($n['serie'] as $celda) {
                if (in_array($celda['periodo_id'], $idsComparables, true)) {
                    $valores[] = $celda['pct_logro'];
                }
            }
            $series[] = ['name' => $n['nivel_nombre'], 'values' => $valores];
        }

        $chartData['evolucion'] = [
            'labels'   => array_column($comparables, 'nombre'),
            'datasets' => $series,
        ];
    }
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
        'etq' => (int) $s['grado_numero'] . '° ' . $s['seccion_nombre']
            . ' (' . strtoupper(substr((string) $s['nivel_codigo'], 0, 1)) . ')',
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
