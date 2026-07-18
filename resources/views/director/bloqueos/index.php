<?php
/**
 * @var array      $periodos
 * @var int        $periodoId
 * @var array|null $periodo
 * @var array      $competencias
 * @var array      $transversales
 * @var array      $transStats    ['total','cerradas']
 * @var array      $conducta      [['seccion_id','grado_nombre','seccion_nombre','nivel_nombre','tutor_nombre','estado','bloqueada','cerrada','esperados','calificados','ra_bloqueado_en','tutor_cerrado_en'], ...]
 * @var array      $conductaStats ['total','bloqueadas','cerradas']
 * @var array      $asistencia    [['seccion_id','grado_nombre','seccion_nombre','nivel_nombre','bloqueada','esperados','registrados','ra_bloqueado_en'], ...]
 * @var array      $asistenciaStats ['total','bloqueadas']
 * @var array      $stats         ['total','bloqueadas','pendientes','sin_criterios','cierre_forzado']
 * @var array      $statsDocentes
 * @var array      $topCriticos
 */

// Mini-stats de las cards del hub.
$acTotal = (int) $stats['total'];
$acBloq  = (int) $stats['bloqueadas'];
$acPct   = $acTotal > 0 ? round($acBloq / $acTotal * 100) : 0;

$trTotal = (int) ($transStats['total'] ?? 0);
$trCerr  = (int) ($transStats['cerradas'] ?? 0);
$trPct   = $trTotal > 0 ? round($trCerr / $trTotal * 100) : 0;

$coTotal = (int) ($conductaStats['total'] ?? 0);
$coCerr  = (int) ($conductaStats['cerradas'] ?? 0);
$coPct   = $coTotal > 0 ? round($coCerr / $coTotal * 100) : 0;

$asTotal = (int) ($asistenciaStats['total'] ?? 0);
$asBloq  = (int) ($asistenciaStats['bloqueadas'] ?? 0);
$asPct   = $asTotal > 0 ? round($asBloq / $asTotal * 100) : 0;
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&#8592; Dashboard</a>
    <div>
        <h1 class="page-title">Gestión de bloqueos del bimestre</h1>
        <p class="page-subtitle">Controla el permiso de edición de calificaciones por periodo</p>
    </div>
    <a href="<?= url('consulta-notas' . ($periodoId ? '?periodo_id=' . (int) $periodoId : '')) ?>"
       class="btn btn--secondary btn--sm">Consultar notas (lectura)</a>
</div>

<div class="card mb-md">
    <div class="card__body">
        <form method="GET" action="<?= url('director/bloqueos') ?>" class="bloqueos-filtro">
            <label class="form-label" for="periodo_id">Periodo / Bimestre</label>
            <select name="periodo_id" id="periodo_id"
                    class="form-input bloqueos-filtro__select"
                    onchange="this.form.submit()">
                <option value="">— Seleccionar periodo —</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= (int)$p['id'] === $periodoId ? 'selected' : '' ?>>
                        <?= e($p['nombre_display']) ?> &mdash; <?= e($p['anio']) ?>
                        <?= $p['estado'] === 'activo' ? ' (Activo)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($periodo): ?>

