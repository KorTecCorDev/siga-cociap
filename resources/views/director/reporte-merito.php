<?php
/**
 * Vista: Reporte imprimible de Orden de Mérito — A4 vertical (portrait)
 *
 * Estructura por grado — cada hoja es un documento autónomo y firmable por sí solo:
 *   1 hoja  → Orden de mérito del grado (firman Director EBR + todos sus tutores)
 *   N hojas → Ranking por sección, UNA HOJA POR SECCIÓN
 *             (firman Director EBR + el tutor de esa sección)
 *
 * Las hojas van agrupadas por grado: mérito del grado y a continuación sus secciones.
 *
 * @var array      $periodo       { id, anio, nombre_display, numero }
 * @var array      $ranking       [grado_id => { grado, conteos, general[], por_seccion{sec => []}, tutores }]
 * @var string     $institucion
 * @var array|null $directorEbr   { nombre_completo, sexo, firma_path }
 * @var bool       $hayPendientes
 */
$hoy = (new DateTime())->format('d/m/Y');

$cargoDirector = match($directorEbr['sexo'] ?? null) {
    'F'     => 'Directora E.B.R.',
    'M'     => 'Director E.B.R.',
    default => 'Director(a) E.B.R.',
};

// Bloque de firma del Director EBR: idéntico en todas las hojas del documento.
$firmaDirector = [
    'nombre'     => $directorEbr['nombre_completo'] ?? null,
    'cargo'      => $cargoDirector,
    'firma_path' => $directorEbr['firma_path'] ?? null,
];

$cargoTutor = static fn(?array $tutor): string => match($tutor['sexo'] ?? null) {
    'M'     => 'Tutor de Aula',
    'F'     => 'Tutora de Aula',
    default => 'Tutor(a) de Aula',
};

// El salto va ANTES de cada hoja menos la primera: un salto al final del
// documento imprime una hoja en blanco de más.
$primeraHoja = true;
?>

<?php if (!empty($hayPendientes)): ?>
    <div class="reporte-aviso-pendiente">
        ⚠ Documento NO oficializable: existen empates sin resolver. Los puestos en disputa
        son provisionales hasta que la Dirección, Registro Académico o Administración los resuelvan.
    </div>
<?php endif; ?>

