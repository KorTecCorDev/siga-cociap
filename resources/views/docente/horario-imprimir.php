<?php
/**
 * Horario imprimible (A4 horizontal) del docente — tabla de doble entrada.
 * Días (lunes-viernes) en columnas, franjas horarias en filas, color por carga
 * y leyenda de cargas al final. Layout: print.
 * @var string     $docente
 * @var array|null $anio     { anio }
 * @var array      $dias     [clave => Label]
 * @var array      $franjas  claves de franja ordenadas (hora_inicio|hora_fin)
 * @var array      $matriz   [clave_franja][dia_clave] = { area, seccion, nivel, color }
 * @var array      $bloques  [{ inicio, fin }]  alineado al orden de $franjas
 * @var array      $leyenda  [{ color, nivel, seccion, areas[], horas }]
 * @var int        $totalHoras
 * @var array|null $directorEbr { sello_path }
 */
// Abreviaturas de nivel para la leyenda (explicadas en la tabla "Niveles").
$nivelAbrev = ['prim' => 'PRI', 'sec' => 'SEC'];
?>
<div class="horario-print">

    <header class="horario-print__head">
        <img class="horario-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="horario-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Horario del docente<?= !empty($anio['anio']) ? ' &middot; ' . e($anio['anio']) : '' ?></p>
            <p class="horario-print__doc"><?= e($docente) ?></p>
        </div>
    </header>

    <div class="horario-print__meta">
        <span><strong>Fecha de impresión:</strong> <?= e(date('d/m/Y H:i')) ?></span>
    </div>

    <table class="horario-print__tabla">
        <thead>
            <tr>
                <th class="horario-print__hora-col">Hora</th>
                <?php foreach ($dias as $label): ?>
                    <th><?= e($label) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($franjas as $i => $clave): ?>
                <tr>
                    <th class="horario-print__hora-col"><?= ($i + 1) ?>ª hora</th>
                    <?php foreach (array_keys($dias) as $diaKey): ?>
                        <?php $celda = $matriz[$clave][$diaKey] ?? null; ?>
                        <?php if ($celda): ?>
                            <td class="horario-celda" style="--hbg: <?= e($celda['color']) ?>">
                                <span class="horario-celda__area"><?= e($celda['area']) ?></span>
                                <span class="horario-celda__sec">
                                    <?= e($celda['seccion']) ?>
                                    <?php if (!empty($nivelAbrev[$celda['nivel'] ?? ''])): ?>
                                        <span class="horario-celda__nivel"><?= e($nivelAbrev[$celda['nivel']]) ?></span>
                                    <?php endif; ?>
                                </span>
                            </td>
                        <?php else: ?>
                            <td class="horario-celda horario-celda--vacia"></td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="horario-bottom">
    <div class="horario-refs">
        <section class="horario-leyenda">
            <h2 class="horario-leyenda__titulo">Bloques horarios</h2>
            <table class="horario-leyenda__tabla">
                <thead>
                    <tr>
                        <th class="horario-leyenda__num">Hora</th>
                        <th>Horario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloques as $i => $b): ?>
                        <tr>
                            <td class="horario-leyenda__num"><?= $i + 1 ?>ª</td>
                            <td><?= e(substr($b['inicio'], 0, 5)) ?>–<?= e(substr($b['fin'], 0, 5)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="horario-leyenda">
            <h2 class="horario-leyenda__titulo">Cargas y secciones</h2>
            <table class="horario-leyenda__tabla">
                <thead>
                    <tr>
                        <th class="horario-leyenda__num">N°</th>
                        <th>Color</th>
                        <th class="horario-leyenda__num">Nivel</th>
                        <th>Grado y sección</th>
                        <th>Cargas</th>
                        <th class="horario-leyenda__num">Horas/sem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leyenda as $n => $l): ?>
                        <tr>
                            <td class="horario-leyenda__num"><?= $n + 1 ?></td>
                            <td><span class="horario-swatch" style="--hbg: <?= e($l['color']) ?>"></span></td>
                            <td class="horario-leyenda__num"><?= e($nivelAbrev[$l['nivel']] ?? '') ?></td>
                            <td><?= e($l['seccion']) ?></td>
                            <td><?= e(implode(', ', $l['areas'])) ?></td>
                            <td class="horario-leyenda__num"><?= (int) $l['horas'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="horario-leyenda__total">
                        <td colspan="5">Total de horas dictadas en la semana</td>
                        <td class="horario-leyenda__num"><?= (int) $totalHoras ?></td>
                    </tr>
                </tfoot>
            </table>
        </section>

        <section class="horario-leyenda">
            <h2 class="horario-leyenda__titulo">Niveles</h2>
            <table class="horario-leyenda__tabla">
                <thead>
                    <tr>
                        <th class="horario-leyenda__num">Código</th>
                        <th>Nivel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="horario-leyenda__num">PRI</td>
                        <td>Primaria</td>
                    </tr>
                    <tr>
                        <td class="horario-leyenda__num">SEC</td>
                        <td>Secundaria</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>

    <?php if ($directorEbr && !empty($directorEbr['sello_path'])): ?>
        <footer class="horario-print__footer">
            <div class="horario-print__sello-bloque">
                <img class="horario-print__sello" src="<?= url($directorEbr['sello_path']) ?>"
                     alt="" aria-hidden="true">
            </div>
        </footer>
    <?php endif; ?>
    </div>

</div>
