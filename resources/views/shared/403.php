<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso denegado — SIGA-COCIAP</title>
    <style>
        body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#334155}
        .box{text-align:center;padding:40px}
        .code{font-size:96px;font-weight:700;color:#fee2e2;line-height:1}
        h1{font-size:24px;margin:12px 0 8px}
        p{color:#64748b;margin:0 0 24px}
        a{display:inline-block;padding:10px 24px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-size:14px}
    </style>
</head>
<body>
    <div class="box">
        <div class="code">403</div>
        <h1>Acceso denegado</h1>
        <p>No tienes permisos para acceder a esta sección.</p>
        <a href="<?= url('dashboard') ?>">Volver al inicio</a>
    </div>
</body>
</html>
