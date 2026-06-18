<?php
/**
 * @var array      $cargas
 * @var array|null $periodo
 * @var bool       $tieneAula  es tutor(a) de aula (unidocente de alguna seccion)
 * @var string|null $aula      etiqueta del aula (ej. "1° A") cuando tiene aula
 */
?>

<div class="page-header">
    <a href="<?= url('docente/inicio') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <h1 class="page-title">Mis cargas académicas</h1>
    <?php if ($tieneAula): ?>
        <span class="badge badge--aula">
            <?= e(rol_aula(auth()['sexo'] ?? null)) ?> — <?= e($aula) ?> Primaria
        </span>
    <?php endif; ?>
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
    // Avance GLOBAL de la carga = competencias propias del area + transversales
    // TIC/GAMA que el docente registra en su propia carga. Devuelve
    // [bloqueadas, total, pct, estado]. En una carga de tipo transversal
    // (modelo antiguo del tutor) sus competencias YA son las transversales, por
    // lo que NO se vuelven a sumar (evitaria un doble conteo).
    $avanceCarga = function (array $c): array {
        $esTransversal = ($c['area_tipo'] ?? '') === 'transversal';
        $total      = (int) ($c['total_competencias']      ?? 0);
        $bloqueadas = (int) ($c['competencias_bloqueadas'] ?? 0);
        if (!$esTransversal) {
            $total      += (int) ($c['total_transversales']      ?? 0);
            $bloqueadas += (int) ($c['transversales_bloqueadas'] ?? 0);
        }
        $pct    = $total > 0 ? (int) round($bloqueadas / $total * 100) : 0;
        $estado = $pct >= 100 ? 'completo' : ($pct > 0 ? 'parcial' : 'vacio');
        return [$bloqueadas, $total, $pct, $estado];
    };

    // Sub-avance SOLO de transversales: [bloqueadas, total, estado] o NULL.
    // NULL en cargas transversales (la barra principal ya las representa) o si
    // el nivel no define transversales.
    $transAvance = function (array $c): ?array {
        if (($c['area_tipo'] ?? '') === 'transversal') {
            return null;
        }
        $total = (int) ($c['total_transversales'] ?? 0);
        if ($total === 0) {
            return null;
        }
        $bloqueadas = (int) ($c['transversales_bloqueadas']   ?? 0);
        $criterios  = (int) ($c['transversales_con_criterios'] ?? 0);
        $estado     = $bloqueadas >= $total
            ? 'completo'
            : (($bloqueadas > 0 || $criterios > 0) ? 'progreso' : 'pendiente');
        return [$bloqueadas, $total, $estado];
    };

    // Devuelve [clase_badge, texto_badge] según el estado GLOBAL de la carga
    // (propias + transversales). "Bloqueada" solo cuando TODO esta aprobado.
    $estadoBadge = function (array $c) use ($avanceCarga): array {
        [$bloqueadas, $total] = $avanceCarga($c);
        $esTransversal = ($c['area_tipo'] ?? '') === 'transversal';
        $conCriterios  = (int) ($c['competencias_con_criterios'] ?? 0)
            + ($esTransversal ? 0 : (int) ($c['transversales_con_criterios'] ?? 0));

        if ($total === 0 || ($bloqueadas === 0 && $conCriterios === 0)) {
            return ['badge--error',   'Sin criterios'];
        }
        if ($bloqueadas === $total) {
            return ['badge--activo',  'Bloqueada <span class="badge__icono" aria-hidden="true"></span>'];
        }
        if ($bloqueadas > 0) {
            return ['badge--warning', 'Parcial'];
        }
        return ['badge--warning', 'Pendiente'];
    };

    // Pill de transversales con contador "X/Y" (ej. 1/2, 2/2). Devuelve
    // [modificador, texto] o NULL si no aplica (carga transversal / sin TIC-GAMA).
    $transBadge = function (array $c) use ($transAvance): ?array {
        $av = $transAvance($c);
        if ($av === null) {
            return null;
        }
        [$bloqueadas, $total, $estado] = $av;
        $icono = $estado === 'completo'
            ? ' <span class="carga-transversal__icono" aria-hidden="true"></span>'
            : '';
        return [$estado, 'Transversales ' . $bloqueadas . '/' . $total . $icono];
    };

    // Agrupar: nivel+grado → seccion_id → area_id → cargas[]
    $agrupadas = [];
    foreach ($cargas as $carga) {
        $ng = $carga['nivel_nombre'] . ' — ' . $carga['grado_nombre'];
        $agrupadas[$ng][$carga['seccion_id']][$carga['area_id']][] = $carga;
    }

    // ¿El grupo es el AULA del docente? (alguna de sus cargas es de una seccion
    // es_unidocente). Sirve para destacar su encabezado como "Mi aula".
    $grupoEsAula = function (array $secciones): bool {
        foreach ($secciones as $areas) {
            foreach ($areas as $areaCargas) {
                foreach ($areaCargas as $c) {
                    if (!empty($c['es_unidocente'])) {
                        return true;
                    }
                }
            }
        }
        return false;
    };

    // Primera carga del grupo (para leer grado/seccion/nivel del encabezado).
    $primeraDelGrupo = function (array $secciones): ?array {
        foreach ($secciones as $areas) {
            foreach ($areas as $areaCargas) {
                return $areaCargas[0];
            }
        }
        return null;
    };

    // El AULA va primero; el resto (caso mixto: cargas de especialista en otras
    // secciones) conserva su orden natural por nivel/grado.
    $aulaGrupos  = [];
    $otrosGrupos = [];
    foreach ($agrupadas as $grupo => $secciones) {
        if ($grupoEsAula($secciones)) {
            $aulaGrupos[$grupo] = $secciones;
        } else {
            $otrosGrupos[$grupo] = $secciones;
        }
    }
    $agrupadas = $aulaGrupos + $otrosGrupos;
    ?>

    <?php foreach ($agrupadas as $grupo => $secciones): ?>
        <?php $esAula = $grupoEsAula($secciones); ?>
        <div class="card mb-md">
            <div class="card__header">
                <?php if ($esAula): ?>
                    <?php $pc = $primeraDelGrupo($secciones); ?>
                    <h2 class="card__title">
                        Mi aula — <?= e($pc['grado_nombre'] . ' ' . $pc['seccion_nombre']) ?>
                        <?= e($pc['nivel_nombre']) ?>
                    </h2>
                    <span class="badge badge--aula"><?= e(rol_aula(auth()['sexo'] ?? null)) ?></span>
                <?php else: ?>
                    <h2 class="card__title"><?= e($grupo) ?></h2>
                <?php endif; ?>
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

                                <div class="carga-area <?= $esAula ? 'carga-area--aula' : '' ?>">
                                    <div class="carga-area__header">
                                        <?php if (!$esAula): ?>
                                            <span class="carga-area__seccion">
                                                Sección <?= e($primeraC['seccion_nombre']) ?>
                                            </span>
                                            <span class="carga-area__sep">—</span>
                                        <?php endif; ?>
                                        <span class="carga-area__nombre">
                                            <?= e($primeraC['area_nombre']) ?>
                                        </span>
                                    </div>
                                    <div class="carga-area__items">
                                        <?php foreach ($areaCargas as $carga): ?>
                                            <?php [$bloqueadas, $total, $pct, $estado] = $avanceCarga($carga); ?>
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

                                                <?php if ($tr = $transBadge($carga)): ?>
                                                    <?php [$trClase, $trTexto] = $tr; ?>
                                                    <span class="carga-transversal carga-transversal--<?= $trClase ?>">
                                                        <?= $trTexto ?>
                                                    </span>
                                                <?php endif; ?>

                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            <?php else: ?>

                                <?php
                                $carga = $areaCargas[0];
                                [$bloqueadas, $total, $pct, $estado] = $avanceCarga($carga);
                                [$badgeClase, $badgeTexto] = $estadoBadge($carga);
                                ?>
                                <a href="<?= url('docente/calificaciones/' . $carga['id']) ?>"
                                   class="carga-item <?= $esAula ? 'carga-item--aula' : '' ?> <?= $periodo ? '' : 'carga-item--disabled' ?>">

                                    <?php if (!$esAula): ?>
                                        <div class="carga-item__seccion">
                                            Sección <?= e($carga['seccion_nombre']) ?>
                                        </div>
                                    <?php endif; ?>

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

                                    <?php if ($tr = $transBadge($carga)): ?>
                                        <?php [$trClase, $trTexto] = $tr; ?>
                                        <span class="carga-transversal carga-transversal--<?= $trClase ?>">
                                            <?= $trTexto ?>
                                        </span>
                                    <?php endif; ?>

                                </a>

                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; // fin empty($cargas) ?>
