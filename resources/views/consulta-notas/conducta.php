<?php
/**
 * Consulta — CONDUCTA de una seccion (solo lectura).
 * Sin formularios ni JS: para escribir esta /admin/conducta, que es admin+RA.
 *
 * ⚠️ Dos modelos distintos segun el bimestre:
 *   · LEGADO (I Bimestre, anterior a la migracion 021): literal directo, sin
 *     criterios ni respuestas Si/No -> tabla simple nombre + literal.
 *   · NUEVO (II Bimestre+): nota RA derivada de las respuestas + nota del tutor
 *     -> grilla con las dos y la final.
 * `getEstudiantesParaTutor` resuelve las dos y marca `es_legado`.
 *
 * @var array $periodo
 * @var array $seccion    [seccion_id, seccion_nombre, grado_nombre, nivel_nombre]
 * @var array $cierre     cierre con las dos etapas (ra_nombre, tutor_nombre, fechas)
 * @var array $alumnos    [{matricula_id, nombre_completo, nota_ra, nota_tutor, nota_final, literal_final, si, respondidos, es_legado}]
 * @var array $criterios  criterios de conducta del nivel (vacio si es legado)
 * @var bool  $esLegado
 */
?>

<div class="page-header">
    <a href="<?= url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $seccion['seccion_id']) ?>"
       class="btn btn--secondary btn--sm">← Áreas</a>
    <div>
        <h1 class="page-title">Conducta</h1>
        <p class="page-subtitle">
            <?= e($seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre']) ?> ·
            <?= e($seccion['nivel_nombre']) ?> ·
            <?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?>
        </p>
    </div>
    <span class="badge badge--activo">Solo lectura</span>
</div>

<div class="flash flash--info">
    Cerrada en sus dos etapas:
    bloqueada por <strong><?= e($cierre['ra_nombre'] ?? '—') ?></strong>
    el <?= fechaLima($cierre['ra_bloqueado_en'], 'd/m/Y H:i') ?>,
    cerrada por <strong><?= e($cierre['tutor_nombre'] ?? '—') ?></strong>
    el <?= fechaLima($cierre['tutor_cerrado_en'], 'd/m/Y H:i') ?>.
    <?php if ($esLegado): ?>
        <br>Este bimestre usa el <strong>registro legado</strong>: solo literal,
        sin criterios ni nota numérica.
    <?php endif; ?>
</div>

<?php if (empty($alumnos)): ?>
    <div class="empty-state"><p>La sección no tiene estudiantes en el roster.</p></div>
<?php else: ?>
<div class="card mb-lg">
    <div class="tabla-notas-wrapper">
        <table class="tabla-resumen">
            <thead>
                <tr>
                    <th class="col-num">N°</th>
                    <th class="col-nombre">Apellidos y nombres</th>
                    <?php if ($esLegado): ?>
                        <th class="col-literal col-resultado text-center">Literal</th>
                    <?php else: ?>
                        <th class="col-numeral col-resultado col-resultado--inicio text-center"
                            title="Derivada de las respuestas registradas">Nota auxiliar</th>
                        <th class="col-numeral col-resultado text-center">Nota tutor</th>
                        <th class="col-numeral col-resultado text-center">Final</th>
                        <th class="col-literal col-resultado text-center">Literal</th>
                        <th class="text-center" title="Criterios respondidos con Sí sobre el total">Sí / total</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $i => $a): ?>
                    <?php $lit = $a['literal_final']; ?>
                    <tr>
                        <td class="col-num"><?= $i + 1 ?></td>
                        <td class="col-nombre"><?= e($a['nombre_completo']) ?></td>

                        <?php if ($esLegado): ?>
                            <td class="col-literal col-resultado text-center">
                                <?php if ($lit !== null): ?>
                                    <span class="nota-literal nota-literal--<?= strtolower($lit) ?>"><?= e($lit) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td class="col-numeral col-resultado col-resultado--inicio text-center">
                                <?= $a['nota_ra'] !== null ? fmt_nota((int) $a['nota_ra']) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="col-numeral col-resultado text-center">
                                <?= $a['nota_tutor'] !== null ? fmt_nota((int) $a['nota_tutor']) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="col-numeral col-resultado text-center">
                                <?php if ($a['nota_final'] !== null): ?>
                                    <span class="nota-numeral nota-numeral--<?= strtolower($lit ?? '') ?>">
                                        <?= fmt_nota((int) $a['nota_final']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-literal col-resultado text-center">
                                <?php if ($lit !== null): ?>
                                    <span class="nota-literal nota-literal--<?= strtolower($lit) ?>"><?= e($lit) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-sm text-muted">
                                <?= (int) $a['si'] ?> / <?= count($criterios) ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$esLegado && !empty($criterios)): ?>
    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Criterios evaluados (<?= count($criterios) ?>)</h2>
        </div>
        <div class="card__body">
            <ol class="text-sm">
                <?php foreach ($criterios as $cr): ?>
                    <li><?= e($cr['texto']) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
<?php endif; ?>
<?php endif; ?>
