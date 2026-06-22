<?php
/**
 * Parcial compartido: cuadro cruzado de matrícula por grado.
 * Lo incluyen la vista de pantalla (resumen.php) y la imprimible
 * (resumen-imprimir.php). Calcula subtotales por nivel y total general.
 *
 * @var array $cuadro  filas de MatriculaModel::getCuadroMatricula()
 */
$cols = ['t_nuevo', 't_cont', 't_tras', 'e_aprob', 'e_pend', 'e_desact', 'gen_m', 'gen_f', 'total'];

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
?>
<table class="cuadro-matricula">
    <thead>
        <tr>
            <th class="cuadro-matricula__grado" rowspan="2">Grado</th>
            <th colspan="3">Tipo</th>
            <th colspan="3">Estado</th>
            <th colspan="2">Género</th>
            <th rowspan="2">Total</th>
        </tr>
        <tr>
            <th>Nuevo</th><th>Cont.</th><th>Tras.</th>
            <th>Aprob.</th><th>Pend.</th><th>Desact.</th>
            <th>M</th><th>F</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($porNivel as $nivel): $sub = array_fill_keys($cols, 0); ?>
            <tr class="cuadro-matricula__nivel">
                <td colspan="10"><?= e($nivel['nombre']) ?></td>
            </tr>
            <?php foreach ($nivel['rows'] as $r): ?>
                <?php foreach ($cols as $c) { $sub[$c] += $r[$c]; $gran[$c] += $r[$c]; } ?>
                <tr>
                    <td class="cuadro-matricula__grado"><?= e($r['grado_nombre']) ?></td>
                    <td><?= $r['t_nuevo'] ?></td>
                    <td><?= $r['t_cont'] ?></td>
                    <td><?= $r['t_tras'] ?></td>
                    <td><?= $r['e_aprob'] ?></td>
                    <td><?= $r['e_pend'] ?></td>
                    <td><?= $r['e_desact'] ?></td>
                    <td><?= $r['gen_m'] ?></td>
                    <td><?= $r['gen_f'] ?></td>
                    <td class="cuadro-matricula__total"><?= $r['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="cuadro-matricula__subtotal">
                <td class="cuadro-matricula__grado">Subtotal <?= e($nivel['nombre']) ?></td>
                <td><?= $sub['t_nuevo'] ?></td>
                <td><?= $sub['t_cont'] ?></td>
                <td><?= $sub['t_tras'] ?></td>
                <td><?= $sub['e_aprob'] ?></td>
                <td><?= $sub['e_pend'] ?></td>
                <td><?= $sub['e_desact'] ?></td>
                <td><?= $sub['gen_m'] ?></td>
                <td><?= $sub['gen_f'] ?></td>
                <td class="cuadro-matricula__total"><?= $sub['total'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr class="cuadro-matricula__grandtotal">
            <td class="cuadro-matricula__grado">TOTAL GENERAL</td>
            <td><?= $gran['t_nuevo'] ?></td>
            <td><?= $gran['t_cont'] ?></td>
            <td><?= $gran['t_tras'] ?></td>
            <td><?= $gran['e_aprob'] ?></td>
            <td><?= $gran['e_pend'] ?></td>
            <td><?= $gran['e_desact'] ?></td>
            <td><?= $gran['gen_m'] ?></td>
            <td><?= $gran['gen_f'] ?></td>
            <td class="cuadro-matricula__total"><?= $gran['total'] ?></td>
        </tr>
    </tfoot>
</table>
<p class="cuadro-matricula__nota">El conteo de género no incluye a quienes no tienen sexo registrado (M + F puede ser menor al total). El retorno de grado se cuenta una sola vez, en el grado oficial.</p>
