<?php
/**
 * @var array  $periodos  [{ id, numero, nombre_display, anio, total_generadas }]
 * @var string $titulo
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Boletas Públicas</h1>
        <p class="page-subtitle">Genera y distribuye boletas con código de acceso sin login</p>
    </div>
</div>

<?php if ($flash_success): ?>
<div class="alert alert--success"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert--error"><?= e($flash_error) ?></div>
<?php endif; ?>

<?php if (empty($periodos)): ?>
<div class="card">
    <div class="card__body">
        <p class="text-muted text-center">No hay períodos activos configurados.</p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">Períodos del año activo</h2>
        <p class="text-sm text-muted">Selecciona un período para gestionar sus boletas públicas</p>
    </div>
    <div class="bp-periodos-grid">
        <?php foreach ($periodos as $p): ?>
        <a href="<?= url("admin/boletas-publicas/{$p['id']}") ?>" class="bp-periodo-card">
            <div class="bp-periodo-card__num">B<?= (int) $p['numero'] ?></div>
            <div class="bp-periodo-card__info">
                <strong class="bp-periodo-card__nombre"><?= e($p['nombre_display']) ?></strong>
                <span class="bp-periodo-card__anio"><?= e($p['anio']) ?></span>
            </div>
            <div class="bp-periodo-card__badge">
                <?php if ($p['total_generadas'] > 0): ?>
                <span class="badge badge--success"><?= (int) $p['total_generadas'] ?> generada<?= $p['total_generadas'] != 1 ? 's' : '' ?></span>
                <?php else: ?>
                <span class="badge badge--warning">Sin generar</span>
                <?php endif; ?>
            </div>
            <div class="bp-periodo-card__arrow">→</div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>
