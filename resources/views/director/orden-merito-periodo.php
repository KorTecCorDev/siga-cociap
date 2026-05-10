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
</div>

<?php if (empty($ranking)): ?>
    <div class="empty-state">
        <p>No hay calificaciones registradas en este periodo.</p>
    </div>
<?php else: ?>

    <?php foreach ($ranking as $gradoId => $data): ?>
        <div class="card mb-lg">
            <div class="card__header">
                <h2 class="card__title">
                    <?= e($data['grado']['nivel_nombre']) ?> —
                    <?= e($data['grado']['nombre_display']) ?>
                </h2>
                <span class="badge badge--info">
                    <?= count($data['estudiantes']) ?> estudiantes
                </span>
            </div>

            <?php if (empty($data['estudiantes'])): ?>
                <div class="card__body">
                    <p class="text-muted">Sin calificaciones registradas.</p>
                </div>
            <?php else: ?>

                <table class="tabla-ranking">
                    <thead>
                        <tr>
                            <th class="text-center">Puesto</th>
                            <th>Apellidos y nombres</th>
                            <th class="text-center">Sección</th>
                            <th class="text-center">Promedio</th>
                            <th class="text-center">Distinción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['estudiantes'] as $est): ?>
                            <tr class="<?= $est['media_beca'] ? 'fila-media-beca' : '' ?>">
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
                                    <strong><?= sprintf('%05.2f', $est['promedio_general']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php if ($est['media_beca']): ?>
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
        </div>
    <?php endforeach; ?>

<?php endif; ?>