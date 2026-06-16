<?php
/**
 * Conducta — ETAPA 2 (panel del tutor).
 *
 * @var array       $seccion       { id, nombre, grado_nombre, nivel_nombre, nivel_id }
 * @var array       $periodos      [{ id, numero, nombre_display, estado }]
 * @var array       $periodoSel    { id, nombre_display, estado }
 * @var array|null  $cierre        cierre vigente de RA o null (Etapa 1 no hecha)
 * @var array       $estudiantes   [{ matricula_id, nombre_completo, nota_ra, nota_tutor, nota_final, literal_final }]
 * @var bool        $editable      true si se puede editar/cerrar
 * @var bool        $cerradoTutor  true si el tutor ya cerro
 */

$csrfToken = \Core\Session::csrfToken();
$pid       = (int) $periodoSel['id'];
?>

<div class="page-header">
    <a href="<?= url('docente/mis-cargas') ?>" class="btn btn--secondary btn--sm">← Mis cargas</a>
    <div>
        <h1 class="page-title">Conducta — Sección <?= e($seccion['nombre']) ?></h1>
        <p class="page-subtitle">
            <?= e($seccion['grado_nombre']) ?> <?= e($seccion['nivel_nombre']) ?>
            · <strong><?= e($periodoSel['nombre_display']) ?></strong>
        </p>
    </div>
</div>

<div class="conducta-bimestres">
    <?php foreach ($periodos as $p): ?>
        <a href="<?= url('docente/conducta/' . (int) $p['id']) ?>"
           class="conducta-bimestre-tab<?= (int) $p['id'] === $pid ? ' conducta-bimestre-tab--activo' : '' ?>">
            <?= e($p['nombre_display']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div id="conducta-feedback" class="conducta-feedback" hidden role="status" aria-live="polite"></div>

<?php if (!$cierre): ?>

    <div class="conducta-espera">
        <div class="conducta-espera__icono" aria-hidden="true">⏳</div>
        <h2 class="conducta-espera__titulo">Conducta pendiente de Registro Académico</h2>
        <p class="conducta-espera__texto">
            Todavía los auxiliares académicos no han registrado sus calificaciones de conducta
            de esta sección para el <strong><?= e($periodoSel['nombre_display']) ?></strong>.
            Consulte con Registro Académico para más información.
        </p>
    </div>

<?php elseif (empty($estudiantes)): ?>

    <div class="empty-state"><p>No hay estudiantes matriculados en esta sección.</p></div>

<?php else: ?>

    <?php if ($cerradoTutor): ?>
        <div class="alert alert--success">
            ✓ Conducta <strong>cerrada y aprobada</strong> el <?= e(fechaLima($cierre['tutor_cerrado_en'])) ?>.
            Para modificarla, solicita el desbloqueo a Dirección.
        </div>
    <?php else: ?>
        <div class="alert alert--info">
            🔒 Registro Académico bloqueó la conducta el <?= e(fechaLima($cierre['ra_bloqueado_en'])) ?>.
            Puedes agregar tu nota (00–20, opcional) por estudiante; se promedia con la de RA.
            Cuando termines, <strong>cierra y aprueba</strong> la sección.
        </div>
    <?php endif; ?>

    <div class="tabla-notas-wrapper">
        <table class="tabla-notas conducta-tutor-tabla">
            <thead>
                <tr>
                    <th class="col-num">N°</th>
                    <th class="col-nombre">Apellidos y Nombres</th>
                    <th class="conducta-th-nota" title="Nota de Registro Académico">Nota RA</th>
                    <th class="conducta-th-nota" title="Tu nota (opcional)">Tu nota</th>
                    <th class="conducta-th-nota" title="Promedio final (.5 a favor)">Final</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $i => $est): ?>
                    <tr class="conducta-tutor-fila"
                        data-matricula="<?= (int) $est['matricula_id'] ?>"
                        data-periodo="<?= $pid ?>"
                        data-csrf="<?= e($csrfToken) ?>"
                        data-nota-ra="<?= (int) $est['nota_ra'] ?>">
                        <td class="col-num"><?= $i + 1 ?></td>
                        <td class="col-nombre"><?= e($est['nombre_completo']) ?></td>
                        <td class="conducta-td-nota"><?= fmt_nota((int) $est['nota_ra']) ?></td>
                        <td class="conducta-td-nota">
                            <?php if ($editable): ?>
                                <input type="number" class="conducta-nota-tutor" min="0" max="20" step="1"
                                       inputmode="numeric" autocomplete="off"
                                       value="<?= $est['nota_tutor'] !== null ? (int) $est['nota_tutor'] : '' ?>"
                                       aria-label="Tu nota para <?= e($est['nombre_completo']) ?>">
                                <span class="conducta-status" aria-live="polite"></span>
                            <?php else: ?>
                                <?= $est['nota_tutor'] !== null ? fmt_nota((int) $est['nota_tutor']) : '—' ?>
                            <?php endif; ?>
                        </td>
                        <td class="conducta-td-nota conducta-final">
                            <span class="conducta-final__nota"><?= fmt_nota((int) $est['nota_final']) ?></span>
                            <span class="conducta-final__lit cc-nota--<?= strtolower($est['literal_final']) ?>">
                                (<?= e($est['literal_final']) ?>)
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($editable): ?>
        <form id="conducta-cerrar-form" class="conducta-bloqueo-form"
              data-action="<?= url('docente/conducta/' . $pid . '/cerrar') ?>"
              data-csrf="<?= e($csrfToken) ?>">
            <div class="conducta-bloqueo-info">
                Tu nota es opcional: puedes cerrar sin agregarla (la final será la nota de RA).
            </div>
            <button type="submit" class="btn btn--success">✓ Cerrar y aprobar sección</button>
        </form>
    <?php endif; ?>

<?php endif; ?>
