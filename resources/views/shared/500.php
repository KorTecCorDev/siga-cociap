<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor — SIGA-COCIAP</title>
    <style>
        body{font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;color:#334155}
        .box{text-align:center;padding:40px}
        .code{font-size:96px;font-weight:700;color:#e2e8f0;line-height:1}
        h1{font-size:24px;margin:12px 0 8px}
        p{color:#64748b;margin:0 0 24px}
        a{display:inline-block;padding:10px 24px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-size:14px}
        a:hover{background:#1d4ed8}
    </style>
</head>
<body>
    <div class="box">
        <div class="code">500</div>
        <h1>Ocurrió un error</h1>
        <p>Tuvimos un problema al procesar tu solicitud. Inténtalo de nuevo en unos minutos.</p>
        <a href="<?= url('') ?>">Volver al inicio</a>
    </div>
</body>
</html>
