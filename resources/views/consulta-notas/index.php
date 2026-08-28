<?php
/**
 * Consulta de calificaciones (solo lectura) — selector de periodo + secciones.
 * @var array      $periodos
 * @var int        $periodoId
 * @var array|null $periodo
 * @var array      $secciones  [{seccion_id, grado_nombre, seccion_nombre, nivel_nombre, areas, competencias}]
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Consulta de calificaciones</h1>
        <p class="page-subtitle">
            Solo lectura. Muestra únicamente las notas oficiales (competencias aprobadas y bloqueadas).
            Para corregir, usa <a href="<?= url('rectificaciones') ?>">Rectificación</a>.
        </p>
    </div>
</div>

<?php // El selector de bimestre (el AMBITO, comun a las tres pestañas) y el
      // conmutador de ejes viven juntos en `_nav.php`: salen del mismo mapa de
      // rutas. La card va ENCIMA de las pestañas — debajo se leia como contenido
      // de esta pestaña, cuando manda sobre las tres. ?>
<?php $ejeActivo = 'secciones'; ?>
<?php require VIEW_PATH . '/consulta-notas/_nav.php'; ?>

<?php if ($periodo === null): ?>
    <div class="empty-state"><p>Selecciona un periodo para ver las secciones con notas oficiales.</p></div>
<?php elseif (empty($secciones)): ?>
    <div class="empty-state"><p>No hay notas oficiales (competencias bloqueadas) en este periodo.</p></div>
<?php else: ?>
    <div class="consulta-grid">
        <?php foreach ($secciones as $s): ?>
            <a class="consulta-card"
               href="<?= url('consulta-notas/' . $periodoId . '/seccion/' . (int) $s['seccion_id']) ?>">
                <div class="consulta-card__titulo"><?= e($s['grado_nombre'] . ' ' . $s['seccion_nombre']) ?></div>
                <div class="consulta-card__nivel"><?= e($s['nivel_nombre']) ?></div>
                <div class="consulta-card__meta">
                    <?= (int) $s['areas'] ?> área(s) · <?= (int) $s['competencias'] ?> competencia(s) oficial(es)
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
