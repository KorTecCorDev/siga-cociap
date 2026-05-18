<?php
/**
 * @var array  $porNivel  { nivel_nombre => [ seccion[] ] }
 * @var array  $periodos  [{ id, numero, nombre_display, editable }]
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Calificaciones de Conducta</h1>
        <p class="page-subtitle">Selecciona una sección para registrar o revisar las calificaciones de comportamiento.</p>
    </div>
</div>

<?php if (empty($porNivel)): ?>
    <div class="empty-state">
        <p>No hay secciones activas configuradas para el año académico en curso.</p>
    </div>
<?php else: ?>

    <?php foreach ($porNivel as $nivel => $secciones): ?>
        <h2 class="conducta-nivel-titulo"><?= e($nivel) ?></h2>
        <div class="conducta-secciones-grid">
            <?php foreach ($secciones as $s): ?>
                <a href="<?= url('admin/conducta/' . $s['id']) ?>"
                   class="conducta-seccion-card">
                    <span class="conducta-seccion-card__grado"><?= e($s['grado_nombre']) ?></span>
                    <span class="conducta-seccion-card__nombre">Sección <?= e($s['seccion_nombre']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
