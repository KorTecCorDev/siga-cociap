<?php
/**
 * Parcial: grilla semanal de horario (días en columnas, franjas en filas).
 *
 * PUNTO ÚNICO de la tabla, compartido por el horario del DOCENTE y el de la
 * SECCIÓN (24/08/2026). Se extrajo al nacer el segundo consumidor, no antes:
 * la lógica de armado (puntos de corte, rowspan, colores) ya vivía en
 * `HorarioModel::armarGrilla()` desde la extracción de la Fase 1B.
 *
 * Los dos ejes pintan lo mismo salvo el rótulo secundario de cada celda:
 *   - $eje = 'seccion'  (horario del docente)  → "3° A  SEC"
 *   - $eje = 'docente'  (horario de la sección) → nombre del docente
 *
 * @var array  $dias      ['lunes' => 'Lunes', ...]
 * @var array  $segmentos [{ inicio, fin }]
 * @var array  $startAt   [dia][fila] = { area, seccion, docente, nivel, color, rowspan }
 * @var array  $covered   [dia][fila] = true
 * @var string $eje       'seccion' | 'docente'
 */
$eje        = $eje ?? 'seccion';
$nivelAbrev = ['prim' => 'PRI', 'sec' => 'SEC'];
?>
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
        <?php foreach ($segmentos as $r => $seg): ?>
            <tr>
                <th class="horario-print__hora-col"><?= e(substr($seg['inicio'], 0, 5)) ?>–<?= e(substr($seg['fin'], 0, 5)) ?></th>
                <?php foreach (array_keys($dias) as $diaKey): ?>
                    <?php if (isset($startAt[$diaKey][$r])): $celda = $startAt[$diaKey][$r]; ?>
                        <td class="horario-celda" rowspan="<?= (int) $celda['rowspan'] ?>" style="--hbg: <?= e($celda['color']) ?>">
                            <span class="horario-celda__area"><?= e($celda['area']) ?></span>
                            <span class="horario-celda__sec">
                                <?php if ($eje === 'docente'): ?>
                                    <?= e($celda['docente'] ?? '') ?>
                                <?php else: ?>
                                    <?= e($celda['seccion']) ?>
                                    <?php if (!empty($nivelAbrev[$celda['nivel'] ?? ''])): ?>
                                        <span class="horario-celda__nivel"><?= e($nivelAbrev[$celda['nivel']]) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </td>
                    <?php elseif (isset($covered[$diaKey][$r])): ?>
                        <?php /* celda continuada por rowspan del bloque de arriba: no se dibuja */ ?>
                    <?php else: ?>
                        <td class="horario-celda horario-celda--vacia"></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
