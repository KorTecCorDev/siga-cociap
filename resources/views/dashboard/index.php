<?php /** @var array $auth_user */ ?>

<div class="welcome">
    <h1>Bienvenido, <?= e($auth_user['nombres']) ?> </h1>
    <p>Panel de <?= e($auth_user['rol_nombre']) ?> · SIGA-COCIAP <?= date('Y') ?></p>
</div>

<?php if ($flash_success): ?>
    <div class="flash flash--success">✓ <?= e($flash_success) ?></div>
<?php endif; ?>

<div class="cards">
    <?php if (has_role(['admin', 'registro_academico'])): ?>
        <div class="card">
            <a href="<?= url('admin/usuarios') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/user-plus.svg') ?>" alt="Usuarios"></div>
                <div class="card__title">Usuarios</div>
                <div class="card__desc">Gestionar cuentas del sistema</div>
            </a>
        </div>
    <?php endif; ?>

    <?php if (has_role('admin')): ?>
        <div class="card">
            <a href="<?= url('admin/secciones') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/book-bookmark.svg') ?>" alt="Secciones"></div>
                <div class="card__title">Secciones y Tutores</div>
                <div class="card__desc">Asignar tutores por sección</div>
            </a>
        </div>
    <?php endif; ?>

    <?php if (has_role(['admin', 'registro_academico', 'secretaria'])): ?>
        <div class="card">
            <a href="<?= url('secretaria/matriculas') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/folder-2.svg') ?>" alt="Matrículas"></div>
                <div class="card__title">Matrículas</div>
                <div class="card__desc">Registro y seguimiento de matrículas</div>
            </a>
        </div>
    <?php endif; ?>

    <?php if (has_role(['admin', 'director_general', 'director_ebr', 'registro_academico'])): ?>
        <div class="card">
            <a href="<?= url('director/anios') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/calendar.svg') ?>" alt="Año académico"></div>
                <div class="card__title">Año académico</div>
                <div class="card__desc">Periodos, secciones y cargas</div>
            </a>
        </div>
        <div class="card">
            <a href="<?= url('director/orden-merito') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/medal-ribbon-star.svg') ?>" alt="Orden de mérito"></div>
                <div class="card__title">Orden de mérito</div>
                <div class="card__desc">Ranking bimestral por grado</div>
            </a>
        </div>
        <div class="card">
            <a href="<?= url('director/cargas') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/book-bookmark.svg') ?>" alt="Cargas académicas"></div>
                <div class="card__title">Cargas académicas</div>
                <div class="card__desc">Gestión de cargas docentes</div>
            </a>
        </div>
        <div class="card">
            <a href="<?= url('director/bloqueos') ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/folder-check.svg') ?>" alt="Bloqueos"></div>
                <div class="card__title">Bloqueos del bimestre</div>
                <div class="card__desc">Gestionar permisos de edición de notas</div>
            </a>
        </div>
    <?php endif; ?>
</div>
