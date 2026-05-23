<?php
/**
 * Vista: boleta anual — A4 landscape, tabla con 4 bimestres + conclusión
 *
 * @var array  $alumno      { nombre_completo, dni, grado_nombre, seccion_nombre,
 *                            nivel_nombre, escala_boleta, anio_academico }
 * @var array  $periodos    [{ id, numero, nombre_display }, ...]
 * @var array  $areas       areas[nombre][comp_id]
 *                            = { nombre, bimestres[pid]{nota,literal,conclusion},
 *                                literal_final }
 * @var array  $conducta    [periodo_id => literal]  (vacío si no hay notas de conducta)
 * @var string $institucion
 */

$esSecundaria = ($alumno['escala_boleta'] === 'ambas');
$hoy          = (new DateTime())->format('d/m/Y');

// Vista previa antes de la aprobación del registro académico:
// suprime QR y la imagen de firma del director (los datos textuales
// del director — nombre y cargo — se mantienen para que el RA pueda
// validar el documento como va a salir).
$vistaPrevia = $vistaPrevia ?? false;

// Columnas por bimestre: nota(sec)+literal+conclusión  ó  literal+conclusión
$subCols   = $esSecundaria ? 3 : 2;
$totalCols = 1 + count($periodos) * $subCols + 1;

// Etiquetas de bimestre abreviadas para el encabezado de columna
$romanos = ['I', 'II', 'III', 'IV'];

// Separa las competencias transversales para ubicarlas al final
$areasRegulares     = [];
$areasTransversales = [];
foreach ($areas as $_n => $_c) {
    if (stripos($_n, 'transversal') !== false) {
        $areasTransversales[$_n] = $_c;
    } else {
        $areasRegulares[$_n] = $_c;
    }
}
$areasOrdenadas = array_merge($areasRegulares, $areasTransversales);
unset($_n, $_c);
?>

<?php if ($vistaPrevia): ?>
<!-- ── Banner BORRADOR (vista previa, una vez por boleta = una por página) ── -->
<div class="boleta-borrador-banner" role="note">
    <span class="boleta-borrador-banner__tag">BORRADOR</span>
    <span class="boleta-borrador-banner__msg">
        Vista previa para revisión &middot;
        No constituye documento oficial mientras no sea aprobado por Registro Académico
    </span>
</div>
<?php endif; ?>

<!-- ── Cabecera institucional ───────────────────────────────── -->
<header class="boleta-header">
    <div class="boleta-header__logo-wrap">
        <img src="<?= url('assets/img/logo_cociap.png') ?>"
             alt="COCIAP" class="boleta-header__logo">
    </div>
    <div class="boleta-header__centro">
        <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
        <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
        <div class="boleta-header__modular">
            Cód. Modular: <?= $esSecundaria ? '1310044 - 0' : '1719525 - 0' ?>
        </div>
        <div class="boleta-header__titulo">Informe de Progreso de las Competencias del Estudiante</div>
        <div class="boleta-header__anio">Año Académico <?= e($alumno['anio_academico'] ?? '') ?></div>
    </div>
    <div class="boleta-header__fecha-wrap">
        <div class="boleta-header__fecha-label">Emitida</div>
        <div class="boleta-header__fecha"><?= $hoy ?></div>
    </div>
</header>

<!-- ── Datos del estudiante ─────────────────────────────────── -->
<?php
$sexoTutor     = $tutor['sexo'] ?? null;
$cargoTutor    = match($sexoTutor) {
    'F'     => 'Tutora de Aula',
    'M'     => 'Tutor de Aula',
    default => 'Tutor(a) de Aula',
};
$cargoDirector = match($directorEbr['sexo'] ?? null) {
    'F'     => 'Directora E.B.R.',
    'M'     => 'Director E.B.R.',
    default => 'Director(a) E.B.R.',
};
?>
<section class="boleta-alumno">
    <div class="boleta-alumno__item boleta-alumno__item--nombre">
        <span class="boleta-alumno__label">Apellidos y Nombres</span>
        <span class="boleta-alumno__valor"><?= e($alumno['nombre_completo'] ?? '') ?></span>
    </div>
    <div class="boleta-alumno__item">
        <span class="boleta-alumno__label">DNI</span>
        <span class="boleta-alumno__valor"><?= e($alumno['dni'] ?? '') ?></span>
    </div>
    <div class="boleta-alumno__item">
        <span class="boleta-alumno__label">Grado y Sección</span>
        <span class="boleta-alumno__valor"><?= e($alumno['grado_nombre'] ?? '') ?> &mdash; <?= e($alumno['seccion_nombre'] ?? '') ?></span>
    </div>
    <div class="boleta-alumno__item">
        <span class="boleta-alumno__label">Nivel</span>
        <span class="boleta-alumno__valor"><?= e($alumno['nivel_nombre'] ?? '') ?></span>
    </div>
    <?php if (!empty($tutor['nombre'])): ?>
    <div class="boleta-alumno__item boleta-alumno__item--tutor">
        <span class="boleta-alumno__label"><?= $cargoTutor ?></span>
        <span class="boleta-alumno__valor"><?= e($tutor['nombre']) ?></span>
    </div>
    <?php endif; ?>
