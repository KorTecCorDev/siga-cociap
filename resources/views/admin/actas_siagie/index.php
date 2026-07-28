<?php
/**
 * Vista: Actas SIAGIE — subida de la plantilla RegNotas.
 */
?>

<div class="page-header">
    <div>
        <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
        <h1 class="page-title">Actas SIAGIE</h1>
        <p class="page-subtitle">Vuelca las notas oficiales de SIGA a la plantilla que el SIAGIE exporta por sección</p>
    </div>
    <div>
        <a href="<?= url('admin/actas-siagie/vinculos') ?>" class="btn btn--secondary btn--sm">
            Vínculos y cobertura
        </a>
    </div>
</div>

<div class="actas-siagie">

    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Cómo funciona</h2>
        </div>
        <div class="card__body">
            <ol class="actas-pasos">
                <li><strong>Subes</strong> el archivo <code>RegNotas_..._COCIAP_2026.xlsx</code> que descargaste del SIAGIE, de <strong>una sección</strong>.</li>
                <li><strong>Previsualizas</strong>: el sistema muestra qué se va a escribir y te deja resolver la identidad de los estudiantes que no emparejó solo. <em>No se modifica nada todavía.</em></li>
                <li><strong>Confirmas</strong> y descargas el acta llenada, lista para volver a subir al SIAGIE.</li>
            </ol>
            <p class="actas-nota">
                <span class="badge badge--info">Primaria y Secundaria</span>
                El bimestre debe estar <strong>cerrado</strong> en SIGA. Solo se escriben competencias
                bloqueadas; las celdas que ya traigan valor nunca se tocan.
            </p>
            <p class="text-muted">
                Un área solo se vuelca si tiene <strong>código de hoja SIAGIE</strong>; si no, queda
                fuera del acta sin aviso. Revísalo en
                <a href="<?= url('admin/actas-siagie/vinculos') ?>">Vínculos y cobertura</a>
                (ahí se ve qué notas se están quedando fuera). <strong>Una sección por archivo.</strong>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Subir plantilla de una sección</h2>
        </div>
        <div class="card__body">
            <form method="POST"
                  action="<?= url('admin/actas-siagie/previsualizar') ?>"
                  enctype="multipart/form-data"
                  class="actas-upload">
                <?= csrf_field() ?>
                <label class="actas-upload__drop">
                    <input type="file" name="acta" accept=".xlsx"
                           class="actas-upload__input" required>
                    <span class="actas-upload__hint">
                        Elige el archivo <strong>.xlsx</strong> del SIAGIE (máx. 6 MB)
                    </span>
                </label>
                <div class="actas-upload__actions">
                    <button type="submit" class="btn btn--primary">
                        Previsualizar →
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
