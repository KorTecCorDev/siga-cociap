<?php
/**
 * Ranking por SECCIÓN (vista docente, read-only). Ranking interno de cada
 * sección. NO otorga media beca: esa solo la define el orden de mérito del
 * GRADO (/docente/orden-merito).
 *
 * @var array $periodo
 * @var array $ranking  [grado_id => ['grado'=>..., 'secciones'=>[sec=>[...]]]]
 */
?>

<div class="page-header">
    <a href="<?= url('docente/ranking-seccion') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title page-title--wf page-title--ranking">Ranking <span class="merito-tag merito-tag--seccion">por sección</span></h1>
        <p class="page-subtitle"><?= e($periodo['nombre_display']) ?> — <?= e($periodo['anio']) ?></p>
    </div>
</div>

<div class="merito-aviso merito-aviso--seccion">
    Ranking <strong>interno de cada sección</strong>. Ser el 1.° de la sección
    <strong>NO otorga media beca</strong>: esa solo la obtiene el 1.° del grado.
    Para la media beca, mira el
    <a href="<?= url('docente/orden-merito/' . $periodo['id']) ?>">Orden de mérito del grado</a>.
</div>

<?php if (empty($ranking)): ?>
    <div class="empty-state"><p>No hay calificaciones registradas en este periodo.</p></div>
<?php else: ?>

    <?php $i = 0; foreach ($ranking as $gradoId => $data): $i++; ?>
        <details class="card mb-lg" <?= $i === 1 ? 'open' : '' ?>>
            <summary class="card__header card__header--toggle">
                <div class="card__header-left">
                    <h2 class="card__title">
                        <?= e($data['grado']['nivel_nombre']) ?> — <?= e($data['grado']['nombre_display']) ?>
                    </h2>
                    <span class="badge badge--info"><?= count($data['secciones']) ?> secciones</span>
                </div>
                <svg class="card__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </summary>

            <div class="card__body">
                <?php if (empty($data['secciones'])): ?>
                    <p class="text-muted">Sin calificaciones registradas.</p>
                <?php else: ?>
                    <?php $j = 0; foreach ($data['secciones'] as $secNombre => $estudiantes): $j++;
                        // Mismo color por letra de seccion que en /docente/mis-cargas:
                        // la clase seccion-ancla--{letra} solo define las custom props
                        // --sec-line/bg/ink, que el acordeon reutiliza (sin duplicar).
                        $secLetra = mb_strtoupper(mb_substr((string) $secNombre, 0, 1));
                        $secColor = in_array(mb_strtolower($secLetra), ['a','b','c','d','e','f'], true)
                            ? mb_strtolower($secLetra) : 'x';
                    ?>
                        <details class="merito-seccion-acordeon seccion-ancla--<?= $secColor ?>" <?= $j === 1 ? 'open' : '' ?>>
                            <summary class="merito-seccion-acordeon__head">
                                <span class="merito-seccion-acordeon__letra"><?= e($secLetra) ?></span>
                                <span class="merito-seccion-acordeon__titulo">Sección <?= e($secNombre) ?></span>
                                <span class="badge badge--info"><?= count($estudiantes) ?> estudiantes</span>
                                <svg class="card__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <polyline points="6,9 12,15 18,9"/>
                                </svg>
                            </summary>
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
                                            <?php $pendiente = !empty($est['empate_pendiente']); ?>
                                            <tr class="<?= $pendiente ? 'fila-empate' : '' ?>">
                                                <td class="col-puesto text-center">
                                                    <span class="puesto puesto--<?= $est['puesto'] <= 3 ? $est['puesto'] : 'normal' ?>"><?= (int) $est['puesto'] ?>°</span>
                                                </td>
                                                <td class="col-nombre"><?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?></td>
                                                <td class="text-center"><?= (int) $est['num_competencias'] ?></td>
                                                <td class="text-center"><strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>

<?php endif; ?>
