<?php
/**
 * @var array  $periodo         { id, numero, nombre_display, anio }
 * @var array  $boletas         [{ id, matricula_id, codigo_acceso, nombre_completo,
 *                                 grado_nombre, seccion_nombre, veces_consultada,
 *                                 ultima_consulta, generada_en }]
 * @var int    $totalAprobadas  matrículas con ≥1 competencia bloqueada
 * @var string $titulo
 */
$totalGeneradas = count($boletas);
?>

<div class="page-header">
    <a href="<?= url('admin/boletas-publicas') ?>" class="btn btn--secondary btn--sm">← Períodos</a>
    <div>
        <h1 class="page-title">Boletas Públicas — <?= e($periodo['nombre_display']) ?></h1>
        <p class="page-subtitle"><?= e($periodo['anio']) ?></p>
    </div>
    <div class="btn-group">
        <?php if ($totalGeneradas > 0): ?>
        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/boletas-alumno") ?>"
           class="btn btn--secondary btn--sm"
           target="_blank">
            🖨 Imprimir boletas
        </a>
        <a href="<?= url("admin/boletas-publicas/{$periodo['id']}/imprimir") ?>"
           class="btn btn--secondary btn--sm"
           target="_blank">
            🔑 Imprimir códigos
        </a>
        <?php endif; ?>
        <form method="POST"
              action="<?= url("admin/boletas-publicas/{$periodo['id']}/generar") ?>"
              style="display:inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--primary btn--sm"
                    onclick="return confirm('¿Generar códigos para todas las boletas listas?')">
                ⚡ Generar boletas
            </button>
        </form>
    </div>
</div>

<?php if ($flash_success): ?>
<div class="alert alert--success"><?= e($flash_success) ?></div>
<?php endif; ?>
<?php if ($flash_error): ?>
<div class="alert alert--error"><?= e($flash_error) ?></div>
<?php endif; ?>

<div class="bp-stats-bar">
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalAprobadas ?></span>
        <span class="bp-stat__label">con notas bloqueadas</span>
    </div>
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalGeneradas ?></span>
        <span class="bp-stat__label">boletas generadas</span>
    </div>
    <div class="bp-stat">
        <span class="bp-stat__num"><?= $totalAprobadas - $totalGeneradas ?></span>
        <span class="bp-stat__label">pendientes</span>
    </div>
</div>

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
                </tr>
            </thead>
            <tbody>
            <?php foreach ($boletas as $i => $b): ?>
                <tr>
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
                        <span class="badge badge--success"><?= (int) $b['veces_consultada'] ?></span>
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
                        <?= date('d/m/Y', strtotime($b['generada_en'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