<?php foreach ($ranking as $data): ?>
    <?php
    $grado          = $data['grado'];
    $conteos        = $data['conteos'];
    $tutores        = $data['tutores'];
    $codModular     = ($grado['nivel_codigo'] ?? '') === 'sec' ? '1310044 - 0' : '1719525 - 0';
    $totalGeneral   = count($data['general']);
    $infoConteos    = $conteos['num_areas'] . ' área' . ($conteos['num_areas'] !== 1 ? 's' : '') . ' a promediar';

    // Hoja del grado: firma el Director EBR y todos los tutores del grado.
    $firmasGrado = [$firmaDirector];
    foreach ($tutores as $secNombre => $tutor) {
        $firmasGrado[] = [
            'nombre' => $tutor['nombre'] ?? null,
            'cargo'  => $cargoTutor($tutor) . ' — Secc. ' . $secNombre,
        ];
    }
    ?>

    <!-- ══════════════════════════════════════════════════════════
         HOJA: Orden de Mérito del grado
    ═══════════════════════════════════════════════════════════ -->
    <?php if (!$primeraHoja): ?><div class="boleta-salto-pagina"></div><?php endif; $primeraHoja = false; ?>
    <div class="reporte-pagina merito-doc">

        <header class="boleta-header">
            <div class="boleta-header__logo-wrap">
                <img src="<?= url('assets/img/logo_cociap.png') ?>"
                     alt="COCIAP" class="boleta-header__logo">
            </div>
            <div class="boleta-header__centro">
                <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
                <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
                <div class="boleta-header__modular">Cód. Modular: <?= $codModular ?></div>
                <div class="boleta-header__titulo">Reporte de Orden de Mérito &mdash; <?= e($periodo['anio'] ?? '') ?></div>
            </div>
            <div class="boleta-header__fecha-wrap">
                <div class="boleta-header__fecha-label">Impresión</div>
                <div class="boleta-header__fecha"><?= $hoy ?></div>
            </div>
        </header>

        <div class="reporte-titulo">
            <div class="reporte-titulo__grupo">
                <span class="reporte-titulo__principal">Orden de Mérito</span>
                <span class="reporte-titulo__sub">
                    &mdash; <?= e($grado['nombre_display'] ?? '') ?> &mdash; <?= e($grado['nivel_nombre'] ?? '') ?> &mdash; <?= e($periodo['nombre_display'] ?? '') ?>
                </span>
            </div>
            <div class="reporte-titulo__meta">
                <span class="reporte-titulo__info"><?= e($infoConteos) ?></span>
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
                                <?php if ($est['media_beca']): ?>
                                    <span class="distincion-beca">Media Beca &mdash; 1° Puesto del Grado</span>
                                <?php elseif ($pos === 2): ?>
                                    <span class="distincion-grado distincion-grado--2">2° Puesto del Grado</span>
                                <?php elseif ($pos === 3): ?>
                                    <span class="distincion-grado distincion-grado--3">3° Puesto del Grado</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($est['seccion_nombre']) ?></td>
                            <td class="tm-comp"><?= (int) $est['num_competencias'] ?></td>
                            <td class="tm-total"><?= (int) $est['total_notas'] ?></td>
                            <td>
                                <span class="promedio-val"><?= number_format((float) $est['promedio_general'], 2) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

        <footer class="reporte-footer">
            <?php foreach ($firmasGrado as $firma): ?>
                <div class="reporte-footer__bloque">
                    <div class="reporte-footer__espacio-firma">
                        <?php if (!empty($firma['firma_path'])): ?>
                            <img src="<?= url($firma['firma_path']) ?>"
                                 alt=""
                                 aria-hidden="true"
                                 class="reporte-footer__firma-img">
                        <?php endif; ?>
                    </div>
                    <div class="reporte-footer__linea"></div>
                    <?php if (!empty($firma['nombre'])): ?>
                        <div class="reporte-footer__nombre"><?= e($firma['nombre']) ?></div>
                    <?php endif; ?>
                    <div class="reporte-footer__cargo"><?= e($firma['cargo']) ?></div>
                </div>
            <?php endforeach; ?>
        </footer>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         HOJAS: Ranking por sección — UNA HOJA POR SECCIÓN
    ═══════════════════════════════════════════════════════════ -->
    <?php foreach ($data['por_seccion'] as $secNombre => $estudiantes): ?>
        <?php
        $tutor        = $tutores[$secNombre] ?? null;
        $totalSeccion = count($estudiantes);
        $firmasSeccion = [
            $firmaDirector,
            [
                'nombre' => $tutor['nombre'] ?? null,
                'cargo'  => $cargoTutor($tutor) . ' — Secc. ' . $secNombre,
            ],
        ];
        ?>

        <div class="boleta-salto-pagina"></div>
        <div class="reporte-pagina merito-doc">

            <header class="boleta-header">
                <div class="boleta-header__logo-wrap">
                    <img src="<?= url('assets/img/logo_cociap.png') ?>"
                         alt="COCIAP" class="boleta-header__logo">
                </div>
                <div class="boleta-header__centro">
                    <div class="boleta-header__ugel">MINEDU &middot; DRE Áncash &middot; UGEL Huaraz</div>
                    <div class="boleta-header__colegio"><?= e($institucion ?? '') ?></div>
                    <div class="boleta-header__modular">Cód. Modular: <?= $codModular ?></div>
                    <div class="boleta-header__titulo">Orden de Mérito por Sección &mdash; <?= e($periodo['nombre_display'] ?? '') ?> &mdash; <?= e($periodo['anio'] ?? '') ?></div>
                </div>
                <div class="boleta-header__fecha-wrap">
                    <div class="boleta-header__fecha-label">Impresión</div>
                    <div class="boleta-header__fecha"><?= $hoy ?></div>
                </div>
            </header>

            <div class="reporte-titulo">
                <div class="reporte-titulo__grupo">
                    <span class="reporte-titulo__principal">Ranking &mdash; Sección <?= e($secNombre) ?></span>
                    <span class="reporte-titulo__sub">
                        &mdash; <?= e($grado['nombre_display'] ?? '') ?> &mdash; <?= e($grado['nivel_nombre'] ?? '') ?> &mdash; <?= e($periodo['nombre_display'] ?? '') ?>
                    </span>
                </div>
                <div class="reporte-titulo__meta">
                    <span class="reporte-titulo__info"><?= e($infoConteos) ?></span>
                    <span class="reporte-titulo__badge"><?= $totalSeccion ?> estudiante<?= $totalSeccion !== 1 ? 's' : '' ?></span>
                </div>
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

            <footer class="reporte-footer">
                <?php foreach ($firmasSeccion as $firma): ?>
                    <div class="reporte-footer__bloque">
                        <div class="reporte-footer__espacio-firma">
                            <?php if (!empty($firma['firma_path'])): ?>
                                <img src="<?= url($firma['firma_path']) ?>"
                                     alt=""
                                     aria-hidden="true"
                                     class="reporte-footer__firma-img">
                            <?php endif; ?>
                        </div>
                        <div class="reporte-footer__linea"></div>
                        <?php if (!empty($firma['nombre'])): ?>
                            <div class="reporte-footer__nombre"><?= e($firma['nombre']) ?></div>
                        <?php endif; ?>
                        <div class="reporte-footer__cargo"><?= e($firma['cargo']) ?></div>
                    </div>
                <?php endforeach; ?>
            </footer>

        </div>
    <?php endforeach; ?>

<?php endforeach; ?>
