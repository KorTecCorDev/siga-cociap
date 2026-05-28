<?php
/**
 * @var array|null $anioActivo  ['id' => int, 'anio' => int] o null
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Buscador de Estudiantes</h1>
        <p class="page-subtitle">
            <?php if ($anioActivo): ?>
                Consulta por DNI o apellidos y nombres — Año académico <?= (int) $anioActivo['anio'] ?>
            <?php else: ?>
                No hay un año académico activo configurado.
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!$anioActivo): ?>
<div class="card">
    <div class="card__body">
        <p class="text-muted text-center">
            Active un año académico para poder buscar estudiantes.
        </p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card__body">
        <div class="buscador">
            <label class="form-label" for="buscadorInput">Buscar estudiante</label>
            <div class="buscador__campo">
                <svg class="buscador__icono" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M20 20l-3.2-3.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <input type="search"
                       id="buscadorInput"
                       class="form-input buscador__input"
                       placeholder="Escriba el DNI o los apellidos y nombres…"
                       autocomplete="off"
                       autofocus>
                <button type="button" class="buscador__limpiar" id="buscadorLimpiar" aria-label="Limpiar búsqueda" hidden>✕</button>
                <span class="buscador__spinner" id="buscadorSpinner" hidden></span>
            </div>
            <p class="buscador__hint text-sm text-muted">
                <span class="buscador__hint-icono" aria-hidden="true">💡</span>
                Escriba al menos 2 caracteres. El sistema detecta automáticamente
                si busca por <strong>DNI</strong> o por <strong>apellidos y nombres</strong>.
            </p>
        </div>
    </div>
</div>

<div id="buscadorResultados" class="buscador-resultados" aria-live="polite"></div>

<?php endif; ?>