</section>

<!-- ── Leyenda de escala ─────────────────────────────────────── -->
<div class="boleta-leyenda">
    <span class="boleta-leyenda__titulo">Escala:</span>
    <span class="boleta-leyenda__item boleta-leyenda__item--ad"><strong>AD</strong> Logro destacado (17&ndash;20)</span>
    <span class="boleta-leyenda__sep">&middot;</span>
    <span class="boleta-leyenda__item boleta-leyenda__item--a"><strong>A</strong> Logro esperado (14&ndash;16)</span>
    <span class="boleta-leyenda__sep">&middot;</span>
    <span class="boleta-leyenda__item boleta-leyenda__item--b"><strong>B</strong> En proceso (11&ndash;13)</span>
    <span class="boleta-leyenda__sep">&middot;</span>
    <span class="boleta-leyenda__item boleta-leyenda__item--c"><strong>C</strong> En inicio (00&ndash;10)</span>
    <span class="boleta-leyenda__sep">&middot;</span>
    <span class="boleta-leyenda__item"><em>La conclusión descriptiva orienta las acciones de mejora.</em></span>
</div>

<!-- ── Tabla de calificaciones ──────────────────────────────── -->
<?php if (empty($areas)): ?>
    <p class="boleta-sin-notas">No hay calificaciones registradas para este año académico.</p>
<?php else: ?>

<table class="boleta-tabla">
    <thead>
        <!-- Fila 1: nombres de bimestres -->
        <tr>
            <th class="th-comp" rowspan="2">Área / Competencia</th>
            <?php foreach ($periodos as $p):
                $num = (int) $p['numero'];
                $abr = ($romanos[$num - 1] ?? $num) . ' Bimestre';
            ?>
                <th class="th-bimestre" colspan="<?= $subCols ?>"><?= $abr ?></th>
            <?php endforeach; ?>
            <th class="th-final" rowspan="2">Logro<br>Anual</th>
        </tr>
        <!-- Fila 2: sub-columnas -->
        <tr>
            <?php foreach ($periodos as $p): ?>
                <?php if ($esSecundaria): ?><th class="th-mini">Nota</th><?php endif; ?>
                <th class="th-mini">Lit.</th>
                <th class="th-concl">Conclusión descriptiva</th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($areasOrdenadas as $areaNombre => $competencias):
            $esTransversal = stripos($areaNombre, 'transversal') !== false;
        ?>

            <tr class="fila-area <?= $esTransversal ? 'fila-area--transversal' : '' ?>">
                <td colspan="<?= $totalCols ?>"><?= e(mb_strtoupper($areaNombre)) ?></td>
            </tr>

            <?php foreach ($competencias as $idx => $comp): ?>
                <tr class="fila-comp <?= $idx % 2 !== 0 ? 'fila-comp--alt' : '' ?>">
                    <td class="td-comp">
                        <?= e($comp['nombre']) ?>
                    </td>

                    <?php foreach ($periodos as $p):
                        $b   = $comp['bimestres'][$p['id']] ?? null;
                        $lit = $b['literal'] ?? null;
                        $lc  = $lit ? strtolower($lit) : 'vacio';
                    ?>
                        <?php if ($esSecundaria): ?>
                            <td class="td-mini td-nota">
                                <?= ($b && $b['nota'] !== null) ? fmt_nota($b['nota']) : '' ?>
                            </td>
                        <?php endif; ?>

                        <td class="td-mini td-lit td-lit--<?= $lc ?>">
                            <?= $lit ? e($lit) : '' ?>
                        </td>

                        <td class="td-concl">
                            <?php if ($b && !empty($b['conclusion'])): ?>
                                <div class="conclusion-clip"><?= e($b['conclusion']) ?></div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>

                    <?php $lf = $comp['literal_final']; ?>
                    <td class="td-final td-lit--<?= $lf ? strtolower($lf) : 'vacio' ?>">
                        <?= $lf ? e($lf) : '' ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php endforeach; ?>
        <?php if (!empty($conducta)): ?>
            <tr class="fila-area fila-area--conducta">
                <td colspan="<?= $totalCols ?>">CONDUCTA</td>
            </tr>
            <tr class="fila-comp">
                <td class="td-comp">Comportamiento</td>
                <?php foreach ($periodos as $p):
                    $clit = $conducta[$p['id']] ?? null;
                    $clc  = $clit ? strtolower($clit) : 'vacio';
                ?>
                    <?php if ($esSecundaria): ?><td class="td-mini td-nota"></td><?php endif; ?>
                    <td class="td-mini td-lit td-lit--<?= $clc ?>">
                        <?= $clit ? e($clit) : '' ?>
                    </td>
                    <td class="td-concl"></td>
                <?php endforeach; ?>
                <td class="td-final td-lit--vacio"></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php endif; ?>

