<?php
/**
 * Vista: Registro imprimible de Conducta — A4 portrait (layout print).
 * Copia oficial de la grilla de criterios Si/No aprobada y bloqueada,
 * con encabezado formal, fecha de impresión y espacios de firma
 * (Auxiliar Responsable + Personal de Registro Académico).
 *
 * @var array  $seccion      { grado_nombre, seccion_nombre, nivel_nombre }
 * @var array  $periodo      { nombre_display, anio }
 * @var array  $criterios    [{ id, texto }]
 * @var array  $estudiantes  [{ matricula_id, nombre_completo, respuestas[criterio_id] }]
 * @var array  $cierre       { ra_bloqueado_en, ra_nombre, tutor_cerrado_en, tutor_nombre }
 * @var string $institucion
 */

$hoy   = (new DateTime())->format('d/m/Y');
$total = count($criterios);
?>

<div class="reporte-pagina registro-doc">

    <header class="boleta-header">
        <div class="boleta-header__logo-wrap">
            <img src="<?= url('assets/img/logo_cociap.png') ?>"
                 alt="COCIAP" class="boleta-header__logo">
        </div>
        <div class="boleta-header__centro">
            <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
            <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
            <div class="boleta-header__titulo">
                Registro de Conducta &mdash; <?= e($periodo['nombre_display'] ?? '') ?> <?= e((string) ($periodo['anio'] ?? '')) ?>
            </div>
        </div>
        <div class="boleta-header__fecha-wrap">
            <div class="boleta-header__fecha-label">Impresión</div>
            <div class="boleta-header__fecha"><?= $hoy ?></div>
        </div>
    </header>

    <div class="reporte-titulo">
        <div class="reporte-titulo__grupo">
            <span class="reporte-titulo__principal">
                <?= e($seccion['grado_nombre']) ?> &laquo;<?= e($seccion['seccion_nombre']) ?>&raquo;
            </span>
            <span class="reporte-titulo__sub">
                &mdash; <?= e($seccion['nivel_nombre']) ?> &mdash; Criterios Sí/No registrados por los auxiliares académicos
            </span>
        </div>
        <div class="reporte-titulo__meta">
            <span class="reporte-titulo__badge"><?= count($estudiantes) ?> estudiantes</span>
        </div>
    </div>

    <table class="tabla-registro">
        <thead>
            <tr>
                <th class="tr-num">N&deg;</th>
                <th class="tr-nombre">Apellidos y Nombres</th>
                <?php foreach ($criterios as $i => $c): ?>
                    <th class="tr-crit">C<?= $i + 1 ?></th>
                <?php endforeach; ?>
                <th class="tr-nota">Nota</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $idx => $est):
                $resp   = $est['respuestas'];
                $notaRa = $litRa = null;
                if ($total > 0 && count($resp) >= $total) {
                    $si     = count(array_filter($resp, fn($v) => (int) $v === 1));
                    $notaRa = (int) round(($si / $total) * 20, 0, PHP_ROUND_HALF_UP);
                    $litRa  = nota_a_literal($notaRa);
                }
            ?>
                <tr>
                    <td class="tr-num"><?= $idx + 1 ?></td>
                    <td class="tr-nombre"><?= e($est['nombre_completo']) ?></td>
                    <?php foreach ($criterios as $c):
                        $val = $resp[(int) $c['id']] ?? null;
                    ?>
                        <td class="tr-crit">
                            <?= $val === null ? '&mdash;' : ($val === 1 || $val === '1' ? '&#10003;' : '&#10007;') ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="tr-nota">
                        <?= $notaRa !== null ? fmt_nota($notaRa) . ' (' . $litRa . ')' : '&mdash;' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="registro-doc__leyenda">
        <span class="registro-doc__leyenda-titulo">Criterios evaluados (&#10003; = cumple &middot; &#10007; = no cumple):</span>
        <table class="tabla-leyenda">
            <thead>
                <tr>
                    <th class="tl-cod">C&oacute;d.</th>
                    <th class="tl-texto">Criterio evaluado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($criterios as $i => $c): ?>
                    <tr>
                        <td class="tl-cod">C<?= $i + 1 ?></td>
                        <td class="tl-texto"><?= e($c['texto']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="registro-doc__traza">
        Registro bloqueado y aprobado por <strong><?= e($cierre['ra_nombre']) ?></strong>
        (Registro Académico) el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>.
        <?php if (!empty($cierre['tutor_cerrado_en'])): ?>
            Cerrado por el tutor(a) <strong><?= e($cierre['tutor_nombre'] ?? '') ?></strong>
            el <?= e(fechaLima($cierre['tutor_cerrado_en'])) ?>.
        <?php endif; ?>
    </p>

    <footer class="reporte-footer">
        <div class="reporte-footer__bloque">
            <div class="reporte-footer__espacio-firma"></div>
            <div class="reporte-footer__linea"></div>
            <div class="reporte-footer__cargo">Auxiliar Responsable</div>
        </div>
        <div class="reporte-footer__bloque">
            <div class="reporte-footer__espacio-firma"></div>
            <div class="reporte-footer__linea"></div>
            <div class="reporte-footer__cargo">Personal de Registro Académico</div>
        </div>
    </footer>

</div>
