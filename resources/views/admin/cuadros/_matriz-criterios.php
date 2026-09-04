<?php
/**
 * Mapa de calor: incumplimiento de cada criterio de conducta, sección a sección.
 * Compartido por la pantalla y el imprimible A4.
 *
 * Responde la pregunta que ningún gráfico contesta: no "qué norma cuesta más
 * en el colegio" (eso es el gráfico de barras) sino "en QUÉ AULA cuesta". Una
 * tabla y no un gráfico porque son 23 x 10 valores: cualquier gráfico de esa
 * densidad se vuelve ilegible, y una tabla se imprime.
 *
 * Las cabeceras son códigos (C1..C10) porque el texto completo no cabe en una
 * columna de 34px. El `title` NO es la explicación —un tooltip no existe en
 * móvil ni para quien navega con teclado—: la explicación es la leyenda de
 * abajo, que sale de la misma fuente de datos.
 *
 * Ese mismo criterio se aplicó a la CELDA el 04/09/2026: el `n/N` que antes
 * solo estaba en su `title` ahora se pinta bajo el porcentaje. Un tooltip no
 * se imprime, y sin el denominador un 50 % no dice si son 1 de 2 o 14 de 28.
 *
 * @var array $condCrit  {criterios, secciones} de ConductaModel::getIncumplimientoCriterios
 */

$criterios = $condCrit['criterios'] ?? [];
$secciones = $condCrit['secciones'] ?? [];

if (empty($criterios)) {
    return;
}

// Cinco escalones y no un degradado continuo: el degradado obliga a CSS inline
// y, en papel, cinco tonos se distinguen y cien no. El corte alto (>=50%) es
// "la mitad del aula no lo cumple", que es cuando deja de ser un caso suelto.
$calor = static function (float $pct): string {
    if ($pct <= 0)   { return 'n0'; }
    if ($pct < 10)   { return 'n1'; }
    if ($pct < 25)   { return 'n2'; }
    if ($pct < 50)   { return 'n3'; }
    return 'n4';
};
?>

<div class="tabla-notas-wrapper">
    <table class="tabla-notas cuadros-matriz">
        <thead>
            <tr>
                <th class="col-nombre">Sección</th>
                <?php foreach ($criterios as $k): ?>
                    <th class="cuadros-matriz__th" title="<?= e($k['texto']) ?>">
                        <span class="competencia-card__codigo competencia-card__codigo--solo"><?= e($k['codigo']) ?></span>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($secciones as $s): ?>
                <tr>
                    <td class="col-nombre"><?= e($s['etq']) ?></td>
                    <?php foreach ($criterios as $k):
                        $celda = $s['por_criterio'][$k['id']] ?? null;
                        $pctK  = $celda ? (float) $celda['pct'] : 0.0;
                    ?>
                        <?php if ($celda === null): ?>
                            <?php // Sin respuestas registradas: guion apagado. Un 0 % se
                                  // leeria como "aqui nadie lo incumple", un dato inventado. ?>
                            <td class="cuadros-matriz__c cuadros-matriz__c--sd">&ndash;</td>
                        <?php else: ?>
                            <?php // 🔴 EL DENOMINADOR SE IMPRIME, no se deja en el `title`.
                                  // Hasta el 04/09/2026 la celda solo mostraba el porcentaje y
                                  // el "12 de 28" vivia unicamente en el tooltip: en papel, un
                                  // 50 % podia ser 1 de 2 o 14 de 28, que son dos realidades
                                  // distintas para quien tiene que decidir algo. El `title` se
                                  // conserva para el raton, pero ya no es la unica fuente. ?>
                            <td class="cuadros-matriz__c cuadros-matriz__c--<?= $calor($pctK) ?>"
                                title="<?= (int) $celda['no_cumple'] ?> de <?= (int) $celda['respondidos'] ?> no cumplen">
                                <?= $pctK > 0 ? (int) round($pctK) . '%' : '&mdash;' ?>
                                <span class="cuadros-matriz__den"><?= (int) $celda['no_cumple'] ?>/<?= (int) $celda['respondidos'] ?></span>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php // 🔴 El pie va FUERA del wrapper, como hermano: dentro se desplazaria con
      // el scroll horizontal, perderia el margen y la .card lo recortaria por
      // su `overflow: hidden`. ?>
<div class="tabla-pie">
    <p class="text-sm text-muted">
        Porcentaje de estudiantes que <strong>no cumplen</strong> cada criterio, sobre
        los que tienen respuesta registrada. Incluye las secciones que todavía no
        han cerrado el bimestre.
    </p>
    <div class="tabla-pie__leyenda">
        <?php foreach ($criterios as $k): ?>
            <span class="tabla-pie__leyenda__item tabla-pie__leyenda__item--bloque">
                <strong><?= e($k['codigo']) ?></strong> <?= e($k['texto']) ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>
