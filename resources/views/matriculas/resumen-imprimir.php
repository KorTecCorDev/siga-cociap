<?php
/**
 * Cuadro de matrícula imprimible (A4 portrait) — reporte al comité directivo.
 * Layout: print. Reutiliza el parcial _cuadro-matricula.php.
 *
 * @var array       $cuadro
 * @var string      $anioLabel
 * @var string      $nivelLabel
 * @var array|null  $directorEbr  { sello_path }
 */
?>
<div class="resumen-print">

    <header class="resumen-print__head">
        <img class="resumen-print__logo" src="<?= url('assets/img/logo_cociap.png') ?>" alt="COCIAP">
        <div class="resumen-print__titulo">
            <h1><?= e(config('institucion')) ?></h1>
            <p>Cuadro de matrícula por grado<?= $anioLabel !== '' ? ' &middot; ' . e($anioLabel) : '' ?></p>
            <p class="resumen-print__sub"><strong>Nivel:</strong> <?= e($nivelLabel) ?></p>
        </div>
    </header>

    <div class="resumen-print__meta">
        <span><strong>Fecha de impresión:</strong> <?= e(date('d/m/Y H:i')) ?></span>
    </div>

    <?php if (empty($cuadro)): ?>
        <p class="resumen-print__vacio">No hay matrículas registradas para el año y nivel seleccionados.</p>
    <?php else: ?>
        <?php require VIEW_PATH . '/matriculas/_cuadro-matricula.php'; ?>
    <?php endif; ?>

    <?php if ($directorEbr && !empty($directorEbr['sello_path'])): ?>
        <footer class="resumen-print__footer">
            <div class="resumen-print__sello-bloque">
                <img class="resumen-print__sello" src="<?= url($directorEbr['sello_path']) ?>"
                     alt="" aria-hidden="true">
            </div>
        </footer>
    <?php endif; ?>

</div>
