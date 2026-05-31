<?php
/**
 * @var array $matricula
 * @var array $vinculos
 * @var array $documentos
 * @var array $notasExternas
 * @var array $tiposVinculo
 * @var array|null $retorno
 * @var bool  $puedeGestionar
 * @var array $pendientes  requisitos faltantes para activar (vacío = completa)
 */
$pendientes = $pendientes ?? [];
$mid = (int) $matricula['id'];

$labelEstado = fn(string $e): string => match($e) {
    'pendiente'          => 'Pendiente',
    'activo', 'aprobada' => 'Activo',
    'desactivado'        => 'Desactivado',
    default              => ucfirst($e),
};
$esActivo = in_array($matricula['estado'], ['activo', 'aprobada'], true);
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
        </p>
    </div>
</div>

<div class="mat-detalle-grid">

    <!-- Datos del estudiante -->
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Estudiante</p>
            <div class="info-grid">
                <div class="info-item"><span class="info-item__label">DNI</span><span class="info-item__value"><?= e($matricula['dni']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Sexo</span><span class="info-item__value"><?= $matricula['sexo'] === 'F' ? 'Femenino' : ($matricula['sexo'] === 'M' ? 'Masculino' : '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Nivel / Grado</span><span class="info-item__value"><?= e(($matricula['nivel_nombre'] ?? '—') . ' · ' . ($matricula['grado_nombre'] ?? '')) ?></span></div>
                <div class="info-item"><span class="info-item__label">Sección</span><span class="info-item__value"><?= e($matricula['seccion_nombre'] ?? '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Año académico</span><span class="info-item__value"><?= e((string) $matricula['anio']) ?></span></div>
                <div class="info-item"><span class="info-item__label">Serie del recibo</span><span class="info-item__value"><?= e($matricula['serie_recibo'] ?? '—') ?></span></div>
                <div class="info-item"><span class="info-item__label">Fecha de registro</span><span class="info-item__value"><?= $matricula['fecha_registro'] ? fecha_es($matricula['fecha_registro']) : '—' ?></span></div>
            </div>
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

    <!-- Retorno de grado -->
    <?php if ($retorno): ?>
    <div class="card">
        <div class="card__body">
            <p class="form-section-title">Retorno de grado</p>
            <div class="retorno-aviso">
                Asiste al grado operativo <strong><?= e($retorno['grado_destino'] ?? '—') ?></strong>
                desde el <?= fecha_es($retorno['fecha_retorno']) ?>.
                <br>Motivo: <?= e($retorno['motivo']) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Acciones -->
<div class="card mb-lg">
    <div class="card__body">
        <p class="form-section-title">Acciones</p>
        <div class="btn-group">
            <?php if (in_array($matricula['estado'], ['aprobada', 'activo'], true)): ?>
                <a href="<?= url('boleta/digital/' . $mid . '/1') ?>" class="btn btn--secondary">Ver boleta</a>
            <?php endif; ?>

            <?php if ($puedeGestionar): ?>
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
                    <form method="POST" action="<?= url('matriculas/' . $mid . '/activar') ?>"
                          onsubmit="return confirm('¿Activar esta matrícula?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--primary">Activar matrícula</button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                <form method="POST" action="<?= url('matriculas/' . $mid . '/desactivar') ?>"
                      onsubmit="return confirm('¿Desactivar y marcar como trasladada? Se desactivará el acceso del apoderado y sus boletas públicas.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--danger">Desactivar / Trasladar</button>
                </form>
                <?php endif; ?>

                <a href="<?= url('matriculas/' . $mid . '/retorno') ?>" class="btn btn--secondary">Retorno de grado</a>
            <?php endif; ?>
        </div>
    </div>
</div>
