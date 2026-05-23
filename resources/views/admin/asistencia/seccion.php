<?php
/**
 * @var array       $seccion         { id, grado_nombre, seccion_nombre, nivel_nombre }
 * @var array|null  $periodoActivo   { id, nombre_display, ... }
 * @var array       $estudiantes     [{ matricula_id, dni, nombre_completo, incidencias{...} }]
 * @var int         $topeMax         valor máximo por contador (espejo del backend)
 */

$csrfToken = \Core\Session::csrfToken();
?>

<div class="page-header">
    <a href="<?= url('admin/asistencia') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Asistencia — <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle">
            <?= e($seccion['nivel_nombre']) ?>
            <?php if ($periodoActivo): ?>
                · <strong><?= e($periodoActivo['nombre_display']) ?></strong>
            <?php endif; ?>
        </p>
    </div>
</div>

<div id="asistencia-feedback" class="asistencia-feedback" hidden role="status" aria-live="polite"></div>

<?php if (!$periodoActivo): ?>
    <div class="empty-state">
        <p>No hay periodo abierto para edición. Comunícate con Registro Académico.</p>
    </div>
<?php elseif (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>No hay estudiantes matriculados en esta sección.</p>
    </div>
<?php else: ?>

<div class="tabla-notas-wrapper">
    <table class="tabla-notas asistencia-tabla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <th class="asistencia-th-dni">DNI</th>
                <th class="asistencia-th-contador" title="Faltas">F</th>
                <th class="asistencia-th-contador" title="Faltas justificadas">FJ</th>
                <th class="asistencia-th-contador" title="Tardanzas">T</th>
                <th class="asistencia-th-contador" title="Tardanzas justificadas">TJ</th>
                <th class="asistencia-th-acciones">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $i => $est):
                $inc = $est['incidencias'];
            ?>
                <tr class="asistencia-fila<?= $inc['registrado'] ? ' asistencia-fila--registrada' : '' ?>"
                    data-matricula-id="<?= (int) $est['matricula_id'] ?>"
                    data-periodo-id="<?= (int) $periodoActivo['id'] ?>"
                    data-csrf="<?= e($csrfToken) ?>">
                    <td class="col-num"><?= $i + 1 ?></td>
                    <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>
                    <td class="asistencia-td-dni"><?= e($est['dni']) ?></td>

                    <?php foreach (['faltas', 'faltas_justificadas', 'tardanzas', 'tardanzas_justificadas'] as $campo):
                        $val = (int) $inc[$campo];
                    ?>
                        <td class="asistencia-td-input">
                            <input type="number"
                                   class="asistencia-input"
                                   name="<?= $campo ?>"
                                   min="0"
                                   max="<?= $topeMax ?>"
                                   step="1"
                                   inputmode="numeric"
                                   autocomplete="off"
                                   value="<?= $val ?>"
                                   data-inicial="<?= $val ?>"
                                   aria-label="<?= $campo ?> de <?= e($est['nombre_completo']) ?>">
                        </td>
                    <?php endforeach; ?>

                    <td class="asistencia-td-acciones">
                        <button type="button" class="btn btn--primary btn--sm asistencia-guardar">
                            Guardar
                        </button>
                        <span class="asistencia-status" aria-live="polite"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>
