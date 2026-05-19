<?php
/**
 * Vista de impresión de códigos de acceso — layout print.
 * Una tarjeta por alumno, 2 columnas por página (A4 portrait).
 *
 * @var array  $periodo  { id, numero, nombre_display, anio }
 * @var array  $boletas  [{ nombre_completo, grado_nombre, seccion_nombre,
 *                          codigo_acceso, matricula_id }]
 * @var string $titulo
 */
$urlBase = rtrim(url(''), '/');
?>

<div class="bpi-encabezado">
    <h1 class="bpi-encabezado__titulo">Códigos de Acceso — Boleta Pública</h1>
    <p class="bpi-encabezado__sub">
        <?= e($periodo['nombre_display']) ?> &middot; <?= e($periodo['anio']) ?>
        &middot; <?= count($boletas) ?> estudiante<?= count($boletas) !== 1 ? 's' : '' ?>
    </p>
    <p class="bpi-encabezado__instruccion">
        Entrega este comprobante a cada padre/madre. El código permite consultar
        la boleta sin necesidad de crear una cuenta.
    </p>
</div>

<div class="bpi-grid">
<?php foreach ($boletas as $b):
    $urlConsulta = $urlBase . '/boleta-publica';
    $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=120x120&chl='
           . urlencode($urlConsulta . '?codigo=' . urlencode($b['codigo_acceso']))
           . '&choe=UTF-8';
?>
<div class="bpi-tarjeta">
    <div class="bpi-tarjeta__header">
        <img src="<?= url('assets/img/logo_cociap.png') ?>"
             alt="COCIAP"
             class="bpi-tarjeta__logo">
        <div class="bpi-tarjeta__inst">
            <span class="bpi-tarjeta__colegio">Colegio de Aplicación COCIAP</span>
            <span class="bpi-tarjeta__periodo"><?= e($periodo['nombre_display'] . ' ' . $periodo['anio']) ?></span>
        </div>
    </div>
    <div class="bpi-tarjeta__body">
        <div class="bpi-tarjeta__datos">
            <p class="bpi-tarjeta__nombre"><?= e($b['nombre_completo']) ?></p>
            <p class="bpi-tarjeta__seccion">
                <?= e($b['grado_nombre']) ?> &ldquo;<?= e($b['seccion_nombre']) ?>&rdquo;
            </p>
            <p class="bpi-tarjeta__etiqueta">Código de acceso:</p>
            <p class="bpi-tarjeta__codigo"><?= e($b['codigo_acceso']) ?></p>
            <p class="bpi-tarjeta__url"><?= e($urlConsulta) ?></p>
        </div>
        <div class="bpi-tarjeta__qr"
             data-qr-url="<?= e($urlConsulta . '?codigo=' . urlencode($b['codigo_acceso'])) ?>">
        </div>
    </div>
    <div class="bpi-tarjeta__footer">
        Ingresa el código en <strong><?= e($urlConsulta) ?></strong>
        o escanea el QR para ver la boleta.
    </div>
</div>
<?php endforeach; ?>
</div>

<script src="<?= url('js/qrcode.min.js') ?>"></script>
<script>
document.querySelectorAll('[data-qr-url]').forEach(function (container) {
    var urlQr = container.getAttribute('data-qr-url');
    if (urlQr && typeof QRCode !== 'undefined') {
        new QRCode(container, { text: urlQr, width: 60, height: 60, correctLevel: QRCode.CorrectLevel.M });
    }
});
</script>
