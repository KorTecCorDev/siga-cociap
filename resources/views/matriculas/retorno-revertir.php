<?php
/**
 * @var array $matricula  matrícula OFICIAL (grado/sección SIAGIE)
 * @var array $retorno    retorno activo (incluye grado_destino, seccion_destino)
 * @var array $periodos   bimestres cerrados con notas en el grado operativo
 */
$mid = (int) $matricula['id'];
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary btn--sm">← Ver matrícula</a>
    <div>
        <h1 class="page-title">Revertir retorno de grado</h1>
        <p class="page-subtitle"><?= e($matricula['nombre_completo']) ?> · Grado oficial: <?= e($matricula['grado_nombre'] ?? '—') ?></p>
    </div>
</div>

<div class="retorno-aviso">
    <strong>⚠ Caso especial.</strong> El estudiante vuelve a calificarse en su
    grado/sección OFICIAL
    <strong><?= e(($matricula['grado_nombre'] ?? '') . ' ' . ($matricula['seccion_nombre'] ?? '')) ?></strong>.
    El grado operativo
    <strong><?= e(($retorno['grado_destino'] ?? '—') . ' ' . ($retorno['seccion_destino'] ?? '')) ?></strong>
    se desactivará. La boleta siempre muestra el
    grado/sección oficial y consolida automáticamente las notas de los bimestres
    cursados en el grado operativo.
</div>

<div class="card">
    <div class="card__body">
        <p class="form-section-title">Bimestres que se consolidan</p>
        <?php if (empty($periodos)): ?>
            <p class="text-muted">El grado operativo aún no tiene notas registradas; no hay nada que consolidar.</p>
        <?php else: ?>
            <ul class="mat-pendientes__list">
                <?php foreach ($periodos as $p): ?>
                    <li><?= e($p['nombre_display']) ?> (cerrado)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="<?= url('matriculas/' . $mid . '/retorno/revertir') ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label class="form-label" for="motivo">Motivo de la reversión <span class="text-danger">*</span></label>
                    <textarea id="motivo" name="motivo" class="form-input" rows="4" required
                              placeholder="Describe por qué el estudiante vuelve a su grado oficial..."></textarea>
                </div>
            </div>
            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--danger"
                        onclick="return confirm('¿Confirmas la reversión? El estudiante volverá a calificarse en su grado oficial.')">
                    Revertir retorno
                </button>
                <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
