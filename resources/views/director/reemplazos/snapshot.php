<?php
/**
 * Reporte de auditoría de un reemplazo: foto solo-lectura del trabajo del
 * docente saliente al momento del reemplazo (todos los bimestres).
 * @var array $evento         datos del evento + saliente/entrante/motivo
 * @var array $carga
 * @var array $snapshot       ['criterios','calificaciones','bloqueos', ...]
 * @var array $compNombres    [competencia_id => 'CODIGO — nombre']
 * @var array $alumnoNombres  [matricula_id => 'APELLIDOS, Nombres']
 * @var array $periodoNombres [periodo_id => 'nombre_display']
 */

// Agrupa los arreglos planos del snapshot en periodo -> competencia.
$porPeriodo = [];
foreach ($snapshot['criterios'] ?? [] as $cr) {
    $porPeriodo[(int) $cr['periodo_id']][(int) $cr['competencia_id']]['criterios'][] = $cr;
}
foreach ($snapshot['calificaciones'] ?? [] as $c) {
    $porPeriodo[(int) $c['periodo_id']][(int) $c['competencia_id']]['calificaciones'][] = $c;
}
foreach ($snapshot['bloqueos'] ?? [] as $b) {
    $porPeriodo[(int) $b['periodo_id']][(int) $b['competencia_id']]['bloqueo'] = $b;
}
ksort($porPeriodo);

$nombreComp   = fn(int $id): string => $compNombres[$id]   ?? ('Competencia #' . $id);
$nombreAlumno = fn(int $id): string => $alumnoNombres[$id] ?? ('Matrícula #' . $id);
$nombrePeriodo = fn(int $id): string => $periodoNombres[$id] ?? ('Bimestre #' . $id);
?>

<div class="page-header">
    <a href="<?= $carga ? url('director/cargas/' . (int) $carga['id'] . '/reemplazos') : url('director/cargas') ?>"
       class="btn btn--secondary btn--sm">← Reemplazos</a>
    <div>
        <h1 class="page-title">Auditoría de reemplazo</h1>
        <p class="page-subtitle">
            <?php if ($carga): ?>
                <?= e($carga['area_nombre']) ?><?= $carga['subarea_nombre'] ? ' · ' . e($carga['subarea_nombre']) : '' ?>
                — <?= e($carga['grado_nombre']) ?> <?= e($carga['seccion_nombre']) ?>
            <?php endif; ?>
        </p>
    </div>
    <span class="badge badge--activo">Solo lectura</span>
</div>

<div class="card mb-md">
    <div class="card__body">
        <div class="info-grid">
            <div><span class="text-muted text-sm">Docente saliente</span><div><?= e($evento['saliente_nombre']) ?></div></div>
            <div><span class="text-muted text-sm">Docente entrante</span><div><?= e($evento['entrante_nombre']) ?></div></div>
            <div><span class="text-muted text-sm">Bimestre activo</span><div><?= e($evento['periodo_nombre'] ?? '—') ?></div></div>
            <div><span class="text-muted text-sm">Reasignó</span><div><?= e($evento['reasignado_por_nombre']) ?></div></div>
            <div><span class="text-muted text-sm">Fecha</span><div><?= e(date('d/m/Y H:i', strtotime($evento['reasignado_en']))) ?></div></div>
        </div>
        <p><span class="text-muted text-sm">Motivo</span><br><?= e($evento['motivo']) ?></p>
    </div>
</div>

<div class="flash flash--info">
    Esta es la versión congelada del trabajo del saliente al momento del reemplazo.
    Las notas vivas pueden haber cambiado desde entonces (el entrante continúa la carga).
</div>

<?php if (empty($porPeriodo)): ?>
    <div class="card"><div class="card__body"><div class="empty-state">
        <p>El docente saliente no había registrado criterios ni notas en esta carga.</p>
    </div></div></div>
<?php else: ?>
    <?php foreach ($porPeriodo as $pid => $competencias): ?>
        <h2 class="reemplazo-periodo-titulo"><?= e($nombrePeriodo($pid)) ?></h2>
        <?php ksort($competencias); foreach ($competencias as $cid => $bloque): ?>
            <?php $bloqueo = $bloque['bloqueo'] ?? null; ?>
            <div class="card mb-md">
                <div class="card__header">
                    <h3 class="card__title"><?= e($nombreComp($cid)) ?></h3>
                    <?php if ($bloqueo): ?>
                        <span class="badge badge--activo">
                            Aprobada (<?= e($bloqueo['origen']) ?>) ·
                            <?= e(date('d/m/Y', strtotime($bloqueo['bloqueado_en']))) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge badge--warning">No aprobada</span>
                    <?php endif; ?>
                </div>
                <div class="card__body">

                    <?php $cals = $bloque['calificaciones'] ?? []; ?>
                    <?php if (!empty($cals)): ?>
                        <p class="form-section-title">Promedios y conclusiones</p>
                        <div class="tabla-notas-wrapper">
                            <table class="tabla-notas">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th class="text-center">Nota</th>
                                        <th class="text-center">Literal</th>
                                        <th>Conclusión</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cals as $cal):
                                        $nota = $cal['nota_numerica'] === null ? null : (int) $cal['nota_numerica'];
                                    ?>
                                        <tr>
                                            <td><?= e($nombreAlumno((int) $cal['matricula_id'])) ?></td>
                                            <td class="text-center"><?= $nota === null ? '—' : fmt_nota($nota) ?></td>
                                            <td class="text-center"><?= $nota === null ? '—' : e(nota_a_literal($nota)) ?></td>
                                            <td class="text-sm"><?= e($cal['conclusion_descriptiva'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php $criterios = $bloque['criterios'] ?? []; ?>
                    <?php if (!empty($criterios)): ?>
                        <p class="form-section-title">Criterios y notas</p>
                        <?php foreach ($criterios as $cr): ?>
                            <div class="mb-md">
                                <strong><?= e($cr['nombre']) ?></strong>
                                <?php if (!empty($cr['eliminado_en'])): ?>
                                    <span class="badge badge--error">Eliminado</span>
                                <?php elseif (!empty($cr['confirmado_en'])): ?>
                                    <span class="badge badge--activo">Confirmado</span>
                                <?php endif; ?>
                                <?php if (!empty($cr['descripcion'])): ?>
                                    <div class="text-sm text-muted"><?= e($cr['descripcion']) ?></div>
                                <?php endif; ?>
                                <?php $notas = $cr['notas'] ?? []; ?>
                                <?php if (!empty($notas)): ?>
                                    <div class="tabla-notas-wrapper">
                                        <table class="tabla-notas">
                                            <thead>
                                                <tr><th>Alumno</th><th class="text-center">Nota</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notas as $n): ?>
                                                    <tr>
                                                        <td><?= e($nombreAlumno((int) $n['matricula_id'])) ?></td>
                                                        <td class="text-center">
                                                            <?= $n['nota'] === null ? '—' : fmt_nota((int) $n['nota']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-muted">Sin notas registradas.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (empty($cals) && empty($criterios)): ?>
                        <p class="text-muted">Sin criterios ni notas en esta competencia.</p>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
