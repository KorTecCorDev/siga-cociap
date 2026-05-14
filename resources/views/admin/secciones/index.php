<?php
/**
 * @var array $secciones  [{ id, seccion_nombre, grado_nombre, nivel_nombre, nivel_id,
 *                           tutor_id, tutor_apellido_paterno, tutor_apellido_materno,
 *                           tutor_nombres, tutor_dni, total_matriculados, es_unidocente }]
 * @var array $docentes   [{ id, apellido_paterno, apellido_materno, nombres, dni }]
 */

$nivelActual = null;

$nombreTutor = function (?array $s): string {
    if (!$s['tutor_id']) return '';
    return mb_strtoupper($s['tutor_apellido_paterno']) . ' '
         . mb_strtoupper($s['tutor_apellido_materno']) . ', '
         . $s['tutor_nombres'];
};
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Secciones y Tutores</h1>
        <p class="page-subtitle">
            <?= count($secciones) ?> sección<?= count($secciones) !== 1 ? 'es' : '' ?> registrada<?= count($secciones) !== 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<?php if (empty($secciones)): ?>
<div class="card">
    <div class="card__body">
        <p class="text-muted text-center">No hay secciones registradas para el año activo.</p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="tabla-notas-wrapper">
        <table class="tabla-notas">
            <thead>
                <tr>
                    <th>Sección</th>
                    <th class="text-center">Alumnos</th>
                    <th class="text-center">Unidocente</th>
                    <th>Tutor / Tutora asignado/a</th>
                    <th class="text-right">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($secciones as $s):
                if ($s['nivel_nombre'] !== $nivelActual):
                    $nivelActual = $s['nivel_nombre'];
            ?>
                <tr class="seccion-nivel-header">
                    <td colspan="5">
                        <span class="seccion-nivel-label"><?= e($s['nivel_nombre']) ?></span>
                    </td>
                </tr>
            <?php endif; ?>
                <tr>
                    <td>
                        <span class="seccion-nombre">
                            <?= e($s['grado_nombre']) ?> &ldquo;<?= e($s['seccion_nombre']) ?>&rdquo;
                        </span>
                    </td>
                    <td class="text-center text-sm">
                        <?= (int) $s['total_matriculados'] ?>
                    </td>
                    <td class="text-center">
                        <?php if ($s['es_unidocente']): ?>
                            <span class="badge badge--info">Sí</span>
                        <?php else: ?>
                            <span class="text-muted text-sm">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['tutor_id']): ?>
                            <div class="tutor-celda">
                                <div class="usuario-avatar usuario-avatar--docente tutor-avatar--sm">
                                    <?= mb_strtoupper(
                                        mb_substr($s['tutor_apellido_paterno'], 0, 1) .
                                        mb_substr($s['tutor_nombres'], 0, 1)
                                    ) ?>
                                </div>
                                <div>
                                    <div class="td-usuario__nombre">
                                        <?= e($nombreTutor($s)) ?>
                                    </div>
                                    <div class="td-usuario__sub">DNI <?= e($s['tutor_dni']) ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="tutor-sin-asignar">Sin tutor asignado</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <button type="button"
                                class="btn btn--sm btn--secondary"
                                onclick=”abrirModalTutor(<?= (int)$s['id'] ?>, <?= (int)($s['tutor_id'] ?? 0) ?>, <?= e(json_encode($s['grado_nombre'] . ' “' . $s['seccion_nombre'] . '”')) ?>, <?= e(json_encode($s['nivel_nombre'])) ?>)”>
                            <?= $s['tutor_id'] ? 'Cambiar' : 'Asignar' ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<!-- Modal de asignación de tutor -->
<div class="modal-overlay" id="modalTutor" hidden>
    <div class="modal-box">
        <div class="modal-header">
            <h2 class="modal-title">Asignar Tutor</h2>
            <button type="button" class="modal-cerrar" data-modal-cerrar="modalTutor" aria-label="Cerrar">✕</button>
        </div>
        <form method="POST" id="formTutor" novalidate>
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="modal-seccion-info">
                    <span class="modal-seccion-label" id="modalSeccionLabel"></span>
                    <span class="modal-nivel-badge" id="modalNivelBadge"></span>
                </div>

                <label class="form-label" for="tutor_id">Docente tutora/tutor</label>
                <select name="tutor_id" id="tutor_id" class="form-select">
                    <option value="">— Quitar tutor —</option>
                    <?php foreach ($docentes as $d): ?>
                    <option value="<?= (int) $d['id'] ?>">
                        <?= e(mb_strtoupper($d['apellido_paterno']) . ' ' . mb_strtoupper($d['apellido_materno']) . ', ' . $d['nombres']) ?>
                        &nbsp;(<?= e($d['dni']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <p class="text-sm text-muted mt-2">
                    Al asignar un tutor se crea automáticamente su carga de
                    <strong>Competencias Transversales</strong> para esta sección.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" data-modal-cerrar="modalTutor">
                    Cancelar
                </button>
                <button type="submit" class="btn btn--primary">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalTutor(seccionId, tutorActualId, label, nivel) {
    document.getElementById('modalSeccionLabel').textContent = label;

    const badge = document.getElementById('modalNivelBadge');
    badge.textContent = nivel;
    badge.className = 'modal-nivel-badge modal-nivel-badge--' +
        (nivel.toLowerCase().includes('prim') ? 'primaria' : 'secundaria');

    document.getElementById('formTutor').action =
        '<?= url('admin/secciones') ?>/' + seccionId + '/tutor';

    const sel = document.getElementById('tutor_id');

    // Limpiar etiqueta "(actual)" de ejecuciones previas y marcar el tutor actual
    Array.from(sel.options).forEach(o => {
        o.textContent = o.textContent.replace(/\s*\(actual\)$/, '');
    });
    sel.value = tutorActualId || '';
    if (tutorActualId) {
        const opActual = sel.querySelector(`option[value="${tutorActualId}"]`);
        if (opActual) opActual.textContent += ' (actual)';
    }

    Modal.abrir('modalTutor');
}

// Confirmación antes de quitar el tutor de la sección
document.getElementById('formTutor').addEventListener('submit', function(e) {
    if (document.getElementById('tutor_id').value === '') {
        if (!confirm('¿Confirma que desea quitar el tutor de esta sección?')) {
            e.preventDefault();
        }
    }
});
</script>
