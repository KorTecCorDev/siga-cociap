<?php
/**
 * @var array  $carga
 * @var array  $periodo
 * @var array  $competencias
 * @var array  $alumnos
 * @var bool   $bloqueado
 * @var array  $bloqueos
 * @var array  $notasExistentes
 */
?>

<div class="page-header">
    <a href="<?= url('docente/mis-cargas') ?>"
       class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title"><?= e($carga['nombre_display']) ?></h1>
        <p class="page-subtitle">
            <?= 
            e($carga['nivel_nombre']) ?> —
            <?= 
            e($carga['grado_nombre']) ?> —
            Sección <?= e($carga['seccion_nombre']) ?> —
            <?= e($periodo['nombre_display'] ?? '') ?>
        </p>
    </div>
    <?php if ($bloqueado): ?>
        <span class="badge badge--error">⚠ Periodo bloqueado</span>
    <?php endif; ?>
</div>

<?php if ($bloqueado): ?>
    <div class="flash flash--warning">
        El plazo para registrar calificaciones ha vencido.
        Comunícate con Registro Académico.
    </div>
<?php endif; ?>

<?php if (empty($alumnos)): ?>
    <div class="empty-state">
        <p>No hay alumnos matriculados y aprobados en esta sección.</p>
    </div>
<?php else: ?>

    <?php foreach ($competencias as $competencia): ?>
        <?php $compBloqueada = in_array($competencia['id'], $bloqueos ?? []); ?>

        <div class="competencia-card" id="comp-<?= $competencia['id'] ?>">

            <!-- Encabezado -->
            <div class="competencia-card__header">
                <div>
                    <span class="competencia-card__codigo">
                        <?= e($competencia['codigo_minedu'] ?? '') ?>
                    </span>
                    <h3 class="competencia-card__nombre">
                        <?= e($competencia['nombre_completo']) ?>
                    </h3>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <?php if ($compBloqueada): ?>
                        <span class="badge badge--error">🔒 Aprobada</span>
                    <?php endif; ?>
                    <a href="<?= url('docente/calificaciones/' . $carga['id'] . '/resumen/' . $competencia['id']) ?>"
                       class="btn btn--secondary btn--sm">
                        📊 Ver resumen
                    </a>
                </div>
            </div>

            <!-- Cuerpo -->
            <div class="competencia-card__body">

                <?php if ($compBloqueada): ?>
                    <!-- Mensaje de bloqueada — sin botón extra -->
                    <div class="flash flash--warning">
                        ✅ Esta competencia fue aprobada y bloqueada.
                        Las notas ya no pueden modificarse.
                    </div>

                <?php else: ?>
                    <!-- Criterios disponibles -->
                    <?php if (empty($competencia['criterios'])): ?>
                        <p class="text-muted mb-md">
                            Sin criterios aún. Agrega uno para comenzar.
                        </p>
                    <?php else: ?>

                        <?php foreach ($competencia['criterios'] as $ci => $criterio): ?>
                            <?php $tieneCals = !empty($notasExistentes[$criterio['id']]); ?>
                            <div class="criterio-bloque<?= $ci === 0 ? ' criterio-bloque--open' : '' ?><?= $tieneCals ? ' criterio-bloque--con-notas' : '' ?>"
                                 id="criterio-<?= $criterio['id'] ?>">

                                <div class="criterio-bloque__header">
                                    <div class="criterio-bloque__titulo">
                                        <span class="criterio-bloque__caret"></span>
                                        <h4 class="criterio-bloque__nombre">
                                            <?= e($criterio['nombre']) ?>
                                        </h4>
                                    </div>
                                    <?php if (!$bloqueado): ?>
                                        <div class="criterio-bloque__acciones">
                                            <button
                                                class="btn btn--secondary btn--sm btn-renombrar-criterio"
                                                data-criterio-id="<?= $criterio['id'] ?>">
                                                Editar
                                            </button>
                                            <button
                                                class="btn btn--danger btn--sm btn-eliminar-criterio"
                                                data-criterio-id="<?= $criterio['id'] ?>"
                                                data-nombre="<?= e($criterio['nombre']) ?>"
                                                data-tiene-calificaciones="<?= $tieneCals ? '1' : '0' ?>">
                                                Eliminar
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="criterio-bloque__content">
                                    <form class="form-notas"
                                          data-criterio-id="<?= $criterio['id'] ?>"
                                          data-competencia-id="<?= $competencia['id'] ?>"
                                          data-carga-id="<?= $carga['id'] ?>">
                                        <?= csrf_field() ?>

                                        <div class="tabla-notas-wrapper">
                                        <table class="tabla-notas">
                                            <thead>
                                                <tr>
                                                    <th class="col-num">N°</th>
                                                    <th class="col-nombre">Apellidos y nombres</th>
                                                    <th>DNI</th>
                                                    <th class="text-center">Nota (0-20)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alumnos as $i => $alumno): ?>
                                                    <tr>
                                                        <td class="col-num"><?= $i + 1 ?></td>
                                                        <td class="col-nombre"><?= e($alumno['nombre_completo']) ?></td>
                                                        <td><?= e($alumno['dni']) ?></td>
                                                        <td class="text-center">
                                                            <?php $valorInicial = isset($notasExistentes[$criterio['id']][$alumno['matricula_id']]) ? fmt_nota((int)$notasExistentes[$criterio['id']][$alumno['matricula_id']]) : ''; ?>
                                                            <input
                                                                type="text"
                                                                inputmode="numeric"
                                                                pattern="(0?[0-9]|1[0-9]|20)"
                                                                class="input-nota"
                                                                name="notas[<?= $alumno['matricula_id'] ?>]"
                                                                maxlength="2"
                                                                autocomplete="off"
                                                                <?= $bloqueado ? 'disabled' : '' ?>
                                                                placeholder="—"
                                                                value="<?= $valorInicial ?>"
                                                                data-nota-inicial="<?= $valorInicial ?>"
                                                            >
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        </div>

                                        <?php if (!$bloqueado): ?>
                                            <div class="form-notas__footer">
                                                <button type="submit"
                                                        class="btn btn--primary">
                                                    Guardar notas
                                                </button>
                                                <span class="form-notas__status"></span>
                                            </div>
                                        <?php endif; ?>

                                    </form>
                                </div>

                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                    <!-- Agregar criterio — solo si no está bloqueado -->
                    <?php if (!$bloqueado): ?>
                        <div class="agregar-criterio">
                            <input
                                type="text"
                                class="form-input input-nuevo-criterio"
                                placeholder="Ej: Examen escrito, Trabajo grupal..."
                                maxlength="120"
                            >
                            <button
                                class="btn btn--primary btn-agregar-criterio"
                                data-carga-id="<?= $carga['id'] ?>"
                                data-competencia-id="<?= $competencia['id'] ?>">
                                + Agregar criterio
                            </button>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
                <!-- fin compBloqueada -->

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