<!-- ── Bloque inferior: asistencia + QR alineados horizontalmente ─ -->
<?php
$mostrarAsistencia = !empty($asistencia);
$mostrarQr         = !$vistaPrevia && !empty($url_boleta);
?>
<?php if ($mostrarAsistencia || $mostrarQr): ?>
<div class="boleta-info">

    <?php if ($mostrarAsistencia):
        $aB = $asistencia['bimestre'];
        $aA = $asistencia['anual'];
    ?>
    <section class="boleta-asistencia">
        <h2 class="boleta-asistencia__titulo">Asistencia</h2>
        <table class="boleta-asistencia__tabla">
            <thead>
                <tr>
                    <th class="boleta-asistencia__th-tipo">Tipo</th>
                    <th class="boleta-asistencia__th-num">Bim.</th>
                    <th class="boleta-asistencia__th-num">Anual</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Faltas</td>
                    <td class="boleta-asistencia__num"><?= $aB['faltas'] ?></td>
                    <td class="boleta-asistencia__num"><?= $aA['faltas'] ?></td>
                </tr>
                <tr>
                    <td>Faltas justificadas</td>
                    <td class="boleta-asistencia__num"><?= $aB['faltas_justificadas'] ?></td>
                    <td class="boleta-asistencia__num"><?= $aA['faltas_justificadas'] ?></td>
                </tr>
                <tr>
                    <td>Tardanzas</td>
                    <td class="boleta-asistencia__num"><?= $aB['tardanzas'] ?></td>
                    <td class="boleta-asistencia__num"><?= $aA['tardanzas'] ?></td>
                </tr>
                <tr>
                    <td>Tardanzas justificadas</td>
                    <td class="boleta-asistencia__num"><?= $aB['tardanzas_justificadas'] ?></td>
                    <td class="boleta-asistencia__num"><?= $aA['tardanzas_justificadas'] ?></td>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if ($mostrarQr): ?>
    <div class="boleta-qr-wrap">
        <div class="boleta-qr" data-qr-url="<?= e($url_boleta) ?>"></div>
        <div class="boleta-qr-info">
            <p class="boleta-qr-info__titulo">Boleta digital</p>
            <p class="boleta-qr-info__sub">Escanea para ver la versión digital</p>
            <code class="boleta-qr-info__url"><?= e($url_boleta) ?></code>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<?php if ($mostrarQr): ?>
<?php if (!defined('BOLETA_QR_SCRIPT_LOADED')): define('BOLETA_QR_SCRIPT_LOADED', true); ?>
<script src="<?= url('js/qrcode.min.js') ?>"></script>
<script>
(function () {
    function generarQRs() {
        document.querySelectorAll('[data-qr-url]').forEach(function (el) {
            if (el.dataset.qrInit) return;
            el.dataset.qrInit = '1';
            var u = el.getAttribute('data-qr-url');
            if (u && typeof QRCode !== 'undefined') {
                new QRCode(el, { text: u, width: 72, height: 72, correctLevel: QRCode.CorrectLevel.M });
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', generarQRs);
    } else {
        generarQRs();
    }
})();
</script>
<?php endif; ?>
<?php endif; ?>

<!-- ── Pie de página — firmas ────────────────────────────────── -->
<footer class="boleta-footer">

    <!-- Tutor: espacio de firma vacío para igualar altura con Director -->
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__espacio-firma"></div>
        <div class="boleta-footer__linea"></div>
        <?php if (!empty($tutor['nombre'])): ?>
            <div class="boleta-footer__nombre"><?= e($tutor['nombre']) ?></div>
        <?php endif; ?>
        <div class="boleta-footer__cargo"><?= $cargoTutor ?></div>
    </div>

    <!-- Director EBR: firma PNG anclada al fondo del espacio.
         En vista previa el espacio queda vacío (igual que el del tutor) para
         mantener la alineación, pero el nombre y cargo siguen impresos. -->
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__espacio-firma">
            <?php if (!$vistaPrevia && !empty($directorEbr['firma_path'])): ?>
                <img src="<?= url($directorEbr['firma_path']) ?>"
                     alt=""
                     aria-hidden="true"
                     class="boleta-footer__firma-img">
            <?php endif; ?>
        </div>
        <div class="boleta-footer__linea"></div>
        <?php if (!empty($directorEbr['nombre_completo'])): ?>
            <div class="boleta-footer__nombre"><?= e($directorEbr['nombre_completo']) ?></div>
        <?php endif; ?>
        <div class="boleta-footer__cargo"><?= $cargoDirector ?></div>
    </div>

</footer>
