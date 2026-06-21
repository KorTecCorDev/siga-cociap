<?php
/**
 * Vista: dashboard de estadísticas de matrícula.
 *
 * @var array      $resumen  ['kpis'=>..., 'por_grado'=>[...], 'por_tipo'=>[...], 'por_genero'=>...]
 * @var array      $anios    [{id, anio, ...}]
 * @var int        $anioId
 * @var array|null $anioSel
 */
$kpis       = $resumen['kpis']        ?? [];
$porGrado   = $resumen['por_grado']   ?? [];
$porSeccion = $resumen['por_seccion'] ?? [];
$porTipo    = $resumen['por_tipo']    ?? [];
$genero   = $resumen['por_genero'] ?? ['m' => 0, 'f' => 0, 'sin_dato' => 0, 'cobertura' => 0];

$hayDatos = !empty($kpis) && ($kpis['aprobadas'] ?? 0) > 0;

$labelTipo = fn(string $t): string => match ($t) {
    'continuador' => 'Continuador',
    'nuevo'       => 'Nuevo',
    'trasladado'  => 'Trasladado',
    default       => ucfirst($t),
};

// Orden canónico de tipos -> color estable (continuador, nuevo, trasladado).
$tipoOrden = ['continuador', 'nuevo', 'trasladado'];
$tipoMap   = [];
foreach ($porTipo as $t) {
    $tipoMap[$t['tipo']] = $t['n'];
}
$tipoColor  = ['continuador' => '#1e6fa8', 'nuevo' => '#0d9488', 'trasladado' => '#7c3aed'];
$tipoLabels = [];
$tipoValues = [];
$tipoColors = [];
foreach ($tipoOrden as $t) {
    if (!empty($tipoMap[$t])) {
        $tipoLabels[] = $labelTipo($t);
        $tipoValues[] = $tipoMap[$t];
        $tipoColors[] = $tipoColor[$t];
    }
}

// Etiqueta corta de grado para el eje (ej: "1° Prim").
$labelGrado = static function (array $g): string {
    return $g['grado_numero'] . '° ' . ucfirst((string) ($g['nivel_codigo'] ?: $g['nivel_nombre']));
};

// Etiqueta corta de sección (ej: "1°A P" — inicial del nivel para distinguir
// 1°A Primaria de 1°A Secundaria).
$labelSeccion = static function (array $s): string {
    $ini = mb_strtoupper(mb_substr((string) $s['nivel_codigo'], 0, 1));
    return $s['grado_numero'] . '°' . $s['seccion_nombre'] . ' ' . $ini;
};

// Estructura de datos que consume Frappe Charts (sin JS inline: JSON en un tag).
$chartData = [
    'porGrado' => [
        'labels' => array_map($labelGrado, $porGrado),
        'values' => array_map(static fn($g) => $g['n'], $porGrado),
    ],
    'porSeccion' => [
        'labels' => array_map($labelSeccion, $porSeccion),
        'values' => array_map(static fn($s) => $s['n'], $porSeccion),
    ],
    'generoSeccion' => [
        'labels'  => array_map($labelSeccion, $porSeccion),
        'm'       => array_map(static fn($s) => $s['m'], $porSeccion),
        'f'       => array_map(static fn($s) => $s['f'], $porSeccion),
        'sinDato' => array_map(static fn($s) => $s['sin_dato'], $porSeccion),
    ],
    'porTipo' => [
        'labels' => $tipoLabels,
        'values' => $tipoValues,
        'colors' => $tipoColors,
    ],
    'genero' => [
        'labels' => ['Masculino', 'Femenino', 'Sin dato'],
        'values' => [(int) $genero['m'], (int) $genero['f'], (int) $genero['sin_dato']],
        'colors' => ['#1e6fa8', '#0d9488', '#9ca3af'],
    ],
];
?>

