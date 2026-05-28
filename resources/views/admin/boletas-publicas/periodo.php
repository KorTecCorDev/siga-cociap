<?php
/**
 * @var array  $periodo           { id, numero, nombre_display, anio }
 * @var array  $boletas           [{ id, matricula_id, codigo_acceso, nombre_completo,
 *                                   grado_nombre, seccion_nombre, veces_consultada,
 *                                   ultima_consulta, generada_en, novedades_count }]
 * @var array  $secciones         [{ seccion_id, seccion_nombre, grado_nombre,
 *                                   nivel_id, nivel_nombre, total_aprobables,
 *                                   total_generadas }]
 * @var int    $totalAprobadas    matrículas con ≥1 competencia bloqueada
 * @var int    $totalConNovedades boletas con competencias nuevas desde la generación
 * @var string $titulo
 */
$totalGeneradas  = count($boletas);
$_pendientes     = $totalAprobadas - $totalGeneradas;
$hayNovedades    = $totalConNovedades > 0;
$hayPendientes   = $_pendientes > 0;

// Agrupar secciones por nivel para el grid de loteo
$seccionesPorNivel = [];
foreach ($secciones ?? [] as $_sec) {
    $seccionesPorNivel[$_sec['nivel_nombre']][] = $_sec;
}
unset($_sec);
?>

<div class="page-header">
    <a href="<?= url('admin/boletas-publicas') ?>" class="btn btn--secondary btn--sm">← Períodos</a>
    <div>
        <h1 class="page-title">Boletas Públicas — <?= e($periodo['nombre_display']) ?></h1>
        <p class="page-subtitle"><?= e($periodo['anio']) ?></p>
    </div>
    <div class="btn-group">
        <?php if ($totalGeneradas > 0): ?>
        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/imprimir") ?>"
           class="btn btn--secondary btn--sm"
           target="_blank"
           title="Imprime los códigos de todo el periodo">
            🔑 Imprimir códigos
        </a>
        <?php endif; ?>

        <?php if ($hayNovedades): ?>
        <form method="POST"
              action="<?= url("admin/boletas-publicas/{$periodo['id']}/actualizar") ?>"
              style="display:inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--warning btn--sm"
                    onclick="return confirm('Actualizar <?= $totalConNovedades ?> boleta(s) con nuevas competencias bloqueadas?\n\nSolo se actualiza la fecha de generacion. El contenido de la boleta digital ya refleja los cambios.')">
                🔄 Actualizar (<?= $totalConNovedades ?>)
            </button>
        </form>
        <?php endif; ?>

        <form method="POST"
              action="<?= url("admin/boletas-publicas/{$periodo['id']}/generar") ?>"
              style="display:inline">
            <?= csrf_field() ?>
            <button type="submit"
                    class="btn btn--sm <?= $hayPendientes ? 'btn--primary' : 'btn--secondary' ?>"
                    onclick="return confirm('<?= $hayPendientes
                        ? "Generar codigos para {$_pendientes} boletas pendientes?"
                        : "No hay boletas pendientes. Ejecutar de todas formas?" ?>')">
                ⚡ <?= $hayPendientes ? "Generar pendientes ({$_pendientes})" : 'Generar boletas' ?>
            </button>
        </form>
    </div>
</div>

<div class="bp-stats-bar">
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalAprobadas ?></span>
        <span class="bp-stat__label">con notas bloqueadas</span>
    </div>
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalGeneradas ?></span>
        <span class="bp-stat__label">boletas generadas</span>
    </div>
    <div class="bp-stat <?= $_pendientes > 0 ? 'bp-stat--warn' : '' ?>">
        <span class="bp-stat__num"><?= $_pendientes ?></span>
        <span class="bp-stat__label">pendientes de código</span>
    </div>
    <div class="bp-stat <?= $hayNovedades ? 'bp-stat--update' : '' ?>">
        <span class="bp-stat__num"><?= $totalConNovedades ?></span>
        <span class="bp-stat__label">con novedades</span>
    </div>
</div>

<?php if ($hayNovedades): ?>
<div class="flash flash--warning">
    <strong><?= $totalConNovedades ?> boleta(s)</strong> tienen competencias aprobadas
    después de la última generación. La boleta digital ya las muestra correctamente,
    pero si entregaste copias impresas debes reimprimir esas boletas.
    Usa <strong>Actualizar</strong> para registrar la fecha de actualización.
