<?php
/**
 * Contenido de indicadores para el MODAL de cierre (show.php): cards de grado
 * y ranking de docentes, apilados. La página completa (stats.php) usa un
 * layout propio con panel lateral; por eso aquí solo se reusan los sub-partials.
 *
 * @var array $stats  ['por_grado' => [...], 'docentes' => [...]]
 */
$porGrado = $stats['por_grado'] ?? [];
$docentes = $stats['docentes'] ?? [];
?>
<div class="stats-cierre">
    <p class="text-muted text-sm cierre-intro">
        Indicadores calculados al cierre del bimestre.
    </p>
    <?php require __DIR__ . '/_grados.php'; ?>
    <?php require __DIR__ . '/_docentes.php'; ?>
</div>
