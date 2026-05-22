<?php /** @var array $cargas @var array|null $periodo */ ?>

<div class="page-header">
    <h1 class="page-title">Mis cargas académicas</h1>
    <?php if ($periodo): ?>
        <span class="badge badge--activo">
            <?= e($periodo['nombre_display'] ?? 'Periodo activo') ?>
            — <?= e($periodo['anio']) ?>
        </span>
    <?php else: ?>
        <span class="badge badge--warning">Sin periodo activo</span>
    <?php endif; ?>
</div>

<?php if (empty($cargas)): ?>
    <div class="empty-state">
        <p>No tienes cargas académicas asignadas para este año.</p>
        <p>Comunícate con el administrador.</p>
    </div>

<?php else: ?>

    <?php
    // Agrupar cargas por nivel y grado
    $agrupadas = [];
    foreach ($cargas as $carga) {
        $key = $carga['nivel_nombre'] . ' — ' . $carga['grado_nombre'];
        $agrupadas[$key][] = $carga;
    }
    ?>

    <?php foreach ($agrupadas as $grupo => $items): ?>
        <div class="card mb-md">
            <div class="card__header">
                <h2 class="card__title"><?= e($grupo) ?></h2>
            </div>
            <div class="card__body">
                <div class="cargas-grid">
                    <?php foreach ($items as $carga): ?>
                        <?php
                        $total      = (int) ($carga['total_competencias'] ?? 0);
                        $bloqueadas = (int) ($carga['competencias_bloqueadas'] ?? 0);
                        $pct        = $total > 0 ? round($bloqueadas / $total * 100) : 0;
                        $estado     = $pct >= 100 ? 'completo' : ($pct > 0 ? 'parcial' : 'vacio');
                        ?>
                        <a href="<?= url('docente/calificaciones/' . $carga['id']) ?>"
                           class="carga-item <?= $periodo ? '' : 'carga-item--disabled' ?>">

                            <div class="carga-item__seccion">
                                Sección <?= e($carga['seccion_nombre']) ?>
                            </div>

                            <div class="carga-item__nombre">
                                <?= e($carga['nombre_display']) ?>
                            </div>

                            <?php if ($carga['es_unidocente']): ?>
                                <span class="carga-item__tag">Unidocente</span>
                            <?php endif; ?>

                            <div class="carga-item__horas">
                                <?= e($carga['horas_semanales']) ?> hrs/semana
                            </div>

                            <div class="carga-progreso">
                                <div class="carga-progreso__track">
                                    <div class="carga-progreso__fill carga-progreso__fill--<?= $estado ?>"
                                         style="--pct: <?= $pct ?>%"></div>
                                </div>
                                <div class="carga-progreso__meta">
                                    <span><?= $bloqueadas ?>/<?= $total ?> aprobadas</span>
                                    <span class="carga-progreso__valor carga-progreso__valor--<?= $estado ?>">
                                        <?= $pct ?>%
                                    </span>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>