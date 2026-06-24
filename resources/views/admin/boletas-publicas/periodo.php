<?php
/**
 * @var array  $periodo          { id, numero, nombre_display, anio }
 * @var array  $estudiantes      [{ matricula_id, nombre_completo, seccion_id,
 *                                  seccion_nombre, grado_nombre, nivel_nombre,
 *                                  token_acceso, token_consultas, token_ultima_consulta }]
 * @var array  $secciones        [{ seccion_id, seccion_nombre, grado_nombre,
 *                                  grado_numero, nivel_id, nivel_nombre,
 *                                  total_aprobables, total_generadas }]
 * @var int    $totalBoletas     matrículas con boleta oficial (≥1 competencia bloqueada)
 * @var int    $totalConsultadas boletas con al menos una visita por token
 * @var string $titulo
 */

// Agrupar secciones por nivel para el grid de loteo
$seccionesPorNivel = [];
foreach ($secciones ?? [] as $_sec) {
    $seccionesPorNivel[$_sec['nivel_nombre']][] = $_sec;
}
unset($_sec);

// Agrupar estudiantes por sección (clave = seccion_id, única) para los acordeones.
$estudiantesPorSeccion = [];
foreach ($estudiantes as $_e) {
    $_sid = (int) $_e['seccion_id'];
    if (!isset($estudiantesPorSeccion[$_sid])) {
        $estudiantesPorSeccion[$_sid] = [
            'label'       => $_e['nivel_nombre'] . ' · ' . $_e['grado_nombre'] . ' "' . $_e['seccion_nombre'] . '"',
            'estudiantes' => [],
        ];
    }
    $estudiantesPorSeccion[$_sid]['estudiantes'][] = $_e;
}
unset($_e, $_sid);
?>

<div class="page-header">
    <a href="<?= url('admin/boletas-publicas') ?>" class="btn btn--secondary btn--sm">← Períodos</a>
    <div>
        <h1 class="page-title">Boletas — <?= e($periodo['nombre_display']) ?></h1>
        <p class="page-subtitle"><?= e($periodo['anio']) ?> · documento oficial por código QR (token)</p>
    </div>
</div>

<div class="bp-stats-bar">
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalBoletas ?></span>
        <span class="bp-stat__label">boletas oficiales</span>
    </div>
    <div class="bp-stat <?= $totalConsultadas > 0 ? 'bp-stat--update' : '' ?>">
        <span class="bp-stat__num"><?= $totalConsultadas ?></span>
        <span class="bp-stat__label">consultadas por QR</span>
    </div>
</div>

<?php if (!empty($seccionesPorNivel)): ?>
<section class="bp-secciones">
    <header class="bp-secciones__header">
        <h2 class="bp-secciones__titulo">Impresión por sección</h2>
        <p class="bp-secciones__sub">
            Procesa las boletas en lotes más pequeños para evitar tiempos de espera largos.
            Cada boleta lleva su QR permanente; el padre lo escanea para ver la versión digital.
        </p>
    </header>

    <?php foreach ($seccionesPorNivel as $nivel => $secs): ?>
        <h3 class="bp-secciones__nivel"><?= e($nivel) ?></h3>
        <div class="bp-secciones__grid">
            <?php foreach ($secs as $sec):
                $sid        = (int) $sec['seccion_id'];
                $aprobables = (int) $sec['total_aprobables'];
                $hayBoletas = $aprobables > 0;
            ?>
                <article class="bp-seccion-card">
                    <header class="bp-seccion-card__head">
                        <span class="bp-seccion-card__grado"><?= e($sec['grado_nombre']) ?></span>
                        <span class="bp-seccion-card__nombre">Sección <?= e($sec['seccion_nombre']) ?></span>
                    </header>

                    <dl class="bp-seccion-card__stats">
                        <div>
                            <dt>Boletas oficiales</dt>
                            <dd><?= $aprobables ?></dd>
                        </div>
                    </dl>

                    <div class="bp-seccion-card__acciones">
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/vista-previa?seccion_id={$sid}") ?>"
                           class="btn btn--secondary btn--sm"
                           target="_blank"
                           title="Vista previa de esta sección">
                            👁 Vista previa
                        </a>

                        <?php if ($hayBoletas): ?>
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/boletas-alumno?seccion_id={$sid}") ?>"
                           class="btn btn--primary btn--sm"
                           target="_blank"
                           title="Imprimir boletas de esta sección">
                            🖨 Boletas
                        </a>
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/archivar?seccion_id={$sid}") ?>"
                           class="btn btn--secondary btn--sm"
                           target="_blank"
                           title="Descargar PDFs de esta sección en un ZIP">
                            🗂 Archivar
                        </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (empty($estudiantes)): ?>
<div class="card">
    <div class="card__body">
        <p class="text-muted text-center">
            No hay boletas oficiales para este período aún.<br>
            Una boleta aparece cuando el docente aprueba (bloquea) al menos una competencia.
        </p>
    </div>
</div>
<?php else: ?>

<?php foreach ($estudiantesPorSeccion as $grupo):
    $estudiantesSeccion = $grupo['estudiantes'];
    $totalSec     = count($estudiantesSeccion);
    $consultasSec = count(array_filter($estudiantesSeccion, fn($e) => (int) $e['token_consultas'] > 0));
?>
<details class="bp-acordeon">
    <summary class="bp-acordeon__header">
        <span class="bp-acordeon__chevron" aria-hidden="true"></span>
        <span class="bp-acordeon__titulo"><?= e($grupo['label']) ?></span>
        <div class="bp-acordeon__meta">
            <span class="bp-acordeon__chip"><?= $totalSec ?> estudiantes</span>
            <?php if ($consultasSec > 0): ?>
            <span class="bp-acordeon__chip bp-acordeon__chip--ok"><?= $consultasSec ?> consultadas</span>
            <?php endif; ?>
        </div>
    </summary>

    <div class="bp-acordeon__cuerpo">
        <div class="tabla-notas-wrapper">
            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Boleta digital</th>
                        <th class="text-center">Consultas</th>
                        <th>Última consulta</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($estudiantesSeccion as $i => $e): ?>
                    <tr>
                        <td class="text-sm text-muted"><?= $i + 1 ?></td>
                        <td>
                            <strong><?= e($e['nombre_completo']) ?></strong>
                        </td>
                        <td>
                            <?php if (!empty($e['token_acceso'])): ?>
                            <a href="<?= url("boleta/digital/{$e['token_acceso']}") ?>"
                               target="_blank"
                               class="bp-enlace-digital"
                               title="Abrir boleta digital">
                                Ver boleta ↗
                            </a>
                            <?php else: ?>
                            <span class="text-muted">Sin token</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int) $e['token_consultas'] > 0): ?>
                            <span class="badge badge--activo"><?= (int) $e['token_consultas'] ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted">
                            <?= fechaLima($e['token_ultima_consulta']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</details>
<?php endforeach; ?>

<?php endif; ?>
