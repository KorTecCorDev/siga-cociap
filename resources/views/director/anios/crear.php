<?php
/**
 * @var int $anioSugerido
 */
?>

<div class="page-header">
    <a href="<?= url('director/anios') ?>" class="btn btn--secondary btn--sm">&larr; Años académicos</a>
    <div>
        <h1 class="page-title">Nuevo año académico</h1>
        <p class="page-subtitle">Se crearán los 4 bimestres con fechas referenciales editables</p>
    </div>
</div>

<div class="card anio-form-card">
    <div class="card__body">
        <form method="POST" action="<?= url('director/anios/crear') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="anio">Año escolar</label>
                <input type="number"
                       name="anio"
                       id="anio"
                       class="form-input"
                       min="2000"
                       max="2100"
                       step="1"
                       value="<?= e((string) $anioSugerido) ?>"
                       required
                       autofocus>
                <p class="form-hint">
                    Solo se crea el año y sus 4 bimestres (I a IV) en estado
                    <strong>pendiente</strong>. Las secciones y cargas se gestionan aparte.
                    El año queda <strong>planificado</strong> hasta que lo actives.
                </p>
            </div>

            <div class="anio-form-aviso">
                <strong>Fechas referenciales que se generarán</strong> (podrás ajustarlas luego):
                <ul>
                    <li>I Bimestre &middot; marzo &ndash; mayo</li>
                    <li>II Bimestre &middot; mayo &ndash; julio</li>
                    <li>III Bimestre &middot; agosto &ndash; octubre</li>
                    <li>IV Bimestre &middot; octubre &ndash; diciembre</li>
                </ul>
            </div>

            <div class="btn-group form-actions">
                <button type="submit" class="btn btn--primary">Crear año académico</button>
                <a href="<?= url('director/anios') ?>" class="btn btn--secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
