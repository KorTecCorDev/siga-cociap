<?php /** @var array $auth_user */ ?>

<div class="welcome">
    <h1>Bienvenido, <?= e(nombre_corto($auth_user['nombres'] ?? '', $auth_user['apellido_paterno'] ?? '')) ?><img class="welcome__wave" src="<?= url('assets/icons/hand-saludo.svg') ?>" alt="" aria-hidden="true"></h1>
    <p>Panel de <?= e($auth_user['rol_nombre']) ?> · <?= date('Y') ?></p>
</div>

<?php if ($flash_success): ?>
    <div class="flash flash--success">✓ <?= e($flash_success) ?></div>
<?php endif; ?>

<?php
// Módulos del dashboard agrupados por categoría. Cada módulo declara los
// roles que pueden verlo; un grupo solo se renderiza si el rol actual ve
// al menos un módulo, así no aparecen títulos de sección huérfanos.
$grupos = [
    'Gestión académica' => [
        ['roles' => ['admin', 'director_general', 'director_ebr', 'registro_academico'], 'url' => 'director/anios',         'icon' => 'calendar.svg',          'titulo' => 'Año académico',        'desc' => 'Periodos, secciones y cargas'],
        ['roles' => ['admin', 'director_general', 'director_ebr', 'registro_academico'], 'url' => 'director/cargas',        'icon' => 'files-packed.svg',     'titulo' => 'Cargas académicas',    'desc' => 'Gestión de cargas docentes'],
        ['roles' => ['admin', 'registro_academico', 'secretaria_academica', 'secretaria_administrativa'], 'url' => 'matriculas',            'icon' => 'doc-add.svg',          'titulo' => 'Matrículas',           'desc' => 'Registro y seguimiento de matrículas'],
        ['roles' => ['admin', 'registro_academico', 'secretaria_academica', 'secretaria_administrativa', 'director_general', 'director_ebr'], 'url' => 'admin/buscar-estudiante', 'icon' => 'lupa-look.svg', 'titulo' => 'Buscar estudiante',    'desc' => 'Consultar nivel, grado y sección por DNI o nombre'],
        ['roles' => ['admin'],                                                           'url' => 'admin/secciones',       'icon' => 'users-group-rounded.svg',     'titulo' => 'Secciones y Tutores',  'desc' => 'Asignar tutores por sección'],
    ],
    'Evaluación y reportes' => [
        ['roles' => ['admin', 'registro_academico', 'director_general', 'director_ebr'], 'url' => 'admin/control',          'icon' => 'remote-controller.svg',      'titulo' => 'Centro de Control',    'desc' => 'Inconsistencias operativas pendientes'],
        ['roles' => ['admin', 'registro_academico'],                                     'url' => 'admin/conducta',        'icon' => 'social-city.svg',   'titulo' => 'Conducta',             'desc' => 'Calificaciones de comportamiento - Auxiliares académicos'],
        ['roles' => ['admin', 'registro_academico'],                                     'url' => 'admin/asistencia',      'icon' => 'calendar-add.svg',      'titulo' => 'Asistencia',           'desc' => 'Registro de faltas y tardanzas por sección'],
        ['roles' => ['admin', 'director_general', 'director_ebr', 'registro_academico'], 'url' => 'director/bloqueos',     'icon' => 'key-unblocked.svg',      'titulo' => 'Bloqueos del bimestre','desc' => 'Gestionar permisos de edición de notas'],
        ['roles' => ['admin', 'director_general', 'director_ebr', 'registro_academico'], 'url' => 'director/orden-merito', 'icon' => 'medal-ribbon-star.svg', 'titulo' => 'Orden de mérito',      'desc' => 'Ranking bimestral por grado'],
        ['roles' => ['admin', 'registro_academico'],                                     'url' => 'admin/boletas-publicas','icon' => 'file-send.svg',         'titulo' => 'Boletas públicas',     'desc' => 'Generar y distribuir boletas con código QR'],
        ['roles' => ['admin', 'registro_academico'],                                     'url' => 'rectificaciones',       'icon' => 'edit-pen.svg',      'titulo' => 'Rectificación de notas','desc' => 'Corregir notas cerradas o bloqueadas con auditoría'],
    ],
    'Administración' => [
        ['roles' => ['admin', 'registro_academico'],                                     'url' => 'admin/usuarios',        'icon' => 'user-plus.svg',         'titulo' => 'Usuarios',             'desc' => 'Gestionar cuentas del sistema'],
        ['roles' => ['admin'],                                                           'url' => 'admin/director-ebr',    'icon' => 'maletin-elegante.svg',         'titulo' => 'Director EBR',         'desc' => 'Historial y asignación del Director EBR'],
    ],
];
?>

<?php $g = 0; foreach ($grupos as $tituloGrupo => $modulos):
    $visibles = array_filter($modulos, fn($m) => has_role($m['roles']));
    if (empty($visibles)) continue;
    $g++;
?>
<section class="dash-grupo" aria-labelledby="dash-grupo-<?= $g ?>">
    <h2 id="dash-grupo-<?= $g ?>" class="dash-grupo__titulo"><?= e($tituloGrupo) ?></h2>
    <div class="cards">
        <?php foreach ($visibles as $m): ?>
        <div class="card">
            <a href="<?= url($m['url']) ?>" aria-label="<?= e($m['titulo'] . ': ' . $m['desc']) ?>">
                <div class="card__icon"><img src="<?= url('assets/icons/' . $m['icon']) ?>" alt="" aria-hidden="true"></div>
                <div class="card__title"><?= e($m['titulo']) ?></div>
                <div class="card__desc"><?= e($m['desc']) ?></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>
