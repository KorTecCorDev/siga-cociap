<?php
/**
 * @var array      $cargas
 * @var array|null $periodo
 * @var array|null $tutoria  estado de tutoría (solo tutores del año activo)
 */
?>

<div class="page-header">
    <a href="<?= url('docente/inicio') ?>" class="btn btn--secondary btn--sm">← Volver</a>
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

<?php if (!empty($tutoria)): ?>
    <?php
    // 3 estados de la card: cerrado / disponible / en progreso
    if ($tutoria['cierre']) {
        $estadoClase = 'cerrado';
        $estadoTexto = 'Cerrado el ' . fechaLima($tutoria['cierre']['cerrado_en'], 'd/m/Y');
    } elseif ($tutoria['listo']) {
        $estadoClase = 'disponible';
        $estadoTexto = $tutoria['pendientes'] > 0
            ? 'Disponible — ' . $tutoria['pendientes'] . ' conclusión(es) pendiente(s)'
            : 'Disponible para cerrar';
    } else {
        $estadoClase = 'progreso';
        $estadoTexto = '⏳ Bloqueadas ' . $tutoria['bloqueadas'] . ' de ' . $tutoria['total'];
    }
    ?>
    <a href="<?= url('docente/tutoria') ?>"
       class="tutoria-card tutoria-card--<?= $estadoClase ?>">
        <div class="tutoria-card__icono" aria-hidden="true">★</div>
        <div class="tutoria-card__cuerpo">
            <h2 class="tutoria-card__titulo">
                Tutoría — Sección <?= e($tutoria['seccion']['nombre']) ?>
                (<?= e($tutoria['seccion']['grado_nombre']) ?> <?= e($tutoria['seccion']['nivel_nombre']) ?>)
            </h2>
            <p class="tutoria-card__desc">
                Competencias transversales TIC/GAMA: revisa promedios,
                registra conclusiones y cierra el bimestre.
            </p>
        </div>
        <span class="tutoria-card__estado"><?= e($estadoTexto) ?></span>
    </a>
<?php endif; ?>

<?php if (empty($cargas)): ?>
    <div class="empty-state">
        <p>No tienes cargas académicas asignadas para este año.</p>
        <p>Comunícate con el administrador.</p>
    </div>

