<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'Iniciar sesión') ?> — SIGA-COCIAP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/auth.css') ?>">
</head>
<body class="auth-body">

    <div class="auth-wrapper">

        <!-- Panel izquierdo — identidad institucional -->
        <div class="auth-brand">
            <div class="auth-brand__inner">
                <div class="auth-brand__logo">
                    <img src="<?= url('assets/img/logo_cociap.png') ?>" 
                        alt="Logo_COCIAP" 
                        style="width:52px;height:52px;object-fit:contain;">
                </div>
                <h1 class="auth-brand__sistema">SIGA-COCIAP</h1>
                <p class="auth-brand__nombre">Sistema Integrado de<br>Gestión Académica</p>
                <div class="auth-brand__divider"></div>
                <p class="auth-brand__institucion">
                    Colegio de Aplicación<br>
                    <strong>"Víctor Valenzuela Guardia"</strong>
                </p>
                <p class="auth-brand__unasam">UNASAM · Huaraz, Ancash</p>
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
