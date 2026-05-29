<?php
/**
 * Panel-columna con los indicadores del total del bimestre, separados por
 * nivel (Primaria / Secundaria). Solo se usa en la página de indicadores
 * (stats.php). Espera la variable $resumen.
 *
 * @var array $resumen  ['niveles' => [...]]
 */
$niveles = $resumen['niveles'] ?? [];

// Etiquetas y orden de los literales para la leyenda del donut.
$literales = [
    ['ad', 'AD', 'Logro destacado'],
    ['a',  'A',  'Logro esperado'],
    ['b',  'B',  'En proceso'],
    ['c',  'C',  'En inicio'],
];
?>
<section class="bimestre-panel">
    <h3 class="bimestre-panel__titulo">Resumen del bimestre</h3>

    <?php if (empty($niveles)): ?>
        <p class="text-muted text-sm">Aún no hay calificaciones registradas en este bimestre.</p>
    <?php else: ?>
        <?php foreach ($niveles as $n): ?>
        <div class="bimestre-nivel">
            <div class="bimestre-nivel__head">
                <span class="bimestre-nivel__nombre"><?= e($n['nivel_nombre']) ?></span>
                <span class="bimestre-nivel__contexto">
                    <?= (int) $n['total_estudiantes'] ?> estud. &middot; <?= (int) $n['total_calif'] ?> calif.
                </span>
            </div>

            <?php if ((int) $n['total_calif'] === 0): ?>
                <p class="text-muted text-sm">Sin calificaciones en este nivel.</p>
            <?php else: ?>

                <!-- Donut de distribución de literales -->
                <div class="bimestre-donut-bloque">
                    <div class="bimestre-donut"
                         style="background: conic-gradient(
                            var(--lit-ad) 0deg <?= $n['deg_ad'] ?>deg,
                            var(--lit-a) <?= $n['deg_ad'] ?>deg <?= $n['deg_a'] ?>deg,
                            var(--lit-b) <?= $n['deg_a'] ?>deg <?= $n['deg_b'] ?>deg,
                            var(--lit-c) <?= $n['deg_b'] ?>deg 360deg);">
                        <span class="bimestre-donut__hole">
                            <strong><?= (int) $n['total_calif'] ?></strong>
                            <small>calif.</small>
                        </span>
                    </div>
                    <ul class="bimestre-leyenda">
                        <?php foreach ($literales as [$key, $label, $desc]): ?>
                        <li class="bimestre-leyenda__item">
                            <span class="bimestre-leyenda__dot bimestre-leyenda__dot--<?= $key ?>"></span>
                            <span class="bimestre-leyenda__label"><?= $label ?></span>
                            <span class="bimestre-leyenda__val">
                                <?= (int) $n[$key] ?> &middot; <?= e(rtrim(rtrim(number_format($n['pct_' . $key], 1), '0'), '.')) ?>%
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Logro vs en proceso/inicio -->
                <div class="bimestre-logro">
                    <div class="bimestre-logro__barra">
                        <span class="bimestre-logro__seg bimestre-logro__seg--logro"
                              style="width: <?= $n['pct_logro'] ?>%;"></span>
                        <span class="bimestre-logro__seg bimestre-logro__seg--proceso"
                              style="width: <?= $n['pct_proceso'] ?>%;"></span>
                    </div>
                    <div class="bimestre-logro__labels">
                        <span class="bimestre-logro__l bimestre-logro__l--logro">
                            En logro <strong><?= e(rtrim(rtrim(number_format($n['pct_logro'], 1), '0'), '.')) ?>%</strong>
                        </span>
                        <span class="bimestre-logro__l bimestre-logro__l--proceso">
                            En proceso/inicio <strong><?= e(rtrim(rtrim(number_format($n['pct_proceso'], 1), '0'), '.')) ?>%</strong>
                        </span>
                    </div>
                </div>

                <!-- Estudiantes en riesgo -->
                <div class="bimestre-riesgo">
                    <span class="bimestre-riesgo__icono" aria-hidden="true">&#9888;</span>
                    <span class="bimestre-riesgo__num"><?= (int) $n['en_riesgo'] ?></span>
                    <span class="bimestre-riesgo__txt">
                        estudiante<?= (int) $n['en_riesgo'] !== 1 ? 's' : '' ?> en riesgo
                        <small>(promedio general en C)</small>
                    </span>
                </div>

                <!-- Histograma: estudiantes según nº de competencias en C -->
                <?php
                    $hist   = $n['hist'];
                    $buckets = [
                        ['1 C',  $hist['c1']],
                        ['2 C',  $hist['c2']],
                        ['3 C',  $hist['c3']],
                        ['4+ C', $hist['c4plus']],
                    ];
                    $maxBucket = max(1, $hist['c1'], $hist['c2'], $hist['c3'], $hist['c4plus']);
                ?>
                <div class="bimestre-hist">
                    <span class="bimestre-hist__titulo">
                        Estudiantes con C en varias competencias
                        <small>(<?= (int) $n['con_c'] ?> con al menos una)</small>
                    </span>
                    <?php foreach ($buckets as [$etq, $val]):
                        $w = (int) round($val / $maxBucket * 100);
                    ?>
                    <div class="bimestre-hist__fila">
                        <span class="bimestre-hist__etq"><?= $etq ?></span>
                        <span class="bimestre-hist__barra">
                            <span class="bimestre-hist__relleno" style="width: <?= $w ?>%;"></span>
                        </span>
                        <span class="bimestre-hist__val"><?= (int) $val ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