<!-- Hub: 4 accesos (tabs). Sin detalle hasta hacer clic. -->
<div class="bloqueos-hub" role="tablist" aria-label="Tipos de bloqueo">

    <button type="button"
            class="bloqueos-tabcard bloqueos-tabcard--academicas"
            role="tab" id="tabcard-academicas"
            data-tab="academicas" aria-controls="tab-academicas" aria-selected="false">
        <span class="bloqueos-tabcard__titulo">Competencias académicas</span>
        <span class="bloqueos-tabcard__stat"><?= $acBloq ?>/<?= $acTotal ?> bloqueadas</span>
        <span class="bloqueos-tabcard__bar">
            <span class="bloqueos-tabcard__fill" style="--pct:<?= $acPct ?>%"></span>
        </span>
        <span class="bloqueos-tabcard__pct"><?= $acPct ?>%</span>
    </button>

    <button type="button"
            class="bloqueos-tabcard bloqueos-tabcard--transversales"
            role="tab" id="tabcard-transversales"
            data-tab="transversales" aria-controls="tab-transversales" aria-selected="false">
        <span class="bloqueos-tabcard__titulo">Competencias transversales</span>
        <span class="bloqueos-tabcard__stat"><?= $trCerr ?>/<?= $trTotal ?> cerradas</span>
        <span class="bloqueos-tabcard__bar">
            <span class="bloqueos-tabcard__fill" style="--pct:<?= $trPct ?>%"></span>
        </span>
        <span class="bloqueos-tabcard__pct"><?= $trPct ?>%</span>
    </button>

    <button type="button"
            class="bloqueos-tabcard bloqueos-tabcard--conducta"
            role="tab" id="tabcard-conducta"
            data-tab="conducta" aria-controls="tab-conducta" aria-selected="false">
        <span class="bloqueos-tabcard__titulo">Conducta</span>
        <span class="bloqueos-tabcard__stat"><?= $coCerr ?>/<?= $coTotal ?> cerradas</span>
        <span class="bloqueos-tabcard__bar">
            <span class="bloqueos-tabcard__fill" style="--pct:<?= $coPct ?>%"></span>
        </span>
        <span class="bloqueos-tabcard__pct"><?= $coPct ?>%</span>
    </button>

    <button type="button"
            class="bloqueos-tabcard bloqueos-tabcard--asistencia"
            role="tab" id="tabcard-asistencia"
            data-tab="asistencia" aria-controls="tab-asistencia" aria-selected="false">
        <span class="bloqueos-tabcard__titulo">Asistencia</span>
        <span class="bloqueos-tabcard__stat"><?= $asBloq ?>/<?= $asTotal ?> bloqueadas</span>
        <span class="bloqueos-tabcard__bar">
            <span class="bloqueos-tabcard__fill" style="--pct:<?= $asPct ?>%"></span>
        </span>
        <span class="bloqueos-tabcard__pct"><?= $asPct ?>%</span>
    </button>

</div>

<!-- ══ PANEL: Competencias académicas ══════════════════════════ -->
<section id="tab-academicas" class="bloqueos-panel" data-panel="academicas"
         role="tabpanel" aria-labelledby="tabcard-academicas" hidden>

<?php if (!empty($competencias)): ?>

<?php
// Reagrupar: nivel → seccion_id → { header, competencias[] }
$porNivel = [];
foreach ($competencias as $c) {
    $nk = $c['nivel_nombre'];
    $sk = (int) $c['seccion_id'];

    if (!isset($porNivel[$nk][$sk])) {
        $porNivel[$nk][$sk] = [
            'grado_nombre'   => $c['grado_nombre'],
            'seccion_nombre' => $c['seccion_nombre'],
            'bloqueadas'     => 0,
            'sin_criterios'  => 0,
            'pendientes'     => 0,
            'total'          => 0,
            'competencias'   => [],
        ];
    }

    $s = &$porNivel[$nk][$sk];
    $s['total']++;
    if ($c['bloqueo_id'] !== null) {
        $s['bloqueadas']++;
    } elseif ((int) $c['num_criterios'] === 0) {
        $s['sin_criterios']++;
    } else {
        $s['pendientes']++;
    }
    $s['competencias'][] = $c;
    unset($s);
}
?>

<?php
$_t   = $stats['total'];
$_pB  = $_t > 0 ? $stats['bloqueadas']    / $_t * 100 : 0;
$_pP  = $_t > 0 ? $stats['pendientes']    / $_t * 100 : 0;
$_pS  = $_t > 0 ? $stats['sin_criterios'] / $_t * 100 : 0;

// stroke-dasharray: segmento visible, resto oculto (circunferencia = 100)
$_dB  = round($_pB, 2) . ' ' . round(100 - $_pB, 2);
$_dP  = round($_pP, 2) . ' ' . round(100 - $_pP, 2);
$_dS  = round($_pS, 2) . ' ' . round(100 - $_pS, 2);

// stroke-dashoffset: posición de inicio de cada arco (12 en punto = offset 25)
$_oB  = 25;
$_oP  = round(25 - $_pB, 2);
$_oS  = round(25 - $_pB - $_pP, 2);
?>

