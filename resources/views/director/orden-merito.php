<?php /** @var array $periodos */ ?>

<div class="page-header">
    <h1 class="page-title">Orden de mérito</h1>
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
                    <a href="<?= url('director/orden-merito/' . $periodo['id']) ?>"
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