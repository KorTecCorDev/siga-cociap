<?php
/**
 * Rectificación de calificaciones — buscador de estudiante + historial.
 * @var array $historial  últimas rectificaciones (auditoría)
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Rectificación de calificaciones</h1>
        <p class="page-subtitle">
            Corrige, notas ya aprobadas o bloqueadas.
            Busca al estudiante para empezar.
        </p>
    </div>
    <a href="<?= url('consulta-notas') ?>" class="btn btn--secondary btn--sm">Consultar notas (lectura)</a>
</div>

<?php if ($flash_success): ?>
    <div class="flash flash--success">✓ <?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
    <div class="flash flash--error"><?= e($flash_error) ?></div>
<?php endif; ?>

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
                Escriba al menos 2 caracteres. Al elegir un estudiante verá sus
                competencias rectificables (cerradas o bloqueadas).
            </p>
        </div>
    </div>
</div>

<!-- data-target-base redirige las tarjetas del buscador hacia este módulo -->
<div id="buscadorResultados" class="buscador-resultados" aria-live="polite"
     data-target-base="/rectificaciones/matricula/"></div>

<?php if (!empty($historial)): ?>
<div class="card mt-md">
    <div class="card__body">
        <p class="form-section-title">Rectificaciones recientes</p>
        <div class="tabla-notas-wrapper">
            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Estudiante</th>
                        <th>Competencia</th>
                        <th>Bimestre</th>
                        <th class="text-center">Antes</th>
                        <th class="text-center">Después</th>
                        <th>Motivo</th>
                        <th>Rectificó</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $h): ?>
                    <tr>
                        <td class="text-sm"><?= e(fecha_es(substr((string) $h['rectificado_en'], 0, 10))) ?></td>
                        <td class="text-sm"><?= e($h['estudiante']) ?></td>
                        <td class="text-sm"><?= e($h['competencia_nombre'] ?? '—') ?></td>
                        <td class="text-sm"><?= e($h['periodo_nombre'] ?? '—') ?></td>
                        <td class="text-center"><?= fmt_nota($h['nota_anterior'] !== null ? (int) $h['nota_anterior'] : null) ?></td>
                        <td class="text-center"><strong><?= fmt_nota($h['nota_nueva'] !== null ? (int) $h['nota_nueva'] : null) ?></strong></td>
                        <td class="text-sm rect-motivo"><?= e($h['motivo']) ?></td>
                        <td class="text-sm"><?= e($h['rectificador'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
