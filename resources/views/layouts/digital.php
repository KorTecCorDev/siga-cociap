<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo ?? 'Boleta Digital') ?> — SIGA-COCIAP</title>
    <meta name="base-url" content="<?= rtrim(url(''), '/') ?>">
    <meta name="theme-color" content="#1a3a5c">
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/logo_cociap.png') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="bd-body">

    <?= $content ?>

    <script src="<?= url('js/qrcode.min.js') ?>"></script>
    <script src="<?= url('js/boleta-digital.js') ?>"></script>
</body>
</html>
