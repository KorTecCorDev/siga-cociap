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
 * @var string $institucion
 */

use App\Models\CalificacionModel;

$esSecundaria = ($alumno['escala_boleta'] === 'ambas');
$hoy          = (new DateTime())->format('d/m/Y');

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

<!-- ── Cabecera institucional ───────────────────────────────── -->
<header class="boleta-header">
    <div class="boleta-header__logo-wrap">
        <img src="<?= url('assets/img/logo_cociap.png') ?>"
             alt="COCIAP" class="boleta-header__logo">
    </div>
    <div class="boleta-header__centro">
        <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
        <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
        <div class="boleta-header__titulo">Informe de Progreso de las Competencias del Estudiante</div>
        <div class="boleta-header__anio">Año Académico <?= e($alumno['anio_academico'] ?? '') ?></div>
    </div>
    <div class="boleta-header__fecha-wrap">
        <div class="boleta-header__fecha-label">Emitida</div>
        <div class="boleta-header__fecha"><?= $hoy ?></div>
    </div>
</header>

<!-- ── Datos del estudiante ─────────────────────────────────── -->
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
                    <td class="td-comp" title="<?= e($comp['nombre']) ?>">
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
    </tbody>
</table>

<?php endif; ?>

<!-- ── Pie de página — firmas ────────────────────────────────── -->
<footer class="boleta-footer">
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div class="boleta-footer__cargo">Tutor(a) de Aula</div>
    </div>
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div class="boleta-footer__cargo">Director(a) Académico(a)</div>
    </div>
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div class="boleta-footer__cargo">Padre / Madre / Tutor(a)</div>
    </div>
</footer>
