<?php
/**
 * Vista previa antes de la aprobación del registro académico — layout print.
 * Una boleta por página usando boleta/alumno.php con $vistaPrevia=true.
 * Sin QR y sin imagen de firma del director (los datos de la línea sí).
 *
 * @var array  $periodo     { id, numero, nombre_display, anio }
 * @var array  $boletasData [{ alumno, periodos, areas, conducta, asistencia,
 *                             institucion, tutor, directorEbr, vistaPrevia }]
 * @var string $titulo
 */

if (empty($boletasData)): ?>
<p style="text-align:center;padding:20mm;font-family:Arial,sans-serif">
    No hay matrículas con competencias bloqueadas para este período.
</p>
<?php return; endif; ?>

<!-- Marca de agua "BORRADOR". position:fixed se repite por página al imprimir. -->
<div class="boleta-watermark" aria-hidden="true">BORRADOR</div>

<?php foreach ($boletasData as $i => $boletaData):
    extract($boletaData, EXTR_OVERWRITE);
?>
<?php include VIEW_PATH . '/boleta/alumno.php'; ?>
<?php if ($i < count($boletasData) - 1): ?>
<div class="boleta-salto-pagina"></div>
<?php endif; ?>
<?php endforeach; ?>
