<?php
/**
 * @var array $carga
 * @var array $periodo
 * @var array $competencia
 * @var array $criterios
 * @var array $alumnos
 * @var bool  $bloqueada
 */

$nivelCodigo = $carga['nivel_codigo'];
?>

<div class="page-header">
    <a href="<?= url('docente/calificaciones/' . $carga['id']) ?>"
       class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            <?php
            // Subárea → mostrar nombre de la subárea
            // Área-curso → mostrar nombre completo de la competencia
            $titulo = $carga['area_tipo'] === 'con_subareas'
                ? ($carga['nombre_display'] ?? $carga['area_nombre'])
                : $competencia['nombre_completo'];

            $tituloCorto = mb_strlen($titulo) > 60
                ? mb_substr($titulo, 0, 60) . '...'
                : $titulo;
            ?>
            <span title="<?= e($titulo) ?>">
                <?= e($tituloCorto) ?>
            </span>
        </h1>
            <p class="page-subtitle">
                <?= e($carga['grado_nombre']) ?> —
                Sección <?= e($carga['seccion_nombre']) ?> —
                <?= e($periodo['nombre_display']) ?>
            </p>
    </div>
    <?php if ($bloqueada): ?>
        <span class="badge badge--error">🔒 Bloqueada</span>
    <?php endif; ?>
</div>

<?php if ($bloqueada): ?>
    <div class="flash flash--warning">
        Esta competencia ya fue aprobada y bloqueada.
        No se pueden realizar más cambios.
    </div>
<?php endif; ?>

<!-- Tabla de resumen -->
<div class="card mb-lg">
    <div class="card__header">
        <h2 class="card__title">
            <?= e($competencia['nombre_completo']) ?>
        </h2>
        <span class="competencia-card__codigo">
            <?= e($competencia['codigo_minedu'] ?? '') ?>
        </span>
    </div>

    <?php if (empty($alumnos)): ?>
        <div class="card__body">
            <p class="text-muted">No hay alumnos matriculados.</p>
        </div>
    <?php else: ?>

        <div class="tabla-responsive">
            <table class="tabla-resumen">
                <thead>
                    <tr>
                        <th class="col-num">N°</th>
                        <th class="col-nombre">Apellidos y nombres</th>
                        <!-- Criterios con tooltip -->
                        <?php foreach ($criterios as $criterio): ?>
                            <th class="col-criterio text-center" title="<?= e($criterio['nombre']) ?>">
                                <span class="criterio-header">
                                    <?= e(mb_strlen($criterio['nombre']) > 15
                                        ? mb_substr($criterio['nombre'], 0, 15) . '...'
                                        : $criterio['nombre']) ?>
                                </span>
                            </th>
                        <?php endforeach; ?>
                        <th class="col-promedio text-center">Promedio</th>
                        <?php if ($nivelCodigo === 'sec'): ?>
                            <th class="col-literal text-center">Literal</th>
                        <?php endif; ?>
                        <th class="col-conclusion">Conclusión descriptiva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $i => $alumno): ?>
                        <?php
                        $promedio  = $alumno['promedio'];
                        $literal   = $alumno['literal'];
                        $esOblig   = false;

                        if ($literal !== null) {
                            if ($nivelCodigo === 'prim' && in_array($literal, ['B','C'])) {
                                $esOblig = true;
                            }
                            if ($nivelCodigo === 'sec' && $literal === 'C') {
                                $esOblig = true;
                            }
                        }
                        ?>
                        <tr class="<?= $esOblig && empty($alumno['conclusion_descriptiva']) ? 'fila-pendiente' : '' ?>">
                            <td class="col-num"><?= $i + 1 ?></td>
                            <td class="col-nombre">
                                <strong><?= e($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']) ?></strong>
                                <br>
                                <small class="text-muted"><?= e($alumno['nombres']) ?></small>
                            </td>

                            <!-- Notas por criterio -->
                            <?php foreach ($criterios as $criterio): ?>
                                <td class="col-criterio text-center">
                                    <?php $nc = $alumno['notas_criterios'][$criterio['id']] ?? null; ?>
                                    <?= $nc !== null ? fmt_nota((int)$nc) : '—' ?>
                                </td>
                            <?php endforeach; ?>

                            <!-- Promedio -->
                            <td class="col-promedio text-center">
                                <?php if ($promedio !== null): ?>
                                    <?php if ($nivelCodigo === 'sec'): ?>
                                        <strong><?= fmt_nota((int)$promedio) ?></strong>
                                    <?php else: ?>
                                        <span class="nota-literal nota-literal--<?= strtolower($literal) ?>">
                                            <?= $literal ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin nota</span>
                                <?php endif; ?>
                            </td>

                            <!-- Literal (solo secundaria) -->
                            <?php if ($nivelCodigo === 'sec'): ?>
                                <td class="col-literal text-center">
                                    <?php if ($literal !== null): ?>
                                        <span class="nota-literal nota-literal--<?= strtolower($literal) ?>">
                                            <?= $literal ?>
                                        </span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>

                            <!-- Conclusión descriptiva -->
                            <td class="col-conclusion">
                                <?php if (!$bloqueada && $promedio !== null): ?>
                                    <div class="conclusion-alumno">
                                        <textarea
                                            class="form-input textarea-conclusion-alumno"
                                            rows="2"
                                            maxlength="500"
                                            data-matricula-id="<?= $alumno['matricula_id'] ?>"
                                            data-carga-id="<?= $carga['id'] ?>"
                                            data-competencia-id="<?= $competencia['id'] ?>"
                                            placeholder="<?= $esOblig ? '* Obligatoria' : 'Opcional...' ?>"
                                        ><?= e($alumno['conclusion_descriptiva'] ?? '') ?></textarea>
                                        <?php if ($esOblig): ?>
                                            <small class="obligatorio">
                                                * Requerida para nota <?= $literal ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!empty($alumno['conclusion_descriptiva'])): ?>
                                    <p class="conclusion-texto">
                                        <?= e($alumno['conclusion_descriptiva']) ?>
                                    </p>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Botones de acción -->
        <?php if (!$bloqueada): ?>
            <div class="resumen-footer">
                <button class="btn btn--primary" id="btn-guardar-conclusiones">
                    💾 Guardar conclusiones
                </button>
                <button class="btn btn--success" id="btn-aprobar-bloquear"
                        data-carga-id="<?= $carga['id'] ?>"
                        data-competencia-id="<?= $competencia['id'] ?>"
                        disabled
                        style="opacity:.5;cursor:not-allowed"
                        title="Primero guarda las conclusiones">
                    ✅ Aprobar y bloquear
                </button>
                <span id="resumen-status"></span>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