<?php else: ?>

    <?php
    // Agrupar: nivel+grado → seccion_id → area_id → cargas[]
    $agrupadas = [];
    foreach ($cargas as $carga) {
        $ng = $carga['nivel_nombre'] . ' — ' . $carga['grado_nombre'];
        $agrupadas[$ng][$carga['seccion_id']][$carga['area_id']][] = $carga;
    }

    // Devuelve [clase_badge, texto_badge] según el estado de bloqueo de una carga
    $estadoBadge = function (array $c): array {
        $total        = (int) ($c['total_competencias']      ?? 0);
        $bloqueadas   = (int) ($c['competencias_bloqueadas'] ?? 0);
        $conCriterios = (int) ($c['competencias_con_criterios'] ?? 0);

        if ($total === 0 || ($bloqueadas === 0 && $conCriterios === 0)) {
            return ['badge--error',   'Sin criterios'];
        }
        if ($bloqueadas === $total) {
            return ['badge--activo',  '✓ Bloqueada'];
        }
        if ($bloqueadas > 0) {
            return ['badge--warning', 'Parcial'];
        }
        return ['badge--warning', 'Pendiente'];
    };

    // Distintivo de transversales (TIC/GAMA) de la carga: completas cuando
    // todas estan bloqueadas; en progreso si ya tienen criterios; pendiente
    // si aun no se inician. Devuelve [modificador, texto].
    $transBadge = function (array $c): array {
        $total      = (int) ($c['total_transversales']        ?? 0);
        $bloqueadas = (int) ($c['transversales_bloqueadas']   ?? 0);
        $criterios  = (int) ($c['transversales_con_criterios'] ?? 0);

        if ($total > 0 && $bloqueadas >= $total) {
            return ['completo', 'Transversales · Completas ✓'];
        }
        if ($criterios > 0) {
            return ['progreso', 'Transversales · En progreso'];
        }
        return ['pendiente', 'Transversales · Pendiente'];
    };
    ?>

    <?php foreach ($agrupadas as $grupo => $secciones): ?>
        <div class="card mb-md">
            <div class="card__header">
                <h2 class="card__title"><?= e($grupo) ?></h2>
            </div>
            <div class="card__body">
                <div class="cargas-grid">

                    <?php foreach ($secciones as $seccionId => $areas): ?>
                        <?php foreach ($areas as $areaId => $areaCargas): ?>
                            <?php
                            $primeraC = $areaCargas[0];
                            $esGrupo  = count($areaCargas) > 1;
                            ?>

                            <?php if ($esGrupo): ?>

                                <div class="carga-area">
                                    <div class="carga-area__header">
                                        <span class="carga-area__seccion">
                                            Sección <?= e($primeraC['seccion_nombre']) ?>
                                        </span>
                                        <span class="carga-area__sep">—</span>
                                        <span class="carga-area__nombre">
                                            <?= e($primeraC['area_nombre']) ?>
                                        </span>
                                    </div>
                                    <div class="carga-area__items">
                                        <?php foreach ($areaCargas as $carga): ?>
                                            <?php
                                            $total      = (int) ($carga['total_competencias'] ?? 0);
                                            $bloqueadas = (int) ($carga['competencias_bloqueadas'] ?? 0);
                                            $pct        = $total > 0 ? round($bloqueadas / $total * 100) : 0;
                                            $estado     = $pct >= 100 ? 'completo' : ($pct > 0 ? 'parcial' : 'vacio');
                                            ?>
                                            <?php [$badgeClase, $badgeTexto] = $estadoBadge($carga); ?>
                                            <a href="<?= url('docente/calificaciones/' . $carga['id']) ?>"
                                               class="carga-item <?= $periodo ? '' : 'carga-item--disabled' ?>">

                                                <div class="carga-item__nombre">
                                                    <?= e($carga['subarea_nombre'] ?? $carga['nombre_display']) ?>
                                                </div>

                                                <span class="badge <?= $badgeClase ?> carga-item__badge">
                                                    <?= $badgeTexto ?>
                                                </span>

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

                                                <?php [$trClase, $trTexto] = $transBadge($carga); ?>
                                                <span class="carga-transversal carga-transversal--<?= $trClase ?>">
                                                    <?= $trTexto ?>
                                                </span>

                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php else: ?>

                                <?php
                                $carga      = $areaCargas[0];
                                $total      = (int) ($carga['total_competencias'] ?? 0);
                                $bloqueadas = (int) ($carga['competencias_bloqueadas'] ?? 0);
                                $pct        = $total > 0 ? round($bloqueadas / $total * 100) : 0;
                                $estado     = $pct >= 100 ? 'completo' : ($pct > 0 ? 'parcial' : 'vacio');
                                [$badgeClase, $badgeTexto] = $estadoBadge($carga);
                                ?>
                                <a href="<?= url('docente/calificaciones/' . $carga['id']) ?>"
                                   class="carga-item <?= $periodo ? '' : 'carga-item--disabled' ?>">

                                    <div class="carga-item__seccion">
                                        Sección <?= e($carga['seccion_nombre']) ?>
                                    </div>

                                    <div class="carga-item__nombre">
                                        <?= e($carga['nombre_display']) ?>
                                    </div>

                                    <span class="badge <?= $badgeClase ?> carga-item__badge">
                                        <?= $badgeTexto ?>
                                    </span>

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

                                    <?php [$trClase, $trTexto] = $transBadge($carga); ?>
                                    <span class="carga-transversal carga-transversal--<?= $trClase ?>">
                                        <?= $trTexto ?>
                                    </span>

                                </a>

                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
