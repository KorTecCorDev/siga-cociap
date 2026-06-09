<?php
/**
 * Registro oficial de constancias de traslado.
 *
 * @var array      $traslados
 * @var array      $anios
 * @var int        $anioId
 * @var string|null $estado
 */
?>

<div class="page-header">
    <a href="<?= url('matriculas') ?>" class="btn btn--secondary btn--sm">← Matrículas</a>
    <div>
        <h1 class="page-title">Constancias de traslado</h1>
        <p class="page-subtitle"><?= count($traslados) ?> constancia<?= count($traslados) !== 1 ? 's' : '' ?> en el registro</p>
    </div>
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('traslados') ?>">
            <div class="mat-filtros">
                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="anio_id">Año académico</label>
                    <select id="anio_id" name="anio_id" class="form-input" onchange="this.form.submit()">
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= (int) $a['id'] === $anioId ? 'selected' : '' ?>>
                                <?= e((string) $a['anio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mat-filtros__campo">
                    <label class="mat-filtros__label" for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="vigente" <?= $estado === 'vigente' ? 'selected' : '' ?>>Vigentes</option>
                        <option value="anulado" <?= $estado === 'anulado' ? 'selected' : '' ?>>Anuladas</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <?php if (empty($traslados)): ?>
            <div class="empty-state"><p>No hay constancias registradas para este filtro.</p></div>
        <?php else: ?>
        <div class="tabla-notas-wrapper">
            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Estudiante</th>
                        <th>Grado / Sección</th>
                        <th>IE destino</th>
                        <th>Fecha</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($traslados as $t): ?>
                    <tr class="<?= $t['estado'] === 'anulado' ? 'fila-inactiva' : '' ?>">
                        <td class="text-sm"><strong><?= e($t['numero_constancia']) ?></strong></td>
                        <td class="text-sm"><?= e($t['estudiante_nombre']) ?><br>
                            <span class="text-muted">DNI <?= e($t['dni']) ?></span></td>
                        <td class="text-sm"><?= e(($t['grado_nombre'] ?? '—') . ' ' . ($t['seccion_nombre'] ?? '')) ?></td>
                        <td class="text-sm"><?= e($t['ie_destino_nombre']) ?></td>
                        <td class="text-sm"><?= fecha_es($t['fecha_constancia']) ?></td>
                        <td class="text-center">
                            <span class="matricula-badge matricula-badge--<?= $t['estado'] === 'anulado' ? 'desactivado' : 'continuador' ?>">
                                <?= $t['estado'] === 'anulado' ? 'Anulada' : 'Vigente' ?>
                            </span>
                        </td>
                        <td class="td-acciones">
                            <a href="<?= url('traslados/' . $t['id'] . '/imprimir') ?>"
                               class="btn btn--secondary btn--sm" target="_blank" rel="noopener">Imprimir</a>
                            <?php if ($t['estado'] === 'vigente'): ?>
                            <form method="POST" action="<?= url('traslados/' . $t['id'] . '/anular') ?>"
                                  onsubmit="return anularConstancia(this)">
                                <?= csrf_field() ?>
                                <input type="hidden" name="motivo_anulacion" value="">
                                <button type="submit" class="btn btn--danger btn--sm">Anular</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function anularConstancia(form) {
    var motivo = window.prompt('Motivo de anulación de la constancia (queda registrado):');
    if (motivo === null) return false;
    motivo = motivo.trim();
    if (motivo === '') { alert('Debes indicar un motivo.'); return false; }
    form.querySelector('input[name="motivo_anulacion"]').value = motivo;
    return true;
}
</script>
