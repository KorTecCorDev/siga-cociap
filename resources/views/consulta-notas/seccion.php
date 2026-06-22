<?php
/**
 * Consulta de calificaciones — areas/cargas oficiales de una seccion.
 * @var array      $periodo
 * @var array|null $seccion  null si la seccion no tiene notas oficiales
 * @var array      $cargas   [{carga_id, area_nombre, subarea_nombre, docente, competencias}]
 */
?>

<div class="page-header">
    <a href="<?= url('consulta-notas?periodo_id=' . (int) $periodo['id']) ?>"
       class="btn btn--secondary btn--sm">← Secciones</a>
    <div>
        <h1 class="page-title">
            <?= $seccion ? e($seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre']) : 'Sección' ?>
        </h1>
        <p class="page-subtitle">
            <?= $seccion ? e($seccion['nivel_nombre']) . ' · ' : '' ?>
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?> — solo notas oficiales
        </p>
    </div>
</div>

<?php if (empty($cargas)): ?>
    <div class="empty-state"><p>Esta sección no tiene notas oficiales en este periodo.</p></div>
<?php else: ?>
    <ul class="consulta-cargas">
        <?php foreach ($cargas as $c): ?>
            <?php
            $area = $c['subarea_nombre']
                ? $c['area_nombre'] . ' — ' . $c['subarea_nombre']
                : $c['area_nombre'];
            ?>
            <li>
                <a class="consulta-carga"
                   href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/carga/' . (int) $c['carga_id']) ?>">
                    <span>
                        <span class="consulta-carga__area"><?= e($area) ?></span>
                        <span class="consulta-carga__docente"><?= e($c['docente'] ?: 'Sin docente') ?></span>
                    </span>
                    <span class="consulta-carga__meta"><?= (int) $c['competencias'] ?> competencia(s) →</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
