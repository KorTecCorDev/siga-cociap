<?php
/**
 * Vista: Actas SIAGIE — acta generada (paso 3).
 *
 * @var array $res  ['nombre','reporte','resumen','persistidos', ...]
 */
$resumen = $res['resumen'];
$totalCeldas = $resumen['nl'] + $resumen['conc'];
?>

<div class="page-header">
    <div>
        <a href="<?= url('admin/actas-siagie') ?>" class="btn btn--secondary btn--sm">← Llenar otra sección</a>
        <h1 class="page-title">Acta generada</h1>
        <p class="page-subtitle"><?= e($res['nombre']) ?></p>
    </div>
</div>

<div class="actas-siagie">

    <div class="card mb-lg">
        <div class="card__body">
            <div class="actas-ok">
                <span class="actas-ok__icono">✓</span>
                <div>
                    <strong>Acta llenada y verificada.</strong>
                    Se escribieron <strong><?= (int) $resumen['nl'] ?></strong> notas y
                    <strong><?= (int) $resumen['conc'] ?></strong> conclusiones
                    (<?= $totalCeldas ?> celdas, verificadas una a una).
                    <?php if ((int) $res['persistidos'] > 0): ?>
                        Se guardaron <strong><?= (int) $res['persistidos'] ?></strong> código(s) SIAGIE en SIGA.
                    <?php endif; ?>
                </div>
            </div>

            <div class="actas-acciones">
                <a href="<?= url('admin/actas-siagie/resultado/descargar') ?>" class="btn btn--primary" data-descarga="<?= e($res['nombre']) ?>">
                    <span class="btn-icon btn-icon--save" aria-hidden="true"></span>
                    Descargar acta llenada (.xlsx)
                </a>
                <a href="<?= url('admin/actas-siagie/resultado/reporte') ?>" class="btn btn--secondary" data-descarga="<?= e('reporte_' . pathinfo($res['nombre'], PATHINFO_FILENAME) . '.txt') ?>">
                    Descargar reporte (.txt)
                </a>
            </div>

            <p class="actas-nota">
                <span class="badge badge--info">Siguiente paso</span>
                Sube este archivo al SIAGIE tal cual. El archivo generado es temporal:
                descárgalo ahora.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Reporte de auditoría</h2>
        </div>
        <div class="card__body">
            <details class="actas-reporte" open>
                <summary>Ver detalle</summary>
                <pre class="actas-reporte__pre"><?= e(implode("\n", $res['reporte'])) ?></pre>
            </details>
        </div>
    </div>

</div>
