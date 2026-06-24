<?php
/**
 * @var array $matricula
 * @var array $vinculos
 * @var array $documentos
 * @var array $notasExternas
 * @var array $tiposVinculo
 * @var array|null $retorno
 * @var array|null $traslado    última constancia de traslado (o null)
 * @var bool  $puedeGestionar
 * @var array $pendientes  requisitos faltantes para activar (vacío = completa)
 */
$traslado = $traslado ?? null;
$pendientes = $pendientes ?? [];
$mid = (int) $matricula['id'];

$labelEstado = fn(string $e): string => match($e) {
    'pendiente'   => 'Pendiente',
    'aprobada'    => 'Aprobado',
    'desactivado' => 'Desactivado',
    default       => ucfirst($e),
};
$esActivo = $matricula['estado'] === 'aprobada';
$labelDoc = [
    'recibo_pago' => 'Recibo de pago', 'certificado_estudios' => 'Certificado de estudios',
    'boleta_siagie' => 'Boleta SIAGIE', 'ficha_matricula_siagie' => 'Ficha de matrícula SIAGIE',
    'dni_estudiante' => 'DNI del estudiante', 'dni_padre' => 'DNI del padre',
    'dni_madre' => 'DNI de la madre', 'dni_apoderado' => 'DNI del apoderado',
];
?>

