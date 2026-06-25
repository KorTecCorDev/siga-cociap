<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'Iniciar sesión') ?> — SIGACOCIAP</title>
    <meta name="theme-color" content="#1a3a5c">
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= url('siga-cociap-logo-sin-nombre.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="auth-body">

    <div class="auth-wrapper">

        <!-- Panel izquierdo — identidad institucional -->
        <div class="auth-brand">
            <div class="auth-brand__inner">
                <div class="auth-brand__logo">
                    <img src="<?= url('assets/img/logo_cociap.png') ?>" 
                        alt="Logo_COCIAP">
                </div>
                <h1 class="auth-brand__sistema">SIGACOCIAP</h1>
                <p class="auth-brand__nombre">Sistema Integrado de<br>Gestión Académica</p>
                <div class="auth-brand__divider"></div>
                <p class="auth-brand__institucion">
                    Colegio de Aplicación<br>
                    <strong>"Víctor Valenzuela Guardia"</strong>
                </p>
                <p class="auth-brand__unasam">Todos los derechos reservados.</p>
            </div>
        </div>

        <!-- Panel derecho — formulario -->
        <div class="auth-form-panel">
            <div class="auth-form-panel__inner">
                <?= $content ?>
            </div>
        </div>

    </div>

    <script src="<?= url('js/auth.js') ?>"></script>
</body>
</html>