<div class="bloqueos-donut card mb-md">
    <div class="bloqueos-donut__svg-wrap" role="img" aria-label="Gráfico de estado de competencias">
        <svg viewBox="0 0 42 42" class="bloqueos-donut__svg">
            <!-- Fondo -->
            <circle cx="21" cy="21" r="15.9155" fill="none"
                    stroke="#e5e7eb" stroke-width="4"/>
            <!-- Bloqueadas -->
            <?php if ($_pB > 0): ?>
            <circle cx="21" cy="21" r="15.9155" fill="none"
                    stroke="#16a34a" stroke-width="4"
                    stroke-dasharray="<?= $_dB ?>"
                    stroke-dashoffset="<?= $_oB ?>"/>
            <?php endif; ?>
            <!-- Pendientes -->
            <?php if ($_pP > 0): ?>
            <circle cx="21" cy="21" r="15.9155" fill="none"
                    stroke="#d97706" stroke-width="4"
                    stroke-dasharray="<?= $_dP ?>"
                    stroke-dashoffset="<?= $_oP ?>"/>
            <?php endif; ?>
            <!-- Sin criterios -->
            <?php if ($_pS > 0): ?>
            <circle cx="21" cy="21" r="15.9155" fill="none"
                    stroke="#dc2626" stroke-width="4"
                    stroke-dasharray="<?= $_dS ?>"
                    stroke-dashoffset="<?= $_oS ?>"/>
            <?php endif; ?>
            <!-- Texto central -->
            <text x="21" y="19.5" class="bloqueos-donut__pct-svg"><?= number_format($_pB, 2) ?>%</text>
            <text x="21" y="24"   class="bloqueos-donut__sub-svg">completado</text>
        </svg>
    </div>

    <div class="bloqueos-donut__leyenda">
        <p class="bloqueos-donut__titulo">
            <?= $_t ?> competencias &mdash; <?= e($periodo['nombre_display']) ?>
        </p>
        <div class="bloqueos-donut__item">
            <span class="bloqueos-donut__dot bloqueos-donut__dot--ok"></span>
            <span class="bloqueos-donut__label">Bloqueadas</span>
            <strong class="bloqueos-donut__num"><?= $stats['bloqueadas'] ?></strong>
            <span class="bloqueos-donut__pct-txt"><?= number_format($_pB, 2) ?>%</span>
        </div>
        <div class="bloqueos-donut__item">
            <span class="bloqueos-donut__dot bloqueos-donut__dot--warn"></span>
            <span class="bloqueos-donut__label">Pendientes</span>
            <strong class="bloqueos-donut__num"><?= $stats['pendientes'] ?></strong>
            <span class="bloqueos-donut__pct-txt"><?= number_format($_pP, 2) ?>%</span>
        </div>
        <div class="bloqueos-donut__item">
            <span class="bloqueos-donut__dot bloqueos-donut__dot--err"></span>
            <span class="bloqueos-donut__label">Sin criterios</span>
            <strong class="bloqueos-donut__num"><?= $stats['sin_criterios'] ?></strong>
            <span class="bloqueos-donut__pct-txt"><?= number_format($_pS, 2) ?>%</span>
        </div>
    </div>
</div>

<?php if (($periodo['estado'] ?? '') === 'activo' && $stats['cierre_forzado'] > 0): ?>
<div class="card mb-md bloqueos-cierre-accion">
    <div class="bloqueos-cierre-accion__texto">
        <p class="bloqueos-cierre-accion__titulo">
            <?= $stats['cierre_forzado'] ?> competencia(s) bloqueadas por el cierre forzado
        </p>
        <p class="text-sm text-muted">
            Son competencias que el docente no aprobo y que el cierre del bimestre bloqueo
            automaticamente. Liberarlas permite que los docentes vuelvan a editarlas. Las
            competencias aprobadas por el docente (incluidas las finalizadas sin notas) NO se tocan.
        </p>
    </div>
    <form method="POST"
          action="<?= url('director/bloqueos/limpiar-cierre') ?>"
          onsubmit="return confirm('Liberar <?= $stats['cierre_forzado'] ?> competencia(s) del cierre forzado? Esta accion no afecta los bloqueos aprobados por el docente.')">
        <?= csrf_field() ?>
        <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
        <button type="submit" class="btn btn--danger">
            Liberar bloqueos del cierre forzado
        </button>
    </form>
