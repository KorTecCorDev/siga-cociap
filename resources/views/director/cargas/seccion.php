<?php
/**
 * @var array      $seccion  [id, seccion_nombre, grado_nombre, nivel_nombre, anio,
 *                            es_unidocente, tutor_id, tutor_paterno, tutor_materno, tutor_nombres]
 * @var array      $cargas
 * @var array|null $grupos   Solo unidocente: cargas agrupadas por área (ver controlador)
 * @var array      $auth_user
 */

$badgeEstado = fn(string $e): string => $e === 'activa' ? 'badge--activo' : 'badge--error';

$esUnidocente = !empty($seccion['es_unidocente']);
$tutorId      = (int) ($seccion['tutor_id'] ?? 0);
$tutorNombre  = !empty($seccion['tutor_paterno'])
    ? mb_strtoupper($seccion['tutor_paterno'] . ' ' . $seccion['tutor_materno'])
      . ', ' . ucwords(mb_strtolower($seccion['tutor_nombres']))
    : null;

// Filas a renderizar: en unidocente, cabecera de área (solo si el área tiene
// varias subárea-cargas) seguida de sus cargas; en polidocente, filas planas.
$filas = [];
if ($esUnidocente && $grupos) {
    foreach ($grupos as $g) {
        $varias = count($g['cargas']) > 1;
        if ($varias) {
            $filas[] = ['tipo' => 'area', 'g' => $g];
        }
        foreach ($g['cargas'] as $c) {
            $filas[] = ['tipo' => 'carga', 'c' => $c, 'sub' => $varias];
        }
    }
} else {
    foreach ($cargas as $c) {
        $filas[] = ['tipo' => 'carga', 'c' => $c, 'sub' => false];
    }
}
?>

<div class="page-header">
    <a href="<?= url('director/cargas') ?>" class="btn btn--secondary btn--sm">← Secciones</a>
    <div>
        <h1 class="page-title">
            <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle">
            <?= e($seccion['nivel_nombre']) ?> · <?= e($seccion['anio']) ?>
            · <?= count($cargas) ?> carga<?= count($cargas) !== 1 ? 's' : '' ?>
        </p>
        <?php if ($esUnidocente): ?>
            <p class="seccion-unidocente">
                <span class="badge badge--info">Unidocente</span>
                <?php if ($tutorNombre): ?>
                    <span class="seccion-unidocente__tutor">Tutor(a) de aula: <?= e($tutorNombre) ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <a href="<?= url('director/cargas/crear?seccion_id=' . $seccion['id']) ?>" class="btn btn--primary">+ Nueva carga</a>
</div>

<?php if (empty($cargas)): ?>
    <div class="card">
        <div class="card__body">
            <div class="empty-state">
                <p>Esta sección no tiene cargas académicas registradas.</p>
            </div>
        </div>
    </div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>Docente</th>
                    <th>Área / Subárea</th>
                    <th>Horario semanal</th>
                    <th class="text-center">Hrs.</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $fila): ?>

                <?php if ($fila['tipo'] === 'area'): $g = $fila['g']; ?>
                <tr class="fila-area-grupo">
                    <td colspan="2">
                        <span class="fila-area-grupo__nombre"><?= e($g['area_nombre']) ?></span>
                        <span class="fila-area-grupo__meta"><?= count($g['cargas']) ?> subáreas</span>
                    </td>
                    <td>
                        <?php if ($g['horarios']): ?>
                            <div class="carga-horario"><?= e(implode(' | ', $g['horarios'])) ?></div>
                        <?php else: ?>
                            <span class="carga-sin-horario">Sin horario registrado</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-sm">
                        <?= $g['total_horas'] > 0 ? $g['total_horas'] . 'h' : '—' ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
                <?php continue; endif; ?>

                <?php
                $c = $fila['c'];
                $nombre = mb_strtoupper($c['apellido_paterno'] . ' ' . $c['apellido_materno'])
                        . ', ' . ucwords(mb_strtolower($c['docente_nombres']));
                $activa = $c['estado'] === 'activa';
                // Solo tiene sentido señalar al especialista en una sección
                // unidocente: es la carga que NO dicta el tutor de aula.
                $esEspecialista = $esUnidocente && $tutorId > 0
                    && (int) $c['docente_id'] !== $tutorId;
                ?>
                <tr class="<?= !$activa ? 'fila-inactiva' : '' ?><?= $fila['sub'] ? ' fila-carga--sub' : '' ?>">

                    <td>
                        <div class="td-usuario__nombre"><?= e($nombre) ?></div>
                        <?php if ($esEspecialista): ?>
                            <span class="carga-especialista">Especialista</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($fila['sub']): ?>
                            <div class="carga-subarea carga-subarea--indent"><?= e($c['subarea_nombre']) ?></div>
                        <?php else: ?>
                            <div class="carga-area"><?= e($c['area_nombre']) ?></div>
                            <?php if ($c['subarea_nombre']): ?>
                                <div class="carga-subarea"><?= e($c['subarea_nombre']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($c['horario_resumen']): ?>
                            <div class="carga-horario"><?= e($c['horario_resumen']) ?></div>
                        <?php else: ?>
                            <span class="carga-sin-horario">Sin horario propio</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-center text-sm">
                        <?= (int) $c['horas_semanales'] > 0 ? $c['horas_semanales'] . 'h' : '—' ?>
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
                            <?php if ($activa): ?>
                                <a href="<?= url('director/cargas/' . $c['id'] . '/reemplazar') ?>"
                                   class="btn btn--secondary btn--sm">Reemplazar docente</a>
                            <?php endif; ?>
                            <a href="<?= url('director/cargas/' . $c['id'] . '/reemplazos') ?>"
                               class="btn btn--secondary btn--sm">Reemplazos</a>
                            <?php
                            // Al DESACTIVAR pedimos un motivo (el servidor lo exige solo si
                            // la carga ya tiene notas en el bimestre activo; aqui lo pedimos
                            // siempre para no fallar el primer intento). Cancelar aborta.
                            $onSubmit = $activa
                                ? "var m=prompt('Motivo para desactivar esta carga (obligatorio si ya tiene notas):',''); if(m===null)return false; this.motivo.value=m; return true;"
                                : "return confirm('¿Activar esta carga?')";
                            ?>
                            <form method="POST"
                                  action="<?= url('director/cargas/' . $c['id'] . '/estado') ?>"
                                  onsubmit="<?= $onSubmit ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="motivo" value="">
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
