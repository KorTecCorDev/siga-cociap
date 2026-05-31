<?php
/**
 * @var array $matricula
 * @var array $secciones   secciones de grados inferiores
 */
$mid = (int) $matricula['id'];
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary btn--sm">← Ver matrícula</a>
    <div>
        <h1 class="page-title">Retorno de grado</h1>
        <p class="page-subtitle"><?= e($matricula['nombre_completo']) ?> · Grado oficial: <?= e($matricula['grado_nombre'] ?? '—') ?></p>
    </div>
</div>

<div class="retorno-aviso">
    <strong>⚠ Caso especial.</strong> El retorno de grado crea una matrícula
    <em>operativa</em> en un grado inferior al oficial de SIAGIE. El estudiante
    asistirá y será calificado en ese grado operativo, y competirá en el orden
    de mérito de ese grado. La matrícula oficial se conserva intacta. Esta acción
    queda registrada y es auditable.
</div>

<?php if (empty($secciones)): ?>
    <div class="card"><div class="card__body"><div class="empty-state">
        <p>No hay secciones de grados inferiores disponibles para este estudiante.</p>
    </div></div></div>
<?php else: ?>
<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('matriculas/' . $mid . '/retorno') ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label class="form-label" for="seccion_destino_id">Sección destino (grado inferior) <span class="text-danger">*</span></label>
                    <select id="seccion_destino_id" name="seccion_destino_id" class="form-input" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($secciones as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['grado_nombre'] . ' ' . $s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="motivo">Motivo <span class="text-danger">*</span></label>
                    <textarea id="motivo" name="motivo" class="form-input" rows="4" required
                              placeholder="Describe la razón del retorno de grado..."></textarea>
                </div>
            </div>
            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--danger"
                        onclick="return confirm('¿Confirmas el retorno de grado? Se creará la matrícula operativa.')">
                    Registrar retorno
                </button>
                <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
