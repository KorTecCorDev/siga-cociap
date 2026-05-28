<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo ?? 'Boleta') ?> — SIGA-COCIAP</title>
    <link rel="icon" type="image/x-icon" href="<?= url('favicon.ico') ?>">
    <meta name="base-url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
</head>
<body class="boleta-body">

    <?= $content ?>

    <div class="boleta-acciones">
        <a href="javascript:history.back()" class="btn-boleta btn-boleta--volver">← Volver</a>
        <button class="btn-boleta btn-boleta--imprimir" onclick="window.print()">🖨 Imprimir</button>
    </div>

</body>
</html>