<div class="page-header">
    <a href="<?= url('matriculas') ?>" class="btn btn--secondary btn--sm">← Matrículas</a>
    <div>
        <h1 class="page-title"><?= e($matricula['nombre_completo']) ?></h1>
        <p class="page-subtitle">
            <span class="matricula-badge matricula-badge--<?= e($matricula['estado']) ?>"><?= $labelEstado($matricula['estado']) ?></span>
            <span class="matricula-badge matricula-badge--<?= e($matricula['tipo']) ?>"><?= ucfirst(e($matricula['tipo'])) ?></span>
            <?php if (!empty($matricula['motivo_estado'])): ?>
            <span class="matricula-motivo">Motivo: <?= e($matricula['motivo_estado']) ?></span>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="mat-detalle-grid">

    <!-- Datos del estudiante -->
    <div class="card">
        <div class="card__body">
            <div class="card__header card__header--between">
                <p class="form-section-title">Estudiante</p>
                <?php if ($puedeGestionar): ?>
                <div class="mat-editar__control" data-editar-control hidden>
                    <button type="button" class="btn btn--secondary btn--sm" data-editar-toggle>Editar datos</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="info-grid">
                <div class="info-item"><span class="info-item__label">DNI</span><span class="info-item__value"><?= e($matricula['dni']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Sexo</span><span class="info-item__value"><?= $matricula['sexo'] === 'F' ? 'Femenino' : ($matricula['sexo'] === 'M' ? 'Masculino' : '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Nivel / Grado</span><span class="info-item__value"><?= e(($matricula['nivel_nombre'] ?? '—') . ' · ' . ($matricula['grado_nombre'] ?? '')) ?></span></div>
                <div class="info-item"><span class="info-item__label">Sección</span><span class="info-item__value"><?= e($matricula['seccion_nombre'] ?? '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Año académico</span><span class="info-item__value"><?= e((string) $matricula['anio']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Serie del recibo</span><span class="info-item__value"><?= e($matricula['serie_recibo'] ?? '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Fecha de registro</span><span class="info-item__value"><?= $matricula['fecha_registro'] ? fecha_es($matricula['fecha_registro']) : '—' ?></span></div>
            </div>

            <?php if ($puedeGestionar): ?>
            <!-- Editar datos personales (inline, disclosure). Mejora progresiva:
                 sin JS el formulario se ve abierto; matriculas.js lo colapsa. -->
            <form method="POST" action="<?= url('matriculas/' . $mid . '/estudiante') ?>"
                  class="mat-editar" data-editar-form>
                <?= csrf_field() ?>
                <p class="form-section-title">Editar datos del estudiante</p>
                <div class="mat-editar__grid">
                    <div class="form-group">
                        <label class="form-label" for="ed_apellido_paterno">Apellido paterno <span class="text-danger">*</span></label>
                        <input type="text" id="ed_apellido_paterno" name="apellido_paterno" class="form-input"
                               maxlength="60" required value="<?= e($matricula['apellido_paterno']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ed_apellido_materno">Apellido materno <span class="text-danger">*</span></label>
                        <input type="text" id="ed_apellido_materno" name="apellido_materno" class="form-input"
                               maxlength="60" required value="<?= e($matricula['apellido_materno']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ed_nombres">Nombres <span class="text-danger">*</span></label>
                        <input type="text" id="ed_nombres" name="nombres" class="form-input"
                               maxlength="100" required value="<?= e($matricula['nombres']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ed_dni">DNI <span class="text-danger">*</span></label>
                        <input type="text" id="ed_dni" name="dni" class="form-input"
                               maxlength="8" pattern="\d{8}" required value="<?= e($matricula['dni']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ed_fecha_nacimiento">Fecha de nacimiento</label>
                        <input type="date" id="ed_fecha_nacimiento" name="fecha_nacimiento" class="form-input"
                               value="<?= e((string) ($matricula['fecha_nacimiento'] ?? '')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ed_sexo">Sexo</label>
                        <select id="ed_sexo" name="sexo" class="form-input">
                            <option value="">Sin especificar</option>
                            <option value="M" <?= $matricula['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $matricula['sexo'] === 'F' ? 'selected' : '' ?>>Femenino</option>
                        </select>
                    </div>
                </div>
                <p class="resumen-nota">
                    Estos datos pertenecen al estudiante y se aplican a <strong>todos sus años
                    académicos</strong>. El DNI debe ser único en el sistema.
                </p>
                <div class="btn-group">
                    <button type="button" class="btn btn--secondary btn--sm" data-editar-cancel hidden>Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--sm">Guardar cambios</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Apoderados -->
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Apoderados</p>
            <?php if (empty($vinculos)): ?>
                <div class="empty-state"><p>Sin apoderados vinculados.</p></div>
            <?php else: foreach ($vinculos as $v): ?>
                <div class="apoderado-card <?= $v['es_responsable'] ? 'apoderado-card--responsable' : '' ?>">
                    <div class="apoderado-card__head">
                        <span class="apoderado-card__nombre"><?= e($v['nombre_completo']) ?></span>
                        <span class="matricula-badge matricula-badge--continuador"><?= e($tiposVinculo[$v['tipo_vinculo']] ?? $v['tipo_vinculo']) ?></span>
                    </div>
                    <div class="apoderado-card__meta">
                        DNI <?= e($v['dni']) ?><?= $v['telefono'] ? ' · Tel. ' . e($v['telefono']) : '' ?><?= $v['es_responsable'] ? ' · Responsable' : '' ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            <div class="btn-group">
                <a href="<?= url('matriculas/' . $mid . '/apoderado') ?>" class="btn btn--secondary btn--sm">Gestionar apoderados</a>
            </div>
        </div>
    </div>

    <!-- Documentos -->
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Documentos</p>
            <?php if (empty($documentos)): ?>
                <div class="empty-state"><p>Sin documentos registrados.</p></div>
            <?php else: ?>
                <div class="documento-checklist">
                    <?php foreach ($documentos as $d): ?>
                    <div class="documento-checklist__item">
                        <div class="documento-checklist__check"><?= (int) $d['entregado'] === 1 ? '✅' : '⬜' ?></div>
                        <div>
                            <div class="documento-checklist__nombre"><?= e($labelDoc[$d['tipo_documento']] ?? $d['tipo_documento']) ?></div>
                            <?php if (!empty($d['observacion'])): ?>
                                <span class="apoderado-card__meta"><?= e($d['observacion']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="btn-group">
                <a href="<?= url('matriculas/' . $mid . '/documentos') ?>" class="btn btn--secondary btn--sm">Editar documentos</a>
            </div>
        </div>
    </div>

    <!-- Notas externas (solo traslado de entrada / nuevo) -->
    <?php if ($matricula['tipo'] === 'nuevo'): ?>
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Notas externas (colegio origen)</p>
            <?php if (empty($notasExternas)): ?>
                <div class="empty-state"><p>Sin notas externas registradas.</p></div>
            <?php else: ?>
                <div class="tabla-notas-wrapper">
                    <table class="tabla-notas">
                        <thead><tr><th>Área</th><th>Competencia</th><th>Periodo</th><th class="text-center">Nota</th></tr></thead>
                        <tbody>
                            <?php foreach ($notasExternas as $n): ?>
                            <tr>
                                <td class="text-sm"><?= e($n['area_nombre']) ?></td>
                                <td class="text-sm"><?= e($n['competencia_nombre']) ?></td>
                                <td class="text-sm"><?= e($n['periodo_nombre']) ?></td>
                                <td class="text-center"><span class="matricula-badge matricula-badge--nuevo"><?= e($n['nota_literal']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="btn-group">
                <a href="<?= url('matriculas/' . $mid . '/notas-externas') ?>" class="btn btn--secondary btn--sm">Registrar notas externas</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Constancia de traslado -->
    <?php if ($traslado): ?>
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Constancia de traslado</p>
            <div class="info-grid">
                <div class="info-item"><span class="info-item__label">N° de constancia</span><span class="info-item__value"><?= e($traslado['numero_constancia']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Estado</span>
                    <span class="info-item__value">
                        <span class="matricula-badge matricula-badge--<?= $traslado['estado'] === 'anulado' ? 'desactivado' : 'continuador' ?>">
                            <?= $traslado['estado'] === 'anulado' ? 'Anulada' : 'Vigente' ?>
                        </span>
                    </span>
                </div>
                <div class="info-item"><span class="info-item__label">IE destino</span><span class="info-item__value"><?= e($traslado['ie_destino_nombre']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Fecha</span><span class="info-item__value"><?= fecha_es($traslado['fecha_constancia']) ?></span></div>
            </div>
            <div class="btn-group">
                <a href="<?= url('traslados/' . $traslado['id'] . '/imprimir') ?>" target="_blank" rel="noopener"
                   class="btn btn--secondary btn--sm">Imprimir constancia</a>
                <a href="<?= url('traslados') ?>" class="btn btn--secondary btn--sm">Ver registro</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Retorno de grado -->
    <?php if ($retorno): ?>
    <?php $retornoActivo = ($retorno['estado'] ?? 'activo') === 'activo'; ?>
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Retorno de grado</p>
            <div class="retorno-aviso">
                <?php if ($retornoActivo): ?>
                    Asiste al grado operativo <strong><?= e($retorno['grado_destino'] ?? '—') ?></strong>
                    desde el <?= fecha_es($retorno['fecha_retorno']) ?>.
                    <br>Motivo: <?= e($retorno['motivo']) ?>
                <?php else: ?>
                    <strong>Revertido.</strong> El estudiante volvió a su grado oficial
                    el <?= fecha_es($retorno['fecha_reversion'] ?? $retorno['fecha_retorno']) ?>.
                    Estuvo en el grado operativo <strong><?= e($retorno['grado_destino'] ?? '—') ?></strong>
                    desde el <?= fecha_es($retorno['fecha_retorno']) ?>.
                    <br>Motivo del retorno: <?= e($retorno['motivo']) ?>
                    <?php if (!empty($retorno['motivo_reversion'])): ?>
                    <br>Motivo de la reversión: <?= e($retorno['motivo_reversion']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php if ($retornoActivo && $puedeGestionar): ?>
            <div class="btn-group form-actions">
                <a href="<?= url('matriculas/' . $mid . '/retorno/revertir') ?>" class="btn btn--danger">
                    Revertir retorno (volver al grado oficial)
                </a>
            </div>
            <?php elseif (!$retornoActivo && $puedeGestionar && !empty($retorno['matricula_operativa_id'])): ?>
            <div class="btn-group form-actions">
                <a href="<?= url('rectificaciones/matricula/' . (int) $retorno['matricula_operativa_id']) ?>" class="btn btn--secondary">
                    Rectificar notas del grado operativo
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ── Boleta (consulta / impresión) ─────────────────────────── -->
<?php if ($esActivo): ?>
<div class="card mb-md">
    <div class="card__body">
        <p class="form-section-title">Boleta</p>
        <p class="text-sm text-muted mb-sm">Consulta la boleta del estudiante en pantalla o imprímela en formato físico A4.</p>
        <div class="btn-group">
            <a href="<?= url('boleta/digital/' . $tokenBoleta) ?>" target="_blank" rel="noopener"
               class="btn btn--secondary">Ver boleta digital</a>
            <a href="<?= url('boleta/ver/' . $tokenBoleta) ?>" target="_blank" rel="noopener"
               class="btn btn--secondary">
                <span class="btn-icon btn-icon--print" aria-hidden="true"></span>
                Imprimir boleta
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Gestión de la matrícula (operaciones diferenciadas) ────── -->
<?php if ($puedeGestionar): ?>
<div class="card mb-lg">
    <div class="card__body">
        <p class="form-section-title">Gestión de la matrícula</p>

        <?php if (!$esActivo): ?>
            <?php if (!empty($pendientes)): ?>
            <div class="mat-pendientes">
                <p class="mat-pendientes__titulo">Para activar, falta completar:</p>
                <ul class="mat-pendientes__list">
                    <?php foreach ($pendientes as $pend): ?>
                    <li><?= e($pend) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php else: ?>
            <div class="mat-accion mat-accion--safe">
                <div class="mat-accion__info">
                    <span class="mat-accion__titulo">Activar matrícula</span>
                    <span class="mat-accion__desc">Cumple todos los requisitos. Al activarla contará para boletas, notas y orden de mérito.</span>
                </div>
                <div class="mat-accion__control">
                    <form method="POST" action="<?= url('matriculas/' . $mid . '/activar') ?>"
                          onsubmit="return confirm('¿Activar esta matrícula?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Activar matrícula</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Desactivar (requiere motivo) — el motivo se despliega al pulsar
                 "Desactivar". Mejora progresiva: sin JS, el formulario se ve abierto. -->
            <div class="mat-accion mat-accion--danger">
                <div class="mat-accion__info">
                    <span class="mat-accion__titulo">Desactivar matrícula</span>
                    <span class="mat-accion__desc">Conserva el tipo, pero desactiva el acceso del apoderado y oculta sus boletas públicas. Requiere un motivo.</span>
                </div>
                <div class="mat-accion__control" data-desactivar-control hidden>
                    <button type="button" class="btn btn--danger" data-desactivar-toggle>Desactivar</button>
                </div>
                <form method="POST" action="<?= url('matriculas/' . $mid . '/desactivar') ?>"
                      class="mat-desactivar-form" data-desactivar-form
                      onsubmit="return confirm('¿Desactivar esta matrícula? Se desactivará el acceso del apoderado y sus boletas públicas.')">
                    <?= csrf_field() ?>
                    <label class="form-label" for="motivo_desactivar">Motivo de la desactivación <span class="text-danger">*</span></label>
                    <textarea id="motivo_desactivar" name="motivo" class="form-input" rows="2"
                              required placeholder="Indica por qué se desactiva la matrícula"></textarea>
                    <div class="btn-group">
                        <button type="button" class="btn btn--secondary" data-desactivar-cancel hidden>Cancelar</button>
                        <button type="submit" class="btn btn--danger">Confirmar baja</button>
                    </div>
                </form>
            </div>

            <!-- Trasladar de colegio -->
            <div class="mat-accion mat-accion--danger">
                <div class="mat-accion__info">
                    <span class="mat-accion__titulo">Trasladar de colegio</span>
                    <span class="mat-accion__desc">Genera la constancia de traslado a otra institución educativa y da de baja al estudiante.</span>
                </div>
                <div class="mat-accion__control">
                    <a href="<?= url('matriculas/' . $mid . '/trasladar') ?>" class="btn btn--danger">Trasladar</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Retorno de grado: la operación más delicada. Se oculta si ya hay un
             retorno activo (en ese caso, la reversión se ofrece en su tarjeta). -->
        <?php if (!($retorno && ($retorno['estado'] ?? 'activo') === 'activo')): ?>
        <div class="mat-accion mat-accion--critico">
            <div class="mat-accion__info">
                <span class="mat-accion__titulo">⚠ Retorno de grado</span>
                <span class="mat-accion__desc">Caso especial y auditable: crea una matrícula operativa en un grado inferior; el estudiante asiste y compite en ese grado. La matrícula oficial se conserva. Requiere un motivo.</span>
            </div>
            <div class="mat-accion__control">
                <a href="<?= url('matriculas/' . $mid . '/retorno') ?>" class="btn btn--danger">Retorno de grado</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rectificación de calificaciones: corrige notas ya cerradas o
             bloqueadas con auditoría obligatoria (módulo aparte). -->
        <div class="mat-accion">
            <div class="mat-accion__info">
                <span class="mat-accion__titulo">Rectificar calificaciones</span>
                <span class="mat-accion__desc">Corrige notas de bimestres cerrados o competencias bloqueadas dejando traza de auditoría con el motivo. Regenera el orden de mérito del bimestre.</span>
            </div>
            <div class="mat-accion__control">
                <a href="<?= url('rectificaciones/matricula/' . $mid) ?>" class="btn btn--secondary">Rectificar notas</a>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>
