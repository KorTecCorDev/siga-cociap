<?php
/**
 * Parcial compartido: cuadro cruzado de matrícula por grado.
 * Lo incluyen la vista de pantalla (resumen.php) y la imprimible
 * (resumen-imprimir.php). Calcula subtotales por nivel y total general.
 *
 * @var array $cuadro  filas de MatriculaModel::getCuadroMatricula()
 */

/**
 * 🔴 PUNTO ÚNICO DE LAS COLUMNAS. De aquí salen los encabezados de grupo, los
 * `<th>`, las celdas de las tres filas (grado, subtotal y total general), los
 * acumuladores y el `colspan` de la banda de nivel.
 *
 * Antes las celdas estaban escritas A MANO en tres sitios y esta lista solo
 * alimentaba los acumuladores: añadir una columna obligaba a tocar cinco puntos
 * y olvidarse de uno no daba ningún error, solo una tabla descuadrada. Fue
 * exactamente lo que pasó con `retirado` (migración 045): el tipo se añadió a la
 * base y el cuadro siguió sumando tres columnas sobre un total de cuatro.
 *
 * AÑADIR UNA COLUMNA = AÑADIR UNA ENTRADA AQUÍ. Nada más.
 */
$grupos = [
    // Los CUATRO tipos del enum de `matriculas.tipo`. Tienen que sumar el total.
    'Tipo'   => ['t_nuevo' => 'Nuevo', 't_cont' => 'Cont.', 't_tras' => 'Tras.', 't_retir' => 'Retir.'],
    // Los TRES estados del enum. También suman el total.
    'Estado' => ['e_aprob' => 'Aprob.', 'e_pend' => 'Pend.', 'e_desact' => 'Desact.'],
    // Género: M + F NO suma el total, y es deliberado — ver la nota al pie.
    'Género' => ['gen_m' => 'M', 'gen_f' => 'F'],
];

$cols = [];
foreach ($grupos as $columnas) {
    $cols = array_merge($cols, array_keys($columnas));
}
$cols[] = 'total';

// Agrupa por nivel preservando el orden (nivel→grado).
$porNivel = [];
foreach ($cuadro as $row) {
    $nid = $row['nivel_id'];
    if (!isset($porNivel[$nid])) {
        $porNivel[$nid] = ['nombre' => $row['nivel_nombre'], 'rows' => []];
    }
    $porNivel[$nid]['rows'][] = $row;
}

$gran = array_fill_keys($cols, 0);

// Estudiantes sin sexo registrado, para que la nota al pie diga la cifra en vez
// de insinuarla. NO es una columna: se acordó mostrar solo M y F.
$sinSexo = array_sum(array_column($cuadro, 'gen_nd'));

/** Pinta la tira de celdas de una fila a partir de $cols. */
$celdas = static function (array $datos) use ($cols): string {
    $html = '';
    foreach ($cols as $c) {
        $clase = $c === 'total' ? ' class="cuadro-matricula__total"' : '';
        $html .= '<td' . $clase . '>' . (int) ($datos[$c] ?? 0) . '</td>';
    }
    return $html;
};
?>
<table class="cuadro-matricula">
    <thead>
        <tr>
            <th class="cuadro-matricula__grado" rowspan="2">Grado</th>
            <?php foreach ($grupos as $titulo => $columnas): ?>
                <th colspan="<?= count($columnas) ?>"><?= e($titulo) ?></th>
            <?php endforeach; ?>
            <th rowspan="2">Total</th>
        </tr>
        <tr>
            <?php foreach ($grupos as $columnas): ?>
                <?php foreach ($columnas as $etiqueta): ?>
                    <th><?= e($etiqueta) ?></th>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($porNivel as $nivel): $sub = array_fill_keys($cols, 0); ?>
            <tr class="cuadro-matricula__nivel">
                <td colspan="<?= count($cols) + 1 ?>"><?= e($nivel['nombre']) ?></td>
            </tr>
            <?php foreach ($nivel['rows'] as $r): ?>
                <?php foreach ($cols as $c) { $sub[$c] += $r[$c]; $gran[$c] += $r[$c]; } ?>
                <tr>
                    <td class="cuadro-matricula__grado"><?= e($r['grado_nombre']) ?></td>
                    <?= $celdas($r) ?>
                </tr>
            <?php endforeach; ?>
            <tr class="cuadro-matricula__subtotal">
                <td class="cuadro-matricula__grado">Subtotal <?= e($nivel['nombre']) ?></td>
                <?= $celdas($sub) ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="cuadro-matricula__grandtotal">
            <td class="cuadro-matricula__grado">TOTAL GENERAL</td>
            <?= $celdas($gran) ?>
        </tr>
    </tfoot>
</table>
<p class="cuadro-matricula__nota">
    Las columnas de <strong>Tipo</strong> y las de <strong>Estado</strong> suman cada una el Total de su fila.
    El conteo de género no: <?= $sinSexo === 1 ? 'hay <strong>1 estudiante</strong>' : 'hay <strong>' . (int) $sinSexo . ' estudiantes</strong>' ?>
    sin sexo registrado, así que M + F es menor que el total.
    El retorno de grado se cuenta una sola vez, en el grado oficial.
</p>
