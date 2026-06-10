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

    <?php
    // Primer criterio pendiente: recorre competencias y criterios en orden
    // visual y devuelve el primero que aún no tiene a todos los alumnos
    // calificados. Las competencias bloqueadas se ignoran porque ya no se
    // editan. Si todo esta al 100 por ciento, queda en null y el banner
    // de "continuar pendiente" no se renderiza.
    $exoneradosFlip = array_flip($exonerados ?? []);
    $totalAlumnos = count(array_filter(
        $alumnos,
        fn($a) => !isset($exoneradosFlip[$a['matricula_id']])
    ));
    $siguientePendiente = null;
    if (!$bloqueado) {
        foreach ($competencias as $_c) {
            if (in_array($_c['id'], $bloqueos ?? [])) continue;
            foreach ($_c['criterios'] ?? [] as $_cri) {
                $calificados = count($notasExistentes[$_cri['id']] ?? []);
                if ($calificados < $totalAlumnos) {
                    $siguientePendiente = [
                        'criterio_id'        => (int) $_cri['id'],
                        'criterio_nombre'    => $_cri['nombre'],
                        'competencia_nombre' => $_c['nombre_completo'],
                    ];
                    break 2;
                }
            }
        }
        unset($_c, $_cri);
    }
    ?>

    <?php if ($siguientePendiente): ?>
        <div class="calificaciones-pendiente" role="note">
            <span class="calificaciones-pendiente__icono" aria-hidden="true">→</span>
            <span class="calificaciones-pendiente__texto">
                Próximo pendiente:
                <strong><?= e($siguientePendiente['competencia_nombre']) ?></strong>
                <span class="calificaciones-pendiente__sep" aria-hidden="true">›</span>
                <strong><?= e($siguientePendiente['criterio_nombre']) ?></strong>
            </span>
            <button type="button"
                    class="btn btn--primary btn--sm calificaciones-pendiente__btn"
                    data-criterio-target="<?= $siguientePendiente['criterio_id'] ?>">
                Ir a este criterio
            </button>
        </div>
    <?php endif; ?>

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
                            <?php
                            $tieneCals       = !empty($notasExistentes[$criterio['id']]);
                            $calificadosCrit = count($notasExistentes[$criterio['id']] ?? []);
                            $completoCrit    = $calificadosCrit >= $totalAlumnos && $totalAlumnos > 0;
                            ?>
                            <div class="criterio-bloque<?= $tieneCals ? ' criterio-bloque--con-notas' : '' ?>"
                                 id="criterio-<?= $criterio['id'] ?>">

                                <div class="criterio-bloque__header">
                                    <div class="criterio-bloque__titulo">
                                        <span class="criterio-bloque__caret"></span>
                                        <div class="criterio-bloque__identidad">
                                            <h4 class="criterio-bloque__nombre">
                                                <?= e($criterio['nombre']) ?>
                                            </h4>
                                            <?php if (!empty($criterio['descripcion'])): ?>
                                                <p class="criterio-bloque__descripcion">
                                                    <?= e($criterio['descripcion']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="criterio-bloque__progreso<?= $completoCrit ? ' criterio-bloque__progreso--completo' : '' ?>"
                                              title="Alumnos con nota guardada">
                                            <?= $calificadosCrit ?> de <?= $totalAlumnos ?>
                                        </span>
                                    </div>
                                    <?php if (!$bloqueado): ?>
                                        <div class="criterio-bloque__acciones">
                                            <button
                                                class="btn btn--secondary btn--sm btn-renombrar-criterio"
                                                data-criterio-id="<?= $criterio['id'] ?>"
                                                data-descripcion="<?= e($criterio['descripcion'] ?? '') ?>">
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
                                                <?php
                                                $exoneradosSet = array_flip($exonerados ?? []);
                                                foreach ($alumnos as $i => $alumno):
                                                    $esExonerado = isset($exoneradosSet[$alumno['matricula_id']]);
                                                ?>
                                                    <tr class="<?= $esExonerado ? 'fila-exonerado' : '' ?>">
                                                        <td class="col-num"><?= $i + 1 ?></td>
                                                        <td class="col-nombre"><?= e($alumno['nombre_completo']) ?></td>
                                                        <td><?= e($alumno['dni']) ?></td>
                                                        <td class="text-center">
                                                            <?php if ($esExonerado): ?>
                                                                <span class="exo-badge"
                                                                      title="Alumno exonerado de esta área — no requiere nota">EXO</span>
                                                            <?php else: ?>
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
                                                                    data-matricula-id="<?= $alumno['matricula_id'] ?>"
                                                                >
                                                            <?php endif; ?>
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
                            <div class="agregar-criterio__fila">
                                <div class="agregar-criterio__campo">
                                    <input
                                        type="text"
                                        class="form-input input-nuevo-criterio"
                                        placeholder="Ej: Examen escrito, Trabajo grupal..."
                                        maxlength="100"
                                    >
                                    <span class="contador-chars" data-contador-de="nombre">0/100</span>
                                </div>
                                <button
                                    class="btn btn--primary btn-agregar-criterio"
                                    data-carga-id="<?= $carga['id'] ?>"
                                    data-competencia-id="<?= $competencia['id'] ?>">
                                    + Agregar criterio
                                </button>
                            </div>
                            <textarea
                                class="form-input input-nuevo-criterio-desc"
                                placeholder="Descripción del criterio (opcional)"
                                rows="2"></textarea>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
                <!-- fin compBloqueada -->

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<!-- ── Modal de omisiones ────────────────────────────────────────
     Oculto por defecto. El JS lo puebla y lo muestra cuando el
     docente intenta guardar dejando alumnos sin calificar.
     Los selects usan los mismos valores ENUM de omisiones_criterio.
-->
<div id="omision-modal" class="omision-modal" hidden aria-modal="true" role="dialog" aria-labelledby="omision-modal-titulo">
    <div class="omision-modal__backdrop"></div>
    <div class="omision-modal__dialog">
        <div class="omision-modal__header">
            <h3 class="omision-modal__titulo" id="omision-modal-titulo">
                Alumnos sin calificar — registra el motivo
            </h3>
            <p class="omision-modal__desc">
                Los siguientes alumnos quedarán sin nota en este criterio.
                Al calcular el promedio, los campos vacíos
                <strong>no cuentan como cero</strong>: se excluyen del cálculo.
                Selecciona el motivo de cada uno para poder guardar.
            </p>
        </div>
        <div class="omision-modal__lista" id="omision-lista">
            <!-- Filas insertadas dinámicamente por JS -->
        </div>
        <div class="omision-modal__footer">
            <button type="button" class="btn btn--secondary" id="omision-cancelar">
                Cancelar
            </button>
            <button type="button" class="btn btn--primary" id="omision-confirmar" disabled>
                Confirmar y guardar
            </button>
            <span class="omision-modal__status" id="omision-status"></span>
        </div>
    </div>
</div>
