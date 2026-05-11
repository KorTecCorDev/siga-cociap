<?php
/**
 * @var array  $cargas
 * @var array  $auth_user
 */

$badgeEstado = fn(string $e): string => $e === 'activa' ? 'badge--activo' : 'badge--error';
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Cargas Académicas</h1>
        <p class="page-subtitle"><?= count($cargas) ?> carga<?= count($cargas) !== 1 ? 's' : '' ?> registrada<?= count($cargas) !== 1 ? 's' : '' ?></p>
    </div>
    <a href="<?= url('director/cargas/crear') ?>" class="btn btn--primary">+ Nueva carga</a>
</div>

<?php if (empty($cargas)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>No hay cargas académicas registradas.</p>
            </div>
        </div>
    </div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>Sección</th>
                    <th>Docente</th>
                    <th>Área / Subárea</th>
                    <th>Horario semanal</th>
                    <th class="text-center">Hrs.</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cargas as $c):
                    $nombre = mb_strtoupper($c['apellido_paterno'] . ' ' . $c['apellido_materno'])
                            . ', ' . ucwords(mb_strtolower($c['docente_nombres']));
                    $activa = $c['estado'] === 'activa';
                ?>
                <tr class="<?= !$activa ? 'fila-inactiva' : '' ?>">

                    <td>
                        <div class="carga-seccion">
                            <span class="carga-seccion__grado"><?= e($c['grado_nombre']) ?></span>
                            <span class="carga-seccion__letra"><?= e($c['seccion_nombre']) ?></span>
                        </div>
                        <div class="text-sm text-muted"><?= e($c['nivel_nombre']) ?> · <?= e($c['anio']) ?></div>
                    </td>

                    <td>
                        <div class="td-usuario__nombre"><?= e($nombre) ?></div>
                    </td>

                    <td>
                        <div class="carga-area"><?= e($c['area_nombre']) ?></div>
                        <?php if ($c['subarea_nombre']): ?>
                            <div class="carga-subarea"><?= e($c['subarea_nombre']) ?></div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($c['horario_resumen']): ?>
                            <div class="carga-horario"><?= e($c['horario_resumen']) ?></div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center text-sm">
                        <?= $c['horas_semanales'] ?>h
                    </td>

                    <td class="text-center">
                        <span class="badge <?= $badgeEstado($c['estado']) ?>">
                            <?= $activa ? 'Activa' : 'Inactiva' ?>
                        </span>
                    </td>

                    <td>
                        <div class="td-acciones">
                            <a href="<?= url('director/cargas/' . $c['id'] . '/editar') ?>"
                               class="btn btn--secondary btn--sm">Editar</a>
                            <form method="POST"
                                  action="<?= url('director/cargas/' . $c['id'] . '/estado') ?>"
                                  onsubmit="return confirm('¿<?= $activa ? 'Desactivar' : 'Activar' ?> esta carga?')">
                                <?= csrf_field() ?>
                                <button type="submit"
                                        class="btn btn--sm <?= $activa ? 'btn--danger' : 'btn--secondary' ?>">
                                    <?= $activa ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
