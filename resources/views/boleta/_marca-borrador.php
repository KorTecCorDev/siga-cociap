<?php
/**
 * PUNTO ÚNICO de la señal de BORRADOR del documento de boleta.
 *
 * Lo incluye `boleta/alumno.php` cuando $vistaPrevia es true, así que la señal
 * viaja CON EL DOCUMENTO y no con quien lo muestra: la vista previa de RA, el
 * ZIP de borradores y la boleta del docente la reciben por el mismo camino, sin
 * que cada entrada tenga que acordarse de pintarla.
 *
 * Antes vivía en dos sitios distintos (el wrapper de la vista previa y el item
 * del ZIP) y la boleta del docente se quedaba SIN NINGUNA señal, que es
 * justamente el documento que un tutor puede imprimir con el bimestre abierto.
 *
 * Va dentro de `.boleta-doc` (position: relative), de modo que se ancla a la
 * hoja y hay UNA marca por boleta. No usar position:fixed: con varias boletas
 * apiladas se superpondrían todas en el mismo punto del viewport, y en el ZIP
 * html2canvas —que captura un contenedor por boleta— no la capturaría.
 */
?>
<div class="boleta-watermark" aria-hidden="true">
    <span class="boleta-watermark__palabra">BORRADOR</span>
    <span class="boleta-watermark__leyenda"><?= e(BOLETA_LEYENDA_BORRADOR) ?></span>
</div>