</div>
<?php endif; ?>

<?php if (!empty($seccionesPorNivel)): ?>
<section class="bp-secciones">
    <header class="bp-secciones__header">
        <h2 class="bp-secciones__titulo">Impresión por sección</h2>
        <p class="bp-secciones__sub">
            Procesa las boletas en lotes más pequeños para evitar tiempos de espera largos.
        </p>
    </header>

    <?php foreach ($seccionesPorNivel as $nivel => $secs): ?>
        <h3 class="bp-secciones__nivel"><?= e($nivel) ?></h3>
        <div class="bp-secciones__grid">
            <?php foreach ($secs as $sec):
                $sid          = (int) $sec['seccion_id'];
                $aprobables   = (int) $sec['total_aprobables'];
                $generadas    = (int) $sec['total_generadas'];
                $tieneGeneradas = $generadas > 0;
            ?>
                <article class="bp-seccion-card">
                    <header class="bp-seccion-card__head">
                        <span class="bp-seccion-card__grado"><?= e($sec['grado_nombre']) ?></span>
                        <span class="bp-seccion-card__nombre">Sección <?= e($sec['seccion_nombre']) ?></span>
                    </header>

                    <dl class="bp-seccion-card__stats">
                        <div>
                            <dt>Aprobables</dt>
                            <dd><?= $aprobables ?></dd>
                        </div>
                        <div>
                            <dt>Con código</dt>
                            <dd class="<?= $tieneGeneradas ? '' : 'bp-seccion-card__stat--vacio' ?>">
                                <?= $generadas ?>
                            </dd>
                        </div>
                    </dl>

                    <div class="bp-seccion-card__acciones">
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/vista-previa?seccion_id={$sid}") ?>"
                           class="btn btn--secondary btn--sm"
                           target="_blank"
                           title="Vista previa de esta sección">
                            👁 Vista previa
                        </a>

                        <?php if ($tieneGeneradas): ?>
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/boletas-alumno?seccion_id={$sid}") ?>"
                           class="btn btn--primary btn--sm"
                           target="_blank"
                           title="Imprimir boletas de esta sección">
                            🖨 Boletas
                        </a>
                        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/imprimir?seccion_id={$sid}") ?>"
                           class="btn btn--secondary btn--sm"
                           target="_blank"
                           title="Imprimir códigos de esta sección">
                            🔑 Códigos
                        </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (empty($boletas)): ?>
<div class="card">
    <div class="card__body">
        <p class="text-muted text-center">
            No hay boletas generadas para este período aún.<br>
            Usa el botón <strong>Generar boletas</strong> para crear los códigos de acceso.
        </p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Estudiante</th>
                    <th>Sección</th>
                    <th>Código de acceso</th>
                    <th class="text-center">Consultas</th>
                    <th>Última consulta</th>
                    <th>Generada</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($boletas as $i => $b):
                $novedades = (int) $b['novedades_count'];
            ?>
                <tr class="<?= $novedades > 0 ? 'fila-novedad' : '' ?>">
                    <td class="text-sm text-muted"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= e($b['nombre_completo']) ?></strong>
                    </td>
                    <td class="text-sm">
                        <?= e($b['grado_nombre']) ?> &ldquo;<?= e($b['seccion_nombre']) ?>&rdquo;
                    </td>
                    <td>
                        <code class="bp-codigo"><?= e($b['codigo_acceso']) ?></code>
                    </td>
                    <td class="text-center">
                        <?php if ($b['veces_consultada'] > 0): ?>
                        <span class="badge badge--activo"><?= (int) $b['veces_consultada'] ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= $b['ultima_consulta']
                            ? date('d/m/Y H:i', strtotime($b['ultima_consulta']))
                            : '—' ?>
                    </td>
                    <td class="text-sm text-muted">
                        <?= date('d/m/Y H:i', strtotime($b['generada_en'])) ?>
                    </td>
                    <td class="text-center">
                        <?php if ($novedades > 0): ?>
                            <span class="badge badge--warning"
                                  title="<?= $novedades ?> competencia(s) aprobada(s) desde la generacion">
                                🔄 +<?= $novedades ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge--activo">Al día</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
