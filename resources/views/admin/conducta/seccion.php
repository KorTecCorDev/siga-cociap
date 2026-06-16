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
        <span class="btn-icon btn-icon--locked" aria-hidden="true"></span>
        <span>
            <?php if ($cerradaT): ?>
                Conducta <strong>bloqueada y aprobada por el tutor(a)</strong>
                el <?= e(fechaLima($cierre['tutor_cerrado_en'])) ?>.
            <?php else: ?>
                Conducta <strong>bloqueada y aprobada por Registro Académico</strong>
                el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>. En espera del cierre del tutor.
            <?php endif; ?>
            Para corregir, solicita el desbloqueo a Registro Académico.
        </span>
    </div>
<?php endif; ?>

<details class="conducta-criterios-leyenda">
    <summary>Ver los <?= $total ?> criterios (✓ = cumple · ✗ = no cumple)</summary>
    <ol class="conducta-criterios-lista">
        <?php foreach ($criterios as $c): ?>
            <li><?= e($c['texto']) ?></li>
        <?php endforeach; ?>
    </ol>
</details>

<?php if (!$bloqueada): ?>
    <div class="conducta-toolbar">
        <button type="button" id="conducta-autollenar" class="btn btn--secondary btn--sm"
                title="Marcar Sí en los criterios sin responder (no cambia las excepciones)">
            <span class="btn-icon btn-icon--saveall" aria-hidden="true"></span> Marcar Sí
        </button>
        <button type="button" id="conducta-guardar-todos" class="btn btn--primary btn--sm"
                title="Guardar todas las filas pendientes">
            <span class="btn-icon btn-icon--save" aria-hidden="true"></span> Guardar todo
        </button>
        <span class="conducta-toolbar__hint text-muted">
            “Marcar Sí” rellena solo lo que falte.
        </span>
    </div>
<?php endif; ?>

<div class="tabla-notas-wrapper conducta-scroll">
    <table class="tabla-notas conducta-grilla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <?php foreach ($criterios as $i => $c): ?>
                    <th class="conducta-th-crit" title="<?= e($c['texto']) ?>">C<?= $i + 1 ?></th>
                <?php endforeach; ?>
                <th class="conducta-th-nota" title="Nota de Registro Académico (Sí ÷ <?= $total ?> × 20)">Nota</th>
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
                    <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>

                    <?php foreach ($criterios as $c):
                        $val = $resp[(int) $c['id']] ?? null; // null | 0 | 1
                    ?>
                        <td class="conducta-td-crit">
                            <div class="cc-toggle" data-criterio="<?= (int) $c['id'] ?>"
                                 data-valor="<?= $val === null ? '' : (int) $val ?>"
                                 role="group" aria-label="Criterio para <?= e($est['nombre_completo']) ?>">
                                <button type="button" class="cc-btn cc-btn--si<?= $val === 1 ? ' cc-btn--activo' : '' ?>"
                                        data-v="1" title="Cumple" aria-label="Cumple" <?= $bloqueada ? 'disabled' : '' ?>>✓</button>
                                <button type="button" class="cc-btn cc-btn--no<?= $val === 0 ? ' cc-btn--activo' : '' ?>"
                                        data-v="0" title="No cumple" aria-label="No cumple" <?= $bloqueada ? 'disabled' : '' ?>>✗</button>
                            </div>
                        </td>
                    <?php endforeach; ?>

                    <td class="conducta-td-nota"><span class="cc-nota">—</span></td>
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
            <span class="btn-icon btn-icon--upload" aria-hidden="true"></span>Bloquear y aprobar 
        </button>
    </form>
<?php endif; ?>

<?php endif; ?>
