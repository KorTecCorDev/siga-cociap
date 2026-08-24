<?php
/**
 * Consulta de calificaciones — areas/cargas oficiales de una seccion.
 * @var array      $periodo
 * @var array|null $seccion  null si la seccion no tiene notas oficiales
 * @var array      $cargas   [{carga_id, area_nombre, subarea_nombre, docente, competencias, transversales}]
 * @var bool       $tieneTransversales  cierre transversal vigente
 * @var bool       $tieneConducta       conducta con sus DOS etapas y sin anular
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

<?php // Registros de nivel SECCION (no cuelgan de ninguna carga). Transversales
      // y conducta solo aparecen cuando estan oficialmente cerrados; si no, su
      // ruta responde 404.
      //
      // ASISTENCIA es la excepcion (24/08/2026): se muestra EN VIVO y por eso
      // su tarjeta esta SIEMPRE. Una inasistencia ya ocurrio: no es una nota
      // sujeta a aprobacion del docente. ?>
<?php if ($seccion): ?>
    <ul class="consulta-cargas mb-lg">
        <?php if ($tieneTransversales): ?>
            <li>
                <a class="consulta-carga"
                   href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $seccion['seccion_id'] . '/transversales') ?>">
                    <span>
                        <span class="consulta-carga__area">Competencias Transversales</span>
                        <span class="consulta-carga__docente">Promedio agregado de la sección — el que va a la boleta</span>
                    </span>
                    <span class="consulta-carga__meta">Ver →</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($tieneConducta): ?>
            <li>
                <a class="consulta-carga"
                   href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $seccion['seccion_id'] . '/conducta') ?>">
                    <span>
                        <span class="consulta-carga__area">Conducta</span>
                        <span class="consulta-carga__docente">Cerrada por auxiliar y tutor</span>
                    </span>
                    <span class="consulta-carga__meta">Ver →</span>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a class="consulta-carga"
               href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $seccion['seccion_id'] . '/asistencia') ?>">
                <span>
                    <span class="consulta-carga__area">Asistencia</span>
                    <span class="consulta-carga__docente">Faltas y tardanzas del bimestre — en vivo</span>
                </span>
                <span class="consulta-carga__meta">Ver &rarr;</span>
            </a>
        </li>
    </ul>
<?php endif; ?>

<?php if (empty($cargas)): ?>
    <div class="empty-state"><p>Esta sección no tiene notas oficiales en este periodo.</p></div>
<?php else: ?>
    <ul class="consulta-cargas">
        <?php foreach ($cargas as $c): ?>
            <?php
            $area = $c['subarea_nombre']
                ? $c['area_nombre'] . ' — ' . $c['subarea_nombre']
                : $c['area_nombre'];
            $nTransv = (int) ($c['transversales'] ?? 0);
            ?>
            <li>
                <a class="consulta-carga"
                   href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/carga/' . (int) $c['carga_id']) ?>">
                    <span>
                        <span class="consulta-carga__area"><?= e($area) ?></span>
                        <span class="consulta-carga__docente"><?= e($c['docente'] ?: 'Sin docente') ?></span>
                    </span>
                    <span class="consulta-carga__meta">
                        <?= (int) $c['competencias'] ?> competencia(s)<?= $nTransv > 0 ? ' · incl. ' . $nTransv . ' transv.' : '' ?> →
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
