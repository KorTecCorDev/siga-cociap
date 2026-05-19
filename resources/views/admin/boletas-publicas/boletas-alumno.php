<?php
/**
 * Impresión masiva de boletas — layout print.
 * Una boleta por página usando el componente boleta/alumno.php.
 *
 * @var array  $periodo     { id, numero, nombre_display, anio }
 * @var array  $boletasData [{ alumno, periodos, areas, conducta, institucion, url_boleta }]
 * @var string $titulo
 */

if (empty($boletasData)): ?>
<p style="text-align:center;padding:20mm;font-family:Arial,sans-serif">
    No hay boletas generadas para este período.
</p>
<?php return; endif; ?>

<?php foreach ($boletasData as $i => $boletaData):
    extract($boletaData, EXTR_OVERWRITE);
?>
<?php include VIEW_PATH . '/boleta/alumno.php'; ?>
<?php if ($i < count($boletasData) - 1): ?>
<div class="boleta-salto-pagina"></div>
<?php endif; ?>
<?php endforeach; ?>