</div>
<?php endif; ?>


<div class="bloqueos-lateral mb-md">

    <!-- Ranking docentes -->
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">Docentes con mayor incumplimiento</h2>
            <?php if (!empty($topCriticos)): ?>
            <span class="badge badge--error">Top <?= count($topCriticos) ?></span>
            <?php else: ?>
            <span class="badge badge--activo">Al dia</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($topCriticos)): ?>
        <ol class="ranking-criticos">
            <?php foreach ($topCriticos as $pos => $d):
                $itemMod = $d['sin_criterios'] > 0 ? 'ranking-criticos__item--err' : 'ranking-criticos__item--warn';
            ?>
            <li class="ranking-criticos__item <?= $itemMod ?>">
                <span class="ranking-criticos__pos"><?= $pos + 1 ?>°</span>
                <div class="ranking-criticos__info">
                    <span class="ranking-criticos__nombre">
                        <?= e($d['apellido'] . ', ' . $d['nombres']) ?>
                    </span>
                    <div class="ranking-criticos__chips">
                        <?php if ($d['sin_criterios'] > 0): ?>
                        <span class="ranking-criticos__chip ranking-criticos__chip--err">
                            <?= $d['sin_criterios'] ?> sin criterios
                        </span>
                        <?php endif; ?>
                        <?php if ($d['pendientes'] > 0): ?>
                        <span class="ranking-criticos__chip ranking-criticos__chip--warn">
                            <?= $d['pendientes'] ?> pendientes
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php else: ?>
        <div class="card__body">
            <p class="empty-state">Todos los docentes estan al dia.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Indicadores laterales -->
    <div class="bloqueos-widgets">

        <!-- Widget: dias restantes -->
        <?php
            if ($diasRestantes === null) {
                $_diasTxt = '&mdash;';
                $_diasSub = 'Sin fecha limite';
                $_diasMod = 'muted';
            } elseif ($diasRestantes < 0) {
                $_diasTxt = abs($diasRestantes);
                $_diasSub = 'dias desde el cierre';
                $_diasMod = 'muted';
            } elseif ($diasRestantes <= 3) {
                $_diasTxt = $diasRestantes;
                $_diasSub = 'dias para el cierre';
                $_diasMod = 'err';
            } elseif ($diasRestantes <= 7) {
                $_diasTxt = $diasRestantes;
                $_diasSub = 'dias para el cierre';
                $_diasMod = 'warn';
            } else {
                $_diasTxt = $diasRestantes;
                $_diasSub = 'dias para el cierre';
                $_diasMod = 'ok';
            }
        ?>
        <div class="card">
            <span class="bloqueos-widget__label">Cierre del periodo</span>
            <div class="bloqueos-widget__body">
                <strong class="widget-dias__num widget-dias__num--<?= $_diasMod ?>">
                    <?= $_diasTxt ?>
                </strong>
                <span class="widget-dias__sub"><?= $_diasSub ?></span>
            </div>
        </div>

        <!-- Widget: avance por nivel -->
        <div class="card">
            <span class="bloqueos-widget__label">Avance por nivel</span>
            <div class="bloqueos-widget__body">
                <?php foreach ($statsPorNivel as $niv):
                    $_pNiv = $niv['total'] > 0
                        ? round($niv['bloqueadas'] / $niv['total'] * 100)
                        : 0;
                ?>
                <div class="widget-niveles__fila">
                    <span class="widget-niveles__nombre"
                          title="<?= e($niv['nombre']) ?>"><?= e($niv['nombre']) ?></span>
                    <div class="widget-niveles__track">
                        <div class="widget-niveles__fill" style="--fill:<?= $_pNiv ?>%"></div>
                    </div>
                    <span class="widget-niveles__pct"><?= $_pNiv ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Widget: secciones completas -->
        <?php $_pSec = $totalSecciones > 0
            ? round($seccionesCompletas / $totalSecciones * 100)
            : 0;
        ?>
        <div class="card">
            <span class="bloqueos-widget__label">Secciones completas</span>
            <div class="bloqueos-widget__body">
                <strong class="widget-secciones__fraction">
                    <?= $seccionesCompletas ?> / <?= $totalSecciones ?>
                </strong>
                <span class="widget-secciones__sub">secciones al 100%</span>
                <div class="widget-secciones__track">
                    <div class="widget-secciones__fill" style="--fill:<?= $_pSec ?>%"></div>
                </div>
            </div>
        </div>

    </div><!-- /.bloqueos-widgets -->
