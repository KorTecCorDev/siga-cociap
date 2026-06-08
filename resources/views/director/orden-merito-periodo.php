<?php
/**
 * @var array $periodo
 * @var array $ranking
 */
?>

<div class="page-header">
    <a href="<?= url('director/orden-merito') ?>"
       class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">Orden de mérito</h1>
        <p class="page-subtitle">
            <?= e($periodo['nombre_display']) ?> — <?= e($periodo['anio']) ?>
        </p>
    </div>
    <a href="<?= url('director/orden-merito/' . $periodo['id'] . '/imprimir') ?>"
       class="btn btn--primary btn--sm" target="_blank">
        🖨 Imprimir reporte
    </a>
</div>

<?php if (empty($ranking)): ?>
    <div class="empty-state">
        <p>No hay calificaciones registradas en este periodo.</p>
    </div>
<?php else: ?>

    <?php $gradoIdx = 0; foreach ($ranking as $gradoId => $data): $gradoIdx++; ?>
        <details class="card mb-lg" <?= $gradoIdx === 1 ? 'open' : '' ?>>
            <summary class="card__header card__header--toggle">
                <div class="card__header-left">
                    <h2 class="card__title">
                        <?= e($data['grado']['nivel_nombre']) ?> —
                        <?= e($data['grado']['nombre_display']) ?>
                    </h2>
                    <span class="badge badge--info">
                        <?= count($data['estudiantes']) ?> estudiantes
                    </span>
                </div>
                <svg class="card__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <polyline points="6,9 12,15 18,9"/>
                </svg>
            </summary>

            <?php if (empty($data['estudiantes'])): ?>
                <div class="card__body">
                    <p class="text-muted">Sin calificaciones registradas.</p>
                </div>
            <?php else: ?>

                <?php
                $hayPendientes = false;
                foreach ($data['estudiantes'] as $est) {
                    if (!empty($est['empate_pendiente'])) { $hayPendientes = true; break; }
                }
                ?>
                <?php if ($hayPendientes): ?>
                    <div class="alerta-empate">
                        <span class="alerta-empate__texto">
                            ⚠ Hay un empate irreducible en este grado. El puesto en disputa
                            debe resolverlo Registro Académico o Administración.
                        </span>
                        <a href="<?= url('director/orden-merito/' . $periodo['id'] . '/desempate/' . $data['grado']['id']) ?>"
                           class="btn btn--primary btn--sm">Resolver empate</a>
                    </div>
                <?php endif; ?>

                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th class="text-center">Puesto</th>
                            <th>Apellidos y nombres</th>
                            <th class="text-center">Sección</th>
                            <th class="text-center">Comp.</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">Distinción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['estudiantes'] as $est): ?>
                            <?php $pendiente = !empty($est['empate_pendiente']); ?>
                            <tr class="<?= $est['media_beca'] ? 'fila-media-beca' : '' ?> <?= $pendiente ? 'fila-empate' : '' ?>">
                                <td class="text-center">
                                    <span class="puesto puesto--<?= $est['puesto'] <= 3 ? $est['puesto'] : 'normal' ?>">
                                        <?= $est['puesto'] ?>°
                                    </span>
                                </td>
                                <td>
                                    <?= e($est['apellido_paterno'] . ' ' .
                                        $est['apellido_materno'] . ', ' .
                                        $est['nombres']) ?>
                                </td>
                                <td class="text-center">
                                    <?= e($est['seccion_nombre']) ?>
                                </td>
                                <td class="text-center">
                                    <?= (int) $est['num_competencias'] ?>
                                </td>
                                <td class="text-center">
                                    <?= (int) $est['total_notas'] ?>
                                </td>
                                <td class="text-center">
                                    <strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($pendiente): ?>
                                        <span class="badge badge--warning">
                                            ⚠ Empate por resolver
                                        </span>
                                    <?php elseif ($est['media_beca']): ?>
                                        <span class="badge badge--activo">
                                            🏆 Media beca
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </details>
    <?php endforeach; ?>

<?php endif; ?>