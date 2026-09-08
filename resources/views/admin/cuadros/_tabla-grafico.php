<?php
/**
 * Tabla de valores de UN gráfico. Compartida por la pantalla y el A4.
 *
 * 🔴 EXISTE PORQUE UN GRÁFICO NO SE PUEDE LEER EN PAPEL. Frappe Charts deja
 * los valores solo en el tooltip: en pantalla se leen con el cursor y en una
 * hoja impresa no existen. Antes de esto, el A4 llevaba once gráficos y solo
 * uno —el pie del embudo, que escribe su leyenda dentro del SVG— dejaba sus
 * cifras legibles. El tooltip tampoco existe en móvil ni para quien navega
 * con teclado.
 *
 * Los gráficos NO se sustituyen: se acompañan. La imagen sigue respondiendo
 * "cómo va la cosa" de un vistazo; la tabla responde "cuánto exactamente".
 *
 * 🔴 EN EL A4 VA SIN `<details>`, Y NO ES UN DETALLE ESTÉTICO: un `<details>`
 * cerrado NO IMPRIME SU CONTENIDO. Saldría una hoja en blanco sin ningún
 * error, que es justo el fallo que este partial viene a evitar. Por eso el
 * imprimible pasa `$abierta = true` y aquí se emite la tabla suelta.
 *
 * @var array $t        una entrada de $chartTablas (ver _chart-data.php)
 * @var bool  $abierta  true = tabla suelta (A4) · false = plegada (pantalla)
 */

$abierta = $abierta ?? false;

if (empty($t) || empty($t['labels'])) {
    return;
}

$nSeries = count($t['series']);
$unidad  = trim((string) $t['unidad']);
?>

<?php if (!$abierta): ?>
    <?php // `<details>` nativo, sin una linea de JS. Nace cerrado para no
          // alargar el tablero, y el navegador ya sabe abrirlo cuando el
          // usuario busca dentro de la pagina (Ctrl+F). ?>
    <details class="cuadros-valores">
        <summary class="cuadros-valores__summary">
            Ver valores<?= $nSeries > 1 ? ' (' . $nSeries . ' series)' : '' ?>
        </summary>
<?php endif; ?>

    <div class="tabla-notas-wrapper">
        <table class="tabla-notas cuadros-valores__tabla">
            <thead>
                <tr>
                    <th class="col-nombre"><?= e($t['col']) ?></th>
                    <?php foreach ($t['series'] as $s): ?>
                        <?php // La unidad va UNA vez en la cabecera, no repetida en cada
                              // celda: con 23 filas, "12 faltas" x 23 es ruido y descuadra
                              // la alineacion numerica de la columna. ?>
                        <th class="text-center">
                            <?= e((string) ($s['name'] ?? 'Valor')) ?>
                            <?php if ($unidad !== ''): ?>
                                <span class="cuadros-valores__u"><?= e($unidad) ?></span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                    <?php if ($t['extra_col'] !== null): ?>
                        <th><?= e($t['extra_col']) ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($t['labels'] as $i => $etq): ?>
                    <tr>
                        <td class="col-nombre"><?= e((string) $etq) ?></td>
                        <?php foreach ($t['series'] as $s): ?>
                            <?php $v = $s['values'][$i] ?? null; ?>
                            <td class="text-center">
                                <?php // Un hueco es un guion, no un cero: "0 faltas" y "no hay
                                      // dato" son afirmaciones distintas, y en papel no hay
                                      // forma de preguntar cual de las dos era. ?>
                                <?= $v === null ? '&mdash;' : e((string) $v) ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($t['extra_col'] !== null): ?>
                            <td class="cuadros-valores__texto">
                                <?= e((string) ($t['extra'][$i] ?? '')) ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php if (!$abierta): ?>
    </details>
<?php endif; ?>
