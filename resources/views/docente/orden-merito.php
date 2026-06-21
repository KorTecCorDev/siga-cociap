<?php
/**
 * Selector de periodos compartido por los dos flujos (orden de merito y
 * ranking por seccion). El destino lo define $rutaBase.
 * @var array  $periodos
 * @var string $rutaBase  'docente/orden-merito' | 'docente/ranking-seccion'
 * @var string $titulo
 */
?>

<div class="page-header">
    <a href="<?= url('docente/inicio') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <h1 class="page-title"><?= e($titulo) ?></h1>
</div>

<?php if (empty($periodos)): ?>
    <div class="empty-state">
        <p>No hay periodos con calificaciones registradas aún.</p>
    </div>
<?php else: ?>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Selecciona un periodo</h2>
        </div>
        <div class="card__body">
            <div class="periodos-grid">
                <?php foreach ($periodos as $periodo): ?>
                    <a href="<?= url($rutaBase . '/' . $periodo['id']) ?>"
                       class="periodo-item">
                        <div class="periodo-item__nombre">
                            <?= e($periodo['nombre_display']) ?>
                        </div>
                        <div class="periodo-item__anio">
                            <?= e($periodo['anio']) ?>
                        </div>
                        <div class="periodo-item__estado badge badge--<?= $periodo['estado'] === 'activo' ? 'activo' : 'info' ?>">
                            <?= e(ucfirst($periodo['estado'])) ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php endif; ?>
