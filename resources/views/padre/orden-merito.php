<?php
/**
 * Orden de mérito del GRADO — vista de familias (rediseño 2, fase 6).
 * Solo lectura y solo del bimestre PUBLICADO al nivel del hijo (compuerta 044).
 * Misma tabla que ve el claustro; la fila del hijo va resaltada.
 *
 * Los rótulos usan el grado/nivel donde el alumno COMPITE (matrícula operativa si
 * hay retorno de grado), NO los de la matrícula oficial: la tabla es de ese grado.
 *
 * @var array  $hijo
 * @var array  $periodo
 * @var string $gradoNombre    Grado donde compite (puede diferir del oficial)
 * @var string $nivelNombre    Nivel de ese grado
 * @var array  $estudiantes    Ranking del grado (shape de OrdenMeritoModel)
 * @var int    $matriculaHijo  Matricula del hijo en el ranking (0 = no rankeado)
 */
?>

<div class="page-header">
    <a href="<?= url('padre/notas') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Orden de mérito del grado</h1>
        <p class="page-subtitle">
            <?= e($nivelNombre) ?> —
            <?= e($gradoNombre) ?> —
            <?= e($periodo['nombre_display']) ?>
        </p>
    </div>
    <div class="btn-group">
        <a href="<?= url('padre/ranking-seccion') ?>" class="btn btn--secondary btn--sm">
            Ranking por sección
        </a>
    </div>
</div>

<div class="merito-aviso merito-aviso--grado">
    Compite <strong>todo el grado</strong>, sin importar la sección.
    El <strong>1.er puesto del grado</strong> obtiene la <strong>Media Beca</strong>.
</div>

<?php if (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>Aún no hay un orden de mérito publicado para este bimestre.</p>
    </div>
<?php else: ?>

    <?php if ($matriculaHijo === 0): ?>
        <div class="flash flash--info">
            <?= e($hijo['nombres']) ?> aún no figura en el orden de mérito de este bimestre.
        </div>
    <?php endif; ?>

    <div class="card mb-lg">
        <div class="card__header card__header--between">
            <h2 class="card__title"><?= e($gradoNombre) ?> — todas las secciones</h2>
            <span class="badge badge--info"><?= count($estudiantes) ?> estudiantes</span>
        </div>
        <div class="card__body">
            <div class="tabla-responsive">
                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th class="col-puesto text-center">Puesto</th>
                            <th class="col-nombre">Apellidos y nombres</th>
                            <th class="text-center">Sección</th>
                            <th class="text-center">Comp.</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">Distinción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $est): ?>
                            <?php
                            $pendiente = !empty($est['empate_pendiente']);
                            $esHijo    = (int) $est['matricula_id'] === $matriculaHijo;
                            $clases    = [];
                            if (!empty($est['media_beca'])) { $clases[] = 'fila-media-beca'; }
                            if ($pendiente)                 { $clases[] = 'fila-empate'; }
                            if ($esHijo)                    { $clases[] = 'fila-propia'; }
                            ?>
                            <tr class="<?= implode(' ', $clases) ?>">
                                <td class="col-puesto text-center">
                                    <span class="puesto puesto--<?= $est['puesto'] <= 3 ? $est['puesto'] : 'normal' ?>"><?= (int) $est['puesto'] ?>°</span>
                                </td>
                                <td class="col-nombre">
                                    <?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?>
                                </td>
                                <td class="text-center"><?= e($est['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) $est['num_competencias'] ?></td>
                                <td class="text-center"><strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong></td>
                                <td class="text-center">
                                    <?php if ($pendiente): ?>
                                        <span class="badge badge--warning">Empate por resolver</span>
                                    <?php elseif (!empty($est['media_beca'])): ?>
                                        <span class="badge badge--activo">🏆 Media beca</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>
