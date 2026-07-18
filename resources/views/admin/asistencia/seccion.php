<?php
/**
 * Asistencia — registro de incidencias por seccion.
 * Con selector de bimestre: el periodo editable se registra; los demas
 * (historial) se muestran en SOLO LECTURA y, si estan aprobados y
 * bloqueados, permiten imprimir la copia oficial del registro.
 *
 * @var array       $seccion      { id, grado_nombre, seccion_nombre, nivel_nombre }
 * @var array|null  $periodoVer   periodo mostrado { id, nombre_display, editable, ... } o null
 * @var array       $periodosNav  periodos del año activo + ['cierre' => cierre vigente|null]
 * @var bool        $soloLectura  true = periodo no editable (historial)
 * @var array|null  $cierre       cierre vigente del periodo mostrado o null
 * @var array       $estudiantes  [{ matricula_id, nombre_completo, incidencias{...} }]
 * @var int         $topeMax      valor máximo por contador (espejo del backend)
 */

$csrfToken = \Core\Session::csrfToken();
$bloqueada = $cierre !== null;
$editable  = !$soloLectura && !$bloqueada;
$pidVer    = $periodoVer ? (int) $periodoVer['id'] : 0;
$campos    = ['faltas', 'faltas_justificadas', 'tardanzas', 'tardanzas_justificadas'];
?>

<div class="page-header">
    <a href="<?= url('admin/asistencia') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Asistencia — <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle">
            <?= e($seccion['nivel_nombre']) ?>
            <?php if ($periodoVer): ?>
                · <strong><?= e($periodoVer['nombre_display']) ?></strong>
                <?php if ($soloLectura): ?> · Solo lectura<?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!empty($periodosNav)): ?>
    <nav class="periodo-tabs" aria-label="Bimestres">
        <?php foreach ($periodosNav as $p):
            $esActual   = (int) $p['id'] === $pidVer;
            $pEditable  = (bool) $p['editable'];
        ?>
            <a href="<?= url('admin/asistencia/' . (int) $seccion['id'] . '?periodo=' . (int) $p['id']) ?>"
               class="periodo-tab<?= $esActual ? ' periodo-tab--activa' : '' ?>"
               <?= $esActual ? 'aria-current="page"' : '' ?>>
                <span class="periodo-tab__nombre"><?= e($p['nombre_display']) ?></span>
                <?php if ($pEditable): ?>
                    <span class="periodo-tab__estado periodo-tab__estado--curso">En curso</span>
                <?php elseif ($p['cierre']): ?>
                    <span class="periodo-tab__estado periodo-tab__estado--bloqueado">Aprobado</span>
                <?php else: ?>
                    <span class="periodo-tab__estado">Sin cierre</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<div id="asistencia-feedback" class="asistencia-feedback" hidden role="status" aria-live="polite"></div>

<?php if (!$periodoVer): ?>
    <div class="empty-state">
        <p>No hay periodo abierto para edición. Selecciona un bimestre del historial.</p>
    </div>
<?php elseif (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>No hay estudiantes matriculados en esta sección.</p>
    </div>
<?php else: ?>

<?php if ($bloqueada): ?>
    <div class="alert alert--info">
        <span class="btn-icon btn-icon--locked" aria-hidden="true"></span>
        <span>
            Asistencia <strong>bloqueada y aprobada por Registro Académico</strong>
            el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>.
            Para corregir, solicita el desbloqueo a Dirección.
        </span>
        <a href="<?= url('admin/asistencia/' . (int) $seccion['id'] . '/imprimir/' . $pidVer) ?>"
           target="_blank" rel="noopener" class="btn btn--secondary btn--sm alert__accion">
            🖨 Imprimir registro
        </a>
    </div>
<?php endif; ?>

<div class="tabla-notas-wrapper">
    <table class="tabla-notas asistencia-tabla">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-nombre">Apellidos y Nombres</th>
                <th class="asistencia-th-contador" title="Faltas">F</th>
                <th class="asistencia-th-contador" title="Faltas justificadas">FJ</th>
                <th class="asistencia-th-contador" title="Tardanzas">T</th>
                <th class="asistencia-th-contador" title="Tardanzas justificadas">TJ</th>
                <?php if ($editable): ?>
                    <th class="asistencia-th-acciones">Acción</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $i => $est):
                $inc = $est['incidencias'];
            ?>
                <tr class="asistencia-fila<?= $inc['registrado'] ? ' asistencia-fila--registrada' : '' ?>"
                    data-matricula-id="<?= (int) $est['matricula_id'] ?>"
                    data-periodo-id="<?= $pidVer ?>"
                    data-csrf="<?= e($csrfToken) ?>">
                    <td class="col-num"><?= $i + 1 ?></td>
                    <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>

                    <?php foreach ($campos as $campo):
                        $val = (int) $inc[$campo];
                    ?>
                        <?php if ($editable): ?>
                            <td class="asistencia-td-input">
                                <input type="number"
                                       class="asistencia-input"
                                       name="<?= $campo ?>"
                                       min="0"
                                       max="<?= $topeMax ?>"
                                       step="1"
                                       inputmode="numeric"
                                       autocomplete="off"
                                       value="<?= $val ?>"
                                       data-inicial="<?= $val ?>"
                                       aria-label="<?= $campo ?> de <?= e($est['nombre_completo']) ?>">
                            </td>
                        <?php else: ?>
                            <td class="asistencia-td-valor"><?= $val ?></td>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($editable): ?>
                        <td class="asistencia-td-acciones">
                            <button type="button" class="btn btn--primary btn--sm asistencia-guardar">
                                Guardar
                            </button>
                            <span class="asistencia-status" aria-live="polite"></span>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($editable): ?>
    <form method="post" action="<?= url('admin/asistencia/' . (int) $seccion['id'] . '/bloquear') ?>"
          class="conducta-bloqueo-form"
          onsubmit="return confirm('¿Bloquear y aprobar la asistencia de toda la sección? Las filas sin registro cuentan como 0 incidencias. Después solo Dirección podrá desbloquearla.');">
        <?= csrf_field() ?>
        <div class="conducta-bloqueo-info">
            Las filas sin guardar se consideran <strong>0 incidencias</strong>.
        </div>
        <button type="submit" class="btn btn--success">
            <span class="btn-icon btn-icon--upload" aria-hidden="true"></span>Bloquear y aprobar
        </button>
    </form>
<?php endif; ?>

<?php endif; ?>
