<?php
/**
 * Vista: Cuadros estadísticos — tablero de indicadores del bimestre (24/08/2026).
 *
 * Reúne los cinco registros en una pantalla. Cada cifra viene ya calculada por
 * el modelo que la posee; esta vista solo pinta. Es de SOLO LECTURA.
 *
 * @var array      $periodos
 * @var array|null $periodo
 * @var array|null $bloques  { matricula, calificaciones, merito, empates,
 *                             reaperturas, conducta, asistencia }
 */
$pct = static fn(int $parte, int $total): int => $total > 0 ? (int) round($parte / $total * 100) : 0;
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&larr; Dashboard</a>
    <div>
        <h1 class="page-title">Cuadros estadísticos</h1>
        <p class="page-subtitle">
            Indicadores de matrícula, calificaciones, orden de mérito, conducta y asistencia.
            Solo lectura.
        </p>
    </div>
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('admin/cuadros') ?>" class="form-inline">
            <label for="periodo_id" class="form-label">Bimestre</label>
            <select name="periodo_id" id="periodo_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $periodo && (int) $p['id'] === (int) $periodo['id'] ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> <?= e($p['anio']) ?>
                        (<?= e($p['estado']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn--primary btn--sm">Ver</button></noscript>
        </form>
    </div>
</div>

<?php if (!$periodo || !$bloques): ?>
    <div class="empty-state"><p>No hay bimestres disponibles.</p></div>
<?php else: ?>

<?php // ── 1. MATRÍCULA ────────────────────────────────────────────── ?>
<?php $k = $bloques['matricula']['kpis']; ?>
<h2 class="dash-grupo__titulo">Matrícula del año</h2>
<div class="cuadros-kpis mb-lg">
    <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['aprobadas'] ?></span><span class="cuadros-kpi__t">Aprobadas</span></div>
    <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['pendientes'] ?></span><span class="cuadros-kpi__t">Pendientes</span></div>
    <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['desactivadas'] ?></span><span class="cuadros-kpi__t">Desactivadas</span></div>
    <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= (int) $k['secciones'] ?></span><span class="cuadros-kpi__t">Secciones</span></div>
    <div class="cuadros-kpi"><span class="cuadros-kpi__n"><?= e((string) $k['promedio_seccion']) ?></span><span class="cuadros-kpi__t">Promedio por sección</span></div>
</div>
<p class="text-sm text-muted mb-lg">
    Detalle por grado, tipo y género en <a href="<?= url('matriculas/resumen') ?>">Resumen de matrículas</a>.
</p>

<?php // ── 2. CALIFICACIONES ───────────────────────────────────────── ?>
<h2 class="dash-grupo__titulo">Calificaciones del bimestre</h2>
<?php if (empty($bloques['calificaciones']['niveles'])): ?>
    <div class="empty-state"><p>Este bimestre todavía no tiene calificaciones registradas.</p></div>
<?php else: ?>
<div class="tabla-responsive mb-lg">
    <table class="tabla-resumen">
        <thead>
            <tr>
                <th>Nivel</th>
                <th class="text-center">Calificaciones</th>
                <th class="text-center">AD</th>
                <th class="text-center">A</th>
                <th class="text-center">B</th>
                <th class="text-center">C</th>
                <th class="text-center">% en logro</th>
                <th class="text-center">Estudiantes</th>
                <th class="text-center">En riesgo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloques['calificaciones']['niveles'] as $n): ?>
                <tr>
                    <td><strong><?= e($n['nivel_nombre']) ?></strong></td>
                    <td class="text-center"><?= (int) $n['total_calif'] ?></td>
                    <td class="text-center"><?= (int) $n['ad'] ?></td>
                    <td class="text-center"><?= (int) $n['a'] ?></td>
                    <td class="text-center"><?= (int) $n['b'] ?></td>
                    <td class="text-center"><?= (int) $n['c'] ?></td>
                    <td class="text-center"><strong><?= (int) $n['pct_logro'] ?>%</strong></td>
                    <td class="text-center"><?= (int) $n['total_estudiantes'] ?></td>
                    <td class="text-center<?= (int) $n['en_riesgo'] > 0 ? ' text-danger' : '' ?>"><?= (int) $n['en_riesgo'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── 3. ORDEN DE MÉRITO ──────────────────────────────────────── ?>
<h2 class="dash-grupo__titulo">Orden de mérito</h2>
<?php if (!empty($bloques['empates'])): ?>
    <p class="text-danger">
        ⚠ Hay <?= count($bloques['empates']) ?> grado(s) con empates sin resolver: el orden no es oficializable
        hasta resolverlos en <a href="<?= url('director/orden-merito') ?>">Orden de mérito</a>.
    </p>
<?php endif; ?>
<?php if (empty($bloques['merito']['por_grado'])): ?>
    <div class="empty-state"><p>Este bimestre todavía no tiene ranking.</p></div>
<?php else: ?>
<div class="tabla-responsive mb-lg">
    <table class="tabla-resumen">
        <thead>
            <tr>
                <th>Grado</th>
                <th>Nivel</th>
                <th class="text-center">Estudiantes en el ranking</th>
                <th>Primer puesto</th>
                <th class="text-center">Promedio</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bloques['merito']['por_grado'] as $g): ?>
                <tr>
                    <td><strong><?= e($g['grado']['nombre_display']) ?></strong></td>
                    <td><?= e($g['grado']['nivel_nombre']) ?></td>
                    <td class="text-center"><?= (int) $g['total'] ?></td>
                    <td><?= e($g['mejor']['nombre_completo'] ?? '—') ?></td>
                    <td class="text-center"><?= e((string) ($g['mejor']['promedio_general'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php // ── 4. CONDUCTA y 5. ASISTENCIA ─────────────────────────────── ?>
<?php $c = $bloques['conducta']; $a = $bloques['asistencia']; ?>
<h2 class="dash-grupo__titulo">Conducta y asistencia</h2>
<div class="cuadros-kpis mb-md">
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= (int) $c['cerradas'] ?>/<?= (int) $c['secciones'] ?></span>
        <span class="cuadros-kpi__t">Conducta cerrada</span>
    </div>
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= (int) $c['pend_tutor'] ?></span>
        <span class="cuadros-kpi__t">Esperan al tutor</span>
    </div>
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= (int) $c['pend_auxiliar'] ?></span>
        <span class="cuadros-kpi__t">Esperan al auxiliar</span>
    </div>
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= $pct((int) $c['calificados'], (int) $c['esperados']) ?>%</span>
        <span class="cuadros-kpi__t">Estudiantes calificados en conducta</span>
    </div>
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= (int) $a['completas'] ?>/<?= (int) $a['secciones'] ?></span>
        <span class="cuadros-kpi__t">Asistencia completa por sección</span>
    </div>
    <div class="cuadros-kpi">
        <span class="cuadros-kpi__n"><?= $pct((int) $a['registrados'], (int) $a['esperados']) ?>%</span>
        <span class="cuadros-kpi__t">Cobertura del registro de asistencia</span>
    </div>
</div>
<p class="text-sm text-muted mb-lg">
    El detalle por sección está en <a href="<?= url('consulta-notas?periodo_id=' . (int) $periodo['id']) ?>">Consulta de notas</a>.
</p>

<?php // ── Reaperturas del bimestre ────────────────────────────────── ?>
<?php if (!empty($bloques['reaperturas'])): ?>
    <h2 class="dash-grupo__titulo">Reaperturas del bimestre</h2>
    <p class="text-sm text-muted">
        <?= count($bloques['reaperturas']) ?> reapertura(s) registrada(s). Quedan auditadas con su motivo.
    </p>
<?php endif; ?>

<?php endif; ?>
