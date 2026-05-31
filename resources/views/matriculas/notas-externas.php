<?php
/**
 * @var array $matricula
 * @var array $notas
 */
$mid = (int) $matricula['id'];
?>

<div class="page-header">
    <a href="<?= url('matriculas/' . $mid) ?>" class="btn btn--secondary btn--sm">← Ver matrícula</a>
    <div>
        <h1 class="page-title">Notas externas</h1>
        <p class="page-subtitle"><?= e($matricula['nombre_completo']) ?> · Traslado de entrada</p>
    </div>
</div>

<div class="retorno-aviso mb-md">
    Registra los promedios obtenidos en el colegio de origen. Estas notas son
    referenciales (escala literal AD/A/B/C) y no reemplazan las calificaciones del COCIAP.
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="POST" action="<?= url('matriculas/' . $mid . '/notas-externas') ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <p class="form-section-title">Nueva nota externa</p>
                <div class="form-group">
                    <label class="form-label" for="area_nombre">Área <span class="text-danger">*</span></label>
                    <input type="text" id="area_nombre" name="area_nombre" class="form-input" maxlength="120" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="competencia_nombre">Competencia <span class="text-danger">*</span></label>
                    <input type="text" id="competencia_nombre" name="competencia_nombre" class="form-input" maxlength="120" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="periodo_nombre">Periodo <span class="text-danger">*</span></label>
                    <input type="text" id="periodo_nombre" name="periodo_nombre" class="form-input" maxlength="30" placeholder="I Bimestre" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="nota_literal">Nota literal <span class="text-danger">*</span></label>
                    <select id="nota_literal" name="nota_literal" class="form-input" required>
                        <option value="">—</option>
                        <option value="AD">AD — Logro destacado</option>
                        <option value="A">A — Logro esperado</option>
                        <option value="B">B — En proceso</option>
                        <option value="C">C — En inicio</option>
                    </select>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="colegio_origen">Colegio de origen</label>
                    <input type="text" id="colegio_origen" name="colegio_origen" class="form-input" maxlength="200">
                </div>
            </div>
            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary">Agregar nota</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <p class="form-section-title">Notas registradas (<?= count($notas) ?>)</p>
        <?php if (empty($notas)): ?>
            <div class="empty-state"><p>Aún no hay notas externas.</p></div>
        <?php else: ?>
            <div class="tabla-notas-wrapper">
                <table class="tabla-notas">
                    <thead><tr><th>Área</th><th>Competencia</th><th>Periodo</th><th class="text-center">Nota</th><th>Colegio origen</th></tr></thead>
                    <tbody>
                        <?php foreach ($notas as $n): ?>
                        <tr>
                            <td class="text-sm"><?= e($n['area_nombre']) ?></td>
                            <td class="text-sm"><?= e($n['competencia_nombre']) ?></td>
                            <td class="text-sm"><?= e($n['periodo_nombre']) ?></td>
                            <td class="text-center"><span class="matricula-badge matricula-badge--nuevo"><?= e($n['nota_literal']) ?></span></td>
                            <td class="text-sm text-muted"><?= e($n['colegio_origen'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
