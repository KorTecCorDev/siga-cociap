<?php
/**
 * PUNTO ÚNICO de la señal de BORRADOR del documento de boleta.
 *
 * La señal viaja CON EL DOCUMENTO, no con quien lo muestra: la vista previa de
 * RA, el ZIP de borradores, la boleta impresa del docente y la boleta DIGITAL
 * la reciben por el mismo camino, sin que cada entrada tenga que acordarse de
 * pintarla. Antes vivía en dos wrappers distintos y la boleta del docente se
 * quedaba SIN NINGUNA señal, que es justamente el documento que un tutor puede
 * imprimir con el bimestre abierto.
 *
 * DOS ANCLAJES, según el formato ($marcaModificador):
 *
 *  - HOJA A4 (por defecto, `boleta/alumno.php`): `position: absolute` dentro de
 *    `.boleta-doc`. Una marca por boleta. NO usar fixed: con varias boletas
 *    apiladas se superpondrían todas en el mismo punto del viewport, y en el
 *    ZIP html2canvas —que captura un contenedor por boleta— no la capturaría.
 *
 *  - PANTALLA (`boleta-watermark--pantalla`, boleta digital): `position: fixed`
 *    y tamaños en `vw`. La digital es un documento largo que se recorre con
 *    scroll, así que una marca anclada al contenido dejaría SIN MARCAR las
 *    capturas de pantalla de la zona de notas. Fija en el viewport, cualquier
 *    captura la incluye — que es el motivo de ponerla ahí: no impide la
 *    captura, la ETIQUETA, para que no circule como resultado oficial.
 */
$marcaModificador = $marcaModificador ?? '';
?>
<div class="<?= e(trim('boleta-watermark ' . $marcaModificador)) ?>" aria-hidden="true">
    <span class="boleta-watermark__palabra">BORRADOR</span>
    <span class="boleta-watermark__leyenda"><?= e(BOLETA_LEYENDA_BORRADOR) ?></span>
</div>
<?php
// No contaminar includes posteriores (una vista puede incluir varias boletas).
unset($marcaModificador);
