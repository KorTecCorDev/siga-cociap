<?php
/**
 * @var array  $seccion      { id, grado_nombre, seccion_nombre, nivel_nombre }
 * @var array  $periodos     [{ id, numero, nombre_display, editable }]
 * @var array  $estudiantes  [{ matricula_id, nombre_completo, dni, conducta[periodo_id] }]
 * @var array  $literales    ['AD','A','B','C']
 */

$csrfToken = \Core\Session::csrfToken();
?>

<div class="page-header">
    <a href="<?= url('admin/conducta') ?>" class="btn btn--secondary btn--sm">← Volver</a>
    <div>
        <h1 class="page-title">
            Conducta — <?= e($seccion['grado_nombre']) ?> <?= e($seccion['seccion_nombre']) ?>
        </h1>
        <p class="page-subtitle"><?= e($seccion['nivel_nombre']) ?></p>
    </div>
</div>

<div class="conducta-leyenda">
    <strong>Escala:</strong>
    <span class="conducta-lit conducta-lit--ad">AD</span> Muy bueno &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--a">A</span> Bueno &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--b">B</span> En proceso &nbsp;·&nbsp;
    <span class="conducta-lit conducta-lit--c">C</span> Inicio
</div>

<div id="conducta-feedback" class="conducta-feedback" style="display:none" role="status" aria-live="polite"></div>

<?php if (empty($estudiantes)): ?>
    <div class="empty-state">
        <p>No hay estudiantes matriculados en esta sección.</p>
    </div>
<?php else: ?>

<div class="table-responsive">
    <table class="table conducta-tabla">
        <thead>
            <tr>
                <th class="conducta-th-num">N°</th>
                <th class="conducta-th-nombre">Apellidos y Nombres</th>
                <th class="conducta-th-dni">DNI</th>
                <?php foreach ($periodos as $p): ?>
                    <th class="conducta-th-periodo <?= $p['editable'] ? 'conducta-th-periodo--activo' : '' ?>">
                        <?= e($p['nombre_display']) ?>
                        <?php if ($p['editable']): ?>
                            <span class="conducta-badge-editable">editable</span>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($estudiantes as $i => $est): ?>
                <tr class="conducta-fila <?= $i % 2 !== 0 ? 'conducta-fila--alt' : '' ?>">
                    <td class="conducta-td-num"><?= $i + 1 ?></td>
                    <td class="conducta-td-nombre"><?= e($est['nombre_completo']) ?></td>
                    <td class="conducta-td-dni"><?= e($est['dni']) ?></td>
                    <?php foreach ($periodos as $p):
                        $val = $est['conducta'][$p['id']] ?? '';
                    ?>
                        <td class="conducta-td-periodo">
                            <?php if ($p['editable']): ?>
                                <select class="conducta-select js-conducta-select"
                                        data-matricula="<?= $est['matricula_id'] ?>"
                                        data-periodo="<?= $p['id'] ?>"
                                        data-csrf="<?= e($csrfToken) ?>">
                                    <option value="">— sin nota —</option>
                                    <?php foreach ($literales as $lit): ?>
                                        <option value="<?= $lit ?>" <?= $val === $lit ? 'selected' : '' ?>>
                                            <?= $lit ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($val): ?>
                                <span class="conducta-lit conducta-lit--<?= strtolower($val) ?>">
                                    <?= e($val) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<script>
(function () {
    const feedback = document.getElementById('conducta-feedback');
    let timer;

    function mostrar(msg, tipo) {
        feedback.textContent = msg;
        feedback.className = 'conducta-feedback conducta-feedback--' + tipo;
        feedback.style.display = 'block';
        clearTimeout(timer);
        timer = setTimeout(() => { feedback.style.display = 'none'; }, 3000);
    }

    document.querySelectorAll('.js-conducta-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            const matriculaId = this.dataset.matricula;
            const periodoId   = this.dataset.periodo;
            const literal     = this.value;
            const csrf        = this.dataset.csrf;
            const original    = this.dataset.original ?? this.value;

            this.dataset.original = this.value;
            this.disabled = true;

            fetch('<?= url('admin/conducta/guardar') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    matricula_id: matriculaId,
                    periodo_id:   periodoId,
                    literal:      literal,
                    _csrf_token:  csrf,
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    mostrar('✓ Guardado', 'ok');
                } else {
                    mostrar('Error: ' + data.mensaje, 'error');
                    this.value = original;
                }
            })
            .catch(() => {
                mostrar('Error de conexión.', 'error');
                this.value = original;
            })
            .finally(() => { this.disabled = false; });
        });
    });
})();
</script>
