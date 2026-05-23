<?php
/**
 * @var array $carga
 * @var array $periodo
 * @var array $competencias      [{ id, codigo_minedu, nombre_completo, orden }]
 * @var array $criteriosPorComp  [competencia_id => criterio_id]
 * @var array $alumnos           [{ matricula_id, dni, nombre_completo }]
 * @var array $notasExistentes   [criterio_id][matricula_id] => nota
 * @var array $bloqueos          [competencia_id, ...]
 * @var bool  $bloqueado
 */
?>

<div class="page-header">
    <a href="<?= url('docente/mis-cargas') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Competencias Transversales</h1>
        <p class="page-subtitle">
            <?= e($carga['grado_nombre']) ?> —
            Sección <?= e($carga['seccion_nombre']) ?> —
            <?= e($periodo['nombre_display'] ?? '') ?>
        </p>
    </div>
    <?php if ($bloqueado): ?>
        <span class="badge badge--error">⚠ Periodo bloqueado</span>
    <?php endif; ?>
</div>

<?php if ($bloqueado): ?>
<div class="flash flash--warning">
    El plazo para registrar calificaciones ha vencido. Comunícate con Registro Académico.
</div>
<?php endif; ?>

<?php if (empty($alumnos)): ?>
<div class="empty-state">
    <p>No hay alumnos matriculados y aprobados en esta sección.</p>
</div>
<?php else: ?>

<?php foreach ($competencias as $comp):
    $compId        = (int) $comp['id'];
    $compBloqueada = in_array($compId, $bloqueos ?? []);
    $criterioId    = $criteriosPorComp[$compId] ?? null;
    $tieneCals     = $criterioId && !empty($notasExistentes[$criterioId]);
    // Solo iluminar cuando la competencia está editable: si ya está aprobada,
    // el badge 🔒 Aprobada comunica el estado y el form no se renderiza.
    $iluminar      = $tieneCals && !$compBloqueada;
?>

<div class="competencia-card<?= $iluminar ? ' competencia-card--con-notas' : '' ?>" id="comp-<?= $compId ?>">

    <div class="competencia-card__header">
        <div>
            <span class="competencia-card__codigo"><?= e($comp['codigo_minedu'] ?? '') ?></span>
            <h3 class="competencia-card__nombre"><?= e($comp['nombre_completo']) ?></h3>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <?php if ($compBloqueada): ?>
                <span class="badge badge--error">🔒 Aprobada</span>
            <?php endif; ?>
            <?php if ($criterioId): ?>
                <a href="<?= url('docente/calificaciones/' . $carga['id'] . '/resumen/' . $compId) ?>"
                   class="btn btn--secondary btn--sm">
                    📊 Ver resumen
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="competencia-card__body">

        <?php if ($compBloqueada): ?>
            <div class="flash flash--warning">
                ✅ Esta competencia fue aprobada y bloqueada.
                Las notas ya no pueden modificarse.
            </div>

        <?php elseif (!$criterioId): ?>
            <p class="text-muted">Error: no se pudo crear el criterio de evaluación.</p>

        <?php else: ?>

            <form class="form-notas"
                  data-criterio-id="<?= $criterioId ?>"
                  data-competencia-id="<?= $compId ?>"
                  data-carga-id="<?= (int) $carga['id'] ?>">
                <?= csrf_field() ?>

                <div class="tabla-notas-wrapper">
                <table class="tabla-notas">
                    <thead>
                        <tr>
                            <th class="col-num">N°</th>
                            <th class="col-nombre">Apellidos y nombres</th>
                            <th>DNI</th>
                            <th class="text-center">Nota (0–20)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $i => $alumno): ?>
                        <tr>
                            <td class="col-num"><?= $i + 1 ?></td>
                            <td class="col-nombre"><?= e($alumno['nombre_completo']) ?></td>
                            <td><?= e($alumno['dni']) ?></td>
                            <td class="text-center">
                                <?php $valorInicial = isset($notasExistentes[$criterioId][$alumno['matricula_id']])
                                    ? fmt_nota((int)$notasExistentes[$criterioId][$alumno['matricula_id']])
                                    : ''; ?>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="(0?[0-9]|1[0-9]|20)"
                                    class="input-nota"
                                    name="notas[<?= $alumno['matricula_id'] ?>]"
                                    maxlength="2"
                                    autocomplete="off"
                                    <?= ($bloqueado || $compBloqueada) ? 'disabled' : '' ?>
                                    placeholder="—"
                                    value="<?= e($valorInicial) ?>"
                                    data-nota-inicial="<?= e($valorInicial) ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <?php if (!$bloqueado): ?>
                <div class="form-notas__footer">
                    <button type="submit" class="btn btn--primary">
                        Guardar notas
                    </button>
                    <span class="form-notas__status"></span>
                </div>
                <?php endif; ?>
            </form>

        <?php endif; ?>
    </div>

</div>

<?php endforeach; ?>
<?php endif; ?>
