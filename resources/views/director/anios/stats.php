<?php
/**
 * @var array $periodo  { id, anio_id, anio, nombre_display, estado, ... }
 * @var array $stats    ['por_grado' => [...], 'docentes' => [...]]
 * @var array $resumen  ['niveles' => [...]]
 */
$badge = match ($periodo['estado']) {
    'activo'  => 'badge--activo',
    'cerrado' => 'badge--warning',
    default   => 'badge--sin-notas',
};
$label = match ($periodo['estado']) {
    'activo'  => 'Activo',
    'cerrado' => 'Cerrado',
    default   => 'Pendiente',
};

$porGrado = $stats['por_grado'] ?? [];
$docentes = $stats['docentes'] ?? [];
?>

<div class="page-header">
    <a href="<?= url('director/anios/' . $periodo['anio_id']) ?>" class="btn btn--secondary btn--sm">
        &larr; Año <?= e($periodo['anio']) ?>
    </a>
    <div>
        <h1 class="page-title">
            Indicadores &middot; <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
            <span class="badge <?= $badge ?>"><?= $label ?></span>
        </h1>
        <p class="page-subtitle">Rendimiento por grado y totales del bimestre por nivel</p>
    </div>
</div>

<div class="stats-layout">
    <div class="stats-layout__main">
        <div class="card">
            <div class="card__body">
                <?php require __DIR__ . '/_grados.php'; ?>
            </div>
        </div>
    </div>

    <aside class="stats-layout__panel">
        <div class="card">
            <div class="card__body">
                <?php require __DIR__ . '/_panel-bimestre.php'; ?>
            </div>
        </div>

        <?php if (!empty($reaperturas)): ?>
        <div class="card">
            <div class="card__body">
                <?php require __DIR__ . '/_reaperturas.php'; ?>
            </div>
        </div>
        <?php endif; ?>
    </aside>
</div>

<div class="card stats-docentes-card">
    <div class="card__body">
        <?php require __DIR__ . '/_docentes.php'; ?>
    </div>
</div>