</div><!-- /.bloqueos-lateral -->

<?php foreach ($porNivel as $nivelNombre => $secciones): ?>

    <p class="bloqueos-nivel-titulo"><?= e($nivelNombre) ?></p>

    <?php foreach ($secciones as $seccionId => $sec):
        $total      = $sec['total'];
        $bloqueadas = $sec['bloqueadas'];
        $pct        = $total > 0 ? round($bloqueadas / $total * 100) : 0;

        if ($bloqueadas === $total && $total > 0) {
            $secEstado      = 'completa';
            $badgeClase     = 'badge--activo';
            $badgeTexto     = 'Completa';
            $expandir       = false;
        } elseif ($sec['sin_criterios'] > 0) {
            $secEstado      = 'sin-criterios';
            $badgeClase     = 'badge--error';
            $badgeTexto     = 'Sin criterios';
            $expandir       = true;
        } elseif ($bloqueadas > 0) {
            $secEstado      = 'parcial';
            $badgeClase     = 'badge--warning';
            $badgeTexto     = 'Parcial';
            $expandir       = true;
        } else {
            $secEstado      = 'pendiente';
            $badgeClase     = 'badge--warning';
            $badgeTexto     = 'Pendiente';
            $expandir       = true;
        }
    ?>

    <div class="bloqueo-seccion bloqueo-seccion--<?= $secEstado ?>"
         <?= $expandir ? 'data-open' : '' ?>>

        <button class="bloqueo-seccion__header"
                aria-expanded="<?= $expandir ? 'true' : 'false' ?>">

            <span class="bloqueo-seccion__titulo">
                <?= e($sec['grado_nombre']) ?>
                <span class="bloqueo-seccion__sep">&mdash;</span>
                Secci&oacute;n <?= e($sec['seccion_nombre']) ?>
            </span>

            <span class="bloqueo-seccion__meta">
                <span class="badge <?= $badgeClase ?>"><?= $badgeTexto ?></span>
                <span class="bloqueo-seccion__progreso">
                    <span class="bloqueo-seccion__barra">
                        <span class="bloqueo-seccion__fill" style="--pct:<?= $pct ?>%"></span>
                    </span>
                    <span class="bloqueo-seccion__cuenta"><?= $bloqueadas ?>/<?= $total ?></span>
                </span>
                <span class="bloqueo-seccion__chevron">&#8964;</span>
            </span>

        </button>

        <div class="bloqueo-seccion__body">
            <table class="tabla-ranking tabla-bloqueos">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Competencia</th>
                        <th>Docente</th>
                        <th>Estado</th>
                        <th>Bloqueado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sec['competencias'] as $fila):
                        $tieneCriterios = (int) $fila['num_criterios'] > 0;
                        $bloqueada      = $fila['bloqueo_id'] !== null;

                        if ($bloqueada && $tieneCriterios) {
                            $estado  = 'bloqueada';
                            $filaCss = '';
                        } elseif ($bloqueada && !$tieneCriterios) {
                            $estado  = 'bloqueada-sin-notas';
                            $filaCss = '';
                        } elseif (!$bloqueada && $tieneCriterios) {
                            $estado  = 'pendiente';
                            $filaCss = 'fila-pendiente';
                        } else {
                            $estado  = 'sin-criterios';
                            $filaCss = 'fila-sin-criterios';
                        }
                    ?>
                    <tr class="<?= $filaCss ?>">

                        <td>
                            <?= e($fila['area_nombre'] ?? '—') ?>
                            <?php if ($fila['subarea_nombre']): ?>
                                <br><span class="text-sm text-muted"><?= e($fila['subarea_nombre']) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="bloqueos-competencia">
                            <?= e($fila['competencia_nombre']) ?>
                        </td>

                        <td class="text-sm">
                            <?= e($fila['docente_apellido']) ?>,
                            <span class="text-muted"><?= e($fila['docente_nombres']) ?></span>
                        </td>

                        <td>
                            <?php if ($estado === 'bloqueada'): ?>
                                <span class="badge badge--activo">Bloqueada <span class="badge__icono" aria-hidden="true"></span></span>
                            <?php elseif ($estado === 'bloqueada-sin-notas'): ?>
                                <span class="badge badge--activo badge--sin-notas">&#10003; Sin notas</span>
                            <?php elseif ($estado === 'pendiente'): ?>
                                <span class="badge badge--warning">Pendiente</span>
                            <?php else: ?>
                                <span class="badge badge--error">Sin criterios</span>
                            <?php endif; ?>
                            <?php if ($bloqueada && ($fila['bloqueo_origen'] ?? '') === 'cierre'): ?>
                                <br><span class="text-sm text-muted">por cierre forzado</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-muted text-sm">
                            <?= $fila['bloqueado_en']
                                ? date('d/m/Y H:i', strtotime($fila['bloqueado_en']))
                                : '—'
                            ?>
                        </td>

                        <td>
                            <?php if ($bloqueada): ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/' . $fila['bloqueo_id'] . '/desbloquear') ?>"
                                      onsubmit="return confirm('Desbloquear esta competencia? El docente podra modificar las notas nuevamente.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn--danger btn--sm">
                                        Desbloquear
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/bloquear') ?>"
                                      onsubmit="return confirm('Bloquear esta competencia? El docente no podra editar las notas.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="carga_id"       value="<?= $fila['carga_id'] ?>">
                                    <input type="hidden" name="competencia_id" value="<?= $fila['competencia_id'] ?>">
                                    <input type="hidden" name="periodo_id"     value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--secondary btn--sm">
                                        Bloquear
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <?php endforeach; ?>
<?php endforeach; ?>

