<?php
/**
 * Panel de tutoría — conclusiones y cierre transversal del bimestre.
 *
 * @var array      $seccion       { id, nombre, grado_nombre, nivel_codigo, ... }
 * @var array      $periodos      bimestres del año activo
 * @var array      $periodoSel    bimestre seleccionado
 * @var array      $estadoCargas  { total, bloqueadas, cargas[] }
 * @var array|null $cierre        cierre vigente o null
 * @var bool       $listo         todas las cargas bloqueadas
 * @var array      $competencias  TIC/GAMA del nivel
 * @var array      $alumnos
 * @var array      $promedios     [matricula_id => [competencia_id => nota]]
 * @var array      $conclusiones  [matricula_id => [competencia_id => texto]]
 */

$nivel    = $seccion['nivel_codigo'] === 'prim' ? 'primaria' : 'secundaria';
$esPrim   = $seccion['nivel_codigo'] === 'prim';
$cerrado  = $cierre !== null;
$pid      = (int) $periodoSel['id'];
?>

<div class="page-header">
    <a href="<?= url('docente/inicio') ?>"
       class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Tutoría — Competencias Transversales</h1>
        <p class="page-subtitle">
            <?= e($seccion['nivel_nombre']) ?> —
            <?= e($seccion['grado_nombre']) ?> —
            Sección <?= e($seccion['nombre']) ?>
        </p>
    </div>
</div>

