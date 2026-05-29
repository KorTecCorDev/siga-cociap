<?php
/**
 * @var array $anios  [{ id, anio, fecha_inicio, fecha_fin, estado,
 *                       total_bimestres, bimestres_activos, bimestres_cerrados }]
 */

/** Devuelve la clase de badge según el estado del año. */
$badgeAnio = fn(string $estado): string => match ($estado) {
    'activo'  => 'badge--activo',
    'cerrado' => 'badge--warning',
    default   => 'badge--info',
};

$labelAnio = fn(string $estado): string => match ($estado) {
    'activo'  => 'Activo',
    'cerrado' => 'Cerrado',
    default   => 'Planificado',
};
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&larr; Dashboard</a>
    <div>
        <h1 class="page-title">Año académico</h1>
        <p class="page-subtitle">Gestiona los años escolares y sus bimestres</p>
    </div>
    <a href="<?= url('director/anios/crear') ?>" class="btn btn--primary">+ Nuevo año</a>
</div>

<?php if (empty($anios)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>No hay años académicos registrados.</p>
                <a href="<?= url('director/anios/crear') ?>" class="btn btn--primary btn--sm">Crear el primero</a>
            </div>
        </div>
    </div>
<?php else: ?>

    <div class="anios-grid">
        <?php foreach ($anios as $a): ?>
        <a href="<?= url('director/anios/' . $a['id']) ?>" class="anio-tile anio-tile--<?= e($a['estado']) ?>">
            <div class="anio-tile__head">
                <span class="anio-tile__anio"><?= e($a['anio']) ?></span>
                <span class="badge <?= $badgeAnio($a['estado']) ?>"><?= $labelAnio($a['estado']) ?></span>
            </div>
            <div class="anio-tile__fechas">
                <?= e(fecha_es($a['fecha_inicio'])) ?> &ndash; <?= e(fecha_es($a['fecha_fin'])) ?>
            </div>
            <div class="anio-tile__bimestres">
                <span><?= (int) $a['total_bimestres'] ?> bimestres</span>
                <?php if ((int) $a['bimestres_activos'] > 0): ?>
                    <span class="anio-tile__chip anio-tile__chip--activo"><?= (int) $a['bimestres_activos'] ?> activo</span>
                <?php endif; ?>
                <?php if ((int) $a['bimestres_cerrados'] > 0): ?>
                    <span class="anio-tile__chip anio-tile__chip--cerrado"><?= (int) $a['bimestres_cerrados'] ?> cerrado<?= (int) $a['bimestres_cerrados'] !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
