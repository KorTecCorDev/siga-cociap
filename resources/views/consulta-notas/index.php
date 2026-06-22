<?php
/**
 * Consulta de calificaciones (solo lectura) — selector de periodo + secciones.
 * @var array      $periodos
 * @var int        $periodoId
 * @var array|null $periodo
 * @var array      $secciones  [{seccion_id, grado_nombre, seccion_nombre, nivel_nombre, areas, competencias}]
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Consulta de calificaciones</h1>
        <p class="page-subtitle">
            Solo lectura. Muestra únicamente las notas oficiales (competencias aprobadas y bloqueadas).
            Para corregir, usa <a href="<?= url('rectificaciones') ?>">Rectificación</a>.
        </p>
    </div>
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('consulta-notas') ?>">
            <label class="form-label" for="periodo_id">Periodo / Bimestre</label>
            <select name="periodo_id" id="periodo_id" class="form-input" onchange="this.form.submit()">
                <option value="">— Seleccionar periodo —</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $periodoId === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> <?= e($p['anio']) ?>
                        (<?= $p['estado'] === 'activo' ? 'activo' : 'cerrado' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($periodo === null): ?>
    <div class="empty-state"><p>Selecciona un periodo para ver las secciones con notas oficiales.</p></div>
<?php elseif (empty($secciones)): ?>
    <div class="empty-state"><p>No hay notas oficiales (competencias bloqueadas) en este periodo.</p></div>
<?php else: ?>
    <div class="consulta-grid">
        <?php foreach ($secciones as $s): ?>
            <a class="consulta-card"
               href="<?= url('consulta-notas/' . $periodoId . '/seccion/' . (int) $s['seccion_id']) ?>">
                <div class="consulta-card__titulo"><?= e($s['grado_nombre'] . ' ' . $s['seccion_nombre']) ?></div>
                <div class="consulta-card__nivel"><?= e($s['nivel_nombre']) ?></div>
                <div class="consulta-card__meta">
                    <?= (int) $s['areas'] ?> área(s) · <?= (int) $s['competencias'] ?> competencia(s) oficial(es)
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