<?php else: ?>
    <div class="empty-state">
        <p>No hay cargas academicas activas para este periodo.</p>
    </div>
<?php endif; ?>

</section><!-- /#tab-academicas -->

<!-- ══ PANEL: Competencias transversales (TIC/GAMA) ════════════ -->
<section id="tab-transversales" class="bloqueos-panel" data-panel="transversales"
         role="tabpanel" aria-labelledby="tabcard-transversales" hidden>

<?php if (!empty($transversales)): ?>
    <p class="bloqueos-nivel-titulo">Competencias Transversales (TIC/GAMA) &mdash; cierre del tutor</p>
    <div class="card mb-md">
        <div class="card__body">
            <p class="text-sm text-muted mb-sm">
                El estado lo gobierna el <strong>cierre del tutor</strong> (es lo que habilita
                TIC/GAMA en la boleta), no la carga heredada. <em>Desbloquear</em> anula el cierre
                vigente; <em>Bloquear</em> lo cierra (requiere todas las cargas bloqueadas y las
                conclusiones obligatorias completas).
            </p>
            <table class="tabla-ranking tabla-bloqueos">
                <thead>
                    <tr>
                        <th>Secci&oacute;n</th>
                        <th>Tutor(a)</th>
                        <th>Estado</th>
                        <th>Bloqueado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transversales as $st): ?>
                    <tr class="<?= $st['cerrada'] ? '' : 'fila-pendiente' ?>">
                        <td class="text-sm">
                            <?= e($st['grado_nombre']) ?> &mdash; <?= e($st['seccion_nombre']) ?>
                            <br><span class="text-muted"><?= e($st['nivel_nombre']) ?></span>
                        </td>
                        <td class="text-sm"><?= e($st['tutor_nombre']) ?></td>
                        <td>
                            <?php if ($st['cerrada']): ?>
                                <span class="badge badge--activo">Bloqueada <span class="badge__icono" aria-hidden="true"></span></span>
                            <?php else: ?>
                                <span class="badge badge--warning">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm">
                            <?= ($st['cerrada'] && $st['cerrado_en'])
                                ? date('d/m/Y H:i', strtotime($st['cerrado_en']))
                                : '—'
                            ?>
                        </td>
                        <td>
                            <?php if ($st['cerrada']): ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/transversal/' . $st['seccion_id'] . '/reabrir') ?>"
                                      onsubmit="return confirm('Desbloquear las transversales de esta seccion? El tutor debera volver a cerrar.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">Desbloquear</button>
                                </form>
                            <?php else: ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/transversal/' . $st['seccion_id'] . '/cerrar') ?>"
                                      onsubmit="return confirm('Bloquear las transversales de esta seccion?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--secondary btn--sm">Bloquear</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>No hay secciones con tutor para gestionar transversales en este periodo.</p>
    </div>
