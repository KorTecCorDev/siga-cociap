<?php
/**
 * Nómina imprimible (A4) de una sección. Layout: print.
 * Sin DNI: dato sensible de consulta restringida.
 * @var array $seccion  { grado_nombre, seccion_nombre, nivel_nombre }
 * @var array $alumnos
 */
?>
<div class="nomina-print">

    <header class="nomina-print__head">
        <img class="nomina-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="nomina-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Nómina de matriculados</p>
            <p class="nomina-print__sec">
                <?= e($seccion['nivel_nombre']) ?> ·
                <?= e($seccion['grado_nombre']) ?> · Sección <?= e($seccion['seccion_nombre']) ?>
                · <?= count($alumnos) ?> estudiantes
            </p>
        </div>
    </header>

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

</div>
