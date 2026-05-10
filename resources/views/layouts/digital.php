<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($titulo ?? 'Boleta Digital') ?> — SIGA-COCIAP</title>
    <meta name="base-url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>">
    <meta name="theme-color" content="#1a3a5c">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="bd-body">

    <?= $content ?>

    <script src="<?= url('js/boleta-digital.js') ?>"></script>
</body>
</html>