<?php endif; ?>

</section><!-- /#tab-transversales -->

<!-- ══ PANEL: Conducta ═════════════════════════════════════════ -->
<section id="tab-conducta" class="bloqueos-panel" data-panel="conducta"
         role="tabpanel" aria-labelledby="tabcard-conducta" hidden>

<?php if (!empty($conducta)): ?>
    <p class="bloqueos-nivel-titulo">Conducta &mdash; dos etapas: auxiliar académico &rarr; tutor</p>
    <div class="card mb-md">
        <div class="card__body">
            <p class="text-sm text-muted mb-sm">
                La conducta cierra en dos etapas: el <strong>auxiliar académico</strong> registra y
                bloquea, luego el <strong>tutor</strong> aprueba. El director puede forzar cualquier
                etapa o <em>reabrir</em> para correcciones. Forzar la etapa del auxiliar exige que
                todos los estudiantes estén calificados.
            </p>
            <table class="tabla-ranking tabla-bloqueos">
                <thead>
                    <tr>
                        <th>Secci&oacute;n</th>
                        <th>Tutor(a)</th>
                        <th>Calificados</th>
                        <th>Estado</th>
                        <th>Cerrado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conducta as $cc):
                        $califTxt = $cc['calificados'] . '/' . $cc['esperados'];
                        $completa = $cc['esperados'] > 0 && $cc['calificados'] >= $cc['esperados'];
                        $filaCss  = $cc['estado'] === 'cerrada' ? '' : 'fila-pendiente';
                    ?>
                    <tr class="<?= $filaCss ?>">
                        <td class="text-sm">
                            <?= e($cc['grado_nombre']) ?> &mdash; <?= e($cc['seccion_nombre']) ?>
                            <br><span class="text-muted"><?= e($cc['nivel_nombre']) ?></span>
                        </td>
                        <td class="text-sm"><?= e($cc['tutor_nombre']) ?></td>
                        <td class="text-sm <?= $completa ? '' : 'text-muted' ?>">
                            <?= $califTxt ?>
                        </td>
                        <td>
                            <?php if ($cc['estado'] === 'cerrada'): ?>
                                <span class="badge badge--activo">&#10003; Cerrada</span>
                            <?php elseif ($cc['estado'] === 'pendiente_tutor'): ?>
                                <span class="badge badge--warning">Pendiente tutor</span>
                            <?php else: ?>
                                <span class="badge badge--error">Pendiente auxiliar</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm">
                            <?= $cc['tutor_cerrado_en']
                                ? date('d/m/Y H:i', strtotime($cc['tutor_cerrado_en']))
                                : '—'
                            ?>
                        </td>
                        <td class="td-acciones-conducta">
                            <?php if ($cc['estado'] === 'pendiente_auxiliar'): ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/conducta/' . $cc['seccion_id'] . '/bloquear') ?>"
                                      onsubmit="return confirm('Forzar el bloqueo del auxiliar académico para esta sección?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--secondary btn--sm"
                                        <?= $completa ? '' : 'disabled title="Faltan estudiantes por calificar"' ?>>
                                        Bloquear (etapa 1)
                                    </button>
                                </form>
                            <?php elseif ($cc['estado'] === 'pendiente_tutor'): ?>
                                <div class="btn-group">
                                    <form method="POST"
                                          action="<?= url('director/bloqueos/conducta/' . $cc['seccion_id'] . '/cerrar') ?>"
                                          onsubmit="return confirm('Forzar el cierre del tutor para esta sección?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                        <button type="submit" class="btn btn--secondary btn--sm">Cerrar (etapa 2)</button>
                                    </form>
                                    <form method="POST"
                                          action="<?= url('director/bloqueos/conducta/' . $cc['seccion_id'] . '/reabrir') ?>"
                                          onsubmit="return confirm('Reabrir la conducta de esta sección? Se anulará el bloqueo del auxiliar.')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Reabrir</button>
                                    </form>
                                </div>
                            <?php else: /* cerrada */ ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/conducta/' . $cc['seccion_id'] . '/reabrir') ?>"
                                      onsubmit="return confirm('Reabrir la conducta de esta sección? El auxiliar y el tutor deberán volver a cerrar.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">Reabrir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>No hay secciones con tutor para gestionar conducta en este periodo.</p>
    </div>