<!-- Selector de bimestre -->
<div class="tutoria-bimestres">
    <?php foreach ($periodos as $p): ?>
        <a href="<?= url('docente/tutoria/' . $p['id']) ?>"
           class="tutoria-bimestres__item<?= (int) $p['id'] === $pid ? ' tutoria-bimestres__item--activo' : '' ?>">
            <?= e($p['nombre_display']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Estado del bimestre transversal -->
<?php if ($cerrado): ?>
    <div class="flash flash--success">
        ✅ Bimestre transversal cerrado el
        <strong><?= fechaLima($cierre['cerrado_en'], 'd/m/Y H:i') ?></strong>
        por <?= e($cierre['cerrado_por_nombre']) ?>.
        TIC y GAMA ya aparecen en las boletas de la sección.
    </div>
<?php elseif (!$listo): ?>
    <div class="flash flash--warning">
        ⏳ Aún no puedes cerrar:
        <strong><?= (int) $estadoCargas['bloqueadas'] ?> de <?= (int) $estadoCargas['total'] ?></strong>
        competencias de la sección están aprobadas/bloqueadas.
        El cierre se habilita cuando todos los docentes aprueban sus cargas.
    </div>

    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Avance por carga de la sección</h2>
        </div>
        <div class="card__body">
            <table class="tabla-notas tutoria-avance">
                <thead>
                    <tr>
                        <th>Curso / Subárea</th>
                        <th>Docente</th>
                        <th class="text-center">Competencias aprobadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estadoCargas['cargas'] as $c): ?>
                        <?php $completa = (int) $c['comp_bloqueadas'] >= (int) $c['total_comp']; ?>
                        <tr>
                            <td><?= e($c['nombre_display'] ?? '—') ?></td>
                            <td><?= e($c['docente_nombre'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge <?= $completa ? 'badge--activo' : 'badge--warning' ?>">
                                    <?= (int) $c['comp_bloqueadas'] ?>/<?= (int) $c['total_comp'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="flash flash--info">
        Todas las cargas de la sección están aprobadas. Revisa los promedios y
        registra las conclusiones descriptivas: puedes escribir una para cualquier
        alumno; las marcadas como requeridas (primaria: B y C · secundaria: C)
        son obligatorias para cerrar el bimestre transversal.
    </div>
<?php endif; ?>

<!-- Tabla de promedios TIC/GAMA -->
<?php if (empty($alumnos)): ?>
    <div class="empty-state"><p>No hay alumnos matriculados en la sección.</p></div>
<?php elseif ($listo || $cerrado): ?>

<div class="card mb-lg">
    <div class="card__header">
        <h2 class="card__title">
            Promedios C. Transversales — <?= e($periodoSel['nombre_display']) ?>
        </h2>
    </div>
    <div class="tabla-notas-wrapper">
        <table class="tabla-resumen tutoria-tabla">
            <thead>
                <tr>
                    <th class="col-num"<?= $esPrim ? '' : ' rowspan="2"' ?>>N°</th>
                    <th class="col-nombre"<?= $esPrim ? '' : ' rowspan="2"' ?>>Apellidos y nombres</th>
                    <?php foreach ($competencias as $comp): ?>
                        <th class="th-competencia text-center"
                            <?= $esPrim ? '' : 'colspan="2" ' ?>title="<?= e($comp['nombre_completo']) ?>">
                            <?= e($comp['nombre_corto'] ?? $comp['codigo_minedu']) ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="col-conclusion"<?= $esPrim ? '' : ' rowspan="2"' ?>>Conclusiones descriptivas</th>
                </tr>
                <?php if (!$esPrim): ?>
                    <tr>
                        <?php foreach ($competencias as $comp): ?>
                            <th class="col-numeral text-center">Numeral</th>
                            <th class="col-literal text-center">Literal</th>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $i => $alumno): ?>
                    <?php $matId = (int) $alumno['matricula_id']; ?>
                    <tr>
                        <td class="col-num"><?= $i + 1 ?></td>
                        <td class="col-nombre">
                            <strong><?= e($alumno['apellido_paterno'] . ' ' . $alumno['apellido_materno']) ?></strong>
                            <br>
                            <small class="text-muted"><?= e($alumno['nombres']) ?></small>
                        </td>

                        <?php foreach ($competencias as $comp): ?>
                            <?php
                            $cid     = (int) $comp['id'];
                            $nota    = $promedios[$matId][$cid] ?? null;
                            $literal = $nota !== null ? nota_a_literal((int) $nota, $nivel) : null;
                            ?>
                            <?php if (!$esPrim): ?>
                                <td class="col-numeral text-center">
                                    <?= $nota !== null ? fmt_nota((int) $nota) : '—' ?>
                                </td>
                            <?php endif; ?>
                            <td class="col-literal text-center">
                                <?php if ($literal !== null): ?>
                                    <span class="nota-literal nota-literal--<?= strtolower($literal) ?>">
                                        <?= $literal ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>

                        <td class="col-conclusion">
                            <?php $algunTexto = false; ?>
                            <?php foreach ($competencias as $comp): ?>
                                <?php
                                $cid         = (int) $comp['id'];
                                $nota        = $promedios[$matId][$cid] ?? null;
                                $literal     = $nota !== null ? nota_a_literal((int) $nota, $nivel) : null;
                                $obligatoria = $literal !== null
                                    && conclusion_es_obligatoria($literal, $nivel);
                                $texto       = $conclusiones[$matId][$cid] ?? '';
                                $nombreComp  = $comp['nombre_corto'] ?? ($comp['codigo_minedu'] ?? '');
                                if ($texto !== '') { $algunTexto = true; }
                                ?>
                                <?php if (!$cerrado): ?>
                                    <?php // El tutor puede registrar conclusión a CUALQUIER alumno.
                                          // Obligatorias (B/C prim, C sec) y opcionales CON texto se
                                          // muestran abiertas. Las opcionales vacías se colapsan tras un
                                          // enlace para no saturar la tabla; el JS las despliega al clic. ?>
                                    <?php
                                    $concluId  = 'concl-' . $matId . '-' . $cid;
                                    $colapsada = !$obligatoria && $texto === '';
                                    ?>
                                    <div class="tutoria-conclusion<?= $colapsada ? ' tutoria-conclusion--colapsada' : '' ?>">
                                        <?php if ($colapsada): ?>
                                            <button type="button"
                                                    class="btn btn--secondary btn--sm tutoria-conclusion__toggle"
                                                    data-target="<?= $concluId ?>"
                                                    aria-expanded="false"
                                                    data-label-abrir="✎ <?= e($nombreComp) ?>: agregar conclusión"
                                                    data-label-cerrar="✕ <?= e($nombreComp) ?>: ocultar conclusión">✎ <?= e($nombreComp) ?>: agregar conclusión</button>
                                        <?php endif; ?>
                                        <div class="tutoria-conclusion__campo"<?= $colapsada ? ' hidden' : '' ?>>
                                            <label class="tutoria-conclusion__label" for="<?= $concluId ?>">
                                                <?= e($nombreComp) ?>
                                                <?php if ($obligatoria): ?>
                                                    <small class="obligatorio">* Requerida (<?= $literal ?>)</small>
                                                <?php else: ?>
                                                    <small class="text-muted">(opcional)</small>
                                                <?php endif; ?>
                                            </label>
                                            <textarea
                                                id="<?= $concluId ?>"
                                                class="form-input textarea-conclusion-transversal"
                                                rows="2"
                                                maxlength="500"
                                                data-matricula-id="<?= $matId ?>"
                                                data-competencia-id="<?= $cid ?>"
                                                data-obligatorio="<?= $obligatoria ? '1' : '0' ?>"
                                                placeholder="<?= $obligatoria ? '* Obligatoria' : 'Conclusión opcional' ?>"><?= e($texto) ?></textarea>
                                        </div>
                                    </div>
                                <?php elseif ($texto !== ''): ?>
                                    <p class="conclusion-texto">
                                        <strong><?= e($nombreComp) ?>:</strong>
                                        <?= e($texto) ?>
                                    </p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($cerrado && !$algunTexto): ?>
                                <span class="text-muted text-sm">— sin conclusión</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!$cerrado): ?>
        <div class="resumen-footer tutoria-footer">
            <button class="btn btn--primary" id="btn-guardar-conclusiones-trans"
                    data-periodo-id="<?= $pid ?>">
                <span class="btn-icon btn-icon--save" aria-hidden="true"></span>
                Guardar conclusiones
            </button>
            <button class="btn btn--success" id="btn-cerrar-transversal"
                    data-periodo-id="<?= $pid ?>" disabled
                    title="Primero guarda las conclusiones">
                <span class="btn-icon btn-icon--upload" aria-hidden="true"></span>
                Aprobar y Bloquear
            </button>
            <span id="tutoria-status"></span>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
