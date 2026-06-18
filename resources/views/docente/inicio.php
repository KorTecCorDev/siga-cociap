<?php
/**
 * Dashboard del docente.
 * @var array|null $periodo
 * @var int   $nCargas
 * @var int   $avance        % aprobado solo de cargas academicas (card Mis cargas)
 * @var int   $avanceTotal   % de TODAS las responsabilidades (academicas + tutoria + conducta)
 * @var int   $sumTotal,$sumBloq
 * @var int   $completas,$sinCriterios
 * @var int|null $diasCierre
 * @var array $pendientes
 * @var array|null $tutoria
 * @var array|null $conducta
 * @var array $niveles
 * @var array $nominaResumen
 * @var int   $totalNomina
 * @var array $horario
 * @var array $auth_user
 */

// Estado del widget "días para el cierre"
if ($diasCierre === null)      { $diasMod = 'muted'; $diasTxt = '—';  $diasSub = 'Sin fecha límite'; }
elseif ($diasCierre < 0)       { $diasMod = 'muted'; $diasTxt = abs($diasCierre); $diasSub = 'días desde el cierre'; }
elseif ($diasCierre <= 3)      { $diasMod = 'err';   $diasTxt = $diasCierre; $diasSub = 'días para el cierre'; }
elseif ($diasCierre <= 7)      { $diasMod = 'warn';  $diasTxt = $diasCierre; $diasSub = 'días para el cierre'; }
else                           { $diasMod = 'ok';    $diasTxt = $diasCierre; $diasSub = 'días para el cierre'; }

$avEstado      = $avance >= 100 ? 'completo' : ($avance > 0 ? 'parcial' : 'vacio');
$avEstadoTotal = $avanceTotal >= 100 ? 'completo' : ($avanceTotal > 0 ? 'parcial' : 'vacio');
?>

<div class="welcome">
    <h1>Bienvenido(a), <?= e(nombre_corto($auth_user['nombres'] ?? '', $auth_user['apellido_paterno'] ?? '')) ?></h1>
    <p>
        Panel del docente · SIGA-COCIAP
        <?php if ($periodo): ?>
            · <strong><?= e($periodo['nombre_display']) ?> <?= e($periodo['anio']) ?></strong>
        <?php else: ?>
            · <span class="badge badge--warning">Sin periodo activo</span>
        <?php endif; ?>
    </p>
</div>

<?php if ($flash_success): ?>
    <div class="flash flash--success"><?= e($flash_success) ?></div>
<?php endif; ?>

<!-- KPIs -->
<div class="dpanel-kpis">
    <div class="dpanel-kpi">
        <span class="dpanel-kpi__num"><?= $nCargas ?></span>
        <span class="dpanel-kpi__label">Cargas asignadas</span>
    </div>
    <div class="dpanel-kpi">
        <span class="dpanel-kpi__num dpanel-kpi__num--<?= $avEstadoTotal ?>"><?= $avanceTotal ?>%</span>
        <span class="dpanel-kpi__label">Avance del bimestre</span>
    </div>
    <div class="dpanel-kpi">
        <span class="dpanel-kpi__num <?= $sinCriterios > 0 ? 'dpanel-kpi__num--err' : '' ?>"><?= $sinCriterios ?></span>
        <span class="dpanel-kpi__label">Cargas sin criterios</span>
    </div>
    <div class="dpanel-kpi">
        <span class="dpanel-kpi__num dpanel-kpi__num--<?= $diasMod ?>"><?= $diasTxt ?></span>
        <span class="dpanel-kpi__label"><?= $diasSub ?></span>
    </div>
</div>