<?php endif; ?>

</section><!-- /#tab-conducta -->

<!-- ══ PANEL: Asistencia ═══════════════════════════════════════ -->
<section id="tab-asistencia" class="bloqueos-panel" data-panel="asistencia"
         role="tabpanel" aria-labelledby="tabcard-asistencia" hidden>

<?php if (!empty($asistencia)): ?>
    <p class="bloqueos-nivel-titulo">Asistencia &mdash; una etapa: Registro Acad&eacute;mico</p>
    <div class="card mb-md">
        <div class="card__body">
            <p class="text-sm text-muted mb-sm">
                <strong>Registro Acad&eacute;mico</strong> registra las incidencias y bloquea la
                secci&oacute;n. El director puede forzar el bloqueo o <em>reabrir</em> para
                correcciones. Las filas sin registro cuentan como 0 incidencias, por lo que
                el bloqueo no exige completitud.
            </p>
            <table class="tabla-ranking tabla-bloqueos">
                <thead>
                    <tr>
                        <th>Secci&oacute;n</th>
                        <th>Registrados</th>
                        <th>Estado</th>
                        <th>Bloqueado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asistencia as $sa):
                        $regTxt  = $sa['registrados'] . '/' . $sa['esperados'];
                        $filaCss = $sa['bloqueada'] ? '' : 'fila-pendiente';
                    ?>
                    <tr class="<?= $filaCss ?>">
                        <td class="text-sm">
                            <?= e($sa['grado_nombre']) ?> &mdash; <?= e($sa['seccion_nombre']) ?>
                            <br><span class="text-muted"><?= e($sa['nivel_nombre']) ?></span>
                        </td>
                        <td class="text-sm text-muted"><?= $regTxt ?></td>
                        <td>
                            <?php if ($sa['bloqueada']): ?>
                                <span class="badge badge--activo">&#10003; Bloqueada</span>
                            <?php else: ?>
                                <span class="badge badge--warning">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm">
                            <?= $sa['ra_bloqueado_en']
                                ? date('d/m/Y H:i', strtotime($sa['ra_bloqueado_en']))
                                : '—'
                            ?>
                        </td>
                        <td class="td-acciones-conducta">
                            <?php if (!$sa['bloqueada']): ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/asistencia/' . $sa['seccion_id'] . '/bloquear') ?>"
                                      onsubmit="return confirm('Forzar el bloqueo de la asistencia de esta sección? Las filas sin registro cuentan como 0 incidencias.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--secondary btn--sm">Bloquear</button>
                                </form>
                            <?php else: ?>
                                <form method="POST"
                                      action="<?= url('director/bloqueos/asistencia/' . $sa['seccion_id'] . '/reabrir') ?>"
                                      onsubmit="return confirm('Reabrir la asistencia de esta sección? Registro Académico deberá volver a bloquearla.')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_id" value="<?= $periodoId ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">Reabrir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>No hay secciones para gestionar asistencia en este periodo.</p>
    </div>
<?php endif; ?>

</section><!-- /#tab-asistencia -->

<?php else: ?>
    <div class="empty-state">
        <p>Selecciona un periodo para ver el estado de los bloqueos.</p>
    </div>
<?php endif; ?>
