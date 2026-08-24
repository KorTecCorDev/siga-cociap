<?php
/**
 * Vista: asistencia de la sección en SOLO LECTURA (24/08/2026).
 *
 * Cuarto registro del bimestre en la capa de supervisión. A diferencia de
 * transversales y conducta, NO exige cierre: se muestra EN VIVO. El estado del
 * cierre aparece como dato en la cabecera, no como candado.
 *
 * @var array      $periodo
 * @var array      $seccion  { seccion_id, seccion_nombre, grado_nombre, nivel_nombre }
 * @var array      $alumnos  [{ matricula_id, nombre_completo, dni, incidencias{...} }]
 * @var array      $totales  { faltas, faltas_justificadas, tardanzas, tardanzas_justificadas, registrados }
 * @var array|null $cierre
 */
$volver = url('consulta-notas/' . (int) $periodo['id'] . '/seccion/' . (int) $seccion['seccion_id']);
?>

<div class="page-header">
    <a href="<?= $volver ?>" class="btn btn--secondary btn--sm">&larr; Sección</a>
    <div>
        <h1 class="page-title">Asistencia</h1>
        <p class="page-subtitle">
            <?= e($seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre']) ?>
            &middot; <?= e($seccion['nivel_nombre']) ?>
            &middot; <?= e($periodo['nombre_display']) ?>
        </p>
    </div>
</div>

<div class="card">
    <div class="card__body">
        <p class="text-sm text-muted">
            <?php if ($cierre && !empty($cierre['bloqueado_en'])): ?>
                Registro <strong>cerrado</strong> el <?= e(fecha_es($cierre['bloqueado_en'])) ?>.
            <?php else: ?>
                Registro <strong>en curso</strong>: Registro Académico todavía puede modificarlo.
            <?php endif; ?>
            <?= (int) $totales['registrados'] ?> de <?= count($alumnos) ?> estudiantes con registro.
            Vista de consulta — para modificar se usa <em>Asistencia</em> en el panel de Registro Académico.
        </p>
    </div>
</div>

<?php if (empty($alumnos)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state"><p>Esta sección no tiene estudiantes en el roster.</p></div>
        </div>
    </div>
<?php else: ?>
<div class="card">
    <div class="card__body">
        <div class="tabla-notas-wrapper">
            <table class="tabla-resumen">
                <thead>
                    <tr>
                        <th class="col-num">N°</th>
                        <th class="col-nombre">Apellidos y nombres</th>
                        <th class="text-center" title="Faltas">F</th>
                        <th class="text-center" title="Faltas justificadas">FJ</th>
                        <th class="text-center" title="Tardanzas">T</th>
                        <th class="text-center" title="Tardanzas justificadas">TJ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos as $i => $a): $inc = $a['incidencias']; ?>
                        <tr<?= empty($inc['registrado']) ? ' class="fila-pendiente"' : '' ?>>
                            <td class="col-num"><?= $i + 1 ?></td>
                            <td class="col-nombre"><?= e($a['nombre_completo']) ?></td>
                            <td class="text-center"><?= (int) $inc['faltas'] ?></td>
                            <td class="text-center"><?= (int) $inc['faltas_justificadas'] ?></td>
                            <td class="text-center"><?= (int) $inc['tardanzas'] ?></td>
                            <td class="text-center"><?= (int) $inc['tardanzas_justificadas'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="tabla-resumen__total">
                        <td colspan="2"><strong>Total de la sección</strong></td>
                        <td class="text-center"><strong><?= (int) $totales['faltas'] ?></strong></td>
                        <td class="text-center"><strong><?= (int) $totales['faltas_justificadas'] ?></strong></td>
                        <td class="text-center"><strong><?= (int) $totales['tardanzas'] ?></strong></td>
                        <td class="text-center"><strong><?= (int) $totales['tardanzas_justificadas'] ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="text-sm text-muted">
            La fila resaltada indica estudiantes <strong>sin registro</strong> de asistencia en este bimestre
            (se muestran en cero, que no es lo mismo que cero inasistencias confirmadas).
        </p>
    </div>
</div>
<?php endif; ?>
