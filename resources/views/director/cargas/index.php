<?php
/**
 * @var array  $porNivel   ['Primaria' => [...secciones...], 'Secundaria' => [...]]
 * @var array  $auth_user
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Cargas Académicas</h1>
        <p class="page-subtitle">Selecciona una sección para ver o gestionar sus cargas</p>
    </div>
    <a href="<?= url('director/cargas/crear') ?>" class="btn btn--primary">+ Nueva carga</a>
</div>

<?php if (empty($porNivel)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>No hay secciones activas registradas.</p>
            </div>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($porNivel as $nivel => $secciones): ?>

        <h2 class="cargas-nivel-titulo"><?= e($nivel) ?></h2>

        <div class="cargas-secciones-grid">
            <?php foreach ($secciones as $s):
                $total   = (int) $s['total_cargas'];
                $activas = (int) $s['cargas_activas'];

                if ($total === 0) {
                    $statsClass = 'empty';
                } elseif ($activas === $total) {
                    $statsClass = 'ok';
                } else {
                    $statsClass = 'warn';
                }
            ?>
            <a href="<?= url('director/cargas/seccion/' . $s['id']) ?>"
               class="cargas-seccion-tile">
                <span class="cargas-seccion-tile__grado"><?= e($s['grado_nombre']) ?></span>
                <span class="cargas-seccion-tile__letra"><?= e($s['seccion_nombre']) ?></span>
                <span class="cargas-seccion-tile__stats cargas-seccion-tile__stats--<?= $statsClass ?>">
                    <?php if ($total === 0): ?>
                        Sin cargas
                    <?php else: ?>
                        <?= $activas ?> / <?= $total ?> activas
                    <?php endif; ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

    <?php endforeach; ?>

<?php endif; ?>
