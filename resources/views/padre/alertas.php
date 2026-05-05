<?php /** @var array $alertas @var array|null $hijo */ ?>

<div class="page-header">
    <a href="<?= url('padre/inicio') ?>" class="btn btn--secondary btn--sm">
        ← Volver
    </a>
    <h1 class="page-title">Alertas del tutor</h1>
</div>

<?php if (empty($alertas)): ?>
    <div class="empty-state">
        <p>No tienes alertas por el momento.</p>
    </div>
<?php else: ?>

    <?php foreach ($alertas as $alerta): ?>
        <div class="alerta-item alerta-item--<?= $alerta['leida'] ? 'leida' : 'nueva' ?>">
            <div class="alerta-item__header">
                <span class="alerta-item__tipo badge badge--<?= $alerta['tipo'] === 'academica' ? 'error' : 'warning' ?>">
                    <?= e(ucfirst($alerta['tipo'])) ?>
                </span>
                <span class="alerta-item__fecha text-muted">
                    <?= fecha_es($alerta['created_at']) ?>
                </span>
                <?php if (!$alerta['leida']): ?>
                    <span class="badge badge--info">Nueva</span>
                <?php endif; ?>
            </div>
            <p class="alerta-item__mensaje"><?= e($alerta['mensaje']) ?></p>
            <p class="alerta-item__tutor text-muted">
                Tutor: <?= e($alerta['tutor_nombre']) ?>
            </p>
        </div>
    <?php endforeach; ?>

<?php endif; ?>