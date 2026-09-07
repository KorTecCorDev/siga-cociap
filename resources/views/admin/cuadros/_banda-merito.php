<?php
/**
 * Banda de magnitud del bloque "Orden de merito". Compartida por la pantalla y
 * el imprimible A4 — igual que la de "Estudiantes en riesgo", con la que forma
 * PAR: son las dos caras de la misma pregunta (quien va mejor y quien necesita
 * apoyo) y el lector tiene que distinguirlas de un vistazo.
 *
 * 🔴 EL PAR ES AZUL <-> ROJO, Y NO ES ARBITRARIO. El otro par obvio —verde
 * contra rojo— es exactamente el que falla en las formas comunes de daltonismo
 * (protanopia y deuteranopia, ~8 % de los hombres); azul contra rojo se
 * distingue en todas ellas. Las dos bandas comparten fondo y estructura: lo que
 * cambia es el ACENTO (borde izquierdo y cifra), no el bloque entero. Ver
 * `.cuadros-banda` en _cuadros.scss.
 *
 * Solo pinta: las cifras salen de `$bloques['merito']`, que ya compuso el
 * controlador desde `OrdenMeritoModel::statsPorGrado`.
 *
 * @var array $bloques
 */
$mGrados = $bloques['merito']['por_grado'] ?? [];
if (empty($mGrados)) {
    return;
}

// `riesgo_resumen()` es el punto unico del agregado por grado y ya recorre esta
// misma lista: se reutiliza en vez de volver a sumar `total` a mano.
$mRes = riesgo_resumen($mGrados);

// El promedio mas alto del bimestre entre los primeros puestos de cada grado.
$mTop = 0.0;
foreach ($mGrados as $g) {
    $mTop = max($mTop, (float) ($g['mejor']['promedio_general'] ?? 0));
}
?>
<div class="cuadros-banda cuadros-banda--merito">
    <p class="cuadros-banda__cifra">
        <span class="cuadros-banda__n"><?= (int) $mRes['grados_total'] ?></span>
        <span class="cuadros-banda__q">
            grado<?= $mRes['grados_total'] !== 1 ? 's' : '' ?> con
            <strong>orden de mérito</strong> en el bimestre
        </span>
    </p>
    <ul class="cuadros-banda__datos">
        <li><strong><?= (int) $mRes['evaluados'] ?></strong> estudiantes en el ranking</li>
        <li>promedio más alto: <strong><?= e(number_format($mTop, 2)) ?></strong></li>
    </ul>
</div>
