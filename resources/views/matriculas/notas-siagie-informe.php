<?php
/**
 * Informe imprimible: Notas autorizadas por dirección para SIAGIE.
 * Respaldo físico de la autorización. Layout `print` (lo fija el controlador).
 *
 * @var array $matricula
 * @var array $bloques   [ ['periodo'=>..., 'registradas'=>[...]], ... ]
 * @var ?array $director  director EBR vigente (nombre_completo, sexo, firma_path)
 */
$cargoDirector = match($director['sexo'] ?? null) {
    'F'     => 'Directora E.B.R.',
    'M'     => 'Director E.B.R.',
    default => 'Director(a) E.B.R.',
};
?>
<div class="informe-siagie">

    <header class="informe-siagie__head">
        <img src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP" class="informe-siagie__logo">
        <div>
            <p class="informe-siagie__institucion"><?= e(config('institucion')) ?></p>
            <h1 class="informe-siagie__titulo">Informe de notas autorizadas — SIAGIE</h1>
            <p class="informe-siagie__sub">Notas autorizadas por Dirección para un estudiante no evaluado por ausencia justificada. Válidas únicamente para el registro en el SIAGIE.</p>
        </div>
    </header>

    <section class="informe-siagie__datos">
        <div><span>Estudiante:</span> <strong><?= e($matricula['nombre_completo']) ?></strong></div>
        <div><span>DNI:</span> <?= e($matricula['dni'] ?? '—') ?></div>
        <div><span>Grado y sección:</span> <?= e(($matricula['grado_numero'] ?? '') . ($matricula['seccion_nombre'] ?? '') . ' — ' . ($matricula['nivel_nombre'] ?? '')) ?></div>
        <div><span>Año académico:</span> <?= e($matricula['anio'] ?? '') ?></div>
    </section>

    <?php foreach ($bloques as $b): ?>
    <section class="informe-siagie__bloque">
        <h2 class="informe-siagie__bimestre"><?= e($b['periodo']['nombre_display']) ?></h2>
        <table class="informe-siagie__tabla">
            <thead>
                <tr>
                    <th>Área</th><th>Competencia</th><th class="tc">Nota</th>
                    <th>Conclusión descriptiva</th><th>Resolución / autorización</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($b['registradas'] as $r): ?>
                <tr>
                    <td><?= e($r['area_nombre'] ?? '—') ?></td>
                    <td><?= e($r['competencia_nombre']) ?></td>
                    <td class="tc"><strong><?= e($r['nota_literal']) ?></strong></td>
                    <td><?= e($r['conclusion_descriptiva'] ?? '—') ?></td>
                    <td><?= e($r['resolucion']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endforeach; ?>

    <footer class="informe-siagie__firma">
        <?php if (!empty($director['firma_path'])): ?>
            <img src="<?= url($director['firma_path']) ?>" alt="Firma" class="informe-siagie__firma-img">
        <?php endif; ?>
        <div class="informe-siagie__firma-linea"></div>
        <div class="informe-siagie__firma-nombre"><?= e($director['nombre_completo'] ?? '') ?></div>
        <div class="informe-siagie__firma-cargo"><?= e($cargoDirector) ?></div>
    </footer>

</div>
