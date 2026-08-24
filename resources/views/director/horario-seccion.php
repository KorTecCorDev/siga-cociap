<?php
/**
 * Vista: horario semanal de una SECCIÓN (24/08/2026).
 *
 * Misma estructura que el horario del docente —comparten `HorarioModel` y el
 * parcial `shared/_horario-grilla.php`—; cambia el eje: aquí cada celda se
 * rotula con el DOCENTE que dicta, no con la sección.
 *
 * Hasta hoy `/director/cargas/seccion/{id}` solo mostraba un resumen en texto
 * por carga (`horario_resumen`): no existía la grilla de doble entrada por
 * sección para ningún rol. Layout: print.
 *
 * @var array      $seccion   { id, grado_nombre, seccion_nombre, nivel_nombre, tutor_nombre }
 * @var array|null $anio
 * @var array      $dias, $segmentos, $startAt, $covered
 * @var array      $leyenda   [{ color, docente, areas[], horas }]
 * @var int        $totalHoras
 * @var array|null $directorEbr
 */
?>
<div class="horario-print">

    <header class="horario-print__head">
        <img class="horario-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="horario-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Horario de la sección<?= !empty($anio['anio']) ? ' &middot; ' . e($anio['anio']) : '' ?></p>
            <p class="horario-print__doc">
                <?= e($seccion['grado_nombre'] . ' ' . $seccion['seccion_nombre']) ?>
                &middot; <?= e($seccion['nivel_nombre'] ?? '') ?>
            </p>
        </div>
    </header>

    <div class="horario-print__meta">
        <span><strong>Fecha de impresión:</strong> <?= e(date('d/m/Y H:i')) ?></span>
        <?php if (!empty($seccion['tutor_nombre'])): ?>
            <span><strong>Tutor(a):</strong> <?= e($seccion['tutor_nombre']) ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($segmentos)): ?>
        <p class="text-muted">Esta sección no tiene horario registrado.</p>
    <?php else: ?>
        <?php $eje = 'docente'; require VIEW_PATH . '/shared/_horario-grilla.php'; ?>

        <div class="horario-bottom">
            <div class="horario-refs">
                <section class="horario-leyenda">
                    <h2 class="horario-leyenda__titulo">Áreas y docentes</h2>
                    <table class="horario-leyenda__tabla">
                        <thead>
                            <tr>
                                <th class="horario-leyenda__num">N°</th>
                                <th></th>
                                <th>Área</th>
                                <th>Docente</th>
                                <th class="horario-leyenda__num">Horas/sem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leyenda as $n => $l): ?>
                                <tr>
                                    <td class="horario-leyenda__num"><?= $n + 1 ?></td>
                                    <td><span class="horario-leyenda__color" style="--hbg: <?= e($l['color']) ?>"></span></td>
                                    <td><?= e(implode(' · ', $l['areas'])) ?></td>
                                    <td><?= e($l['docente'] ?: '—') ?></td>
                                    <td class="horario-leyenda__num"><?= (int) $l['horas'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="horario-leyenda__total">
                                <td colspan="4"><strong>Total de horas semanales</strong></td>
                                <td class="horario-leyenda__num"><?= (int) $totalHoras ?></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <?php if (!empty($directorEbr['sello_path'])): ?>
                <div class="horario-print__sello">
                    <img src="<?= url($directorEbr['sello_path']) ?>" alt="Sello de la dirección">
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
