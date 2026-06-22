<?php
/**
 * Orden de mérito POR GRADO (vista docente, read-only). Compite todo el grado;
 * el 1.er puesto obtiene la Media Beca. El ranking por sección vive en su propia
 * vista (/docente/ranking-seccion) para no confundir ambos conceptos.
 *
 * @var array $periodo
 * @var array $ranking  [grado_id => ['grado'=>..., 'estudiantes'=>[...]]]
 */
?>

<div class="page-header">
    <a href="<?= url('docente/orden-merito') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title page-title--wf page-title--merito">Orden de mérito <span class="merito-tag merito-tag--grado">del grado</span></h1>
        <p class="page-subtitle"><?= e($periodo['nombre_display']) ?> — <?= e($periodo['anio']) ?></p>
    </div>
</div>

<div class="merito-aviso merito-aviso--grado">
    Compite <strong>todo el grado</strong>, sin importar la sección.
    El <strong>1.er puesto del grado</strong> obtiene la <strong>Media Beca</strong>.
    ¿Buscas el mejor de cada salón? Mira el
    <a href="<?= url('docente/ranking-seccion/' . $periodo['id']) ?>">Ranking por sección</a>.
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
                    <span class="badge badge--info"><?= count($data['estudiantes']) ?> estudiantes</span>
                </div>
                <svg class="card__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </summary>

            <div class="card__body">
                <?php if (empty($data['estudiantes'])): ?>
                    <p class="text-muted">Sin calificaciones registradas.</p>
                <?php else: ?>
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
                                <?php foreach ($data['estudiantes'] as $est): ?>
                                    <?php $pendiente = !empty($est['empate_pendiente']); ?>
                                    <tr class="<?= !empty($est['media_beca']) ? 'fila-media-beca' : '' ?> <?= $pendiente ? 'fila-empate' : '' ?>">
                                        <td class="col-puesto text-center">
                                            <span class="puesto puesto--<?= $est['puesto'] <= 3 ? $est['puesto'] : 'normal' ?>"><?= (int) $est['puesto'] ?>°</span>
                                        </td>
                                        <td class="col-nombre"><?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?></td>
                                        <td class="text-center"><?= e($est['seccion_nombre']) ?></td>
                                        <td class="text-center"><?= (int) $est['num_competencias'] ?></td>
                                        <td class="text-center"><strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($pendiente): ?>
                                                <span class="badge badge--warning">⚠ Empate por resolver</span>
                                            <?php elseif (!empty($est['media_beca'])): ?>
                                                <span class="badge badge--activo">🏆 Media beca</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>

<?php endif; ?>