<div class="dpanel-grid">

    <!-- Card: Mis cargas -->
    <a href="<?= url('docente/mis-cargas') ?>" class="card dpanel-card dpanel-card--cargas">
        <div class="dpanel-card__head">
            <h2 class="card__title">Mis cargas académicas</h2>
            <span class="badge badge--activo"><?= $nCargas ?> cargas</span>
        </div>
        <div class="carga-progreso">
            <div class="carga-progreso__track">
                <div class="carga-progreso__fill carga-progreso__fill--<?= $avEstado ?>"
                     style="--pct: <?= $avance ?>%"></div>
            </div>
            <div class="carga-progreso__meta">
                <span><?= $sumBloq ?>/<?= $sumTotal ?> competencias aprobadas</span>
                <span class="carga-progreso__valor carga-progreso__valor--<?= $avEstado ?>"><?= $avance ?>%</span>
            </div>
        </div>
        <p class="dpanel-card__sub">
            <?= $completas ?> completas
            <?php if ($sinCriterios > 0): ?>
                · <span class="text-danger"><?= $sinCriterios ?> sin criterios</span>
            <?php endif; ?>
        </p>
    </a>

    <!-- Card: Tutoría (solo tutores) -->
    <?php if (!empty($tutoria)): ?>
        <?php
        if ($tutoria['cierre']) {
            $tEstado = 'cerrado';
            $tTexto  = 'Cerrado el ' . fechaLima($tutoria['cierre']['cerrado_en'], 'd/m/Y');
        } elseif ($tutoria['listo']) {
            $tEstado = 'disponible';
            $tTexto  = $tutoria['pendientes'] > 0
                ? 'Disponible — ' . $tutoria['pendientes'] . ' conclusión(es) pendiente(s)'
                : 'Disponible para cerrar';
        } else {
            $tEstado = 'progreso';
            $tTexto  = 'Bloqueadas ' . $tutoria['bloqueadas'] . ' de ' . $tutoria['total'];
        }
        ?>
        <a href="<?= url('docente/tutoria') ?>" class="card dpanel-card dpanel-card--tutoria dpanel-card--<?= $tEstado ?>">
            <div class="dpanel-card__head">
                <h2 class="card__title">Tutoría — <?= e($tutoria['seccion']['grado_nombre']) ?> <?= e($tutoria['seccion']['nombre']) ?></h2>
            </div>
            <p class="dpanel-card__sub">Competencias transversales TIC/GAMA: revisa promedios, registra conclusiones y cierra el bimestre.</p>
            <span class="badge badge--<?= $tEstado === 'cerrado' ? 'activo' : 'warning' ?>"><?= e($tTexto) ?></span>
        </a>
    <?php endif; ?>

    <!-- Card: Conducta (solo tutores) -->
    <?php if (!empty($conducta)): ?>
        <?php
        if ($conducta['cerrado']) {
            $cEstado = 'cerrado';
            $cTexto  = 'Cerrada el ' . fechaLima($conducta['cierre']['tutor_cerrado_en'], 'd/m/Y');
        } elseif ($conducta['cierre']) {
            $cEstado = 'disponible';
            $cTexto  = 'Disponible';
        } else {
            $cEstado = 'progreso';
            $cTexto  = 'En espera';
        }
        ?>
        <a href="<?= url('docente/conducta') ?>" class="card dpanel-card dpanel-card--conducta dpanel-card--<?= $cEstado ?>">
            <div class="dpanel-card__head">
                <h2 class="card__title">Conducta — <?= e($conducta['seccion']['grado_nombre']) ?> <?= e($conducta['seccion']['nombre']) ?></h2>
            </div>
            <p class="dpanel-card__sub">Revisa la nota de Registro Académico, agrega tu nota (opcional) y cierra la conducta del bimestre.</p>
            <span class="badge badge--<?= $cEstado === 'cerrado' ? 'activo' : 'warning' ?>"><?= e($cTexto) ?></span>
        </a>
    <?php endif; ?>

    <!-- Card: Nómina de matriculados -->
    <a href="<?= url('docente/nomina') ?>" class="card dpanel-card dpanel-card--nomina">
        <div class="dpanel-card__head">
            <h2 class="card__title">Nómina de matriculados</h2>
            <span class="badge badge--activo"><?= $totalNomina ?> aprobados</span>
        </div>
        <p class="dpanel-card__sub">
            <?php
            $porNivel = [];
            foreach ($nominaResumen as $r) { $porNivel[$r['nivel_nombre']] = ($porNivel[$r['nivel_nombre']] ?? 0) + (int) $r['n']; }
            $partes = [];
            foreach ($porNivel as $nom => $n) { $partes[] = e($nom) . ': ' . $n; }
            echo $partes ? implode(' · ', $partes) : 'Sin matriculados en tus niveles.';
            ?>
        </p>
        <span class="dpanel-card__link">Ver nómina →</span>
    </a>

</div>

<div class="dpanel-grid">

    <!-- Pendientes -->
    <div class="card dpanel-panel">
        <div class="card__header"><h2 class="card__title">Pendientes</h2></div>
        <div class="card__body">
            <?php if (empty($pendientes)): ?>
                <p class="empty-state">Todo al día. No tienes cargas con competencias pendientes.</p>
            <?php else: ?>
                <ul class="dpanel-pend">
                    <?php foreach ($pendientes as $p): ?>
                        <li class="dpanel-pend__item">
                            <a href="<?= url('docente/calificaciones/' . $p['id']) ?>">
                                <span class="dpanel-pend__nombre"><?= e($p['nombre']) ?></span>
                                <span class="dpanel-pend__sec"><?= e($p['seccion']) ?></span>
                            </a>
                            <span class="badge <?= $p['critico'] ? 'badge--error' : 'badge--warning' ?>"><?= e($p['motivo']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mi horario -->
    <div class="card dpanel-panel">
        <div class="card__header dpanel-panel__head">
            <h2 class="card__title">Mi horario</h2>
            <?php if (!empty($horario)): ?>
                <a href="<?= url('docente/horario/imprimir') ?>" target="_blank" rel="noopener"
                   class="btn btn--secondary btn--sm">
                    <span class="btn-icon btn-icon--print" aria-hidden="true"></span>
                    Imprimir
                </a>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if (empty($horario)): ?>
                <p class="empty-state">Sin horario registrado.</p>
            <?php else: ?>
                <?php
                $dias = ['lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves', 'viernes' => 'Viernes'];
                $porDia = [];
                foreach ($horario as $h) { $porDia[$h['dia_semana']][] = $h; }
                ?>
                <div class="dpanel-horario">
                    <?php foreach ($dias as $key => $label): ?>
                        <?php if (empty($porDia[$key])) continue; ?>
                        <div class="dpanel-horario__dia">
                            <h3 class="dpanel-horario__titulo"><?= $label ?></h3>
                            <?php foreach ($porDia[$key] as $b): ?>
                                <div class="dpanel-horario__bloque">
                                    <span class="dpanel-horario__hora"><?= substr($b['hora_inicio'], 0, 5) ?>–<?= substr($b['hora_fin'], 0, 5) ?></span>
                                    <span class="dpanel-horario__area"><?= e($b['area_nombre']) ?></span>
                                    <span class="dpanel-horario__sec"><?= e($b['grado_nombre']) ?> <?= e($b['seccion_nombre']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
