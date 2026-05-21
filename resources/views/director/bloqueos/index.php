<?php
/**
 * @var array      $periodos
 * @var int        $periodoId
 * @var array|null $periodo
 * @var array      $competencias
 * @var array      $stats  ['total','bloqueadas','pendientes','sin_criterios']
 */

$porNivel = [];
foreach ($competencias as $c) {
    $porNivel[$c['nivel_nombre']][] = $c;
}
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">&#8592; Dashboard</a>
    <div>
        <h1 class="page-title">Gestión de bloqueos del bimestre</h1>
        <p class="page-subtitle">Controla el permiso de edición de calificaciones por periodo</p>
    </div>
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

<?php if ($periodo && !empty($competencias)): ?>

<div class="bloqueos-stats">
    <div class="bloqueos-stat">
        <span class="bloqueos-stat__num"><?= $stats['total'] ?></span>
        <span class="bloqueos-stat__label">Total competencias</span>
    </div>
    <div class="bloqueos-stat bloqueos-stat--ok">
        <span class="bloqueos-stat__num"><?= $stats['bloqueadas'] ?></span>
        <span class="bloqueos-stat__label">Bloqueadas</span>
    </div>
    <div class="bloqueos-stat bloqueos-stat--warn">
        <span class="bloqueos-stat__num"><?= $stats['pendientes'] ?></span>
        <span class="bloqueos-stat__label">Pendientes</span>
    </div>
    <div class="bloqueos-stat bloqueos-stat--err">
        <span class="bloqueos-stat__num"><?= $stats['sin_criterios'] ?></span>
        <span class="bloqueos-stat__label">Sin criterios</span>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <?= e($periodo['nombre_display']) ?> &mdash; <?= e($periodo['anio']) ?>
        </h2>
        <span class="text-muted text-sm"><?= $stats['total'] ?> competencias</span>
    </div>
    <div class="tabla-responsive">
        <table class="tabla-ranking tabla-bloqueos">
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th>Grado / Sección</th>
                    <th>Área</th>
                    <th>Competencia</th>
                    <th>Docente</th>
                    <th>Estado</th>
                    <th>Bloqueado el</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <?php foreach ($porNivel as $nivelNombre => $filas): ?>
            <tbody>
                <tr class="bloqueos-nivel-header">
                    <td colspan="8"><?= e($nivelNombre) ?></td>
                </tr>
                <?php foreach ($filas as $fila):
                    $tieneCriterios = (int)$fila['num_criterios'] > 0;
                    $bloqueada      = $fila['bloqueo_id'] !== null;

                    // Cuatro estados posibles
                    if ($bloqueada && $tieneCriterios) {
                        $estado      = 'bloqueada';
                        $filaCss     = '';
                    } elseif ($bloqueada && !$tieneCriterios) {
                        $estado      = 'bloqueada-sin-notas';
                        $filaCss     = '';
                    } elseif (!$bloqueada && $tieneCriterios) {
                        $estado      = 'pendiente';
                        $filaCss     = 'fila-pendiente';
                    } else {
                        $estado      = 'sin-criterios';
                        $filaCss     = 'fila-sin-criterios';
                    }
                ?>
                <tr class="<?= $filaCss ?>">
                    <td class="text-muted text-sm"><?= e($fila['nivel_nombre']) ?></td>
                    <td>
                        <?= e($fila['grado_nombre']) ?>
                        <span class="text-muted"> / <?= e($fila['seccion_nombre']) ?></span>
                    </td>
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
                            <span class="badge badge--activo">&#10003; Bloqueada</span>
                        <?php elseif ($estado === 'bloqueada-sin-notas'): ?>
                            <span class="badge badge--activo badge--sin-notas">&#10003; Sin notas</span>
                        <?php elseif ($estado === 'pendiente'): ?>
                            <span class="badge badge--warning">Pendiente</span>
                        <?php else: ?>
                            <span class="badge badge--error">Sin criterios</span>
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
                                  onsubmit="return confirm('Desbloquear esta competencia?\nEl docente podra modificar las notas nuevamente.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--danger btn--sm">
                                    Desbloquear
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST"
                                  action="<?= url('director/bloqueos/bloquear') ?>"
                                  onsubmit="return confirm('Bloquear esta competencia?\nEl docente no podra editar las notas.')">
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
            <?php endforeach; ?>
        </table>
    </div>
</div>

<?php elseif ($periodo && empty($competencias)): ?>
    <div class="empty-state">
        <p>No hay cargas académicas activas para este periodo.</p>
    </div>
<?php else: ?>
    <div class="empty-state">
        <p>Selecciona un periodo para ver el estado de los bloqueos.</p>
    </div>
<?php endif; ?>
