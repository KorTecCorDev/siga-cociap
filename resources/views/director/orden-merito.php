<?php
/**
 * Selector de periodo del modulo de merito (staff). Sirve a los DOS flujos, que
 * son distintos y no hay que confundir: orden de merito por GRADO (define la
 * media beca) y ranking por SECCION (interno, no la otorga). Los defaults
 * conservan el flujo por grado, que es el que estreno esta vista.
 *
 * @var array  $periodos
 * @var string $rutaBase  destino de cada tarjeta
 * @var string $titulo    encabezado de la pagina
 */
$rutaBase = $rutaBase ?? 'director/orden-merito';
$encabezado = $titulo ?? 'Orden de mérito';
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <h1 class="page-title"><?= e($encabezado) ?></h1>
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