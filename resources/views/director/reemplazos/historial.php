<?php
/**
 * Historial de reemplazos de docente de una carga.
 * @var array $carga
 * @var array $reemplazos  [{id, motivo, reasignado_en, periodo_nombre, saliente_nombre, entrante_nombre, reasignado_por_nombre}]
 */
?>

<div class="page-header">
    <a href="<?= url('director/cargas/seccion/' . (int) $carga['seccion_id']) ?>"
       class="btn btn--secondary btn--sm">← Cargas de la sección</a>
    <div>
        <h1 class="page-title">Reemplazos de docente</h1>
        <p class="page-subtitle">
            <?= e($carga['area_nombre']) ?><?= $carga['subarea_nombre'] ? ' · ' . e($carga['subarea_nombre']) : '' ?>
            — <?= e($carga['grado_nombre']) ?> <?= e($carga['seccion_nombre']) ?>
        </p>
    </div>
    <a href="<?= url('director/cargas/' . (int) $carga['id'] . '/reemplazar') ?>"
       class="btn btn--primary">+ Nuevo reemplazo</a>
</div>

<?php if (empty($reemplazos)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>Esta carga no tiene reemplazos de docente registrados.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="tabla-notas-wrapper">
            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Bimestre</th>
                        <th>Saliente → Entrante</th>
                        <th>Motivo</th>
                        <th>Reasignó</th>
                        <th class="text-right">Auditoría</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reemplazos as $r): ?>
                        <tr>
                            <td class="text-sm"><?= e(date('d/m/Y H:i', strtotime($r['reasignado_en']))) ?></td>
                            <td class="text-sm"><?= e($r['periodo_nombre'] ?? '—') ?></td>
                            <td>
                                <div class="td-usuario__nombre"><?= e($r['saliente_nombre']) ?></div>
                                <div class="text-sm text-muted">→ <?= e($r['entrante_nombre']) ?></div>
                            </td>
                            <td class="text-sm"><?= e($r['motivo']) ?></td>
                            <td class="text-sm"><?= e($r['reasignado_por_nombre']) ?></td>
                            <td class="text-right">
                                <a href="<?= url('director/reemplazos/' . (int) $r['id'] . '/snapshot') ?>"
                                   class="btn btn--secondary btn--sm">Ver snapshot</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
