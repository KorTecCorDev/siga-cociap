<?php
/**
 * Conducta — ETAPA 1 (Registro Academico): grilla de criterios Si/No.
 *
 * @var array       $seccion       { id, grado_nombre, seccion_nombre, nivel_nombre, nivel_id }
 * @var array|null  $periodoActivo { id, nombre_display, ... }
 * @var array       $criterios     [{ id, texto, orden }]
 * @var array       $estudiantes   [{ matricula_id, nombre_completo, respuestas[criterio_id] }]
 * @var array|null  $cierre        cierre vigente (si la seccion ya esta bloqueada) o null
 * @var array       $completitud   { esperados, completos }
 */

$csrfToken = \Core\Session::csrfToken();
$total     = count($criterios);
$bloqueada = $cierre !== null;
$cerradaT  = $bloqueada && !empty($cierre['tutor_cerrado_en']);
$completo  = $completitud['esperados'] > 0 && $completitud['completos'] >= $completitud['esperados'];
?>

<div class="page-header">
    <a href="<?= url('admin/conducta') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Conducta — <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle">
            <?= e($seccion['nivel_nombre']) ?>
            <?php if ($periodoActivo): ?>
                · <strong><?= e($periodoActivo['nombre_display']) ?></strong>
            <?php endif; ?>
        </p>
    </div>
</div>

<div id="conducta-feedback" class="conducta-feedback" hidden role="status" aria-live="polite"></div>

<?php if (!$periodoActivo): ?>
    <div class="empty-state"><p>No hay periodo abierto para edición. Comunícate con Registro Académico.</p></div>
<?php elseif (empty($estudiantes)): ?>
    <div class="empty-state"><p>No hay estudiantes matriculados en esta sección.</p></div>
<?php elseif (empty($criterios)): ?>
    <div class="empty-state"><p>No hay criterios de conducta configurados para este nivel.</p></div>
<?php else: ?>

<?php if ($bloqueada): ?>
    <div class="alert alert--<?= $cerradaT ? 'success' : 'info' ?>">
        <?php if ($cerradaT): ?>
            ✓ Conducta <strong>cerrada y aprobada por el tutor</strong>
            el <?= e(fechaLima($cierre['tutor_cerrado_en'])) ?>.
        <?php else: ?>
            🔒 Conducta <strong>bloqueada y aprobada por Registro Académico</strong>
            el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>. En espera del cierre del tutor.
        <?php endif; ?>
        Para corregir, solicita el desbloqueo a Dirección.
    </div>
<?php endif; ?>

<details class="conducta-criterios-leyenda">
    <summary>Ver los <?= $total ?> criterios (Sí = cumple)</summary>
    <ol class="conducta-criterios-lista">
        <?php foreach ($criterios as $c): ?>
            <li><?= e($c['texto']) ?></li>
        <?php endforeach; ?>
    </ol>
</details>

<div class="tabla-notas-wrapper">
    <table class="tabla-notas conducta-grilla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <?php foreach ($criterios as $i => $c): ?>
                    <th class="conducta-th-crit" title="<?= e($c['texto']) ?>">C<?= $i + 1 ?></th>
                <?php endforeach; ?>
                <th class="conducta-th-nota" title="Nota de Registro Académico (Sí ÷ <?= $total ?> × 20)">Nota RA</th>
                <?php if (!$bloqueada): ?><th class="conducta-th-acciones">Acción</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $idx => $est):
                $resp = $est['respuestas'];
                $guardado = count($resp) >= $total;
            ?>
                <tr class="conducta-fila <?= $guardado ? 'conducta-fila--guardada' : 'conducta-fila--pendiente' ?>"
                    data-matricula="<?= (int) $est['matricula_id'] ?>"
                    data-periodo="<?= (int) $periodoActivo['id'] ?>"
                    data-csrf="<?= e($csrfToken) ?>"
                    data-total="<?= $total ?>">
                    <td class="col-num"><?= $idx + 1 ?></td>
                    <td class="col-nombre">
                        <span class="conducta-estado-dot" aria-hidden="true"></span>
                        <span class="conducta-estado-txt"><?= $guardado ? 'Guardado' : 'Sin guardar' ?></span>
                        <?= e($est['nombre_completo']) ?>
                    </td>

                    <?php foreach ($criterios as $c):
                        $val = $resp[(int) $c['id']] ?? null; // null | 0 | 1
                    ?>
                        <td class="conducta-td-crit">
                            <div class="cc-toggle" data-criterio="<?= (int) $c['id'] ?>"
                                 data-valor="<?= $val === null ? '' : (int) $val ?>"
                                 role="group" aria-label="Criterio para <?= e($est['nombre_completo']) ?>">
                                <button type="button" class="cc-btn cc-btn--si<?= $val === 1 ? ' cc-btn--activo' : '' ?>"
                                        data-v="1" <?= $bloqueada ? 'disabled' : '' ?>>Sí</button>
                                <button type="button" class="cc-btn cc-btn--no<?= $val === 0 ? ' cc-btn--activo' : '' ?>"
                                        data-v="0" <?= $bloqueada ? 'disabled' : '' ?>>No</button>
                            </div>
                        </td>
                    <?php endforeach; ?>

                    <td class="conducta-td-nota"><span class="cc-nota">—</span></td>

                    <?php if (!$bloqueada): ?>
                        <td class="conducta-td-acciones">
                            <button type="button" class="btn btn--primary btn--sm conducta-guardar">Guardar</button>
                            <span class="conducta-status" aria-live="polite"></span>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$bloqueada): ?>
    <form method="post" action="<?= url('admin/conducta/' . (int) $seccion['id'] . '/bloquear') ?>"
          class="conducta-bloqueo-form"
          onsubmit="return confirm('¿Bloquear y aprobar la conducta de toda la sección? Después solo Dirección podrá desbloquearla.');">
        <?= csrf_field() ?>
        <div class="conducta-bloqueo-info">
            Completos: <strong><?= $completitud['completos'] ?>/<?= $completitud['esperados'] ?></strong>
            <?php if (!$completo): ?>
                <span class="text-muted">— faltan estudiantes por calificar</span>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn--success" <?= $completo ? '' : 'disabled' ?>>
            🔒 Bloquear y aprobar sección
        </button>
    </form>
<?php endif; ?>

<?php endif; ?>
