<?php
/**
 * Nómina imprimible (A4) de una sección. Layout: print.
 * Sin DNI: dato sensible de consulta restringida.
 * @var array      $seccion      { grado_nombre, seccion_nombre, nivel_nombre, tutor_nombre, tutor_sexo }
 * @var array      $alumnos
 * @var array|null $directorEbr  { sello_path }
 * @var array|null $anio         { anio }
 */
$tutorLabel = match ($seccion['tutor_sexo'] ?? null) {
    'M'     => 'Tutor de aula',
    'F'     => 'Tutora de aula',
    default => 'Tutor(a) de aula',
};
?>
<div class="nomina-print">

    <header class="nomina-print__head">
        <img class="nomina-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="nomina-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Nómina de matriculados<?= !empty($anio['anio']) ? ' &middot; ' . e($anio['anio']) : '' ?></p>
            <p class="nomina-print__sec">
                <?= e($seccion['nivel_nombre']) ?> &middot;
                <?= e($seccion['grado_nombre']) ?> &middot; Sección <?= e($seccion['seccion_nombre']) ?>
                &middot; <?= count($alumnos) ?> estudiantes
            </p>
        </div>
    </header>

    <div class="nomina-print__meta">
        <span><strong><?= $tutorLabel ?>:</strong>
            <?= $seccion['tutor_nombre'] !== '' ? e($seccion['tutor_nombre']) : '—' ?></span>
        <span><strong>Fecha de impresión:</strong> <?= e(date('d/m/Y H:i')) ?></span>
    </div>

    <table class="nomina-print__tabla">
        <thead>
            <tr>
                <th class="nomina-print__num">N°</th>
                <th>Apellidos y nombres</th>
                <th>Apoderado responsable</th>
                <th>Celular</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alumnos as $i => $a): ?>
                <tr>
                    <td class="nomina-print__num"><?= $i + 1 ?></td>
                    <td><?= e($a['apellido_paterno'] . ' ' . $a['apellido_materno'] . ', ' . $a['nombres']) ?></td>
                    <td><?= $a['apoderado_nombre'] !== '' ? e($a['apoderado_nombre']) : '—' ?></td>
                    <td><?= $a['apoderado_telefono'] ? e($a['apoderado_telefono']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($directorEbr && !empty($directorEbr['sello_path'])): ?>
        <footer class="nomina-print__footer">
            <div class="nomina-print__sello-bloque">
                <img class="nomina-print__sello" src="<?= url($directorEbr['sello_path']) ?>"
                     alt="" aria-hidden="true">
            </div>
        </footer>
    <?php endif; ?>

</div>
