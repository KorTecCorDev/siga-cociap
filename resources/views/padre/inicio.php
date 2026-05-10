<?php /** @var array|null $hijo @var array|null $periodo @var array $alertas */ ?>

<div class="page-header">
    <h1 class="page-title">Panel del padre de familia</h1>
    <?php if ($periodo): ?>
        <span class="badge badge--activo">
            <?= e($periodo['nombre_display']) ?> — <?= e($periodo['anio']) ?>
        </span>
    <?php endif; ?>
</div>

<?php if (!$hijo): ?>
    <div class="empty-state">
        <p>No se encontró un estudiante vinculado a tu cuenta.</p>
        <p>Comunícate con el personal de Registro Académico.</p>
    </div>
<?php else: ?>

    <!-- Tarjeta del estudiante -->
    <div class="card mb-lg">
        <div class="card__header">
            <h2 class="card__title">Datos del estudiante</h2>
            <span class="badge badge--<?= $hijo['estado_matricula'] === 'aprobada' ? 'activo' : 'warning' ?>">
                Matrícula <?= e($hijo['estado_matricula']) ?>
            </span>
        </div>
        <div class="card__body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-item__label">Apellidos y nombres</span>
                    <span class="info-item__value">
                        <?= e($hijo['nombre_completo']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-item__label">DNI</span>
                    <span class="info-item__value"><?= e($hijo['dni']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-item__label">Grado y sección</span>
                    <span class="info-item__value">
                        <?= e($hijo['grado_nombre']) ?> —
                        Sección <?= e($hijo['seccion_nombre']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-item__label">Nivel</span>
                    <span class="info-item__value"><?= e($hijo['nivel_nombre']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="cards">
        <div class="card">
            <a href="<?= url('padre/notas') ?>">
                <div class="card__icon">📊</div>
                <div class="card__title">Ver notas</div>
                <div class="card__desc">
                    Informe de Progreso de las competencias del estudiante al <?= e($periodo['nombre_display'] ?? 'periodo actual') ?>
                </div>
            </a>
        </div>
        <div class="card">
            <a href="<?= url('padre/alertas') ?>">
                <div class="card__icon">
                    🔔<?php if (!empty($alertas)): ?>
                        <span class="badge-count">
                            <?= count(array_filter($alertas, fn($a) => !$a['leida'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card__title">Alertas del tutor</div>
                <div class="card__desc">
                    <?= empty($alertas) ? 'Sin alertas' : count($alertas) . ' alerta(s)' ?>
                </div>
            </a>
        </div>
    </div>

<?php endif; ?>