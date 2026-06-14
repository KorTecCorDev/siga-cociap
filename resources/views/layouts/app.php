<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'SIGA-COCIAP') ?> — SIGA-COCIAP</title>
    <meta name="base-url"   content="<?= rtrim(url(''), '/') ?>">
    <meta name="csrf-token" content="<?= \Core\Session::csrfToken() ?>">
    <meta name="theme-color" content="#1a3a5c">
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/logo_cociap.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="app-body">

    <!-- Loader de transición entre páginas (barra + overlay con logo) -->
    <div class="app-loader" id="appLoader" aria-hidden="true">
        <div class="app-loader__bar"></div>
        <div class="app-loader__overlay">
            <img src="<?= url('assets/img/logo_cociap.png') ?>" alt="" class="app-loader__logo">
            <span class="app-loader__nombre">SIGA-COCIAP</span>
            <span class="app-loader__spinner"></span>
        </div>
    </div>

    <!-- Navbar superior -->
    <nav class="navbar">
        <div class="navbar__brand">
            <img src="<?= url('assets/img/logo_cociap.png') ?>
                "alt="COCIAP" class="navbar__logo">
            <span class="navbar__nombre">SIGA-COCIAP</span>
        </div>

        <div class="navbar__user">
            <span class="navbar__rol">
                <?= e($auth_user['rol_nombre'] ?? '') ?>
            </span>
            <span class="navbar__nombre-usuario">
                <?= e(nombre_corto($auth_user['nombres'] ?? '', $auth_user['apellido_paterno'] ?? '')) ?>
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

    <footer class="app-footer">
        <span class="app-footer__copy">
            &copy; <?= date('Y') ?> SIGA-COCIAP &mdash; Todos los derechos reservados.
        </span>
        <span class="app-footer__meta">
            <span class="app-footer__version">v1.0.0</span>
            <span class="app-footer__sep">&bull;</span>
            <span class="app-footer__author">Desarrollado por <strong>KorTecCorDev</strong></span>
        </span>
    </footer>

    <?php foreach ($page_scripts ?? [] as $script): ?>
    <script src="<?= url('js/' . $script . '.js') ?>"></script>
    <?php endforeach; ?>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>