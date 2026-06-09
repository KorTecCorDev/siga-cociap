<?php
/**
 * @var int   $paso
 * @var array $matricula
 * @var array $requeridos   tipo => label
 * @var array $actuales     tipo => fila documentos_matricula
 * @var array $obligatorios tipos obligatorios para activar (el resto es opcional)
 * @var array $grupoDni     tipos DNI de apoderado ("al menos uno") o []
 */
$obligatorios = $obligatorios ?? [];
$grupoDni     = $grupoDni ?? [];
$pasos = [1 => 'Estudiante', 2 => 'Apoderado', 3 => 'Documentos'];
$mid   = (int) $matricula['id'];
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid . '/apoderado') ?>" class="btn btn--secondary btn--sm">← Apoderado</a>
    <div>
        <h1 class="page-title">Documentos</h1>
        <p class="page-subtitle"><?= e($matricula['nombre_completo']) ?> · <?= ucfirst(e($matricula['tipo'])) ?></p>
    </div>
</div>

<div class="wizard-steps">
    <?php foreach ($pasos as $n => $label): ?>
        <div class="wizard-steps__item <?= $n === $paso ? 'wizard-steps__item--activo' : ($n < $paso ? 'wizard-steps__item--completado' : '') ?>">
            <span class="wizard-steps__num"><?= $n < $paso ? '✓' : $n ?></span>
            <span><?= $n ?>. <?= $label ?></span>
        </div>
        <?php if ($n < 3): ?><span class="wizard-steps__sep"></span><?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card__body">
        <form method="POST" action="<?= url('matriculas/' . $mid . '/documentos') ?>">
            <?= csrf_field() ?>

            <div class="form-grid">
                <p class="form-section-title">Serie del recibo</p>
                <div class="form-group form-group--full">
                    <label class="form-label" for="serie_recibo">Serie del recibo <span class="text-danger">*</span></label>
                    <input type="text" id="serie_recibo" name="serie_recibo" class="form-input" maxlength="30"
                           value="<?= e((string) ($matricula['serie_recibo'] ?? '')) ?>" required>
                </div>
            </div>

            <p class="form-section-title">
                Checklist de documentos
                <?= $matricula['tipo'] === 'continuador' ? '(continuador: solo recibo)' : '(traslado/nuevo: completo)' ?>
            </p>
            <?php if (!empty($grupoDni)): ?>
                <p class="text-sm text-muted">
                    Para activar se exigen los marcados con <span class="text-danger">*</span>
                    y al menos uno de los DNI de apoderado. El resto es opcional.
                </p>
            <?php endif; ?>

            <div class="documento-checklist">
                <?php foreach ($requeridos as $tipo => $label):
                    $doc = $actuales[$tipo] ?? null;
                    $chk = $doc && (int) $doc['entregado'] === 1;
                    $esObligatorio = in_array($tipo, $obligatorios, true);
                    $esGrupoDni    = in_array($tipo, $grupoDni, true);
                ?>
                <div class="documento-checklist__item">
                    <div class="documento-checklist__check">
                        <input type="checkbox" name="entregado[<?= e($tipo) ?>]" value="1" <?= $chk ? 'checked' : '' ?>>
                    </div>
                    <div>
                        <div class="documento-checklist__nombre">
                            <?= e($label) ?>
                            <?php if ($esObligatorio): ?><span class="text-danger">*</span>
                            <?php elseif ($esGrupoDni): ?><span class="text-muted text-sm">(al menos uno)</span>
                            <?php else: ?><span class="text-muted text-sm">(opcional)</span><?php endif; ?>
                        </div>
                        <input type="text" name="observacion[<?= e($tipo) ?>]" class="form-input"
                               placeholder="Observación (opcional)"
                               value="<?= e((string) ($doc['observacion'] ?? '')) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary">Guardar y finalizar</button>
                <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary">Ver matrícula</a>
            </div>
        </form>
    </div>
</div>
