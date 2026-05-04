<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'SIGA-COCIAP') ?> — SIGA-COCIAP</title>
    <meta name="base-url" content="<?= url('') ?>">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="app-body">

    <!-- Navbar superior -->
    <nav class="navbar">
        <div class="navbar__brand">
            <img src="<?= url('assets/img/logo-cociap.png') ?>"
                 alt="COCIAP" class="navbar__logo">
            <span class="navbar__nombre">SIGA-COCIAP</span>
        </div>

        <div class="navbar__user">
            <span class="navbar__rol">
                <?= e($auth_user['rol_nombre'] ?? '') ?>
            </span>
            <span class="navbar__nombre-usuario">
                <?= e(($auth_user['nombres'] ?? '') . ' ' . ($auth_user['apellido_paterno'] ?? '')) ?>
            </span>
            <a href="<?= url('logout') ?>" class="navbar__logout">
                Cerrar sesión
            </a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="app-main">

        <!-- Alertas flash -->
        <?php if (!empty($flash_success)): ?>
            <div class="flash flash--success">
                <span>✓</span> <?= e($flash_success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_error)): ?>
            <div class="flash flash--error">
                <span>⚠</span> <?= e($flash_error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash_warning)): ?>
            <div class="flash flash--warning">
                <span>⚡</span> <?= e($flash_warning) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>

    </main>

    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>