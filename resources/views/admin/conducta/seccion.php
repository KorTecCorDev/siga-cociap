<?php
/**
 * @var array  $seccion      { id, grado_nombre, seccion_nombre, nivel_nombre }
 * @var array  $periodos     [{ id, numero, nombre_display, editable }]
 * @var array  $estudiantes  [{ matricula_id, nombre_completo, conducta[periodo_id] }]
 * @var array  $literales    ['AD','A','B','C']
 */

$csrfToken = \Core\Session::csrfToken();

// Tooltips por literal — coherentes con la leyenda del proyecto
$titulos = [
    'AD' => 'Muy bueno',
    'A'  => 'Bueno',
    'B'  => 'En proceso',
    'C'  => 'Inicio',
];
?>

<div class="page-header">
    <a href="<?= url('admin/conducta') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Conducta — <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle"><?= e($seccion['nivel_nombre']) ?></p>
    </div>
</div>

<div class="conducta-leyenda">
    <strong>Escala:</strong>
    <span class="conducta-lit conducta-lit--ad">AD</span> Muy bueno &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--a">A</span> Bueno &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--b">B</span> En proceso &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--c">C</span> Inicio
</div>

<div id="conducta-feedback" class="conducta-feedback" hidden role="status" aria-live="polite"></div>

<?php if (empty($periodos)): ?>
    <div class="empty-state">
        <p>No hay periodo abierto para edición. Comunícate con Registro Académico.</p>
    </div>
<?php elseif (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>No hay estudiantes matriculados en esta sección.</p>
    </div>
<?php else: ?>

<div class="tabla-notas-wrapper">
    <table class="tabla-notas conducta-tabla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <?php foreach ($periodos as $p): ?>
                    <th class="conducta-th-periodo">
                        <?= e($p['nombre_display']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $i => $est): ?>
                <tr>
                    <td class="col-num"><?= $i + 1 ?></td>
                    <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>
                    <?php foreach ($periodos as $p):
                        $val = $est['conducta'][$p['id']] ?? '';
                    ?>
                        <td class="conducta-td-periodo">
                            <div class="conducta-control"
                                 data-matricula="<?= $est['matricula_id'] ?>"
                                 data-periodo="<?= $p['id'] ?>"
                                 data-csrf="<?= e($csrfToken) ?>"
                                 data-valor="<?= e($val) ?>"
                                 role="group"
                                 aria-label="Conducta de <?= e($est['nombre_completo']) ?> en <?= e($p['nombre_display']) ?>">
                                <?php foreach ($literales as $lit):
                                    $activo = $val === $lit;
                                ?>
                                    <button type="button"
                                            class="conducta-btn conducta-btn--<?= strtolower($lit) ?><?= $activo ? ' conducta-btn--activo' : '' ?>"
                                            data-lit="<?= $lit ?>"
                                            title="<?= e($titulos[$lit]) ?>"
                                            aria-pressed="<?= $activo ? 'true' : 'false' ?>">
                                        <?= $lit ?>
                                    </button>
                                <?php endforeach; ?>
                                <button type="button"
                                        class="conducta-btn conducta-btn--clear"
                                        data-lit=""
                                        title="Limpiar"
                                        aria-label="Limpiar nota">×</button>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