<div class="page-header">
    <div>
        <a href="<?= url('matriculas') ?>" class="btn btn--secondary btn--sm">← Volver a matrículas</a>
        <h1 class="page-title">Resumen de matrículas</h1>
        <p class="page-subtitle">
            <?= $anioSel ? 'Año académico ' . e((string) $anioSel['anio']) : 'Sin año seleccionado' ?>
        </p>
    </div>
    <?php if (!empty($anios)): ?>
        <form method="GET" action="<?= url('matriculas/resumen') ?>" class="control-selector">
            <label for="anio_id" class="form-label">Año</label>
            <select name="anio_id" id="anio_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($anios as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $a['id'] === $anioId ? 'selected' : '' ?>>
                        <?= e((string) $a['anio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if (!$hayDatos): ?>
    <div class="empty-state">
        <p>No hay estudiantes matriculados (aprobados) en este año para generar estadísticas.</p>
    </div>
<?php else: ?>

    <!-- KPIs -->
    <div class="resumen-kpis">
        <div class="resumen-kpi resumen-kpi--ok">
            <span class="resumen-kpi__num"><?= (int) $kpis['aprobadas'] ?></span>
            <span class="resumen-kpi__label">Matriculados</span>
        </div>
        <div class="resumen-kpi">
            <span class="resumen-kpi__num"><?= (int) $kpis['secciones'] ?></span>
            <span class="resumen-kpi__label">Secciones</span>
        </div>
        <div class="resumen-kpi">
            <span class="resumen-kpi__num"><?= e((string) $kpis['promedio_seccion']) ?></span>
            <span class="resumen-kpi__label">Promedio por sección</span>
        </div>
        <div class="resumen-kpi resumen-kpi--muted">
            <span class="resumen-kpi__num"><?= (int) $kpis['desactivadas'] ?></span>
            <span class="resumen-kpi__label">Desactivadas</span>
        </div>
        <?php if (($kpis['pendientes'] ?? 0) > 0): ?>
            <div class="resumen-kpi resumen-kpi--warn">
                <span class="resumen-kpi__num"><?= (int) $kpis['pendientes'] ?></span>
                <span class="resumen-kpi__label">Pendientes</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Gráficos -->
    <div class="resumen-charts">
        <div class="card resumen-chart resumen-chart--ancho">
            <div class="card__header"><h2 class="card__title">Matriculados por grado</h2></div>
            <div class="card__body"><div id="chart-grado"></div></div>
        </div>

        <div class="card resumen-chart resumen-chart--ancho">
            <div class="card__header"><h2 class="card__title">Matriculados por sección</h2></div>
            <div class="card__body"><div id="chart-seccion"></div></div>
        </div>

        <div class="card resumen-chart resumen-chart--ancho">
            <div class="card__header card__header--between">
                <h2 class="card__title">Varones y mujeres por sección</h2>
                <span class="badge <?= $genero['cobertura'] >= 80 ? 'badge--activo' : 'badge--warning' ?>">
                    <?= e((string) $genero['cobertura']) ?>% con dato
                </span>
            </div>
            <div class="card__body">
                <div id="chart-genero-seccion"></div>
                <?php if ($genero['cobertura'] < 80): ?>
                    <p class="resumen-nota">
                        Mientras no se registre el sexo de los estudiantes, las barras se
                        muestran como “Sin dato”. Se irán coloreando por sexo conforme se capture.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card resumen-chart">
            <div class="card__header"><h2 class="card__title">Por tipo de matrícula</h2></div>
            <div class="card__body"><div id="chart-tipo"></div></div>
        </div>

        <div class="card resumen-chart">
            <div class="card__header card__header--between">
                <h2 class="card__title">Por género</h2>
                <span class="badge <?= $genero['cobertura'] >= 80 ? 'badge--activo' : 'badge--warning' ?>">
                    <?= e((string) $genero['cobertura']) ?>% con dato
                </span>
            </div>
            <div class="card__body">
                <div id="chart-genero"></div>
                <?php if ($genero['cobertura'] < 80): ?>
                    <p class="resumen-nota">
                        El sexo de muchos estudiantes aún no está registrado en su matrícula.
                        El gráfico se completará a medida que se capture el dato.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script type="application/json" id="resumen-data"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <script src="<?= url('js/frappe-charts.min.js') ?>"></script>
    <script src="<?= url('js/matriculas-resumen.js') ?>"></script>

<?php endif; ?>
