<?php
/**
 * Vista: boleta de calificaciones individual (impresión A4)
 *
 * @var array  $alumno      { nombre_completo, dni, grado_nombre, seccion_nombre,
 *                            nivel_nombre, escala_boleta }
 * @var array  $periodo     { nombre_display, nombre, anio }
 * @var array  $areas       Notas agrupadas por área: ['Área X' => [...competencias]]
 * @var string $institucion Nombre de la institución (config app.php)
 */

use App\Models\CalificacionModel;

$esSecundaria = ($alumno['escala_boleta'] === 'ambas');
// columnas: competencia + nota(sec) + literal + conclusión
$cols = $esSecundaria ? 4 : 3;
$hoy  = (new DateTime())->format('d/m/Y');
?>

<!-- ── Cabecera institucional ───────────────────────────────── -->
<header class="boleta-header">
    <img
        src="<?= url('assets/img/logo_cociap.png') ?>"
        alt="COCIAP"
        class="boleta-header__logo"
    >
    <div class="boleta-header__texto">
        <div class="boleta-header__ugel">UGEL Huaraz — Gobierno Regional de Ancash — Perú</div>
        <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
        <div class="boleta-header__titulo">Boleta de Calificaciones</div>
        <div class="boleta-header__periodo"><?= e($periodo['nombre_display'] ?? '') ?></div>
        <div class="boleta-header__fecha">Emitida: <?= $hoy ?></div>
    </div>
</header>

<!-- ── Datos del alumno ─────────────────────────────────────── -->
<div class="boleta-alumno">
    <div>
        <strong>Apellidos y Nombres:</strong>
        <?= e($alumno['nombre_completo'] ?? '') ?>
    </div>
    <div>
        <strong>DNI:</strong> <?= e($alumno['dni'] ?? '') ?>
    </div>
    <div>
        <strong>Grado y Sección:</strong>
        <?= e($alumno['grado_nombre'] ?? '') ?> — Sección <?= e($alumno['seccion_nombre'] ?? '') ?>
    </div>
    <div>
        <strong>Nivel:</strong> <?= e($alumno['nivel_nombre'] ?? '') ?>
    </div>
</div>

<!-- ── Tabla de calificaciones ──────────────────────────────── -->
<?php if (empty($areas)): ?>
    <p style="font-size:8pt; color:#555; margin: 3mm 0;">
        No hay calificaciones registradas para este periodo.
    </p>
<?php else: ?>

<table class="boleta-tabla">
    <thead>
        <tr>
            <th class="th-comp">Área / Competencia</th>
            <?php if ($esSecundaria): ?>
                <th class="th-centro">Nota</th>
            <?php endif; ?>
            <th class="th-centro">Lit.</th>
            <th class="th-conclusion">Conclusión descriptiva</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($areas as $areaNombre => $competencias): ?>

            <!-- Encabezado de área -->
            <tr class="fila-area">
                <td colspan="<?= $cols ?>">
                    <?= e(mb_strtoupper($areaNombre)) ?>
                </td>
            </tr>

            <?php foreach ($competencias as $comp): ?>
                <?php
                $nota    = isset($comp['nota_numerica']) ? (int) $comp['nota_numerica'] : null;
                $literal = $nota !== null ? CalificacionModel::toLiteral($nota) : null;

                $prefijoSubarea = '';
                if (($comp['area_tipo'] ?? '') === 'con_subareas' && !empty($comp['subarea_nombre'])) {
                    $prefijoSubarea = $comp['subarea_nombre'] . ' — ';
                }
                $nombreComp = trim(
                    $prefijoSubarea .
                    ($comp['codigo_minedu'] ? $comp['codigo_minedu'] . '. ' : '') .
                    ($comp['competencia_nombre'] ?? '')
                );
                $conclusion = $comp['conclusion_descriptiva'] ?? '';
                ?>

                <tr class="fila-comp">
                    <td><?= e($nombreComp) ?></td>
                    <?php if ($esSecundaria): ?>
                        <td class="td-centro"><?= fmt_nota($nota) ?></td>
                    <?php endif; ?>
                    <td class="td-centro"><?= e($literal ?? '—') ?></td>
                    <td class="td-conclusion">
                        <?php if ($conclusion !== ''): ?>
                            <div class="conclusion-clip"><?= e($conclusion) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php endforeach; ?>
    </tbody>
</table>

<?php endif; ?>

<!-- ── Pie de página con firmas ─────────────────────────────── -->
<footer class="boleta-footer">
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div>Director(a) General</div>
    </div>
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div>Registro Académico</div>
    </div>
    <div class="boleta-footer__bloque">
        <div class="boleta-footer__linea"></div>
        <div>Padre / Madre / Tutor(a)</div>
    </div>
</footer>
