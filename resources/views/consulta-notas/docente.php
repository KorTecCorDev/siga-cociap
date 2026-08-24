<?php
/**
 * Vista: cargas oficiales de UN docente en el periodo (24/08/2026).
 * Cada carga enlaza a `consulta-notas/{periodo}/carga/{carga}` — la misma
 * pantalla a la que llega el eje por sección.
 *
 * @var array $periodo
 * @var array $docente { id, nombre }
 * @var array $cargas  [{ carga_id, area_nombre, subarea_nombre, grado_nombre,
 *                        seccion_nombre, nivel_nombre, competencias }]
 */
?>

<div class="page-header">
    <a href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/docentes') ?>" class="btn btn--secondary btn--sm">&larr; Docentes</a>
    <div>
        <h1 class="page-title"><?= e($docente['nombre']) ?></h1>
        <p class="page-subtitle">
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
            &middot; <?= count($cargas) ?> carga<?= count($cargas) === 1 ? '' : 's' ?> con registro oficial
        </p>
    </div>
</div>

<ul class="consulta-cargas">
    <?php foreach ($cargas as $c): ?>
        <li>
            <a class="consulta-carga"
               href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/carga/' . (int) $c['carga_id']) ?>">
                <span>
                    <span class="consulta-carga__area">
                        <?= e($c['subarea_nombre'] ?: $c['area_nombre']) ?>
                    </span>
                    <span class="consulta-carga__docente">
                        <?= e($c['grado_nombre'] . ' ' . $c['seccion_nombre']) ?>
                        &middot; <?= e($c['nivel_nombre']) ?>
                        &middot; <?= (int) $c['competencias'] ?> competencia<?= (int) $c['competencias'] === 1 ? '' : 's' ?>
                    </span>
                </span>
                <span class="consulta-carga__meta">Ver &rarr;</span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
