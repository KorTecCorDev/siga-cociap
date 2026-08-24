<?php
/**
 * Vista: eje POR DOCENTE de la consulta de notas (24/08/2026).
 * Lista los docentes con registro oficial en el periodo. El destino final es la
 * misma grilla criterio-a-criterio del eje por sección: solo cambia el camino.
 *
 * @var array $periodo
 * @var array $docentes [{ docente_id, nombre, n_cargas, n_secciones, competencias }]
 */
?>

<div class="page-header">
    <a href="<?= url('consulta-notas?periodo_id=' . (int) $periodo['id']) ?>" class="btn btn--secondary btn--sm">&larr; Secciones</a>
    <div>
        <h1 class="page-title">Consulta por docente</h1>
        <p class="page-subtitle">
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
            &middot; <?= count($docentes) ?> docente<?= count($docentes) === 1 ? '' : 's' ?> con registro oficial
        </p>
    </div>
</div>

<?php if (empty($docentes)): ?>
    <div class="empty-state"><p>Ningún docente tiene competencias bloqueadas en este periodo.</p></div>
<?php else: ?>
    <ul class="consulta-cargas">
        <?php foreach ($docentes as $d): ?>
            <li>
                <a class="consulta-carga"
                   href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/docente/' . (int) $d['docente_id']) ?>">
                    <span>
                        <span class="consulta-carga__area"><?= e($d['nombre']) ?></span>
                        <span class="consulta-carga__docente">
                            <?= (int) $d['n_cargas'] ?> carga<?= (int) $d['n_cargas'] === 1 ? '' : 's' ?>
                            &middot; <?= (int) $d['n_secciones'] ?> sección<?= (int) $d['n_secciones'] === 1 ? '' : 'es' ?>
                            &middot; <?= (int) $d['competencias'] ?> competencia<?= (int) $d['competencias'] === 1 ? '' : 's' ?>
                        </span>
                    </span>
                    <span class="consulta-carga__meta">Ver &rarr;</span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
