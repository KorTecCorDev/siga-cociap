<?php
/**
 * Vista: Reporte imprimible de Orden de Mérito — A4 landscape
 *
 * @var array  $periodo     { id, anio, nombre_display, numero }
 * @var array  $ranking     [grado_id => { grado, conteos, general[], por_seccion{sec => []} }]
 * @var string $institucion
 */
$hoy = (new DateTime())->format('d/m/Y');
?>

<?php foreach ($ranking as $data): ?>
    <?php
    $grado         = $data['grado'];
    $conteos       = $data['conteos'];
    $codModular    = ($grado['nivel_codigo'] ?? '') === 'sec' ? '1310044 - 0' : '1719525 - 0';
    $totalGeneral  = count($data['general']);
    $totalSecciones= count($data['por_seccion']);
    $infoConteos   = $conteos['num_areas'] . ' área' . ($conteos['num_areas'] !== 1 ? 's' : '')
                   . ' · ' . $conteos['num_competencias'] . ' competencia' . ($conteos['num_competencias'] !== 1 ? 's' : '');
    ?>

    <!-- ══════════════════════════════════════════════════════════
         PÁGINA 1: Cuadro de Honor General
    ═══════════════════════════════════════════════════════════ -->
    <div class="reporte-pagina">

        <header class="boleta-header">
            <div class="boleta-header__logo-wrap">
                <img src="<?= url('assets/img/logo_cociap.png') ?>"
                     alt="COCIAP" class="boleta-header__logo">
            </div>
            <div class="boleta-header__centro">
                <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
                <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
                <div class="boleta-header__modular">Cód. Modular: <?= $codModular ?></div>
                <div class="boleta-header__titulo">Orden de Mérito — <?= e($periodo['nombre_display'] ?? '') ?> &mdash; <?= e($periodo['anio'] ?? '') ?></div>
            </div>
            <div class="boleta-header__fecha-wrap">
                <div class="boleta-header__fecha-label">Impresión</div>
                <div class="boleta-header__fecha"><?= $hoy ?></div>
            </div>
        </header>

        <div class="reporte-titulo">
            <div class="reporte-titulo__grupo">
                <span class="reporte-titulo__principal">Cuadro de Honor General</span>
                <span class="reporte-titulo__sub">
                    &mdash; <?= e($grado['nivel_nombre'] ?? '') ?> &mdash; <?= e($grado['nombre_display'] ?? '') ?>
                </span>
            </div>
            <div class="reporte-titulo__meta">
                <span class="reporte-titulo__info"><?= e($infoConteos) ?> a promediar</span>
                <span class="reporte-titulo__badge"><?= $totalGeneral ?> estudiante<?= $totalGeneral !== 1 ? 's' : '' ?></span>
            </div>
        </div>

        <?php if (empty($data['general'])): ?>
            <p class="reporte-vacio">Sin calificaciones registradas en este grado para el periodo seleccionado.</p>
        <?php else: ?>

            <table class="tabla-merito">
                <thead>
                    <tr>
                        <th class="tm-puesto">Puesto</th>
                        <th class="tm-nombre">Apellidos y Nombres</th>
                        <th class="tm-seccion">Secc.</th>
                        <th class="tm-comp">Comp.</th>
                        <th class="tm-total">Total</th>
                        <th class="tm-promedio">Promedio</th>
                        <th class="tm-distincion">Distinción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['general'] as $est): ?>
                        <?php $pos = $est['puesto']; ?>
                        <tr class="<?= $pos <= 3 ? 'fila-merito--' . $pos : '' ?>">
                            <td>
                                <span class="medalla medalla--<?= $pos <= 3 ? $pos : 'n' ?>">
                                    <?= $pos ?>°
                                </span>
                            </td>
                            <td class="tm-nombre">
                                <?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?>
                            </td>
                            <td><?= e($est['seccion_nombre']) ?></td>
                            <td class="tm-comp"><?= (int) $est['num_competencias'] ?></td>
                            <td class="tm-total"><?= (int) $est['total_notas'] ?></td>
                            <td>
                                <span class="promedio-val"><?= number_format((float) $est['promedio_general'], 2) ?></span>
                            </td>
                            <td>
                                <?php if ($est['media_beca']): ?>
                                    <span class="distincion-beca">Media Beca — 1° Puesto del Grado</span>
                                <?php elseif ($pos === 2): ?>
                                    <span class="distincion-grado distincion-grado--2">2° Puesto del Grado</span>
                                <?php elseif ($pos === 3): ?>
                                    <span class="distincion-grado distincion-grado--3">3° Puesto del Grado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

        <footer class="boleta-footer">
            <div class="boleta-footer__bloque">
                <div class="boleta-footer__linea"></div>
                <div class="boleta-footer__cargo">Director(a) Académico(a)</div>
            </div>
            <div class="boleta-footer__bloque">
                <div class="boleta-footer__linea"></div>
                <div class="boleta-footer__cargo">Director(a) General</div>
            </div>
            <div class="boleta-footer__bloque">
                <div class="boleta-footer__linea"></div>
                <div class="boleta-footer__cargo">Secretaria Académica</div>
            </div>
        </footer>

    </div>
    <div class="boleta-salto-pagina"></div>

    <!-- ══════════════════════════════════════════════════════════
         PÁGINA 2: Ranking completo por sección
    ═══════════════════════════════════════════════════════════ -->
    <div class="reporte-pagina">

        <header class="boleta-header">
            <div class="boleta-header__logo-wrap">
                <img src="<?= url('assets/img/logo_cociap.png') ?>"
                     alt="COCIAP" class="boleta-header__logo">
            </div>
            <div class="boleta-header__centro">
                <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
                <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
                <div class="boleta-header__modular">Cód. Modular: <?= $codModular ?></div>
                <div class="boleta-header__titulo">Orden de Mérito por Sección — <?= e($periodo['nombre_display'] ?? '') ?> &mdash; <?= e($periodo['anio'] ?? '') ?></div>
            </div>
            <div class="boleta-header__fecha-wrap">
                <div class="boleta-header__fecha-label">Impresión</div>
                <div class="boleta-header__fecha"><?= $hoy ?></div>
            </div>
        </header>

        <div class="reporte-titulo">
            <div class="reporte-titulo__grupo">
                <span class="reporte-titulo__principal">Ranking por Sección</span>
                <span class="reporte-titulo__sub">
                    &mdash; <?= e($grado['nivel_nombre'] ?? '') ?> &mdash; <?= e($grado['nombre_display'] ?? '') ?>
                </span>
            </div>
            <div class="reporte-titulo__meta">
                <span class="reporte-titulo__info"><?= e($infoConteos) ?> a promediar</span>
                <span class="reporte-titulo__badge"><?= $totalSecciones ?> sección<?= $totalSecciones !== 1 ? 'es' : '' ?></span>
            </div>
        </div>

        <?php if (empty($data['por_seccion'])): ?>
            <p class="reporte-vacio">Sin calificaciones registradas en este grado para el periodo seleccionado.</p>
        <?php else: ?>

            <?php foreach ($data['por_seccion'] as $secNombre => $estudiantes): ?>
                <?php $totalSeccion = count($estudiantes); ?>
                <div class="reporte-seccion-bloque">
                    <div class="reporte-seccion-bloque__header">
                        <span class="reporte-seccion-bloque__nombre">Sección <?= e($secNombre) ?></span>
                        <span class="reporte-seccion-bloque__count"><?= $totalSeccion ?> estudiante<?= $totalSeccion !== 1 ? 's' : '' ?></span>
                    </div>
                    <table class="tabla-merito">
                        <thead>
                            <tr>
                                <th class="tm-puesto">Puesto</th>
                                <th class="tm-nombre">Apellidos y Nombres</th>
                                <th class="tm-comp">Comp.</th>
                                <th class="tm-total">Total</th>
                                <th class="tm-promedio">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $est): ?>
                                <?php $pos = $est['puesto']; ?>
                                <tr class="<?= $pos <= 3 ? 'fila-merito--' . $pos : '' ?>">
                                    <td>
                                        <span class="medalla medalla--<?= $pos <= 3 ? $pos : 'n' ?>">
                                            <?= $pos ?>°
                                        </span>
                                    </td>
                                    <td class="tm-nombre">
                                        <?= e($est['apellido_paterno'] . ' ' . $est['apellido_materno'] . ', ' . $est['nombres']) ?>
                                    </td>
                                    <td class="tm-comp"><?= (int) $est['num_competencias'] ?></td>
                                    <td class="tm-total"><?= (int) $est['total_notas'] ?></td>
                                    <td>
                                        <span class="promedio-val"><?= number_format((float) $est['promedio_general'], 2) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
    <div class="boleta-salto-pagina"></div>

<?php endforeach; ?>
