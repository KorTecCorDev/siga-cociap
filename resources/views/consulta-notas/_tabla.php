<?php
/**
 * Parcial: tabla criterio-a-criterio en SOLO LECTURA.
 * Mismo lenguaje visual que el resumen del docente (clases .tabla-resumen,
 * .col-*, .nota-numeral, .nota-literal, .exo-badge, .omision-badge), pero sin
 * inputs ni botones: la conclusion se muestra como texto.
 * @var array $competencia  [nombre_completo, es_transversal, ...]
 * @var array $criterios
 * @var array $alumnos
 * @var array $exonerados   matricula_ids exonerados
 * @var string $nivelCodigo 'prim' | 'sec'
 */
$esTransversal = !empty($competencia['es_transversal']);
$exoneradosSet = array_flip($exonerados ?? []);
?>

<?php if (empty($alumnos)): ?>
    <div class="card__body"><p class="text-muted">No hay alumnos matriculados.</p></div>
<?php elseif (empty($criterios)): ?>
    <div class="card__body">
        <p class="text-muted">
            Competencia bloqueada sin calificaciones registradas
            (no se trabajó en el <?= e($periodo['nombre_display']) ?>).
        </p>
    </div>
<?php else: ?>
    <div class="tabla-responsive">
        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th class="col-num">N°</th>
                    <th class="col-nombre">Apellidos y nombres</th>
                    <?php foreach ($criterios as $criterio): ?>
                        <?php
                        $tooltipCriterio = $criterio['nombre']
                            . (!empty($criterio['descripcion']) ? "\n\n" . $criterio['descripcion'] : '');
                        ?>
                        <th class="col-criterio text-center" title="<?= e($tooltipCriterio) ?>">
                            <span class="criterio-header">
                                <?= e(mb_strlen($criterio['nombre']) > 15
                                    ? mb_substr($criterio['nombre'], 0, 15) . '...'
                                    : $criterio['nombre']) ?>
                            </span>
                        </th>
                    <?php endforeach; ?>
                    <th class="col-numeral col-resultado col-resultado--inicio text-center">Promedio numeral</th>
                    <th class="col-literal col-resultado text-center">Literal</th>
                    <th class="col-conclusion">Conclusión descriptiva</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $i => $alumno): ?>
                    <?php
                    $esExonerado = isset($exoneradosSet[$alumno['matricula_id']]);
                    $promedio    = $alumno['promedio'];
                    $literal     = $alumno['literal'];
                    ?>
                    <tr class="<?= $esExonerado ? 'fila-exonerado' : '' ?>">
                        <td class="col-num"><?= $i + 1 ?></td>
                        <td class="col-nombre"><?= e($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno'] . ', ' . $alumno['nombres']) ?></td>

                        <?php foreach ($criterios as $criterio): ?>
                            <td class="col-criterio text-center">
                                <?php if ($esExonerado): ?>
                                    <span class="exo-badge" title="Exonerado(a)">EXO</span>
                                <?php else:
                                    $nc = $alumno['notas_criterios'][$criterio['id']] ?? null;
                                    $om = $alumno['omisiones_criterios'][$criterio['id']] ?? null;
                                ?>
                                    <?php if ($nc !== null): ?>
                                        <?= fmt_nota((int) $nc) ?>
                                    <?php elseif ($om !== null): ?>
                                        <span class="omision-badge"
                                              title="<?= e(\App\Models\OmisionCriterioModel::etiqueta($om)) ?>">—</span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>

                        <td class="col-numeral col-resultado col-resultado--inicio text-center">
                            <?php if ($esExonerado): ?>
                                <span class="exo-badge" title="Exonerado(a)">EXO</span>
                            <?php elseif ($promedio !== null): ?>
                                <span class="nota-numeral nota-numeral--<?= strtolower($literal) ?>">
                                    <?= fmt_nota((int) $promedio) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <td class="col-literal col-resultado text-center">
                            <?php if ($esExonerado): ?>
                                <span class="exo-badge exo-badge--lit" title="Exonerado(a)">EXO</span>
                            <?php elseif ($literal !== null): ?>
                                <span class="nota-literal nota-literal--<?= strtolower($literal) ?>">
                                    <?= $literal ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td class="col-conclusion">
                            <?php if ($esTransversal): ?>
                                <span class="text-muted text-sm">La registra el tutor</span>
                            <?php elseif ($esExonerado): ?>
                                <span class="text-muted text-sm">Exonerado(a) — no aplica</span>
                            <?php elseif (!empty($alumno['conclusion_descriptiva'])): ?>
                                <p class="conclusion-texto"><?= e($alumno['conclusion_descriptiva']) ?></p>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
