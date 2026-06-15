<?php
/**
 * Vista: Centro de Control Operativo — detección de inconsistencias.
 *
 * @var array       $periodos          [{id, nombre_display, anio, ...}]
 * @var array|null  $periodo           periodo seleccionado
 * @var array       $chequeos          ['empates'=>{titulo,severidad,accion,items[],...}, ...]
 * @var int         $totalIncidencias
 */
$badgeSeveridad = static fn(string $sev): string =>
    $sev === 'critico' ? 'badge--error' : 'badge--warning';
?>

<div class="page-header">
    <div>
        <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
        <h1 class="page-title">Centro de Control</h1>
        <p class="page-subtitle">Inconsistencias operativas pendientes de corregir</p>
    </div>
    <?php if (!empty($periodos)): ?>
        <form method="GET" action="<?= url('admin/control') ?>" class="control-selector">
            <label for="periodo_id" class="form-label">Periodo</label>
            <select name="periodo_id" id="periodo_id" class="form-select"
                    onchange="this.form.submit()">
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= ($periodo && (int) $periodo['id'] === (int) $p['id']) ? 'selected' : '' ?>>
                        <?= e($p['nombre_display'] . ' — ' . $p['anio']) ?>
                        <?= $p['estado'] === 'activo' ? '(activo)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<?php if (!$periodo): ?>
    <div class="empty-state">
        <p>No hay periodos disponibles para analizar.</p>
    </div>
<?php else: ?>

    <?php if ($totalIncidencias === 0): ?>
        <div class="control-resumen control-resumen--ok">
            <span class="control-resumen__icono">✓</span>
            <div>
                <strong>Todo en orden.</strong>
                No se detectaron inconsistencias para <?= e($periodo['nombre_display']) ?>.
            </div>
        </div>
    <?php else: ?>
        <div class="control-resumen control-resumen--alerta">
            <span class="control-resumen__icono">⚠</span>
            <div>
                <strong><?= $totalIncidencias ?></strong>
                <?= $totalIncidencias === 1 ? 'inconsistencia detectada' : 'inconsistencias detectadas' ?>
                en <?= e($periodo['nombre_display']) ?>. Revisá cada bloque y corregí en su módulo.
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($chequeos as $clave => $c): $n = count($c['items']); ?>
        <div class="card mb-lg">
            <div class="card__header card__header--between">
                <h2 class="card__title"><?= e($c['titulo']) ?></h2>
                <?php if ($n === 0): ?>
                    <span class="badge badge--activo">✓ En orden</span>
                <?php else: ?>
                    <span class="badge <?= $badgeSeveridad($c['severidad']) ?>">
                        <?= $n ?> <?= $n === 1 ? 'caso' : 'casos' ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($n === 0): ?>
                <div class="card__body">
                    <p class="text-muted">Sin pendientes en este chequeo.</p>
                </div>
            <?php else: ?>
                <div class="tabla-responsive">
                <table class="tabla-ranking">
                    <?php if ($clave === 'empates'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Grupos en empate</th><th class="text-center">Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= (int) $it['n_grupos'] ?></td>
                                <td class="text-center">
                                    <a class="btn btn--primary btn--sm"
                                       href="<?= url('director/orden-merito/' . (int) $periodo['id'] . '/desempate/' . (int) $it['grado_id']) ?>">
                                        Resolver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($clave === 'competencias'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Sección</th><th class="text-center">Competencias sin bloquear</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= e($it['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) $it['n_competencias'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php elseif ($clave === 'tutores'): ?>
                        <thead><tr><th>Nivel</th><th>Grado</th><th class="text-center">Sección</th><th class="text-center">Matriculados</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['nivel_nombre']) ?></td>
                                <td><?= e($it['grado_nombre']) ?></td>
                                <td class="text-center"><?= e($it['seccion_nombre']) ?></td>
                                <td class="text-center"><?= (int) ($it['total_matriculados'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    <?php else: /* matriculas */ ?>
                        <thead><tr><th>Estudiante</th><th>Grado / Sección</th><th class="text-center">Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($c['items'] as $it): ?>
                            <tr>
                                <td><?= e($it['apellido_paterno'] . ' ' . $it['apellido_materno'] . ', ' . $it['nombres']) ?></td>
                                <td><?= e($it['grado_nombre'] . ' ' . $it['seccion_nombre']) ?></td>
                                <td class="text-center"><span class="badge badge--warning"><?= e($it['estado']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
                </div>

                <?php if (!empty($c['accion_url'])): ?>
                    <div class="card__footer">
                        <a class="btn btn--secondary btn--sm" href="<?= e($c['accion_url']) ?>">
                            <?= e($c['accion']) ?> →
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
