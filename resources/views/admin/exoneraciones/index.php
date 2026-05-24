<?php
/**
 * @var array  $secciones   [{ id, seccion_nombre, grado_nombre, nivel_nombre, nivel_id, total_exoneraciones }, ...]
 * @var array  $anio        { id, anio }
 */

$nivelActual = null;
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Exoneraciones</h1>
        <p class="page-subtitle">Año Académico <?= e($anio['anio']) ?></p>
    </div>
</div>

<?php if (empty($secciones)): ?>
    <div class="empty-state">
        <p>No hay secciones registradas para el año académico activo.</p>
    </div>
<?php else: ?>

<div class="card">
    <table class="tabla-admin">
        <thead>
            <tr>
                <th>Sección</th>
                <th class="text-center">Exoneraciones activas</th>
                <th class="text-right">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($secciones as $s):
                if ($s['nivel_id'] !== $nivelActual):
                    $nivelActual = $s['nivel_id'];
            ?>
                <tr class="fila-separador-nivel">
                    <td colspan="3"><?= e(mb_strtoupper($s['nivel_nombre'])) ?></td>
                </tr>
            <?php endif; ?>
                <tr>
                    <td>
                        <strong><?= e($s['grado_nombre']) ?> — Sección <?= e($s['seccion_nombre']) ?></strong>
                    </td>
                    <td class="text-center">
                        <?php if ((int) $s['total_exoneraciones'] > 0): ?>
                            <span class="badge badge--warning"><?= (int) $s['total_exoneraciones'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= url('admin/exoneraciones/' . $s['id']) ?>"
                           class="btn btn--secondary btn--sm">
                            Ver exoneraciones
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
