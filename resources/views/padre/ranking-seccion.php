<?php
/**
 * Ranking interno de la SECCIÓN del hijo — vista de familias (rediseño 2, fase 6).
 * Solo la sección del estudiante: el resto de secciones del grado no le compete.
 * NO otorga media beca (esa solo sale del orden de mérito del grado).
 *
 * Los rótulos usan el grado/nivel/sección donde el alumno COMPITE (matrícula
 * operativa si hay retorno de grado), NO los de la matrícula oficial.
 *
 * @var array  $hijo
 * @var array  $periodo
 * @var string $gradoNombre
 * @var string $nivelNombre
 * @var string $seccionNombre
 * @var array  $estudiantes    Ranking de la seccion (shape de OrdenMeritoModel)
 * @var int    $matriculaHijo  Matricula del hijo en el ranking (0 = no rankeado)
 */
?>

<div class="page-header">
    <a href="<?= url('padre/notas') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Ranking por sección</h1>
        <p class="page-subtitle">
            <?= e($nivelNombre) ?> —
            <?= e($gradoNombre) ?> —
            Sección <?= e($seccionNombre) ?> —
            <?= e($periodo['nombre_display']) ?>
        </p>
    </div>
    <div class="btn-group">
        <a href="<?= url('padre/orden-merito') ?>" class="btn btn--secondary btn--sm">
            Orden de mérito del grado
        </a>
    </div>
</div>

<div class="merito-aviso merito-aviso--seccion">
    Ranking <strong>interno de la sección</strong>. Ser el 1.° de la sección
    <strong>no otorga media beca</strong>: esa la obtiene el 1.° del grado.
</div>

<?php if (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>Aún no hay un ranking publicado para esta sección en el bimestre.</p>
    </div>
<?php else: ?>

    <?php if ($matriculaHijo === 0): ?>
        <div class="flash flash--info">
            <?= e($hijo['nombres']) ?> aún no figura en el ranking de este bimestre.
        </div>
    <?php endif; ?>

    <div class="card mb-lg">
        <div class="card__header card__header--between">
            <h2 class="card__title">Sección <?= e($seccionNombre) ?></h2>
            <span class="badge badge--info"><?= count($estudiantes) ?> estudiantes</span>
        </div>
        <div class="card__body">
            <div class="tabla-responsive">
                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th class="col-puesto text-center">Puesto</th>
                            <th class="col-nombre">Apellidos y nombres</th>
                            <th class="text-center">Comp.</th>
                            <th class="text-center">Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $est): ?>
                            <?php
                            $pendiente = !empty($est['empate_pendiente']);
                            $esHijo    = (int) $est['matricula_id'] === $matriculaHijo;
                            $clases    = [];
                            if ($pendiente) { $clases[] = 'fila-empate'; }
                            if ($esHijo)    { $clases[] = 'fila-propia'; }
                            ?>
                            <tr class="<?= implode(' ', $clases) ?>">
                                <td class="col-puesto text-center">
                                    <span class="puesto puesto--<?= $est['puesto'] <= 3 ? $est['puesto'] : 'normal' ?>"><?= (int) $est['puesto'] ?>°</span>
                                </td>
                                <td class="col-nombre">
                                    <?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?>
                                </td>
                                <td class="text-center"><?= (int) $est['num_competencias'] ?></td>
                                <td class="text-center"><strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
