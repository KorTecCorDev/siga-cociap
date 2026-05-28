<?php
/**
 * Archivado de boletas en PDF — layout print.
 * html2pdf.js convierte cada boleta a PDF; JSZip las empaqueta por sección.
 *
 * @var array  $periodo     { id, numero, nombre_display, anio }
 * @var array  $boletasData [{ alumno, periodos, areas, conducta, institucion,
 *                             url_boleta, nombre_archivo, carpeta }]
 * @var string $titulo
 */

if (empty($boletasData)): ?>
<p style="text-align:center;padding:20mm;font-family:Arial,sans-serif">
    No hay boletas generadas para este período.
</p>
<?php return; endif;

// Agrupar por carpeta (sección) solo para info del encabezado
$carpetas = array_unique(array_column($boletasData, 'carpeta'));
sort($carpetas);
$nombreZip = 'boletas-' . str_replace([' ', '/'], '-', $periodo['nombre_display']) . '-' . $periodo['anio'] . '.zip';
?>

<!-- ── Panel de progreso ─────────────────────────────────── -->
<div id="archivo-progreso" class="archivo-progreso">
    <div class="archivo-progreso__header">
        <span class="archivo-progreso__icono" id="archivo-icono">⏳</span>
        <div class="archivo-progreso__textos">
            <strong id="archivo-status">Iniciando archivado...</strong>
            <span id="archivo-detalle" class="archivo-progreso__detalle"></span>
        </div>
        <span id="archivo-contador" class="archivo-progreso__contador">
            0 / <?= count($boletasData) ?>
        </span>
    </div>
    <div class="archivo-progreso__barra-wrap">
        <div id="archivo-barra" class="archivo-progreso__barra"></div>
    </div>
    <div class="archivo-progreso__meta">
        <?= count($boletasData) ?> boleta(s) &middot;
        <?= count($carpetas) ?> sección(es):
        <?= e(implode(', ', $carpetas)) ?>
    </div>
</div>

<!-- ── Boletas para procesamiento ────────────────────────── -->
<div id="archivo-items" class="archivo-items-wrap" aria-hidden="true">
<?php foreach ($boletasData as $boletaData):
    extract($boletaData, EXTR_OVERWRITE);
    $vistaPrevia = false;
?>
<div class="boleta-archivo-item"
     data-nombre-archivo="<?= e($boletaData['nombre_archivo']) ?>"
     data-carpeta="<?= e($boletaData['carpeta']) ?>">
    <?php include VIEW_PATH . '/boleta/alumno.php'; ?>
</div>
<div class="boleta-salto-pagina"></div>
<?php endforeach; ?>
</div>

<script>var ARCHIVO_ZIP_NOMBRE = <?= json_encode($nombreZip) ?>;</script>
<script src="<?= url('js/jszip.min.js') ?>"></script>
<script src="<?= url('js/html2pdf.bundle.min.js') ?>"></script>
<script src="<?= url('js/archivar-boletas.js') ?>"></script>
