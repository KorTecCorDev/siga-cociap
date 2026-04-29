<?php /** @var array $auth_user */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — SIGA-COCIAP</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:system-ui,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh}
        .topbar{background:#1e3a5f;color:#fff;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
        .topbar__brand{font-size:18px;font-weight:700;letter-spacing:.5px}
        .topbar__user{font-size:13px;display:flex;align-items:center;gap:16px}
        .topbar__rol{background:rgba(255,255,255,.15);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
        .topbar__logout{color:#93c5fd;font-size:12px;text-decoration:none}
        .topbar__logout:hover{color:#fff}
        .main{padding:32px 28px;max-width:1200px;margin:0 auto}
        .welcome{margin-bottom:32px}
        .welcome h1{font-size:24px;font-weight:700;color:#1e293b}
        .welcome p{color:#64748b;margin-top:4px;font-size:14px}
        .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
        .card{background:#fff;border-radius:12px;padding:24px;border:1px solid #e2e8f0;transition:box-shadow .2s}
        .card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
        .card a{text-decoration:none;color:inherit;display:block}
        .card__icon{font-size:32px;margin-bottom:12px}
        .card__title{font-size:15px;font-weight:600;margin-bottom:4px}
        .card__desc{font-size:12px;color:#94a3b8}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:8px}
        .alert--success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}
        .alert--warning{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
    </style>
</head>
<body>

<nav class="topbar">
    <span class="topbar__brand">🎓 SIGA-COCIAP</span>
    <div class="topbar__user">
        <span class="topbar__rol"><?= e($auth_user['rol_nombre']) ?></span>
        <span><?= e($auth_user['nombres'] . ' ' . $auth_user['apellido_paterno']) ?></span>
        <a href="<?= url('logout') ?>" class="topbar__logout">Cerrar sesión</a>
    </div>
</nav>

<main class="main">

    <?php if ($flash_success): ?>
        <div class="alert alert--success">✓ <?= e($flash_success) ?></div>
    <?php endif; ?>

    <div class="welcome">
        <h1>Bienvenido, <?= e($auth_user['nombres']) ?> 👋</h1>
        <p>Panel de <?= e($auth_user['rol_nombre']) ?> · SIGA-COCIAP <?= date('Y') ?></p>
    </div>

    <div class="cards">
        <?php if (has_role(['admin', 'registro_academico'])): ?>
            <div class="card">
                <a href="<?= url('admin/usuarios') ?>">
                    <div class="card__icon">👥</div>
                    <div class="card__title">Usuarios</div>
                    <div class="card__desc">Gestionar cuentas del sistema</div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (has_role(['admin', 'registro_academico', 'secretaria'])): ?>
            <div class="card">
                <a href="<?= url('secretaria/matriculas') ?>">
                    <div class="card__icon">📋</div>
                    <div class="card__title">Matrículas</div>
                    <div class="card__desc">Registro y seguimiento de matrículas</div>
                </a>
            </div>
        <?php endif; ?>

        <?php if (has_role(['admin', 'director_general', 'director_ebr', 'registro_academico'])): ?>
            <div class="card">
                <a href="<?= url('director/anios') ?>">
                    <div class="card__icon">📅</div>
                    <div class="card__title">Año académico</div>
                    <div class="card__desc">Periodos, secciones y cargas</div>
                </a>
            </div>
            <div class="card">
                <a href="<?= url('director/orden-merito') ?>">
                    <div class="card__icon">🏆</div>
                    <div class="card__title">Orden de mérito</div>
                    <div class="card__desc">Ranking bimestral por grado</div>
                </a>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>
