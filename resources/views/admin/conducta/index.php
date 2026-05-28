<?php
/**
 * @var array       $porNivel       { nivel_nombre => [ seccion[] ] }
 * @var array       $periodos       [{ id, numero, nombre_display, editable }]
 * @var array|null  $periodoActivo  { id, nombre_display, ... } o null
 * @var array       $progreso       [ seccion_id => [esperados, calificados] ]
 */
?>

<div class="page-header">
    <a href="<?= url('/') ?>" class="btn btn--secondary btn--sm">← Dashboard</a>
    <div>
        <h1 class="page-title">Calificaciones de Conducta</h1>
        <p class="page-subtitle">
            <?php if ($periodoActivo): ?>
                Selecciona una sección para registrar las calificaciones del
                <strong><?= e($periodoActivo['nombre_display']) ?></strong>.
            <?php else: ?>
                No hay periodo abierto. Comunícate con Registro Académico.
            <?php endif; ?>
        </p>
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
            <?php foreach ($secciones as $s):
                $datos       = $progreso[(int) $s['id']] ?? ['esperados' => 0, 'calificados' => 0];
                $esperados   = $datos['esperados'];
                $calificados = $datos['calificados'];
                $pct         = $esperados > 0 ? (int) round($calificados * 100 / $esperados) : 0;
                $completo    = $esperados > 0 && $calificados >= $esperados;
            ?>
                <a href="<?= url('admin/conducta/' . $s['id']) ?>"
                   class="conducta-seccion-card">
                    <span class="conducta-seccion-card__grado"><?= e($s['grado_nombre']) ?></span>
                    <span class="conducta-seccion-card__nombre">Sección <?= e($s['seccion_nombre']) ?></span>

                    <?php if ($periodoActivo): ?>
                        <div class="conducta-progreso<?= $completo ? ' conducta-progreso--completo' : '' ?>">
                            <?php if ($esperados === 0): ?>
                                <span class="conducta-progreso__vacio">Sin estudiantes</span>
                            <?php else: ?>
                                <div class="conducta-progreso__track"
                                     role="progressbar"
                                     aria-valuenow="<?= $pct ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100"
                                     aria-label="Progreso de conducta">
                                    <div class="conducta-progreso__fill" style="--pct:<?= $pct ?>%"></div>
                                </div>
                                <div class="conducta-progreso__meta">
                                    <span class="conducta-progreso__conteo">
                                        <?= $calificados ?>/<?= $esperados ?>
                                    </span>
                                    <span class="conducta-progreso__pct"><?= $pct ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
